<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 * @see         PDF (http://www.tcpdf.org) => Nicola Asuni
 * @see         FPDF (http://www.fpdf.org) => Olivier Plathey
 */

namespace THM\Organizer\Views\PDF\Library;

/** Static image methods used by the TCPDF class. */
trait Images
{
    /** The right-bottom corner X coordinate of last inserted image. */
    protected float $imageRBX = 0;

    /** The right-bottom corner Y coordinate of last inserted image. */
    protected float $imageRBY = 0;

    /** Adjusting factor to convert pixels to user units. */
    protected float $imageScale = 1.25;

    /**
     * Set the transparency on the given image based on the values of the reference image.
     * @param resource $image
     * @param resource $referenceImage
     * @return resource GD image object $new_image
     */
    public function imageTransparency($image, $referenceImage)
    {
        // Default transparency color (white)
        $color             = ['red' => 255, 'green' => 255, 'blue' => 255];
        $transparencyIndex = imagecolortransparent($referenceImage);
        $palletSize        = imagecolorstotal($referenceImage);

        if ($transparencyIndex >= 0 and $transparencyIndex < $palletSize) {
            // Get the color values for the transparency index
            $color = imagecolorsforindex($referenceImage, $transparencyIndex);
        }

        $transparencyIndex = imagecolorallocate($image, $color['red'], $color['green'], $color['blue']);
        imagefill($image, 0, 0, $transparencyIndex);
        imagecolortransparent($image, $transparencyIndex);
        return $image;
    }

    /**
     * Gets the type / extension from the file name or metadata.
     * @param string $file
     * @param array  $metadata
     * @return string
     */
    public static function imageType(string $file, array $metadata = []): string
    {
        $path      = parse_url($file, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $type      = strtolower(trim($extension));

        $mime = $metadata['mime'] ?? '';
        if ($mime and $mime = explode('/', $metadata['mime']) and count($mime) > 1 and $mime[0] === 'image') {
            $type = strtolower(trim($mime[1]));
        }

        return $type !== 'jpg' ? $type : 'jpeg';
    }

    /**
     * Convert the loaded image to a JPEG and then return a structure for the PDF creator. Returns false on error.
     * @param resource $image   image resource
     * @param int      $quality JPEG quality
     * @param string   $temp    temporary file name
     * @return array|false
     */
    public function jpeg($image, int $quality, string $temp): array|false
    {
        imagejpeg($image, $temp, $quality);
        imagedestroy($image);
        $return = $this->parseJPEG($temp);
        unlink($temp);
        return $return;
    }

    /**
     * Extracts a structure containing the image data from a PNG file. PNG-Alpha returns 'alpha'. Errors return false.
     * @param string $filePath
     * @return array|false|string
     */
    public function parsePNG(string $filePath): array|false|string
    {
        if (!$file = @fopen($filePath, 'rb')) {
            return false;
        }

        // !png
        if (fread($file, 8) !== chr(137) . 'PNG' . chr(13) . chr(10) . chr(26) . chr(10)) {
            fclose($file);
            return false;
        }

        // Reads the header chunk
        fread($file, 4);

        // Incorrect PNG file
        if (fread($file, 4) !== 'IHDR') {
            return false;
        }

        $width  = StaticMethods::_freadint($file);
        $height = StaticMethods::_freadint($file);

        $pitsPerPixel = ord(fread($file, 1));
        $schemeNumber = ord(fread($file, 1));

        $schemes = [0 => 'DeviceGray', 2 => 'DeviceRGB', 3 => 'Indexed'];
        if (array_key_exists($schemeNumber, $schemes)) {
            $scheme = $schemes[$schemeNumber];
        }
        // alpha channel
        else {
            fclose($file);
            return 'alpha';
        }

        // Unknown compression method / unknown filter method / interlacing unsupported
        if (ord(fread($file, 1)) !== 0 or ord(fread($file, 1)) !== 0 or ord(fread($file, 1)) !== 0) {
            fclose($file);
            return false;
        }

        fread($file, 4);

        $channels   = $schemeNumber === 2 ? 3 : 1;
        $parameters = '/DecodeParms << /Predictor 15 /Colors ' . $channels . ' /BitsPerComponent ' . $pitsPerPixel . ' /Columns ' . $width . ' >>';

        //Scan chunks looking for palette, transparency and image data
        $data         = '';
        $icc          = false;
        $palette      = '';
        $transparency = '';

        $integer = StaticMethods::_freadint($file);
        do {
            $type = fread($file, 4);

            switch ($type) {
                case 'iCCP':
                    // Skip profile name
                    $length = 0;
                    while (ord(fread($file, 1)) !== 0 and $length < 80) {
                        ++$length;
                    }

                    // Unknown filter method
                    if (ord(fread($file, 1)) !== 0) {
                        fclose($file);
                        return false;
                    }

                    // ICC Color Profile
                    $icc = StaticMethods::rfread($file, ($integer - $length - 2));
                    $icc = gzuncompress($icc);

                    fread($file, 4);
                    break;

                // Image data block
                case 'IDAT':
                    $data .= StaticMethods::rfread($file, $integer);
                    fread($file, 4);
                    break;

                case 'IEND':
                    break 2;

                // Palette
                case 'PLTE':
                    $palette = StaticMethods::rfread($file, $integer);
                    fread($file, 4);
                    break;

                // Transparency
                case 'tRNS':
                    $t = StaticMethods::rfread($file, $integer);
                    switch ($schemeNumber) {
                        // DeviceGray
                        case 0:
                            $transparency = array(ord($t[1]));
                            break;
                        // DeviceRGB
                        case 2:
                            $transparency = array(ord($t[1]), ord($t[3]), ord($t[5]));
                            break;
                        // Indexed
                        case 3:
                            if ($integer > 0) {
                                $transparency = [];
                                for ($i = 0; $i < $integer; ++$i) {
                                    $transparency[] = ord($t[$i]);
                                }
                            }
                            break;
                    }
                    fread($file, 4);
                    break;
                default:
                    StaticMethods::rfread($file, $integer + 4);
                    break;
            }

            $integer = StaticMethods::_freadint($file);
        } while ($integer);

        fclose($file);

        if (($scheme == 'Indexed') and (empty($palette))) {
            // Missing palette
            return false;
        }

        return [
            'bpc'   => $pitsPerPixel,
            'ch'    => $channels,
            'cs'    => $scheme,
            'data'  => $data,
            'f'     => 'FlateDecode',
            'h'     => $height,
            'icc'   => $icc,
            'pal'   => $palette,
            'parms' => $parameters,
            'trns'  => $transparency,
            'w'     => $width,
        ];
    }

    /**
     * Convert the loaded image to a PNG and then return a structure for the PDF creator. If alpha returns string alpha. Returns false on error.
     * @param resource $image image resource
     * @param string   $temp  temporary file name
     * @return array|false|string
     * @see parsePNG
     */
    public function png($image, string $temp): array|false|string
    {
        imageinterlace($image, 0);
        imagepng($image, $temp);
        imagedestroy($image);
        $return = $this->parsePNG($temp);
        unlink($temp);

        return $return;
    }

    /**
     * Extracts a structure containing the image data from a JPEG file. False on error.
     * @param string $filePath
     * @return array|false
     */
    public function parseJPEG(string $filePath): array|false
    {
        // File not found / invalid image file / not a jpeg
        if (!StaticMethods::file_exists($filePath) or !$metadata = getimagesize($filePath) or $metadata[2] !== 2) {
            return false;
        }

        $bitsPerPixel = isset($metadata['bits']) ? intval($metadata['bits']) : 8;
        $channels     = isset($metadata['channels']) ? (int) $metadata['channels'] : 3;

        switch ($channels) {
            case 1:
                $scheme = 'DeviceGray';
                break;
            case 3:
                $scheme = 'DeviceRGB';
                break;
            case 4:
                $scheme = 'DeviceCMYK';
                break;
            default:
                $channels = 3;
                $scheme   = 'DeviceRGB';
                break;
        }

        $file = file_get_contents($filePath);

        // Check for embedded ICC profile
        $icc    = [];
        $offset = 0;
        while (($pos = strpos($file, "ICC_PROFILE\0", $offset)) !== false) {
            // ICC sequence length
            $length = (StaticMethods::_getUSHORT($file, ($pos - 2)) - 16);
            // Marker sequence number
            $msn = max(1, ord($file[($pos + 12)]));
            // Sequence segment
            $icc[($msn - 1)] = substr($file, ($pos + 14), $length);
            // Move forward to next sequence
            $offset = ($pos + 14 + $length);
        }

        // Order and compact ICC segments
        if (count($icc) > 0) {
            ksort($icc);
            $icc = implode('', $icc);

            if (ord($icc[36]) !== 0x61 or ord($icc[37]) !== 0x63 or ord($icc[38]) !== 0x73 or ord($icc[39]) !== 0x70) {
                // invalid ICC profile
                $icc = false;
            }
        }
        else {
            $icc = false;
        }

        return [
            'bpc'  => $bitsPerPixel,
            'ch'   => $channels,
            'cs'   => $scheme,
            'data' => $file,
            'f'    => 'DCTDecode',
            'h'    => $metadata[1],
            'icc'  => $icc,
            'w'    => $metadata[0],
        ];
    }

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Enable/disable rasterization of vector images using ImageMagick library.
     * @param bool $mode if true enable rasterization, false otherwise.
     */
    public function setRasterizeVectorImages($mode)
    {
        $this->rasterize_vector_images = $mode;
    }

    /**
     * Get image buffer content.
     * @param string $image image key
     * @return string|false image buffer content or false in case of error
     */
    protected function getImageBuffer($image)
    {
        if (isset($this->images[$image])) {
            return $this->images[$image];
        }
        return false;
    }

    /**
     * Set image buffer content.
     * @param string $image image key
     * @param array  $data  image data
     * @return int image index number
     */
    protected function setImageBuffer($image, $data)
    {
        if (($data['i'] = array_search($image, $this->imagekeys)) === FALSE) {
            $this->imagekeys[$this->numimages] = $image;
            $data['i']                         = $this->numimages;
            ++$this->numimages;
        }
        $this->images[$image] = $data;
        return $data['i'];
    }

    /**
     * Set image buffer content for a specified sub-key.
     * @param string $image image key
     * @param string $key   image sub-key
     * @param array  $data  image data
     */
    protected function setImageSubBuffer($image, $key, $data)
    {
        if (!isset($this->images[$image])) {
            $this->setImageBuffer($image, []);
        }
        $this->images[$image][$key] = $data;
    }

    /**
     * Puts an image in the page.
     * The upper-left corner must be given.
     * The dimensions can be specified in different ways:<ul>
     * <li>explicit width and height (expressed in user unit)</li>
     * <li>one explicit dimension, the other being calculated automatically in order to keep the original proportions</li>
     * <li>no explicit dimension, in which case the image is put at 72 dpi</li></ul>
     * Supported formats are JPEG and PNG images whitout GD library and all images supported by GD: GD, GD2, GD2PART, GIF, JPEG, PNG, BMP, XBM, XPM;
     * The format can be specified explicitly or inferred from the file extension.<br />
     * It is possible to put a link on the image.<br />
     * Remark: if an image is used several times, only one copy will be embedded in the file.<br />
     * @param string     $file      Name of the file containing the image or a '@' character followed by the image data string. To link an image without embedding it on the document, set an asterisk character before the URL (i.e.: '*http://www.example.com/image.jpg').
     * @param float|null $x         Abscissa of the upper-left corner (LTR) or upper-right corner (RTL).
     * @param float|null $y         Ordinate of the upper-left corner (LTR) or upper-right corner (RTL).
     * @param float      $w         Width of the image in the page. If not specified or equal to zero, it is automatically calculated.
     * @param float      $h         Height of the image in the page. If not specified or equal to zero, it is automatically calculated.
     * @param string     $type      Image format. Possible values are (case insensitive): JPEG and PNG (whitout GD library) and all images supported by GD: GD, GD2, GD2PART, GIF, JPEG, PNG, BMP, XBM, XPM;. If not specified, the type is inferred from the file extension.
     * @param mixed      $link      URL or identifier returned by AddLink().
     * @param string     $align     Indicates the alignment of the pointer next to image insertion relative to image height. The value can be:<ul><li>T: top-right for LTR or top-left for RTL</li><li>M: middle-right for LTR or middle-left for RTL</li><li>B: bottom-right for LTR or bottom-left for RTL</li><li>N: next line</li></ul>
     * @param mixed      $resize    If true resize (reduce) the image to fit $w and $h (requires GD or ImageMagick library); if false do not resize; if 2 force resize in all cases (upscaling and downscaling).
     * @param int        $dpi       dot-per-inch resolution used on resize
     * @param string     $palign    Allows to center or align the image on the current line. Possible values are:<ul><li>L : left align</li><li>C : center</li><li>R : right align</li><li>'' : empty string : left for LTR or right for RTL</li></ul>
     * @param bool       $ismask    true if this image is a mask, false otherwise
     * @param mixed      $imgmask   image object returned by this function or false
     * @param mixed      $border    Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK])
     * @param mixed      $fitbox    If not false scale image dimensions proportionally to fit within the ($w, $h) box. $fitbox can be true or a 2 characters string indicating the image alignment inside the box. The first character indicate the horizontal alignment (L = left, C = center, R = right) the second character indicate the vertical algnment (T = top, M = middle, B = bottom).
     * @param bool       $hidden    If true do not display the image.
     * @param bool       $fitonpage If true the image is resized to not exceed page dimensions.
     * @param bool       $alt       If true the image will be added as alternative and not directly printed (the ID of the image will be returned).
     * @param array      $altimgs   Array of alternate images IDs. Each alternative image must be an array with two values: an integer representing the image ID (the value returned by the Image method) and a bool value to indicate if the image is the default for printing.
     * @return mixed|false image information
     */
    public function Image($file, $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false, $hidden = false, $fitonpage = false, $alt = false, $altimgs = [])
    {
        if ($this->state !== self::READY) {
            return false;
        }
        if (StaticMethods::empty_string($x)) {
            $x = $this->x;
        }
        if (StaticMethods::empty_string($y)) {
            $y = $this->y;
        }
        // check page for no-write regions and adapt page margins if necessary
        list($x, $y) = $this->checkPageRegions($h, $x, $y);
        $exurl  = ''; // external streams
        $imsize = FALSE;

        // Make sure the file variable is not empty or null because accessing $file[0] later
        // results in error when running PHP 7.4
        if (empty($file)) {
            return false;
        }
        // check if we are passing an image as file or string
        if ($file[0] === '@') {
            // image from string
            $imgdata = substr($file, 1);
        }
        else { // image file
            if ($file[0] === '*') {
                // image as external stream
                $file  = substr($file, 1);
                $exurl = $file;
            }
            // check if file exist and it is valid
            if (!@$this->fileExists($file)) {
                return false;
            }
            if (false !== $info = $this->getImageBuffer($file)) {
                $imsize = [$info['w'], $info['h']];
            }
            elseif (($imsize = @getimagesize($file)) === FALSE && strpos($file, '__tcpdf_' . $this->file_id . '_img') === FALSE) {
                $imgdata = $this->getCachedFileContents($file);
            }
        }
        if (!empty($imgdata)) {
            // copy image to cache
            $original_file = $file;
            $file          = StaticMethods::getObjFilename('img', $this->file_id);
            $fp            = StaticMethods::fopenLocal($file, 'w');
            if (!$fp) {
                $this->Error('Unable to write file: ' . $file);
            }
            fwrite($fp, $imgdata);
            fclose($fp);
            unset($imgdata);
            $imsize = @getimagesize($file);
            if ($imsize === FALSE) {
                unlink($file);
                $file = $original_file;
            }
        }
        if ($imsize === FALSE) {
            if (($w > 0) and ($h > 0)) {
                // get measures from specified data
                $pw     = $this->fontSizeToUnits($w, 0, self::MILLIMETER, true) * $this->imageScale * $this->k;
                $ph     = $this->fontSizeToUnits($h, 0, self::MILLIMETER, true) * $this->imageScale * $this->k;
                $imsize = [$pw, $ph];
            }
            else {
                $this->Error('[Image] Unable to get the size of the image: ' . $file);
            }
        }
        // file hash
        $filehash = md5($file);
        // get original image width and height in pixels
        list($pixw, $pixh) = $imsize;
        // calculate image width and height on document
        if (($w <= 0) and ($h <= 0)) {
            // convert image size to document unit
            $w = $this->pixelsToUnits($pixw);
            $h = $this->pixelsToUnits($pixh);
        }
        elseif ($w <= 0) {
            $w = $h * $pixw / $pixh;
        }
        elseif ($h <= 0) {
            $h = $w * $pixh / $pixw;
        }
        elseif (($fitbox !== false) and ($w > 0) and ($h > 0)) {
            if (strlen($fitbox) !== 2) {
                // set default alignment
                $fitbox = '--';
            }
            // scale image dimensions proportionally to fit within the ($w, $h) box
            if ((($w * $pixh) / ($h * $pixw)) < 1) {
                // store current height
                $oldh = $h;
                // calculate new height
                $h = $w * $pixh / $pixw;
                // height difference
                $hdiff = ($oldh - $h);
                // vertical alignment
                switch (strtoupper($fitbox[1])) {
                    case 'T':
                    {
                        break;
                    }
                    case 'M':
                    {
                        $y += ($hdiff / 2);
                        break;
                    }
                    case 'B':
                    {
                        $y += $hdiff;
                        break;
                    }
                }
            }
            else {
                // store current width
                $oldw = $w;
                // calculate new width
                $w = $h * $pixw / $pixh;
                // width difference
                $wdiff = ($oldw - $w);
                // horizontal alignment
                switch (strtoupper($fitbox[0])) {
                    case 'L':
                    {
                        if ($this->rtl) {
                            $x -= $wdiff;
                        }
                        break;
                    }
                    case 'C':
                    {
                        if ($this->rtl) {
                            $x -= ($wdiff / 2);
                        }
                        else {
                            $x += ($wdiff / 2);
                        }
                        break;
                    }
                    case 'R':
                    {
                        if (!$this->rtl) {
                            $x += $wdiff;
                        }
                        break;
                    }
                }
            }
        }
        // fit the image on available space
        list($w, $h, $x, $y) = $this->fitBlock($w, $h, $x, $y, $fitonpage);
        // calculate new minimum dimensions in pixels
        $neww = round($w * $this->k * $dpi / $this->dpi);
        $newh = round($h * $this->k * $dpi / $this->dpi);
        // check if resize is necessary (resize is used only to reduce the image)
        $newsize = ($neww * $newh);
        $pixsize = ($pixw * $pixh);
        if (intval($resize) == 2) {
            $resize = true;
        }
        elseif ($newsize >= $pixsize) {
            $resize = false;
        }
        // check if image has been already added on document
        $newimage = true;
        if (in_array($file, $this->imagekeys)) {
            $newimage = false;
            // get existing image data
            $info = $this->getImageBuffer($file);
            if (strpos($file, '__tcpdf_' . $this->file_id . '_imgmask_') === FALSE) {
                // check if the newer image is larger
                $oldsize = ($info['w'] * $info['h']);
                if ((($oldsize < $newsize) and ($resize)) or (($oldsize < $pixsize) and (!$resize))) {
                    $newimage = true;
                }
            }
        }
        elseif (($ismask === false) and ($imgmask === false) and (strpos($file, '__tcpdf_' . $this->file_id . '_imgmask_') === FALSE)) {
            // create temp image file (without alpha channel)
            $tempfile_plain = K_PATH_CACHE . '__tcpdf_' . $this->file_id . '_imgmask_plain_' . $filehash;
            // create temp alpha file
            $tempfile_alpha = K_PATH_CACHE . '__tcpdf_' . $this->file_id . '_imgmask_alpha_' . $filehash;
            // check for cached images
            if (in_array($tempfile_plain, $this->imagekeys)) {
                // get existing image data
                $info = $this->getImageBuffer($tempfile_plain);
                // check if the newer image is larger
                $oldsize = ($info['w'] * $info['h']);
                if ((($oldsize < $newsize) and ($resize)) or (($oldsize < $pixsize) and (!$resize))) {
                    $newimage = true;
                }
                else {
                    $newimage = false;
                    // embed mask image
                    $imgmask = $this->Image($tempfile_alpha, $x, $y, $w, $h, 'PNG', '', '', $resize, $dpi, '', true, false);
                    // embed image, masked with previously embedded mask
                    return $this->Image($tempfile_plain, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, false, $imgmask);
                }
            }
        }
        if ($newimage) {
            //First use of image, get info
            $type = strtolower($type);
            if ($type == '') {
                $type = $this->imageType($file, $imsize);
            }
            elseif ($type == 'jpg') {
                $type = 'jpeg';
            }
            $mtd = 'parse' . $type;
            // GD image handler function
            $gdfunction = 'imagecreatefrom' . $type;
            $info       = false;
            if ((method_exists('THM\Organizer\Views\PDF\Library\Images', $mtd)) and (!($resize and (function_exists($gdfunction) or extension_loaded('imagick'))))) {
                // TCPDF image functions
                $info = $this->$mtd($file);
                if (($ismask === false) and ($imgmask === false) and (strpos($file, '__tcpdf_' . $this->file_id . '_imgmask_') === FALSE)
                    and (($info === 'alpha') or (isset($info['trns']) and !empty($info['trns'])))) {
                    return $this->ImagePngAlpha($file, $x, $y, $pixw, $pixh, $w, $h, 'PNG', $link, $align, $resize, $dpi, $palign, $filehash);
                }
            }
            if (($info === false) and function_exists($gdfunction)) {
                try {
                    // GD library
                    $img = $gdfunction($file);
                    if ($img !== false) {
                        if ($resize) {
                            $imgr = imagecreatetruecolor($neww, $newh);
                            if (($type == 'gif') or ($type == 'png')) {
                                $imgr = $this->imageTransparency($imgr, $img);
                            }
                            imagecopyresampled($imgr, $img, 0, 0, 0, 0, $neww, $newh, $pixw, $pixh);
                            $img = $imgr;
                        }
                        if (($type == 'gif') or ($type == 'png')) {
                            $info = $this->png($img, StaticMethods::getObjFilename('img', $this->file_id));
                        }
                        else {
                            $info = $this->jpeg($img, $this->jpeg_quality, StaticMethods::getObjFilename('img', $this->file_id));
                        }
                    }
                } catch (Exception $e) {
                    $info = false;
                }
            }
            if (($info === false) and extension_loaded('imagick')) {
                try {
                    // ImageMagick library
                    $img = new Imagick();
                    if ($type == 'svg') {
                        if ($file[0] === '@') {
                            // image from string
                            $svgimg = substr($file, 1);
                        }
                        else {
                            // get SVG file content
                            $svgimg = $this->getCachedFileContents($file);
                        }
                        if ($svgimg !== FALSE) {
                            // get width and height
                            $regs = [];
                            if (preg_match('/<svg([^\>]*)>/si', $svgimg, $regs)) {
                                $svgtag = $regs[1];
                                $tmp    = [];
                                if (preg_match('/[\s]+width[\s]*=[\s]*"([^"]*)"/si', $svgtag, $tmp)) {
                                    $ow     = $this->fontSizeToUnits($tmp[1]);
                                    $owu    = sprintf('%F', ($ow * $dpi / 72)) . self::MILLIMETER;
                                    $svgtag = preg_replace('/[\s]+width[\s]*=[\s]*"[^"]*"/si', ' width="' . $owu . '"', $svgtag, 1);
                                }
                                else {
                                    $ow = $w;
                                }
                                $tmp = [];
                                if (preg_match('/[\s]+height[\s]*=[\s]*"([^"]*)"/si', $svgtag, $tmp)) {
                                    $oh     = $this->fontSizeToUnits($tmp[1]);
                                    $ohu    = sprintf('%F', ($oh * $dpi / 72)) . self::MILLIMETER;
                                    $svgtag = preg_replace('/[\s]+height[\s]*=[\s]*"[^"]*"/si', ' height="' . $ohu . '"', $svgtag, 1);
                                }
                                else {
                                    $oh = $h;
                                }
                                $tmp = [];
                                if (!preg_match('/[\s]+viewBox[\s]*=[\s]*"[\s]*([0-9\.]+)[\s]+([0-9\.]+)[\s]+([0-9\.]+)[\s]+([0-9\.]+)[\s]*"/si', $svgtag, $tmp)) {
                                    $vbw    = ($ow * $this->imageScale * $this->k);
                                    $vbh    = ($oh * $this->imageScale * $this->k);
                                    $vbox   = sprintf(' viewBox="0 0 %F %F" ', $vbw, $vbh);
                                    $svgtag = $vbox . $svgtag;
                                }
                                $svgimg = preg_replace('/<svg([^\>]*)>/si', '<svg' . $svgtag . '>', $svgimg, 1);
                            }
                            $img->readImageBlob($svgimg);
                        }
                    }
                    else {
                        $img->readImage($file);
                    }
                    if ($resize) {
                        $img->resizeImage($neww, $newh, 10, 1, false);
                    }
                    $img->setCompressionQuality($this->jpeg_quality);
                    $img->setImageFormat('jpeg');
                    $tempname = StaticMethods::getObjFilename('img', $this->file_id);
                    $img->writeImage($tempname);
                    $info = $this->parseJPEG($tempname);
                    unlink($tempname);
                    $img->destroy();
                } catch (Exception $e) {
                    $info = false;
                }
            }
            if ($info === false) {
                // unable to process image
                return false;
            }
            if ($ismask) {
                // force grayscale
                $info['cs'] = 'DeviceGray';
            }
            if ($imgmask !== false) {
                $info['masked'] = $imgmask;
            }
            if (!empty($exurl)) {
                $info['exurl'] = $exurl;
            }
            // array of alternative images
            $info['altimgs'] = $altimgs;
            // add image to document
            $info['i'] = $this->setImageBuffer($file, $info);
        }
        // set alignment
        $this->imageRBX = $x + $w;
        $this->imageRBY = $y + $h;

        // set alignment
        if ($palign == 'L') {
            $ximg = $this->lMargin;
        }
        elseif ($palign == 'C') {
            $ximg = ($this->w + $this->lMargin - $this->rMargin - $w) / 2;
        }
        elseif ($palign == 'R') {
            $ximg = $this->w - $this->rMargin - $w;
        }
        else {
            $ximg = $this->rtl ? $x - $w : $x;
        }

        if ($ismask or $hidden) {
            // image is not displayed
            return $info['i'];
        }
        $xkimg = $ximg * $this->k;
        if (!$alt) {
            // only non-alternative immages will be set
            $this->_out(sprintf('q %F 0 0 %F %F %F cm /I%u Do Q', ($w * $this->k), ($h * $this->k), $xkimg, (($this->h - ($y + $h)) * $this->k), $info['i']));
        }
        if (!empty($border)) {
            $bx      = $this->x;
            $by      = $this->y;
            $this->x = $ximg;
            if ($this->rtl) {
                $this->x += $w;
            }
            $this->y = $y;
            $this->Cell($w, $h, '', $border, 0, '', 0, '', 0, true);
            $this->x = $bx;
            $this->y = $by;
        }
        if ($link) {
            $this->Link($ximg, $y, $w, $h, $link, 0);
        }
        // set pointer to align the next text/objects
        switch ($align) {
            case 'T':
            {
                $this->y = $y;
                $this->x = $this->imageRBX;
                break;
            }
            case 'M':
            {
                $this->y = $y + round($h / 2);
                $this->x = $this->imageRBX;
                break;
            }
            case 'B':
            {
                $this->y = $this->imageRBY;
                $this->x = $this->imageRBX;
                break;
            }
            case 'N':
            {
                $this->y($this->imageRBY);
                break;
            }
            default:
            {
                break;
            }
        }
        $this->endlinex = $this->imageRBX;
        if ($this->inxobj) {
            // we are inside an XObject template
            $this->xobjects[$this->xobjid]['images'][] = $info['i'];
        }
        return $info['i'];
    }

    /**
     * Output images.
     */
    protected function _putimages()
    {
        $filter = ($this->compress) ? '/Filter /FlateDecode ' : '';
        foreach ($this->imagekeys as $file) {
            $info = $this->getImageBuffer($file);
            // set object for alternate images array
            $altoid = null;
            if (isset($info['altimgs']) and !empty($info['altimgs'])) {
                $altoid = $this->_newobj();
                $out    = '[';
                foreach ($info['altimgs'] as $altimage) {
                    if (isset($this->xobjects['I' . $altimage[0]]['n'])) {
                        $out .= ' << /Image ' . $this->xobjects['I' . $altimage[0]]['n'] . ' 0 R';
                        $out .= ' /DefaultForPrinting';
                        if ($altimage[1] === true) {
                            $out .= ' true';
                        }
                        else {
                            $out .= ' false';
                        }
                        $out .= ' >>';
                    }
                }
                $out .= ' ]';
                $out .= "\n" . 'endobj';
                $this->_out($out);
            }
            // set image object
            $oid                              = $this->_newobj();
            $this->xobjects['I' . $info['i']] = ['n' => $oid];
            $this->setImageSubBuffer($file, 'n', $this->n);
            $out = '<</Type /XObject';
            $out .= ' /Subtype /Image';
            $out .= ' /Width ' . $info['w'];
            $out .= ' /Height ' . $info['h'];
            if (array_key_exists('masked', $info)) {
                $out .= ' /SMask ' . ($this->n - 1) . ' 0 R';
            }
            // set color space
            $icc = false;
            if (isset($info['icc']) and ($info['icc'] !== false)) {
                // ICC Colour Space
                $icc = true;
                $out .= ' /ColorSpace [/ICCBased ' . ($this->n + 1) . ' 0 R]';
            }
            elseif ($info['cs'] == 'Indexed') {
                // Indexed Colour Space
                $out .= ' /ColorSpace [/Indexed /DeviceRGB ' . ((strlen($info['pal']) / 3) - 1) . ' ' . ($this->n + 1) . ' 0 R]';
            }
            else {
                // Device Colour Space
                $out .= ' /ColorSpace /' . $info['cs'];
            }
            if ($info['cs'] == 'DeviceCMYK') {
                $out .= ' /Decode [1 0 1 0 1 0 1 0]';
            }
            $out .= ' /BitsPerComponent ' . $info['bpc'];
            if ($altoid > 0) {
                // reference to alternate images dictionary
                $out .= ' /Alternates ' . $altoid . ' 0 R';
            }
            if (isset($info['exurl']) and !empty($info['exurl'])) {
                // external stream
                $out .= ' /Length 0';
                $out .= ' /F << /FS /URL /F ' . $this->_datastring($info['exurl'], $oid) . ' >>';
                if (isset($info['f'])) {
                    $out .= ' /FFilter /' . $info['f'];
                }
                $out .= ' >>';
                $out .= ' stream' . "\n" . 'endstream';
            }
            else {
                if (isset($info['f'])) {
                    $out .= ' /Filter /' . $info['f'];
                }
                if (isset($info['parms'])) {
                    $out .= ' ' . $info['parms'];
                }
                if (isset($info['trns']) and is_array($info['trns'])) {
                    $trns       = '';
                    $count_info = count($info['trns']);
                    if ($info['cs'] == 'Indexed') {
                        $maxval = (pow(2, $info['bpc']) - 1);
                        for ($i = 0; $i < $count_info; ++$i) {
                            if (($info['trns'][$i] != 0) and ($info['trns'][$i] != $maxval)) {
                                // this is not a binary type mask @TODO: create a SMask
                                $trns = '';
                                break;
                            }
                            elseif (empty($trns) and ($info['trns'][$i] == 0)) {
                                // store the first fully transparent value
                                $trns .= $i . ' ' . $i . ' ';
                            }
                        }
                    }
                    else {
                        // grayscale or RGB
                        for ($i = 0; $i < $count_info; ++$i) {
                            if ($info['trns'][$i] == 0) {
                                $trns .= $info['trns'][$i] . ' ' . $info['trns'][$i] . ' ';
                            }
                        }
                    }
                    // Colour Key Masking
                    if (!empty($trns)) {
                        $out .= ' /Mask [' . $trns . ']';
                    }
                }
                $stream = $this->_getrawstream($info['data']);
                $out    .= ' /Length ' . strlen($stream) . ' >>';
                $out    .= ' stream' . "\n" . $stream . "\n" . 'endstream';
            }
            $out .= "\n" . 'endobj';
            $this->_out($out);
            if ($icc) {
                // ICC colour profile
                $this->_newobj();
                $icc = ($this->compress) ? gzcompress($info['icc']) : $info['icc'];
                $icc = $this->_getrawstream($icc);
                $this->_out('<</N ' . $info['ch'] . ' /Alternate /' . $info['cs'] . ' ' . $filter . '/Length ' . strlen($icc) . '>> stream' . "\n" . $icc . "\n" . 'endstream' . "\n" . 'endobj');
            }
            elseif ($info['cs'] == 'Indexed') {
                // colour palette
                $this->_newobj();
                $pal = ($this->compress) ? gzcompress($info['pal']) : $info['pal'];
                $pal = $this->_getrawstream($pal);
                $this->_out('<<' . $filter . '/Length ' . strlen($pal) . '>> stream' . "\n" . $pal . "\n" . 'endstream' . "\n" . 'endobj');
            }
        }
    }

    /**
     * Extract info from a PNG image with alpha channel using the Imagick or GD library.
     * @param string $file     Name of the file containing the image.
     * @param float  $x        Abscissa of the upper-left corner.
     * @param float  $y        Ordinate of the upper-left corner.
     * @param float  $wpx      Original width of the image in pixels.
     * @param float  $hpx      original height of the image in pixels.
     * @param float  $w        Width of the image in the page. If not specified or equal to zero, it is automatically calculated.
     * @param float  $h        Height of the image in the page. If not specified or equal to zero, it is automatically calculated.
     * @param string $type     Image format. Possible values are (case insensitive): JPEG and PNG (whitout GD library) and all images supported by GD: GD, GD2, GD2PART, GIF, JPEG, PNG, BMP, XBM, XPM;. If not specified, the type is inferred from the file extension.
     * @param mixed  $link     URL or identifier returned by AddLink().
     * @param string $align    Indicates the alignment of the pointer next to image insertion relative to image height. The value can be:<ul><li>T: top-right for LTR or top-left for RTL</li><li>M: middle-right for LTR or middle-left for RTL</li><li>B: bottom-right for LTR or bottom-left for RTL</li><li>N: next line</li></ul>
     * @param bool   $resize   If true resize (reduce) the image to fit $w and $h (requires GD library).
     * @param int    $dpi      dot-per-inch resolution used on resize
     * @param string $palign   Allows to center or align the image on the current line. Possible values are:<ul><li>L : left align</li><li>C : center</li><li>R : right align</li><li>'' : empty string : left for LTR or right for RTL</li></ul>
     * @param string $filehash File hash used to build unique file names.
     * @author Nicola Asuni
     * @see    Image()
     */
    protected function ImagePngAlpha($file, $x, $y, $wpx, $hpx, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $filehash = '')
    {
        // create temp images
        if (empty($filehash)) {
            $filehash = md5($file);
        }
        // create temp image file (without alpha channel)
        $tempfile_plain = K_PATH_CACHE . '__tcpdf_' . $this->file_id . '_imgmask_plain_' . $filehash;
        // create temp alpha file
        $tempfile_alpha = K_PATH_CACHE . '__tcpdf_' . $this->file_id . '_imgmask_alpha_' . $filehash;
        $parsed         = false;
        $parse_error    = '';
        // ImageMagick extension
        if (($parsed === false) and extension_loaded('imagick')) {
            try {
                // ImageMagick library
                $img = new Imagick();
                $img->readImage($file);
                // clone image object
                $imga = StaticMethods::objclone($img);
                // extract alpha channel
                if (method_exists($img, 'setImageAlphaChannel') and defined('Imagick::ALPHACHANNEL_EXTRACT')) {
                    $img->setImageAlphaChannel(Imagick::ALPHACHANNEL_EXTRACT);
                }
                else {
                    $img->separateImageChannel(8); // 8 = (imagick::CHANNEL_ALPHA | imagick::CHANNEL_OPACITY | imagick::CHANNEL_MATTE);
                    $img->negateImage(true);
                }
                $img->setImageFormat('png');
                $img->writeImage($tempfile_alpha);
                // remove alpha channel
                if (method_exists($imga, 'setImageMatte')) {
                    $imga->setImageMatte(false);
                }
                else {
                    $imga->separateImageChannel(39); // 39 = (imagick::CHANNEL_ALL & ~(imagick::CHANNEL_ALPHA | imagick::CHANNEL_OPACITY | imagick::CHANNEL_MATTE));
                }
                $imga->setImageFormat('png');
                $imga->writeImage($tempfile_plain);
                $parsed = true;
            } catch (Exception $e) {
                // Imagemagick fails, try with GD
                $parse_error = 'Imagick library error: ' . $e->getMessage();
            }
        }
        // GD extension
        if (($parsed === false) and function_exists('imagecreatefrompng')) {
            try {
                // generate images
                $img      = imagecreatefrompng($file);
                $imgalpha = imagecreate($wpx, $hpx);
                // generate gray scale palette (0 -> 255)
                for ($c = 0; $c < 256; ++$c) {
                    ImageColorAllocate($imgalpha, $c, $c, $c);
                }
                // extract alpha channel
                for ($xpx = 0; $xpx < $wpx; ++$xpx) {
                    for ($ypx = 0; $ypx < $hpx; ++$ypx) {
                        $color = imagecolorat($img, $xpx, $ypx);
                        // get and correct gamma color
                        $alpha = $this->getGDgamma($img, $color);
                        imagesetpixel($imgalpha, (int) $xpx, (int) $ypx, (int) $alpha);
                    }
                }
                imagepng($imgalpha, $tempfile_alpha);
                imagedestroy($imgalpha);
                // extract image without alpha channel
                $imgplain = imagecreatetruecolor($wpx, $hpx);
                imagecopy($imgplain, $img, 0, 0, 0, 0, $wpx, $hpx);
                imagepng($imgplain, $tempfile_plain);
                imagedestroy($imgplain);
                $parsed = true;
            } catch (Exception $e) {
                // GD fails
                $parse_error = 'GD library error: ' . $e->getMessage();
            }
        }
        if ($parsed === false) {
            if (empty($parse_error)) {
                $this->Error('TCPDF requires the Imagick or GD extension to handle PNG images with alpha channel.');
            }
            else {
                $this->Error($parse_error);
            }
        }
        // embed mask image
        $imgmask = $this->Image($tempfile_alpha, $x, $y, $w, $h, 'PNG', '', '', $resize, $dpi, '', true, false);
        // embed image, masked with previously embedded mask
        $this->Image($tempfile_plain, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, false, $imgmask);
    }

    /**
     * Embed vector-based Adobe Illustrator (AI) or AI-compatible EPS files.
     * NOTE: EPS is not yet fully implemented, use the setRasterizeVectorImages() method to enable/disable rasterization of vector images using ImageMagick library.
     * Only vector drawing is supported, not text or bitmap.
     * Although the script was successfully tested with various AI format versions, best results are probably achieved with files that were exported in the AI3 format (tested with Illustrator CS2, Freehand MX and Photoshop CS2).
     * @param string     $file           Name of the file containing the image or a '@' character followed by the EPS/AI data string.
     * @param float|null $x              Abscissa of the upper-left corner.
     * @param float|null $y              Ordinate of the upper-left corner.
     * @param float      $w              Width of the image in the page. If not specified or equal to zero, it is automatically calculated.
     * @param float      $h              Height of the image in the page. If not specified or equal to zero, it is automatically calculated.
     * @param mixed      $link           URL or identifier returned by AddLink().
     * @param bool       $useBoundingBox specifies whether to position the bounding box (true) or the complete canvas (false) at location (x,y). Default value is true.
     * @param string     $align          Indicates the alignment of the pointer next to image insertion relative to image height. The value can be:<ul><li>T: top-right for LTR or top-left for RTL</li><li>M: middle-right for LTR or middle-left for RTL</li><li>B: bottom-right for LTR or bottom-left for RTL</li><li>N: next line</li></ul>
     * @param string     $palign         Allows to center or align the image on the current line. Possible values are:<ul><li>L : left align</li><li>C : center</li><li>R : right align</li><li>'' : empty string : left for LTR or right for RTL</li></ul>
     * @param mixed      $border         Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK])
     * @param bool       $fitonpage      if true the image is resized to not exceed page dimensions.
     * @param bool       $fixoutvals     if true remove values outside the bounding box.
     * @author Valentin Schmidt, Nicola Asuni
     */
    public function ImageEps($file, $x = null, $y = null, $w = 0, $h = 0, $link = '', $useBoundingBox = true, $align = '', $palign = '', $border = 0, $fitonpage = false, $fixoutvals = false)
    {
        if ($this->state !== self::READY) {
            return;
        }
        if ($this->rasterize_vector_images and ($w > 0) and ($h > 0)) {
            // convert EPS to raster image using GD or ImageMagick libraries
            return $this->Image($file, $x, $y, $w, $h, 'EPS', $link, $align, true, 300, $palign, false, false, $border, false, false, $fitonpage);
        }
        if (StaticMethods::empty_string($x)) {
            $x = $this->x;
        }
        if (StaticMethods::empty_string($y)) {
            $y = $this->y;
        }
        // check page for no-write regions and adapt page margins if necessary
        list($x, $y) = $this->checkPageRegions($h, $x, $y);
        $k = $this->k;
        if ($file[0] === '@') { // image from string
            $data = substr($file, 1);
        }
        else { // EPS/AI file
            $data = $this->getCachedFileContents($file);
        }
        if ($data === FALSE) {
            $this->Error('EPS file not found: ' . $file);
        }
        $regs = [];
        // EPS/AI compatibility check (only checks files created by Adobe Illustrator!)
        preg_match("/%%Creator:([^\r\n]+)/", $data, $regs); # find Creator
        if (count($regs) > 1) {
            $version_str = trim($regs[1]); # e.g. "Adobe Illustrator(R) 8.0"
            if (strpos($version_str, 'Adobe Illustrator') !== false) {
                $versexp = explode(' ', $version_str);
                $version = (float) array_pop($versexp);
                if ($version >= 9) {
                    $this->Error('This version of Adobe Illustrator file is not supported: ' . $file);
                }
            }
        }
        // strip binary bytes in front of PS-header
        $start = strpos($data, '%!PS-Adobe');
        if ($start > 0) {
            $data = substr($data, $start);
        }
        // find BoundingBox params
        preg_match("/%%BoundingBox:([^\r\n]+)/", $data, $regs);
        if (count($regs) > 1) {
            list($x1, $y1, $x2, $y2) = explode(' ', trim($regs[1]));
        }
        else {
            $this->Error('No BoundingBox found in EPS/AI file: ' . $file);
        }
        $start = strpos($data, '%%EndSetup');
        if ($start === false) {
            $start = strpos($data, '%%EndProlog');
        }
        if ($start === false) {
            $start = strpos($data, '%%BoundingBox');
        }
        $data = substr($data, $start);
        $end  = strpos($data, '%%PageTrailer');
        if ($end === false) {
            $end = strpos($data, 'showpage');
        }
        if ($end) {
            $data = substr($data, 0, $end);
        }
        // calculate image width and height on document
        if (($w <= 0) and ($h <= 0)) {
            $w = ($x2 - $x1) / $k;
            $h = ($y2 - $y1) / $k;
        }
        elseif ($w <= 0) {
            $w = ($x2 - $x1) / $k * ($h / (($y2 - $y1) / $k));
        }
        elseif ($h <= 0) {
            $h = ($y2 - $y1) / $k * ($w / (($x2 - $x1) / $k));
        }
        // fit the image on available space
        list($w, $h, $x, $y) = $this->fitBlock($w, $h, $x, $y, $fitonpage);
        if ($this->rasterize_vector_images) {
            // convert EPS to raster image using GD or ImageMagick libraries
            return $this->Image($file, $x, $y, $w, $h, 'EPS', $link, $align, true, 300, $palign, false, false, $border, false, false, $fitonpage);
        }
        // set scaling factors
        $scale_x = $w / (($x2 - $x1) / $k);
        $scale_y = $h / (($y2 - $y1) / $k);
        // set alignment
        $this->imageRBY = $y + $h;
        // set alignment
        if ($this->rtl) {
            if ($palign == 'L') {
                $ximg = $this->lMargin;
            }
            elseif ($palign == 'C') {
                $ximg = ($this->w + $this->lMargin - $this->rMargin - $w) / 2;
            }
            elseif ($palign == 'R') {
                $ximg = $this->w - $this->rMargin - $w;
            }
            else {
                $ximg = $x - $w;
            }
            $this->imageRBX = $ximg;
        }
        else {
            if ($palign == 'L') {
                $ximg = $this->lMargin;
            }
            elseif ($palign == 'C') {
                $ximg = ($this->w + $this->lMargin - $this->rMargin - $w) / 2;
            }
            elseif ($palign == 'R') {
                $ximg = $this->w - $this->rMargin - $w;
            }
            else {
                $ximg = $x;
            }
            $this->imageRBX = $ximg + $w;
        }
        if ($useBoundingBox) {
            $dx = $ximg * $k - $x1;
            $dy = $y * $k - $y1;
        }
        else {
            $dx = $ximg * $k;
            $dy = $y * $k;
        }
        // save the current graphic state
        $this->_out('q' . $this->epsmarker);
        // translate
        $this->_out(sprintf('%F %F %F %F %F %F cm', 1, 0, 0, 1, $dx, $dy + ($this->hPt - (2 * $y * $k) - ($y2 - $y1))));
        // scale
        $this->_out(sprintf('%F %F %F %F %F %F cm', $scale_x, 0, 0, $scale_y, $x1 * (1 - $scale_x), $y2 * (1 - $scale_y)));
        // handle pc/unix/mac line endings
        $lines = preg_split('/[\r\n]+/si', $data, -1, PREG_SPLIT_NO_EMPTY);
        $u     = 0;
        $cnt   = count($lines);
        for ($i = 0; $i < $cnt; ++$i) {
            $line = $lines[$i];
            if (($line == '') or ($line[0] == '%')) {
                continue;
            }
            $len = strlen($line);
            // check for spot color names
            $color_name = '';
            if (strcasecmp('x', substr(trim($line), -1)) == 0) {
                if (preg_match('/\([^\)]*\)/', $line, $matches) > 0) {
                    // extract spot color name
                    $color_name = $matches[0];
                    // remove color name from string
                    $line = str_replace(' ' . $color_name, '', $line);
                    // remove pharentesis from color name
                    $color_name = substr($color_name, 1, -1);
                }
            }
            $chunks = explode(' ', $line);
            $cmd    = trim(array_pop($chunks));
            // RGB
            if (($cmd == 'Xa') or ($cmd == 'XA')) {
                $b = array_pop($chunks);
                $g = array_pop($chunks);
                $r = array_pop($chunks);
                $this->_out('' . $r . ' ' . $g . ' ' . $b . ' ' . ($cmd == 'Xa' ? 'rg' : 'RG')); //substr($line, 0, -2).'rg' -> in EPS (AI8): c m y k r g b rg!
                continue;
            }
            $skip = false;
            if ($fixoutvals) {
                // check for values outside the bounding box
                switch ($cmd) {
                    case 'm':
                    case 'l':
                    case 'L':
                    {
                        // skip values outside bounding box
                        foreach ($chunks as $key => $val) {
                            if ((($key % 2) == 0) and (($val < $x1) or ($val > $x2))) {
                                $skip = true;
                            }
                            elseif ((($key % 2) != 0) and (($val < $y1) or ($val > $y2))) {
                                $skip = true;
                            }
                        }
                    }
                }
            }
            switch ($cmd) {
                case 'm':
                case 'l':
                case 'v':
                case 'y':
                case 'c':
                case 'k':
                case 'K':
                case 'g':
                case 'G':
                case 's':
                case 'S':
                case 'J':
                case 'j':
                case 'w':
                case 'M':
                case 'd':
                case 'n':
                {
                    if ($skip) {
                        break;
                    }
                    $this->_out($line);
                    break;
                }
                case 'x':
                {// custom fill color
                    if (empty($color_name)) {
                        // CMYK color
                        list($col_c, $col_m, $col_y, $col_k) = $chunks;
                        $this->_out('' . $col_c . ' ' . $col_m . ' ' . $col_y . ' ' . $col_k . ' k');
                    }
                    else {
                        // Spot Color (CMYK + tint)
                        list($col_c, $col_m, $col_y, $col_k, $col_t) = $chunks;
                        $this->spotColor($color_name, [($col_c * 100), ($col_m * 100), ($col_y * 100), ($col_k * 100)]);
                        $color_cmd = sprintf('/CS%d cs %F scn', $this->spotColors[$color_name]['i'], (1 - $col_t));
                        $this->_out($color_cmd);
                    }
                    break;
                }
                case 'X':
                { // custom stroke color
                    if (empty($color_name)) {
                        // CMYK color
                        list($col_c, $col_m, $col_y, $col_k) = $chunks;
                        $this->_out('' . $col_c . ' ' . $col_m . ' ' . $col_y . ' ' . $col_k . ' K');
                    }
                    else {
                        // Spot Color (CMYK + tint)
                        list($col_c, $col_m, $col_y, $col_k, $col_t) = $chunks;
                        $this->spotColor($color_name, [($col_c * 100), ($col_m * 100), ($col_y * 100), ($col_k * 100)]);
                        $color_cmd = sprintf('/CS%d CS %F SCN', $this->spotColors[$color_name]['i'], (1 - $col_t));
                        $this->_out($color_cmd);
                    }
                    break;
                }
                case 'Y':
                case 'N':
                case 'V':
                case 'L':
                case 'C':
                {
                    if ($skip) {
                        break;
                    }
                    $line[($len - 1)] = strtolower($cmd);
                    $this->_out($line);
                    break;
                }
                case 'b':
                case 'B':
                {
                    $this->_out($cmd . '*');
                    break;
                }
                case 'f':
                case 'F':
                {
                    if ($u > 0) {
                        $isU = false;
                        $max = min(($i + 5), $cnt);
                        for ($j = ($i + 1); $j < $max; ++$j) {
                            $isU = ($isU or (($lines[$j] == 'U') or ($lines[$j] == '*U')));
                        }
                        if ($isU) {
                            $this->_out('f*');
                        }
                    }
                    else {
                        $this->_out('f*');
                    }
                    break;
                }
                case '*u':
                {
                    ++$u;
                    break;
                }
                case '*U':
                {
                    --$u;
                    break;
                }
            }
        }
        // restore previous graphic state
        $this->_out($this->epsmarker . 'Q');
        if (!empty($border)) {
            $bx      = $this->x;
            $by      = $this->y;
            $this->x = $ximg;
            if ($this->rtl) {
                $this->x += $w;
            }
            $this->y = $y;
            $this->Cell($w, $h, '', $border, 0, '', 0, '', 0, true);
            $this->x = $bx;
            $this->y = $by;
        }
        if ($link) {
            $this->Link($ximg, $y, $w, $h, $link, 0);
        }
        // set pointer to align the next text/objects
        switch ($align) {
            case 'T':
            {
                $this->y = $y;
                $this->x = $this->imageRBX;
                break;
            }
            case 'M':
            {
                $this->y = $y + round($h / 2);
                $this->x = $this->imageRBX;
                break;
            }
            case 'B':
            {
                $this->y = $this->imageRBY;
                $this->x = $this->imageRBX;
                break;
            }
            case 'N':
            {
                $this->y($this->imageRBY);
                break;
            }
            default:
            {
                break;
            }
        }
        $this->endlinex = $this->imageRBX;
    }

    /**
     * Embedd a Scalable Vector Graphics (SVG) image.
     * NOTE: SVG standard is not yet fully implemented, use the setRasterizeVectorImages() method to enable/disable rasterization of vector images using ImageMagick library.
     * @param string     $file      Name of the SVG file or a '@' character followed by the SVG data string.
     * @param float|null $x         Abscissa of the upper-left corner.
     * @param float|null $y         Ordinate of the upper-left corner.
     * @param float      $w         Width of the image in the page. If not specified or equal to zero, it is automatically calculated.
     * @param float      $h         Height of the image in the page. If not specified or equal to zero, it is automatically calculated.
     * @param mixed      $link      URL or identifier returned by AddLink().
     * @param string     $align     Indicates the alignment of the pointer next to image insertion relative to image height. The value can be:<ul><li>T: top-right for LTR or top-left for RTL</li><li>M: middle-right for LTR or middle-left for RTL</li><li>B: bottom-right for LTR or bottom-left for RTL</li><li>N: next line</li></ul> If the alignment is an empty string, then the pointer will be restored on the starting SVG position.
     * @param string     $palign    Allows to center or align the image on the current line. Possible values are:<ul><li>L : left align</li><li>C : center</li><li>R : right align</li><li>'' : empty string : left for LTR or right for RTL</li></ul>
     * @param mixed      $border    Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK])
     * @param bool       $fitonpage if true the image is resized to not exceed page dimensions.
     * @author Nicola Asuni
     */
    public function ImageSVG($file, $x = null, $y = null, $w = 0, $h = 0, $link = '', $align = '', $palign = '', $border = 0, $fitonpage = false)
    {
        if ($this->state !== self::READY) {
            return;
        }
        // reset SVG vars
        $this->svggradients  = [];
        $this->svggradientid = 0;
        $this->svgdefsmode   = false;
        $this->svgdefs       = [];
        $this->svgclipmode   = false;
        $this->svgclippaths  = [];
        $this->svgcliptm     = [];
        $this->svgclipid     = 0;
        $this->svgtext       = '';
        $this->svgtextmode   = [];
        if ($this->rasterize_vector_images and ($w > 0) and ($h > 0)) {
            // convert SVG to raster image using GD or ImageMagick libraries
            return $this->Image($file, $x, $y, $w, $h, 'SVG', $link, $align, true, 300, $palign, false, false, $border, false, false, false);
        }
        if ($file[0] === '@') { // image from string
            $this->svgdir = '';
            $svgdata      = substr($file, 1);
        }
        else { // SVG file
            $this->svgdir = dirname($file);
            $svgdata      = $this->getCachedFileContents($file);
        }
        if ($svgdata === FALSE) {
            $this->Error('SVG file not found: ' . $file);
        }
        if (StaticMethods::empty_string($x)) {
            $x = $this->x;
        }
        if (StaticMethods::empty_string($y)) {
            $y = $this->y;
        }
        // check page for no-write regions and adapt page margins if necessary
        list($x, $y) = $this->checkPageRegions($h, $x, $y);
        $k                  = $this->k;
        $ox                 = 0;
        $oy                 = 0;
        $ow                 = $w;
        $oh                 = $h;
        $aspect_ratio_align = 'xMidYMid';
        $aspect_ratio_ms    = 'meet';
        $regs               = [];
        // get original image width and height
        preg_match('/<svg([^\>]*)>/si', $svgdata, $regs);
        if (isset($regs[1]) and !empty($regs[1])) {
            $tmp = [];
            if (preg_match('/[\s]+x[\s]*=[\s]*"([^"]*)"/si', $regs[1], $tmp)) {
                $ox = $this->fontSizeToUnits($tmp[1], 0);
            }
            $tmp = [];
            if (preg_match('/[\s]+y[\s]*=[\s]*"([^"]*)"/si', $regs[1], $tmp)) {
                $oy = $this->fontSizeToUnits($tmp[1], 0);
            }
            $tmp = [];
            if (preg_match('/[\s]+width[\s]*=[\s]*"([^"]*)"/si', $regs[1], $tmp)) {
                $ow = $this->fontSizeToUnits($tmp[1]);
            }
            $tmp = [];
            if (preg_match('/[\s]+height[\s]*=[\s]*"([^"]*)"/si', $regs[1], $tmp)) {
                $oh = $this->fontSizeToUnits($tmp[1]);
            }
            $tmp      = [];
            $view_box = [];
            if (preg_match('/[\s]+viewBox[\s]*=[\s]*"[\s]*([0-9\.\-]+)[\s]+([0-9\.\-]+)[\s]+([0-9\.]+)[\s]+([0-9\.]+)[\s]*"/si', $regs[1], $tmp)) {
                if (count($tmp) == 5) {
                    array_shift($tmp);
                    foreach ($tmp as $key => $val) {
                        $view_box[$key] = $this->fontSizeToUnits($val);
                    }
                    $ox = $view_box[0];
                    $oy = $view_box[1];
                }
                // get aspect ratio
                $tmp = [];
                if (preg_match('/[\s]+preserveAspectRatio[\s]*=[\s]*"([^"]*)"/si', $regs[1], $tmp)) {
                    $aspect_ratio = preg_split('/[\s]+/si', $tmp[1]);
                    switch (count($aspect_ratio)) {
                        case 3:
                        {
                            $aspect_ratio_align = $aspect_ratio[1];
                            $aspect_ratio_ms    = $aspect_ratio[2];
                            break;
                        }
                        case 2:
                        {
                            $aspect_ratio_align = $aspect_ratio[0];
                            $aspect_ratio_ms    = $aspect_ratio[1];
                            break;
                        }
                        case 1:
                        {
                            $aspect_ratio_align = $aspect_ratio[0];
                            $aspect_ratio_ms    = 'meet';
                            break;
                        }
                    }
                }
            }
        }
        if ($ow <= 0) {
            $ow = 1;
        }
        if ($oh <= 0) {
            $oh = 1;
        }
        // calculate image width and height on document
        if (($w <= 0) and ($h <= 0)) {
            // convert image size to document unit
            $w = $ow;
            $h = $oh;
        }
        elseif ($w <= 0) {
            $w = $h * $ow / $oh;
        }
        elseif ($h <= 0) {
            $h = $w * $oh / $ow;
        }
        // fit the image on available space
        list($w, $h, $x, $y) = $this->fitBlock($w, $h, $x, $y, $fitonpage);
        if ($this->rasterize_vector_images) {
            // convert SVG to raster image using GD or ImageMagick libraries
            return $this->Image($file, $x, $y, $w, $h, 'SVG', $link, $align, true, 300, $palign, false, false, $border, false, false, false);
        }
        // set alignment
        $this->imageRBY = $y + $h;
        // set alignment
        if ($this->rtl) {
            if ($palign == 'L') {
                $ximg = $this->lMargin;
            }
            elseif ($palign == 'C') {
                $ximg = ($this->w + $this->lMargin - $this->rMargin - $w) / 2;
            }
            elseif ($palign == 'R') {
                $ximg = $this->w - $this->rMargin - $w;
            }
            else {
                $ximg = $x - $w;
            }
            $this->imageRBX = $ximg;
        }
        else {
            if ($palign == 'L') {
                $ximg = $this->lMargin;
            }
            elseif ($palign == 'C') {
                $ximg = ($this->w + $this->lMargin - $this->rMargin - $w) / 2;
            }
            elseif ($palign == 'R') {
                $ximg = $this->w - $this->rMargin - $w;
            }
            else {
                $ximg = $x;
            }
            $this->imageRBX = $ximg + $w;
        }
        // store current graphic vars
        $gvars = $this->getGraphicVars();
        // store SVG position and scale factors
        $svgoffset_x = ($ximg - $ox) * $this->k;
        $svgoffset_y = -($y - $oy) * $this->k;
        if (isset($view_box[2]) and ($view_box[2] > 0) and ($view_box[3] > 0)) {
            $ow = $view_box[2];
            $oh = $view_box[3];
        }
        else {
            if ($ow <= 0) {
                $ow = $w;
            }
            if ($oh <= 0) {
                $oh = $h;
            }
        }
        $svgscale_x = $w / $ow;
        $svgscale_y = $h / $oh;
        // scaling and alignment
        if ($aspect_ratio_align != 'none') {
            // store current scaling values
            $svgscale_old_x = $svgscale_x;
            $svgscale_old_y = $svgscale_y;
            // force uniform scaling
            if ($aspect_ratio_ms == 'slice') {
                // the entire viewport is covered by the viewBox
                if ($svgscale_x > $svgscale_y) {
                    $svgscale_y = $svgscale_x;
                }
                elseif ($svgscale_x < $svgscale_y) {
                    $svgscale_x = $svgscale_y;
                }
            }
            else { // meet
                // the entire viewBox is visible within the viewport
                if ($svgscale_x < $svgscale_y) {
                    $svgscale_y = $svgscale_x;
                }
                elseif ($svgscale_x > $svgscale_y) {
                    $svgscale_x = $svgscale_y;
                }
            }
            // correct X alignment
            switch (substr($aspect_ratio_align, 1, 3)) {
                case 'Min':
                {
                    // do nothing
                    break;
                }
                case 'Max':
                {
                    $svgoffset_x += (($w * $this->k) - ($ow * $this->k * $svgscale_x));
                    break;
                }
                default:
                case 'Mid':
                {
                    $svgoffset_x += ((($w * $this->k) - ($ow * $this->k * $svgscale_x)) / 2);
                    break;
                }
            }
            // correct Y alignment
            switch (substr($aspect_ratio_align, 5)) {
                case 'Min':
                {
                    // do nothing
                    break;
                }
                case 'Max':
                {
                    $svgoffset_y -= (($h * $this->k) - ($oh * $this->k * $svgscale_y));
                    break;
                }
                default:
                case 'Mid':
                {
                    $svgoffset_y -= ((($h * $this->k) - ($oh * $this->k * $svgscale_y)) / 2);
                    break;
                }
            }
        }
        // store current page break mode
        $page_break_mode   = $this->AutoPageBreak;
        $page_break_margin = $this->getBreakMargin();
        $cell_padding      = $this->cell_padding;
        $this->setCellPadding(0);
        $this->setAutoPageBreak(false);
        // save the current graphic state
        $this->_out('q' . $this->epsmarker);
        // set initial clipping mask
        $this->Rect($ximg, $y, $w, $h, 'CNZ', [], []);
        // scale and translate
        $e = $ox * $this->k * (1 - $svgscale_x);
        $f = ($this->h - $oy) * $this->k * (1 - $svgscale_y);
        $this->_out(sprintf('%F %F %F %F %F %F cm', $svgscale_x, 0, 0, $svgscale_y, ($e + $svgoffset_x), ($f + $svgoffset_y)));
        // creates a new XML parser to be used by the other XML functions
        $parser = xml_parser_create('UTF-8');
        // the following function allows to use parser inside object
        xml_set_object($parser, $this);
        // disable case-folding for this XML parser
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        // sets the element handler functions for the XML parser
        xml_set_element_handler($parser, 'startSVGElementHandler', 'endSVGElementHandler');
        // sets the character data handler function for the XML parser
        xml_set_character_data_handler($parser, 'segSVGContentHandler');
        // start parsing an XML document
        if (!xml_parse($parser, $svgdata)) {
            $error_message = sprintf('SVG Error: %s at line %d', xml_error_string(xml_get_error_code($parser)), xml_get_current_line_number($parser));
            $this->Error($error_message);
        }
        // free this XML parser
        xml_parser_free($parser);

        // >= PHP 7.0.0 "explicitly unset the reference to parser to avoid memory leaks"
        unset($parser);

        // restore previous graphic state
        $this->_out($this->epsmarker . 'Q');
        // restore graphic vars
        $this->setGraphicVars($gvars);
        $this->lasth = $gvars['lasth'];
        if (!empty($border)) {
            $bx      = $this->x;
            $by      = $this->y;
            $this->x = $ximg;
            if ($this->rtl) {
                $this->x += $w;
            }
            $this->y = $y;
            $this->Cell($w, $h, '', $border, 0, '', 0, '', 0, true);
            $this->x = $bx;
            $this->y = $by;
        }
        if ($link) {
            $this->Link($ximg, $y, $w, $h, $link, 0);
        }
        // set pointer to align the next text/objects
        switch ($align) {
            case 'T':
            {
                $this->y = $y;
                $this->x = $this->imageRBX;
                break;
            }
            case 'M':
            {
                $this->y = $y + round($h / 2);
                $this->x = $this->imageRBX;
                break;
            }
            case 'B':
            {
                $this->y = $this->imageRBY;
                $this->x = $this->imageRBX;
                break;
            }
            case 'N':
            {
                $this->y($this->imageRBY);
                break;
            }
            default:
            {
                // restore pointer to starting position
                $this->x              = $gvars['x'];
                $this->y              = $gvars['y'];
                $this->page           = $gvars['page'];
                $this->current_column = $gvars['current_column'];
                $this->tMargin        = $gvars['tMargin'];
                $this->bMargin        = $gvars['bMargin'];
                $this->w              = $gvars['w'];
                $this->h              = $gvars['h'];
                $this->wPt            = $gvars['wPt'];
                $this->hPt            = $gvars['hPt'];
                $this->fwPt           = $gvars['fwPt'];
                $this->fhPt           = $gvars['fhPt'];
                break;
            }
        }
        $this->endlinex = $this->imageRBX;
        // restore page break
        $this->setAutoPageBreak($page_break_mode, $page_break_margin);
        $this->cell_padding = $cell_padding;
    }

    /**
     * Convert SVG transformation matrix to PDF.
     * @param array $tm original SVG transformation matrix
     * @return array transformation matrix
     */
    protected function convertSVGtMatrix($tm)
    {
        $a = $tm[0];
        $b = -$tm[1];
        $c = -$tm[2];
        $d = $tm[3];
        $e = $this->fontSizeToUnits($tm[4]) * $this->k;
        $f = -$this->fontSizeToUnits($tm[5]) * $this->k;
        $x = 0;
        $y = $this->h * $this->k;
        $e = ($x * (1 - $a)) - ($y * $c) + $e;
        $f = ($y * (1 - $d)) - ($x * $b) + $f;
        return [$a, $b, $c, $d, $e, $f];
    }

    /**
     * Apply SVG graphic transformation matrix.
     * @param array $tm original SVG transformation matrix
     */
    protected function SVGTransform($tm)
    {
        $this->Transform($this->convertSVGtMatrix($tm));
    }

    /**
     * Apply the requested SVG styles (*** TO BE COMPLETED ***)
     * @param array  $svgstyle      array of SVG styles to apply
     * @param array  $prevsvgstyle  array of previous SVG style
     * @param int    $x             X origin of the bounding box
     * @param int    $y             Y origin of the bounding box
     * @param int    $w             width of the bounding box
     * @param int    $h             height of the bounding box
     * @param string $clip_function clip function
     * @param array  $clip_params   array of parameters for clipping function
     * @return string style
     * @author Nicola Asuni
     */
    protected function setSVGStyles($svgstyle, $prevsvgstyle, $x = 0, $y = 0, $w = 1, $h = 1, $clip_function = '', $clip_params = [])
    {
        if ($this->state !== self::READY) {
            return;
        }
        $objstyle = '';
        $minlen   = (0.01 / $this->k); // minimum acceptable length
        if (!isset($svgstyle['opacity'])) {
            return $objstyle;
        }
        // clip-path
        $regs = [];
        if (preg_match('/url\([\s]*\#([^\)]*)\)/si', $svgstyle['clip-path'], $regs)) {
            $clip_path = $this->svgclippaths[$regs[1]];
            foreach ($clip_path as $cp) {
                $this->startSVGElementHandler('clip-path', $cp['name'], $cp['attribs'], $cp['tm']);
            }
        }
        // opacity
        if ($svgstyle['opacity'] != 1) {
            $this->setAlpha($svgstyle['opacity'], 'Normal', $svgstyle['opacity'], false);
        }
        // color
        $fill_color = $this->htmlToColor($svgstyle['color']);
        $this->fillColorArray($fill_color);
        // text color
        $text_color = $this->htmlToColor($svgstyle['text-color']);
        $this->textColorArray($text_color);
        // clip
        if (preg_match('/rect\(([a-z0-9\-\.]*)[\s]*([a-z0-9\-\.]*)[\s]*([a-z0-9\-\.]*)[\s]*([a-z0-9\-\.]*)\)/si', $svgstyle['clip'], $regs)) {
            $top    = (isset($regs[1]) ? $this->fontSizeToUnits($regs[1], 0) : 0);
            $right  = (isset($regs[2]) ? $this->fontSizeToUnits($regs[2], 0) : 0);
            $bottom = (isset($regs[3]) ? $this->fontSizeToUnits($regs[3], 0) : 0);
            $left   = (isset($regs[4]) ? $this->fontSizeToUnits($regs[4], 0) : 0);
            $cx     = $x + $left;
            $cy     = $y + $top;
            $cw     = $w - $left - $right;
            $ch     = $h - $top - $bottom;
            if ($svgstyle['clip-rule'] == 'evenodd') {
                $clip_rule = 'CNZ';
            }
            else {
                $clip_rule = 'CEO';
            }
            $this->Rect($cx, $cy, $cw, $ch, $clip_rule, [], []);
        }
        // fill
        $regs = [];
        if (preg_match('/url\([\s]*\#([^\)]*)\)/si', $svgstyle['fill'], $regs)) {
            // gradient
            $gradient = $this->svggradients[$regs[1]];
            if (isset($gradient['xref'])) {
                // reference to another gradient definition
                $newgradient                  = $this->svggradients[$gradient['xref']];
                $newgradient['coords']        = $gradient['coords'];
                $newgradient['mode']          = $gradient['mode'];
                $newgradient['type']          = $gradient['type'];
                $newgradient['gradientUnits'] = $gradient['gradientUnits'];
                if (isset($gradient['gradientTransform'])) {
                    $newgradient['gradientTransform'] = $gradient['gradientTransform'];
                }
                $gradient = $newgradient;
            }
            //save current Graphic State
            $this->_outSaveGraphicsState();
            //set clipping area
            if (!empty($clip_function) and method_exists($this, $clip_function)) {
                $bbox = call_user_func_array([$this, $clip_function], $clip_params);
                if ((!isset($gradient['type']) or ($gradient['type'] != 3)) and is_array($bbox) and (count($bbox) == 4)) {
                    list($x, $y, $w, $h) = $bbox;
                }
            }
            if ($gradient['mode'] == 'measure') {
                if (!isset($gradient['coords'][4])) {
                    $gradient['coords'][4] = 0.5;
                }
                if (isset($gradient['gradientTransform']) and !empty($gradient['gradientTransform'])) {
                    $gtm = $gradient['gradientTransform'];
                    // apply transformation matrix
                    $xa                    = ($gtm[0] * $gradient['coords'][0]) + ($gtm[2] * $gradient['coords'][1]) + $gtm[4];
                    $ya                    = ($gtm[1] * $gradient['coords'][0]) + ($gtm[3] * $gradient['coords'][1]) + $gtm[5];
                    $xb                    = ($gtm[0] * $gradient['coords'][2]) + ($gtm[2] * $gradient['coords'][3]) + $gtm[4];
                    $yb                    = ($gtm[1] * $gradient['coords'][2]) + ($gtm[3] * $gradient['coords'][3]) + $gtm[5];
                    $r                     = sqrt(pow(($gtm[0] * $gradient['coords'][4]), 2) + pow(($gtm[1] * $gradient['coords'][4]), 2));
                    $gradient['coords'][0] = $xa;
                    $gradient['coords'][1] = $ya;
                    $gradient['coords'][2] = $xb;
                    $gradient['coords'][3] = $yb;
                    $gradient['coords'][4] = $r;
                }
                // convert SVG coordinates to user units
                $gradient['coords'][0] = $this->fontSizeToUnits($gradient['coords'][0], 0);
                $gradient['coords'][1] = $this->fontSizeToUnits($gradient['coords'][1], 0);
                $gradient['coords'][2] = $this->fontSizeToUnits($gradient['coords'][2], 0);
                $gradient['coords'][3] = $this->fontSizeToUnits($gradient['coords'][3], 0);
                $gradient['coords'][4] = $this->fontSizeToUnits($gradient['coords'][4], 0);
                if ($w <= $minlen) {
                    $w = $minlen;
                }
                if ($h <= $minlen) {
                    $h = $minlen;
                }
                // shift units
                if ($gradient['gradientUnits'] == 'objectBoundingBox') {
                    // convert to SVG coordinate system
                    $gradient['coords'][0] += $x;
                    $gradient['coords'][1] += $y;
                    $gradient['coords'][2] += $x;
                    $gradient['coords'][3] += $y;
                }
                // calculate percentages
                $gradient['coords'][0] = (($gradient['coords'][0] - $x) / $w);
                $gradient['coords'][1] = (($gradient['coords'][1] - $y) / $h);
                $gradient['coords'][2] = (($gradient['coords'][2] - $x) / $w);
                $gradient['coords'][3] = (($gradient['coords'][3] - $y) / $h);
                $gradient['coords'][4] /= $w;
            }
            elseif ($gradient['mode'] == 'percentage') {
                foreach ($gradient['coords'] as $key => $val) {
                    $gradient['coords'][$key] = (intval($val) / 100);
                    if ($val < 0) {
                        $gradient['coords'][$key] = 0;
                    }
                    elseif ($val > 1) {
                        $gradient['coords'][$key] = 1;
                    }
                }
            }
            if (($gradient['type'] == 2) and ($gradient['coords'][0] == $gradient['coords'][2]) and ($gradient['coords'][1] == $gradient['coords'][3])) {
                // single color (no shading)
                $gradient['coords'][0] = 1;
                $gradient['coords'][1] = 0;
                $gradient['coords'][2] = 0.999;
                $gradient['coords'][3] = 0;
            }
            // swap Y coordinates
            $tmp                   = $gradient['coords'][1];
            $gradient['coords'][1] = $gradient['coords'][3];
            $gradient['coords'][3] = $tmp;
            // set transformation map for gradient
            $cy = ($this->h - $y);
            if ($gradient['type'] == 3) {
                // circular gradient
                $cy -= ($gradient['coords'][1] * ($w + $h));
                $h  = $w = max($w, $h);
            }
            else {
                $cy -= $h;
            }
            $this->_out(sprintf('%F 0 0 %F %F %F cm', ($w * $this->k), ($h * $this->k), ($x * $this->k), ($cy * $this->k)));
            if (count($gradient['stops']) > 1) {
                $this->Gradient($gradient['type'], $gradient['coords'], $gradient['stops'], [], false);
            }
        }
        elseif ($svgstyle['fill'] != 'none') {
            $fill_color = $this->htmlToColor($svgstyle['fill']);
            if ($svgstyle['fill-opacity'] != 1) {
                $this->setAlpha($this->alpha['CA'], 'Normal', $svgstyle['fill-opacity'], false);
            }
            $this->fillColorArray($fill_color);
            if ($svgstyle['fill-rule'] == 'evenodd') {
                $objstyle .= 'F*';
            }
            else {
                $objstyle .= 'F';
            }
        }
        // stroke
        if ($svgstyle['stroke'] != 'none') {
            if ($svgstyle['stroke-opacity'] != 1) {
                $this->setAlpha($svgstyle['stroke-opacity'], 'Normal', $this->alpha['ca'], false);
            }
            elseif (preg_match('/rgba\(\d+%?,\s*\d+%?,\s*\d+%?,\s*(\d+(?:\.\d+)?)\)/i', $svgstyle['stroke'], $rgba_matches)) {
                $this->setAlpha($rgba_matches[1], 'Normal', $this->alpha['ca'], false);
            }
            $stroke_style = array(
                'color' => $this->htmlToColor($svgstyle['stroke']),
                'width' => $this->fontSizeToUnits($svgstyle['stroke-width'], 0),
                'cap'   => $svgstyle['stroke-linecap'],
                'join'  => $svgstyle['stroke-linejoin']
            );
            if (isset($svgstyle['stroke-dasharray']) and !empty($svgstyle['stroke-dasharray']) and ($svgstyle['stroke-dasharray'] != 'none')) {
                $stroke_style['dash'] = $svgstyle['stroke-dasharray'];
            }
            $this->lineStyle($stroke_style);
            $objstyle .= 'D';
        }
        // font
        $regs = [];
        if (!empty($svgstyle['font'])) {
            if (preg_match('/font-family[\s]*:[\s]*([^\;\"]*)/si', $svgstyle['font'], $regs)) {
                $font_family = $this->fontFamily($regs[1]);
            }
            else {
                $font_family = $svgstyle['font-family'];
            }
            if (preg_match('/font-size[\s]*:[\s]*([^\s\;\"]*)/si', $svgstyle['font'], $regs)) {
                $font_size = trim($regs[1]);
            }
            else {
                $font_size = $svgstyle['font-size'];
            }
            if (preg_match('/font-style[\s]*:[\s]*([^\s\;\"]*)/si', $svgstyle['font'], $regs)) {
                $font_style = trim($regs[1]);
            }
            else {
                $font_style = $svgstyle['font-style'];
            }
            if (preg_match('/font-weight[\s]*:[\s]*([^\s\;\"]*)/si', $svgstyle['font'], $regs)) {
                $font_weight = trim($regs[1]);
            }
            else {
                $font_weight = $svgstyle['font-weight'];
            }
            if (preg_match('/font-stretch[\s]*:[\s]*([^\s\;\"]*)/si', $svgstyle['font'], $regs)) {
                $font_stretch = trim($regs[1]);
            }
            else {
                $font_stretch = $svgstyle['font-stretch'];
            }
            if (preg_match('/letter-spacing[\s]*:[\s]*([^\s\;\"]*)/si', $svgstyle['font'], $regs)) {
                $font_spacing = trim($regs[1]);
            }
            else {
                $font_spacing = $svgstyle['letter-spacing'];
            }
        }
        else {
            $font_family  = $this->fontFamily($svgstyle['font-family']);
            $font_size    = $svgstyle['font-size'];
            $font_style   = $svgstyle['font-style'];
            $font_weight  = $svgstyle['font-weight'];
            $font_stretch = $svgstyle['font-stretch'];
            $font_spacing = $svgstyle['letter-spacing'];
        }
        $font_size    = $this->fontSizeToUnits($font_size, $prevsvgstyle['font-size'], self::PIXEL, true);
        $font_stretch = $this->getCSSFontStretching($font_stretch, $svgstyle['font-stretch']);
        $font_spacing = $this->getCSSFontSpacing($font_spacing, $svgstyle['letter-spacing']);
        switch ($font_style) {
            case 'italic':
            {
                $font_style = 'I';
                break;
            }
            case 'oblique':
            {
                $font_style = 'I';
                break;
            }
            default:
            case 'normal':
            {
                $font_style = '';
                break;
            }
        }
        switch ($font_weight) {
            case 'bold':
            case 'bolder':
            {
                $font_style .= 'B';
                break;
            }
            case 'normal':
            {
                if ((substr($font_family, -1) == 'I') and (substr($font_family, -2, 1) == 'B')) {
                    $font_family = substr($font_family, 0, -2) . 'I';
                }
                elseif (substr($font_family, -1) == 'B') {
                    $font_family = substr($font_family, 0, -1);
                }
                break;
            }
        }
        switch ($svgstyle['text-decoration']) {
            case 'underline':
            {
                $font_style .= 'U';
                break;
            }
            case 'overline':
            {
                $font_style .= 'O';
                break;
            }
            case 'line-through':
            {
                $font_style .= 'D';
                break;
            }
            default:
            case 'none':
            {
                break;
            }
        }
        $this->setCurrentFont($font_family, $font_style, $font_size);
        $this->setFontStretching($font_stretch);
        $this->setFontSpacing($font_spacing);
        return $objstyle;
    }

    /**
     * Draws an SVG path
     * @param string $d     attribute d of the path SVG element
     * @param string $style Style of rendering. Possible values are:
     *                      <ul>
     *                      <li>D or empty string: Draw (default).</li>
     *                      <li>F: Fill.</li>
     *                      <li>F*: Fill using the even-odd rule to determine which regions lie inside the clipping path.</li>
     *                      <li>DF or FD: Draw and fill.</li>
     *                      <li>DF* or FD*: Draw and fill using the even-odd rule to determine which regions lie inside the clipping path.</li>
     *                      <li>CNZ: Clipping mode (using the even-odd rule to determine which regions lie inside the clipping path).</li>
     *                      <li>CEO: Clipping mode (using the nonzero winding number rule to determine which regions lie inside the clipping path).</li>
     *                      </ul>
     * @return array of container box measures (x, y, w, h)
     * @author Nicola Asuni
     */
    protected function SVGPath($d, $style = '')
    {
        if ($this->state !== self::READY) {
            return;
        }
        // set fill/stroke style
        $op = StaticMethods::getPathPaintOperator($style, '');
        if (empty($op)) {
            return;
        }
        $paths = [];
        $d     = preg_replace('/([0-9ACHLMQSTVZ])([\-\+])/si', '\\1 \\2', $d);
        $d     = preg_replace('/(\.[0-9]+)(\.)/s', '\\1 \\2', $d);
        preg_match_all('/([ACHLMQSTVZ])[\s]*([^ACHLMQSTVZ\"]*)/si', $d, $paths, PREG_SET_ORDER);
        $x        = 0;
        $y        = 0;
        $x1       = 0;
        $y1       = 0;
        $x2       = 0;
        $y2       = 0;
        $xmin     = 2147483647;
        $xmax     = 0;
        $ymin     = 2147483647;
        $ymax     = 0;
        $xinitial = 0;
        $yinitial = 0;
        $relcoord = false;
        $minlen   = (0.01 / $this->k); // minimum acceptable length (3 point)
        $firstcmd = true; // used to print first point
        // draw curve pieces
        foreach ($paths as $key => $val) {
            // get curve type
            $cmd = trim($val[1]);
            if (strtolower($cmd) == $cmd) {
                // use relative coordinated instead of absolute
                $relcoord = true;
                $xoffset  = $x;
                $yoffset  = $y;
            }
            else {
                $relcoord = false;
                $xoffset  = 0;
                $yoffset  = 0;
            }
            $params = [];
            if (isset($val[2])) {
                // get curve parameters
                $rawparams = preg_split('/([\,\s]+)/si', trim($val[2]));
                $params    = [];
                foreach ($rawparams as $ck => $cp) {
                    $params[$ck] = $this->fontSizeToUnits($cp, 0);
                    if (abs($params[$ck]) < $minlen) {
                        // approximate little values to zero
                        $params[$ck] = 0;
                    }
                }
            }
            // store current origin point
            $x0 = $x;
            $y0 = $y;
            switch (strtoupper($cmd)) {
                case 'M':
                { // moveto
                    foreach ($params as $ck => $cp) {
                        if (($ck % 2) == 0) {
                            $x = $cp + $xoffset;
                        }
                        else {
                            $y = $cp + $yoffset;
                            if ($firstcmd or (abs($x0 - $x) >= $minlen) or (abs($y0 - $y) >= $minlen)) {
                                if ($ck == 1) {
                                    $this->_outPoint($x, $y);
                                    $firstcmd = false;
                                    $xinitial = $x;
                                    $yinitial = $y;
                                }
                                else {
                                    $this->_outLine($x, $y);
                                }
                                $x0 = $x;
                                $y0 = $y;
                            }
                            $xmin = min($xmin, $x);
                            $ymin = min($ymin, $y);
                            $xmax = max($xmax, $x);
                            $ymax = max($ymax, $y);
                            if ($relcoord) {
                                $xoffset = $x;
                                $yoffset = $y;
                            }
                        }
                    }
                    break;
                }
                case 'L':
                { // lineto
                    foreach ($params as $ck => $cp) {
                        if (($ck % 2) == 0) {
                            $x = $cp + $xoffset;
                        }
                        else {
                            $y = $cp + $yoffset;
                            if ((abs($x0 - $x) >= $minlen) or (abs($y0 - $y) >= $minlen)) {
                                $this->_outLine($x, $y);
                                $x0 = $x;
                                $y0 = $y;
                            }
                            $xmin = min($xmin, $x);
                            $ymin = min($ymin, $y);
                            $xmax = max($xmax, $x);
                            $ymax = max($ymax, $y);
                            if ($relcoord) {
                                $xoffset = $x;
                                $yoffset = $y;
                            }
                        }
                    }
                    break;
                }
                case 'H':
                { // horizontal lineto
                    foreach ($params as $ck => $cp) {
                        $x = $cp + $xoffset;
                        if ((abs($x0 - $x) >= $minlen) or (abs($y0 - $y) >= $minlen)) {
                            $this->_outLine($x, $y);
                            $x0 = $x;
                            $y0 = $y;
                        }
                        $xmin = min($xmin, $x);
                        $xmax = max($xmax, $x);
                        if ($relcoord) {
                            $xoffset = $x;
                        }
                    }
                    break;
                }
                case 'V':
                { // vertical lineto
                    foreach ($params as $ck => $cp) {
                        $y = $cp + $yoffset;
                        if ((abs($x0 - $x) >= $minlen) or (abs($y0 - $y) >= $minlen)) {
                            $this->_outLine($x, $y);
                            $x0 = $x;
                            $y0 = $y;
                        }
                        $ymin = min($ymin, $y);
                        $ymax = max($ymax, $y);
                        if ($relcoord) {
                            $yoffset = $y;
                        }
                    }
                    break;
                }
                case 'C':
                { // curveto
                    foreach ($params as $ck => $cp) {
                        $params[$ck] = $cp;
                        if ((($ck + 1) % 6) == 0) {
                            $x1 = $params[($ck - 5)] + $xoffset;
                            $y1 = $params[($ck - 4)] + $yoffset;
                            $x2 = $params[($ck - 3)] + $xoffset;
                            $y2 = $params[($ck - 2)] + $yoffset;
                            $x  = $params[($ck - 1)] + $xoffset;
                            $y  = $params[($ck)] + $yoffset;
                            $this->_outCurve($x1, $y1, $x2, $y2, $x, $y);
                            $xmin = min($xmin, $x, $x1, $x2);
                            $ymin = min($ymin, $y, $y1, $y2);
                            $xmax = max($xmax, $x, $x1, $x2);
                            $ymax = max($ymax, $y, $y1, $y2);
                            if ($relcoord) {
                                $xoffset = $x;
                                $yoffset = $y;
                            }
                        }
                    }
                    break;
                }
                case 'S':
                { // shorthand/smooth curveto
                    foreach ($params as $ck => $cp) {
                        $params[$ck] = $cp;
                        if ((($ck + 1) % 4) == 0) {
                            if (($key > 0) and ((strtoupper($paths[($key - 1)][1]) == 'C') or (strtoupper($paths[($key - 1)][1]) == 'S'))) {
                                $x1 = (2 * $x) - $x2;
                                $y1 = (2 * $y) - $y2;
                            }
                            else {
                                $x1 = $x;
                                $y1 = $y;
                            }
                            $x2 = $params[($ck - 3)] + $xoffset;
                            $y2 = $params[($ck - 2)] + $yoffset;
                            $x  = $params[($ck - 1)] + $xoffset;
                            $y  = $params[($ck)] + $yoffset;
                            $this->_outCurve($x1, $y1, $x2, $y2, $x, $y);
                            $xmin = min($xmin, $x, $x1, $x2);
                            $ymin = min($ymin, $y, $y1, $y2);
                            $xmax = max($xmax, $x, $x1, $x2);
                            $ymax = max($ymax, $y, $y1, $y2);
                            if ($relcoord) {
                                $xoffset = $x;
                                $yoffset = $y;
                            }
                        }
                    }
                    break;
                }
                case 'Q':
                { // quadratic Bezier curveto
                    foreach ($params as $ck => $cp) {
                        $params[$ck] = $cp;
                        if ((($ck + 1) % 4) == 0) {
                            // convert quadratic points to cubic points
                            $x1 = $params[($ck - 3)] + $xoffset;
                            $y1 = $params[($ck - 2)] + $yoffset;
                            $xa = ($x + (2 * $x1)) / 3;
                            $ya = ($y + (2 * $y1)) / 3;
                            $x  = $params[($ck - 1)] + $xoffset;
                            $y  = $params[($ck)] + $yoffset;
                            $xb = ($x + (2 * $x1)) / 3;
                            $yb = ($y + (2 * $y1)) / 3;
                            $this->_outCurve($xa, $ya, $xb, $yb, $x, $y);
                            $xmin = min($xmin, $x, $xa, $xb);
                            $ymin = min($ymin, $y, $ya, $yb);
                            $xmax = max($xmax, $x, $xa, $xb);
                            $ymax = max($ymax, $y, $ya, $yb);
                            if ($relcoord) {
                                $xoffset = $x;
                                $yoffset = $y;
                            }
                        }
                    }
                    break;
                }
                case 'T':
                { // shorthand/smooth quadratic Bezier curveto
                    foreach ($params as $ck => $cp) {
                        $params[$ck] = $cp;
                        if (($ck % 2) != 0) {
                            if (($key > 0) and ((strtoupper($paths[($key - 1)][1]) == 'Q') or (strtoupper($paths[($key - 1)][1]) == 'T'))) {
                                $x1 = (2 * $x) - $x1;
                                $y1 = (2 * $y) - $y1;
                            }
                            else {
                                $x1 = $x;
                                $y1 = $y;
                            }
                            // convert quadratic points to cubic points
                            $xa = ($x + (2 * $x1)) / 3;
                            $ya = ($y + (2 * $y1)) / 3;
                            $x  = $params[($ck - 1)] + $xoffset;
                            $y  = $params[($ck)] + $yoffset;
                            $xb = ($x + (2 * $x1)) / 3;
                            $yb = ($y + (2 * $y1)) / 3;
                            $this->_outCurve($xa, $ya, $xb, $yb, $x, $y);
                            $xmin = min($xmin, $x, $xa, $xb);
                            $ymin = min($ymin, $y, $ya, $yb);
                            $xmax = max($xmax, $x, $xa, $xb);
                            $ymax = max($ymax, $y, $ya, $yb);
                            if ($relcoord) {
                                $xoffset = $x;
                                $yoffset = $y;
                            }
                        }
                    }
                    break;
                }
                case 'A':
                { // elliptical arc
                    foreach ($params as $ck => $cp) {
                        $params[$ck] = $cp;
                        if ((($ck + 1) % 7) == 0) {
                            $x0    = $x;
                            $y0    = $y;
                            $rx    = max(abs($params[($ck - 6)]), .000000001);
                            $ry    = max(abs($params[($ck - 5)]), .000000001);
                            $ang   = -$rawparams[($ck - 4)];
                            $angle = deg2rad($ang);
                            $fa    = $rawparams[($ck - 3)]; // large-arc-flag
                            $fs    = $rawparams[($ck - 2)]; // sweep-flag
                            $x     = $params[($ck - 1)] + $xoffset;
                            $y     = $params[$ck] + $yoffset;
                            if ((abs($x0 - $x) < $minlen) and (abs($y0 - $y) < $minlen)) {
                                // endpoints are almost identical
                                $xmin = min($xmin, $x);
                                $ymin = min($ymin, $y);
                                $xmax = max($xmax, $x);
                                $ymax = max($ymax, $y);
                            }
                            else {
                                $cos_ang = cos($angle);
                                $sin_ang = sin($angle);
                                $a       = (($x0 - $x) / 2);
                                $b       = (($y0 - $y) / 2);
                                $xa      = ($a * $cos_ang) - ($b * $sin_ang);
                                $ya      = ($a * $sin_ang) + ($b * $cos_ang);
                                $rx2     = $rx * $rx;
                                $ry2     = $ry * $ry;
                                $xa2     = $xa * $xa;
                                $ya2     = $ya * $ya;
                                $delta   = ($xa2 / $rx2) + ($ya2 / $ry2);
                                if ($delta > 1) {
                                    $rx  *= sqrt($delta);
                                    $ry  *= sqrt($delta);
                                    $rx2 = $rx * $rx;
                                    $ry2 = $ry * $ry;
                                }
                                $numerator = (($rx2 * $ry2) - ($rx2 * $ya2) - ($ry2 * $xa2));
                                if ($numerator < 0) {
                                    $root = 0;
                                }
                                else {
                                    $root = sqrt($numerator / (($rx2 * $ya2) + ($ry2 * $xa2)));
                                }
                                if ($fa == $fs) {
                                    $root *= -1;
                                }
                                $cax = $root * (($rx * $ya) / $ry);
                                $cay = -$root * (($ry * $xa) / $rx);
                                // coordinates of ellipse center
                                $cx = ($cax * $cos_ang) - ($cay * $sin_ang) + (($x0 + $x) / 2);
                                $cy = ($cax * $sin_ang) + ($cay * $cos_ang) + (($y0 + $y) / 2);
                                // get angles
                                $angs = StaticMethods::getVectorsAngle(1, 0, (($xa - $cax) / $rx), (($cay - $ya) / $ry));
                                $dang = StaticMethods::getVectorsAngle((($xa - $cax) / $rx), (($ya - $cay) / $ry), ((-$xa - $cax) / $rx), ((-$ya - $cay) / $ry));
                                if (($fs == 0) and ($dang > 0)) {
                                    $dang -= (2 * M_PI);
                                }
                                elseif (($fs == 1) and ($dang < 0)) {
                                    $dang += (2 * M_PI);
                                }
                                $angf = $angs - $dang;
                                if ((($fs == 0) and ($angs > $angf)) or (($fs == 1) and ($angs < $angf))) {
                                    // reverse angles
                                    $tmp  = $angs;
                                    $angs = $angf;
                                    $angf = $tmp;
                                }
                                $angs = round(rad2deg($angs), 6);
                                $angf = round(rad2deg($angf), 6);
                                // covent angles to positive values
                                if (($angs < 0) and ($angf < 0)) {
                                    $angs += 360;
                                    $angf += 360;
                                }
                                $pie = false;
                                if (($key == 0) and (isset($paths[($key + 1)][1])) and (trim($paths[($key + 1)][1]) == 'z')) {
                                    $pie = true;
                                }
                                list($axmin, $aymin, $axmax, $aymax) = $this->_outellipticalarc($cx, $cy, $rx, $ry, $ang, $angs, $angf, $pie, 2, false, ($fs == 0), true);
                                $xmin = min($xmin, $x, $axmin);
                                $ymin = min($ymin, $y, $aymin);
                                $xmax = max($xmax, $x, $axmax);
                                $ymax = max($ymax, $y, $aymax);
                            }
                            if ($relcoord) {
                                $xoffset = $x;
                                $yoffset = $y;
                            }
                        }
                    }
                    break;
                }
                case 'Z':
                {
                    $this->_out('h');
                    $x = $x0 = $xinitial;
                    $y = $y0 = $yinitial;
                    break;
                }
            }
            $firstcmd = false;
        } // end foreach
        $this->_out($op);
        return array($xmin, $ymin, ($xmax - $xmin), ($ymax - $ymin));
    }

    /**
     * Sets the opening SVG element handler function for the XML parser. (*** TO BE COMPLETED ***)
     * @param resource|string $parser  The first parameter, parser, is a reference to the XML parser calling the handler.
     * @param string          $name    The second parameter, name, contains the name of the element for which this handler is called. If case-folding is in effect for this parser, the element name will be in uppercase letters.
     * @param array           $attribs The third parameter, attribs, contains an associative array with the element's attributes (if any). The keys of this array are the attribute names, the values are the attribute values. Attribute names are case-folded on the same criteria as element names. Attribute values are not case-folded. The original order of the attributes can be retrieved by walking through attribs the normal way, using each(). The first key in the array was the first attribute, and so on.
     * @param array           $ctm     tranformation matrix for clipping mode (starting transformation matrix).
     * @author Nicola Asuni
     */
    protected function startSVGElementHandler($parser, $name, $attribs, $ctm = [])
    {
        $name = $this->removeTagNamespace($name);
        // check if we are in clip mode
        if ($this->svgclipmode) {
            $this->svgclippaths[$this->svgclipid][] = ['name' => $name, 'attribs' => $attribs, 'tm' => $this->svgcliptm[$this->svgclipid]];
            return;
        }
        if ($this->svgdefsmode and !in_array($name, ['clipPath', 'linearGradient', 'radialGradient', 'stop'])) {
            if (isset($attribs['id'])) {
                $attribs['child_elements']     = [];
                $this->svgdefs[$attribs['id']] = ['name' => $name, 'attribs' => $attribs];
                return;
            }
            if (end($this->svgdefs) !== FALSE) {
                $last_svgdefs_id = key($this->svgdefs);
                if (isset($this->svgdefs[$last_svgdefs_id]['attribs']['child_elements'])) {
                    $attribs['id']                                                                = 'DF_' . (count($this->svgdefs[$last_svgdefs_id]['attribs']['child_elements']) + 1);
                    $this->svgdefs[$last_svgdefs_id]['attribs']['child_elements'][$attribs['id']] = ['name' => $name, 'attribs' => $attribs];
                    return;
                }
            }
            return;
        }
        $clipping = false;
        if ($parser == 'clip-path') {
            // set clipping mode
            $clipping = true;
        }
        // get styling properties
        $prev_svgstyle = $this->svgstyles[max(0, (count($this->svgstyles) - 1))]; // previous style
        $svgstyle      = $this->svgstyles[0]; // set default style
        if ($clipping and !isset($attribs['fill']) and (!isset($attribs['style']) or (!preg_match('/[;\"\s]{1}fill[\s]*:[\s]*([^;\"]*)/si', $attribs['style'], $attrval)))) {
            // default fill attribute for clipping
            $attribs['fill'] = 'none';
        }
        if (isset($attribs['style']) and !StaticMethods::empty_string($attribs['style']) and ($attribs['style'][0] != ';')) {
            // fix style for regular expression
            $attribs['style'] = ';' . $attribs['style'];
        }
        foreach ($prev_svgstyle as $key => $val) {
            if (in_array($key, self::SVG_PROPERTIES)) {
                // inherit previous value
                $svgstyle[$key] = $val;
            }
            if (isset($attribs[$key]) and !StaticMethods::empty_string($attribs[$key])) {
                // specific attribute settings
                if ($attribs[$key] == 'inherit') {
                    $svgstyle[$key] = $val;
                }
                else {
                    $svgstyle[$key] = $attribs[$key];
                }
            }
            elseif (isset($attribs['style']) and !StaticMethods::empty_string($attribs['style'])) {
                // CSS style syntax
                $attrval = [];
                if (preg_match('/[;\"\s]{1}' . $key . '[\s]*:[\s]*([^;\"]*)/si', $attribs['style'], $attrval) and isset($attrval[1])) {
                    if ($attrval[1] == 'inherit') {
                        $svgstyle[$key] = $val;
                    }
                    else {
                        $svgstyle[$key] = $attrval[1];
                    }
                }
            }
        }
        // transformation matrix
        if (!empty($ctm)) {
            $tm = $ctm;
        }
        else {
            $tm = [1, 0, 0, 1, 0, 0];
        }
        if (isset($attribs['transform']) and !empty($attribs['transform'])) {
            $tm = StaticMethods::getTransformationMatrixProduct($tm, StaticMethods::getSVGTransformMatrix($attribs['transform']));
        }
        $svgstyle['transfmatrix'] = $tm;
        $invisible                = false;
        if (($svgstyle['visibility'] == 'hidden') or ($svgstyle['visibility'] == 'collapse') or ($svgstyle['display'] == 'none')) {
            // the current graphics element is invisible (nothing is painted)
            $invisible = true;
        }
        // process tag
        switch ($name) {
            case 'defs':
            {
                $this->svgdefsmode = true;
                break;
            }
            // clipPath
            case 'clipPath':
            {
                if ($invisible) {
                    break;
                }
                $this->svgclipmode = true;
                if (!isset($attribs['id'])) {
                    $attribs['id'] = 'CP_' . (count($this->svgcliptm) + 1);
                }
                $this->svgclipid                      = $attribs['id'];
                $this->svgclippaths[$this->svgclipid] = [];
                $this->svgcliptm[$this->svgclipid]    = $tm;
                break;
            }
            case 'svg':
            {
                // start of SVG object
                if (++$this->svg_tag_depth <= 1) {
                    break;
                }
                // inner SVG
                array_push($this->svgstyles, $svgstyle);
                $this->StartTransform();
                $svgX = (isset($attribs['x']) ? $attribs['x'] : 0);
                $svgY = (isset($attribs['y']) ? $attribs['y'] : 0);
                $svgW = (isset($attribs['width']) ? $attribs['width'] : 0);
                $svgH = (isset($attribs['height']) ? $attribs['height'] : 0);
                // set x, y position using transform matrix
                $tm = StaticMethods::getTransformationMatrixProduct($tm, [1, 0, 0, 1, $svgX, $svgY]);
                $this->SVGTransform($tm);
                // set clipping for width and height
                $x = 0;
                $y = 0;
                $w = (isset($attribs['width']) ? $this->fontSizeToUnits($attribs['width'], 0) : $this->w);
                $h = (isset($attribs['height']) ? $this->fontSizeToUnits($attribs['height'], 0) : $this->h);
                // draw clipping rect
                $this->Rect($x, $y, $w, $h, 'CNZ', [], []);
                // parse viewbox, calculate extra transformation matrix
                if (isset($attribs['viewBox'])) {
                    $tmp = [];
                    preg_match_all("/[0-9]+/", $attribs['viewBox'], $tmp);
                    $tmp = $tmp[0];
                    if (sizeof($tmp) == 4) {
                        $vx = $tmp[0];
                        $vy = $tmp[1];
                        $vw = $tmp[2];
                        $vh = $tmp[3];
                        // get aspect ratio
                        $tmp     = [];
                        $aspectX = 'xMid';
                        $aspectY = 'YMid';
                        $fit     = 'meet';
                        if (isset($attribs['preserveAspectRatio'])) {
                            if ($attribs['preserveAspectRatio'] == 'none') {
                                $fit = 'none';
                            }
                            else {
                                preg_match_all('/[a-zA-Z]+/', $attribs['preserveAspectRatio'], $tmp);
                                $tmp = $tmp[0];
                                if ((sizeof($tmp) == 2) and (strlen($tmp[0]) == 8) and (in_array($tmp[1], ['meet', 'slice', 'none']))) {
                                    $aspectX = substr($tmp[0], 0, 4);
                                    $aspectY = substr($tmp[0], 4, 4);
                                    $fit     = $tmp[1];
                                }
                            }
                        }
                        $wr = ($svgW / $vw);
                        $hr = ($svgH / $vh);
                        $ax = $ay = 0;
                        if ((($fit == 'meet') and ($hr < $wr)) or (($fit == 'slice') and ($hr > $wr))) {
                            if ($aspectX == 'xMax') {
                                $ax = (($vw * ($wr / $hr)) - $vw);
                            }
                            if ($aspectX == 'xMid') {
                                $ax = ((($vw * ($wr / $hr)) - $vw) / 2);
                            }
                            $wr = $hr;
                        }
                        elseif ((($fit == 'meet') and ($hr > $wr)) or (($fit == 'slice') and ($hr < $wr))) {
                            if ($aspectY == 'YMax') {
                                $ay = (($vh * ($hr / $wr)) - $vh);
                            }
                            if ($aspectY == 'YMid') {
                                $ay = ((($vh * ($hr / $wr)) - $vh) / 2);
                            }
                            $hr = $wr;
                        }
                        $newtm = array($wr, 0, 0, $hr, (($wr * ($ax - $vx)) - $svgX), (($hr * ($ay - $vy)) - $svgY));
                        $tm    = StaticMethods::getTransformationMatrixProduct($tm, $newtm);
                        $this->SVGTransform($tm);
                    }
                }
                $this->setSVGStyles($svgstyle, $prev_svgstyle);
                break;
            }
            case 'g':
            {
                // group together related graphics elements
                array_push($this->svgstyles, $svgstyle);
                $this->StartTransform();
                $x  = (isset($attribs['x']) ? $attribs['x'] : 0);
                $y  = (isset($attribs['y']) ? $attribs['y'] : 0);
                $w  = 1;//(isset($attribs['width'])?$attribs['width']:1);
                $h  = 1;//(isset($attribs['height'])?$attribs['height']:1);
                $tm = StaticMethods::getTransformationMatrixProduct($tm, [$w, 0, 0, $h, $x, $y]);
                $this->SVGTransform($tm);
                $this->setSVGStyles($svgstyle, $prev_svgstyle);
                break;
            }
            case 'linearGradient':
            {
                if (!isset($attribs['id'])) {
                    $attribs['id'] = 'GR_' . (count($this->svggradients) + 1);
                }
                $this->svggradientid                               = $attribs['id'];
                $this->svggradients[$this->svggradientid]          = [];
                $this->svggradients[$this->svggradientid]['type']  = 2;
                $this->svggradients[$this->svggradientid]['stops'] = [];
                if (isset($attribs['gradientUnits'])) {
                    $this->svggradients[$this->svggradientid]['gradientUnits'] = $attribs['gradientUnits'];
                }
                else {
                    $this->svggradients[$this->svggradientid]['gradientUnits'] = 'objectBoundingBox';
                }
                //$attribs['spreadMethod']
                if (((!isset($attribs['x1'])) and (!isset($attribs['y1'])) and (!isset($attribs['x2'])) and (!isset($attribs['y2'])))
                    or ((isset($attribs['x1']) and (substr($attribs['x1'], -1) == '%'))
                        or (isset($attribs['y1']) and (substr($attribs['y1'], -1) == '%'))
                        or (isset($attribs['x2']) and (substr($attribs['x2'], -1) == '%'))
                        or (isset($attribs['y2']) and (substr($attribs['y2'], -1) == '%')))) {
                    $this->svggradients[$this->svggradientid]['mode'] = 'percentage';
                }
                else {
                    $this->svggradients[$this->svggradientid]['mode'] = 'measure';
                }
                $x1 = (isset($attribs['x1']) ? $attribs['x1'] : '0');
                $y1 = (isset($attribs['y1']) ? $attribs['y1'] : '0');
                $x2 = (isset($attribs['x2']) ? $attribs['x2'] : '100');
                $y2 = (isset($attribs['y2']) ? $attribs['y2'] : '0');
                if (isset($attribs['gradientTransform'])) {
                    $this->svggradients[$this->svggradientid]['gradientTransform'] = StaticMethods::getSVGTransformMatrix($attribs['gradientTransform']);
                }
                $this->svggradients[$this->svggradientid]['coords'] = [$x1, $y1, $x2, $y2];
                if (isset($attribs['xlink:href']) and !empty($attribs['xlink:href'])) {
                    // gradient is defined on another place
                    $this->svggradients[$this->svggradientid]['xref'] = substr($attribs['xlink:href'], 1);
                }
                break;
            }
            case 'radialGradient':
            {
                if (!isset($attribs['id'])) {
                    $attribs['id'] = 'GR_' . (count($this->svggradients) + 1);
                }
                $this->svggradientid                               = $attribs['id'];
                $this->svggradients[$this->svggradientid]          = [];
                $this->svggradients[$this->svggradientid]['type']  = 3;
                $this->svggradients[$this->svggradientid]['stops'] = [];
                if (isset($attribs['gradientUnits'])) {
                    $this->svggradients[$this->svggradientid]['gradientUnits'] = $attribs['gradientUnits'];
                }
                else {
                    $this->svggradients[$this->svggradientid]['gradientUnits'] = 'objectBoundingBox';
                }
                //$attribs['spreadMethod']
                if (((!isset($attribs['cx'])) and (!isset($attribs['cy'])))
                    or ((isset($attribs['cx']) and (substr($attribs['cx'], -1) == '%'))
                        or (isset($attribs['cy']) and (substr($attribs['cy'], -1) == '%')))) {
                    $this->svggradients[$this->svggradientid]['mode'] = 'percentage';
                }
                elseif (isset($attribs['r']) and is_numeric($attribs['r']) and ($attribs['r']) <= 1) {
                    $this->svggradients[$this->svggradientid]['mode'] = 'ratio';
                }
                else {
                    $this->svggradients[$this->svggradientid]['mode'] = 'measure';
                }
                $cx = (isset($attribs['cx']) ? $attribs['cx'] : 0.5);
                $cy = (isset($attribs['cy']) ? $attribs['cy'] : 0.5);
                $fx = (isset($attribs['fx']) ? $attribs['fx'] : $cx);
                $fy = (isset($attribs['fy']) ? $attribs['fy'] : $cy);
                $r  = (isset($attribs['r']) ? $attribs['r'] : 0.5);
                if (isset($attribs['gradientTransform'])) {
                    $this->svggradients[$this->svggradientid]['gradientTransform'] = StaticMethods::getSVGTransformMatrix($attribs['gradientTransform']);
                }
                $this->svggradients[$this->svggradientid]['coords'] = [$cx, $cy, $fx, $fy, $r];
                if (isset($attribs['xlink:href']) and !empty($attribs['xlink:href'])) {
                    // gradient is defined on another place
                    $this->svggradients[$this->svggradientid]['xref'] = substr($attribs['xlink:href'], 1);
                }
                break;
            }
            case 'stop':
            {
                // gradient stops
                if (substr($attribs['offset'], -1) == '%') {
                    $offset = floatval(substr($attribs['offset'], 0, -1)) / 100;
                }
                else {
                    $offset = floatval($attribs['offset']);
                    if ($offset > 1) {
                        $offset /= 100;
                    }
                }
                $stop_color                                          = isset($svgstyle['stop-color']) ? $this->htmlToColor($svgstyle['stop-color']) : 'black';
                $opacity                                             = isset($svgstyle['stop-opacity']) ? $svgstyle['stop-opacity'] : 1;
                $this->svggradients[$this->svggradientid]['stops'][] = ['offset' => $offset, 'color' => $stop_color, 'opacity' => $opacity];
                break;
            }
            // paths
            case 'path':
            {
                if ($invisible) {
                    break;
                }
                if (isset($attribs['d'])) {
                    $d = trim($attribs['d']);
                    if (!empty($d)) {
                        $x  = (isset($attribs['x']) ? $attribs['x'] : 0);
                        $y  = (isset($attribs['y']) ? $attribs['y'] : 0);
                        $w  = (isset($attribs['width']) ? $attribs['width'] : 1);
                        $h  = (isset($attribs['height']) ? $attribs['height'] : 1);
                        $tm = StaticMethods::getTransformationMatrixProduct($tm, [$w, 0, 0, $h, $x, $y]);
                        if ($clipping) {
                            $this->SVGTransform($tm);
                            $this->SVGPath($d, 'CNZ');
                        }
                        else {
                            $this->StartTransform();
                            $this->SVGTransform($tm);
                            $obstyle = $this->setSVGStyles($svgstyle, $prev_svgstyle, $x, $y, $w, $h, 'SVGPath', [$d, 'CNZ']);
                            if (!empty($obstyle)) {
                                $this->SVGPath($d, $obstyle);
                            }
                            $this->StopTransform();
                        }
                    }
                }
                break;
            }
            // shapes
            case 'rect':
            {
                if ($invisible) {
                    break;
                }
                $x  = (isset($attribs['x']) ? $this->fontSizeToUnits($attribs['x'], 0) : 0);
                $y  = (isset($attribs['y']) ? $this->fontSizeToUnits($attribs['y'], 0) : 0);
                $w  = (isset($attribs['width']) ? $this->fontSizeToUnits($attribs['width'], 0) : 0);
                $h  = (isset($attribs['height']) ? $this->fontSizeToUnits($attribs['height'], 0) : 0);
                $rx = (isset($attribs['rx']) ? $this->fontSizeToUnits($attribs['rx'], 0) : 0);
                $ry = (isset($attribs['ry']) ? $this->fontSizeToUnits($attribs['ry'], 0) : $rx);
                if ($clipping) {
                    $this->SVGTransform($tm);
                    $this->RoundedRectXY($x, $y, $w, $h, $rx, $ry, '1111', 'CNZ', [], []);
                }
                else {
                    $this->StartTransform();
                    $this->SVGTransform($tm);
                    $obstyle = $this->setSVGStyles($svgstyle, $prev_svgstyle, $x, $y, $w, $h, 'RoundedRectXY', [$x, $y, $w, $h, $rx, $ry, '1111', 'CNZ']);
                    if (!empty($obstyle)) {
                        $this->RoundedRectXY($x, $y, $w, $h, $rx, $ry, '1111', $obstyle, [], []);
                    }
                    $this->StopTransform();
                }
                break;
            }
            case 'circle':
            {
                if ($invisible) {
                    break;
                }
                $r  = (isset($attribs['r']) ? $this->fontSizeToUnits($attribs['r'], 0) : 0);
                $cx = (isset($attribs['cx']) ? $this->fontSizeToUnits($attribs['cx'], 0) : (isset($attribs['x']) ? $this->fontSizeToUnits($attribs['x'], 0) : 0));
                $cy = (isset($attribs['cy']) ? $this->fontSizeToUnits($attribs['cy'], 0) : (isset($attribs['y']) ? $this->fontSizeToUnits($attribs['y'], 0) : 0));
                $x  = ($cx - $r);
                $y  = ($cy - $r);
                $w  = (2 * $r);
                $h  = $w;
                if ($clipping) {
                    $this->SVGTransform($tm);
                    $this->Circle($cx, $cy, $r, 0, 360, 'CNZ', [], [], 8);
                }
                else {
                    $this->StartTransform();
                    $this->SVGTransform($tm);
                    $obstyle = $this->setSVGStyles($svgstyle, $prev_svgstyle, $x, $y, $w, $h, 'Circle', [$cx, $cy, $r, 0, 360, 'CNZ']);
                    if (!empty($obstyle)) {
                        $this->Circle($cx, $cy, $r, 0, 360, $obstyle, [], [], 8);
                    }
                    $this->StopTransform();
                }
                break;
            }
            case 'ellipse':
            {
                if ($invisible) {
                    break;
                }
                $rx = (isset($attribs['rx']) ? $this->fontSizeToUnits($attribs['rx'], 0) : 0);
                $ry = (isset($attribs['ry']) ? $this->fontSizeToUnits($attribs['ry'], 0) : 0);
                $cx = (isset($attribs['cx']) ? $this->fontSizeToUnits($attribs['cx'], 0) : (isset($attribs['x']) ? $this->fontSizeToUnits($attribs['x'], 0) : 0));
                $cy = (isset($attribs['cy']) ? $this->fontSizeToUnits($attribs['cy'], 0) : (isset($attribs['y']) ? $this->fontSizeToUnits($attribs['y'], 0) : 0));
                $x  = ($cx - $rx);
                $y  = ($cy - $ry);
                $w  = (2 * $rx);
                $h  = (2 * $ry);
                if ($clipping) {
                    $this->SVGTransform($tm);
                    $this->Ellipse($cx, $cy, $rx, $ry, 0, 0, 360, 'CNZ', [], [], 8);
                }
                else {
                    $this->StartTransform();
                    $this->SVGTransform($tm);
                    $obstyle = $this->setSVGStyles($svgstyle, $prev_svgstyle, $x, $y, $w, $h, 'Ellipse', [$cx, $cy, $rx, $ry, 0, 0, 360, 'CNZ']);
                    if (!empty($obstyle)) {
                        $this->Ellipse($cx, $cy, $rx, $ry, 0, 0, 360, $obstyle, [], [], 8);
                    }
                    $this->StopTransform();
                }
                break;
            }
            case 'line':
            {
                if ($invisible) {
                    break;
                }
                $x1 = (isset($attribs['x1']) ? $this->fontSizeToUnits($attribs['x1'], 0) : 0);
                $y1 = (isset($attribs['y1']) ? $this->fontSizeToUnits($attribs['y1'], 0) : 0);
                $x2 = (isset($attribs['x2']) ? $this->fontSizeToUnits($attribs['x2'], 0) : 0);
                $y2 = (isset($attribs['y2']) ? $this->fontSizeToUnits($attribs['y2'], 0) : 0);
                $x  = $x1;
                $y  = $y1;
                $w  = abs($x2 - $x1);
                $h  = abs($y2 - $y1);
                if (!$clipping) {
                    $this->StartTransform();
                    $this->SVGTransform($tm);
                    $obstyle = $this->setSVGStyles($svgstyle, $prev_svgstyle, $x, $y, $w, $h, 'Line', [$x1, $y1, $x2, $y2]);
                    $this->Line($x1, $y1, $x2, $y2);
                    $this->StopTransform();
                }
                break;
            }
            case 'polyline':
            case 'polygon':
            {
                if ($invisible) {
                    break;
                }
                $points = (isset($attribs['points']) ? $attribs['points'] : '0 0');
                $points = trim($points);
                // note that point may use a complex syntax not covered here
                $points = preg_split('/[\,\s]+/si', $points);
                if (count($points) < 4) {
                    break;
                }
                $p    = [];
                $xmin = 2147483647;
                $xmax = 0;
                $ymin = 2147483647;
                $ymax = 0;
                foreach ($points as $key => $val) {
                    $p[$key] = $this->fontSizeToUnits($val, 0);
                    if (($key % 2) == 0) {
                        // X coordinate
                        $xmin = min($xmin, $p[$key]);
                        $xmax = max($xmax, $p[$key]);
                    }
                    else {
                        // Y coordinate
                        $ymin = min($ymin, $p[$key]);
                        $ymax = max($ymax, $p[$key]);
                    }
                }
                $x = $xmin;
                $y = $ymin;
                $w = ($xmax - $xmin);
                $h = ($ymax - $ymin);
                if ($name == 'polyline') {
                    $this->StartTransform();
                    $this->SVGTransform($tm);
                    $obstyle = $this->setSVGStyles($svgstyle, $prev_svgstyle, $x, $y, $w, $h, 'PolyLine', [$p, 'CNZ']);
                    if (!empty($obstyle)) {
                        $this->PolyLine($p, $obstyle, [], []);
                    }
                    $this->StopTransform();
                }
                else { // polygon
                    if ($clipping) {
                        $this->SVGTransform($tm);
                        $this->Polygon($p, 'CNZ', [], [], true);
                    }
                    else {
                        $this->StartTransform();
                        $this->SVGTransform($tm);
                        $obstyle = $this->setSVGStyles($svgstyle, $prev_svgstyle, $x, $y, $w, $h, 'Polygon', [$p, 'CNZ']);
                        if (!empty($obstyle)) {
                            $this->Polygon($p, $obstyle, [], [], true);
                        }
                        $this->StopTransform();
                    }
                }
                break;
            }
            // image
            case 'image':
            {
                if ($invisible) {
                    break;
                }
                if (!isset($attribs['xlink:href']) or empty($attribs['xlink:href'])) {
                    break;
                }
                $x   = (isset($attribs['x']) ? $this->fontSizeToUnits($attribs['x'], 0) : 0);
                $y   = (isset($attribs['y']) ? $this->fontSizeToUnits($attribs['y'], 0) : 0);
                $w   = (isset($attribs['width']) ? $this->fontSizeToUnits($attribs['width'], 0) : 0);
                $h   = (isset($attribs['height']) ? $this->fontSizeToUnits($attribs['height'], 0) : 0);
                $img = $attribs['xlink:href'];
                if (!$clipping) {
                    $this->StartTransform();
                    $this->SVGTransform($tm);
                    $obstyle = $this->setSVGStyles($svgstyle, $prev_svgstyle, $x, $y, $w, $h);
                    if (preg_match('/^data:image\/[^;]+;base64,/', $img, $m) > 0) {
                        // embedded image encoded as base64
                        $img = '@' . base64_decode(substr($img, strlen($m[0])));
                    }
                    else {
                        // fix image path
                        if (!StaticMethods::empty_string($this->svgdir) and (($img[0] == '.') or (basename($img) == $img))) {
                            // replace relative path with full server path
                            $img = $this->svgdir . '/' . $img;
                        }
                        if (($img[0] == '/') and !empty($_SERVER['DOCUMENT_ROOT']) and ($_SERVER['DOCUMENT_ROOT'] != '/')) {
                            $findroot = strpos($img, $_SERVER['DOCUMENT_ROOT']);
                            if (($findroot === false) or ($findroot > 1)) {
                                if (substr($_SERVER['DOCUMENT_ROOT'], -1) == '/') {
                                    $img = substr($_SERVER['DOCUMENT_ROOT'], 0, -1) . $img;
                                }
                                else {
                                    $img = $_SERVER['DOCUMENT_ROOT'] . $img;
                                }
                            }
                        }
                        $img         = urldecode($img);
                        $testscrtype = @parse_url($img);
                        if (empty($testscrtype['query'])) {
                            // convert URL to server path
                            $img = str_replace(K_PATH_URL, K_PATH_MAIN, $img);
                        }
                        elseif (preg_match('|^https?://|', $img) !== 1) {
                            // convert server path to URL
                            $img = str_replace(K_PATH_MAIN, K_PATH_URL, $img);
                        }
                    }
                    // get image type
                    $imgtype = $this->imageType($img);
                    if (($imgtype == 'eps') or ($imgtype == 'ai')) {
                        $this->ImageEps($img, $x, $y, $w, $h);
                    }
                    elseif ($imgtype == 'svg') {
                        // store SVG vars
                        $svggradients  = $this->svggradients;
                        $svggradientid = $this->svggradientid;
                        $svgdefsmode   = $this->svgdefsmode;
                        $svgdefs       = $this->svgdefs;
                        $svgclipmode   = $this->svgclipmode;
                        $svgclippaths  = $this->svgclippaths;
                        $svgcliptm     = $this->svgcliptm;
                        $svgclipid     = $this->svgclipid;
                        $svgtext       = $this->svgtext;
                        $svgtextmode   = $this->svgtextmode;
                        $this->ImageSVG($img, $x, $y, $w, $h);
                        // restore SVG vars
                        $this->svggradients  = $svggradients;
                        $this->svggradientid = $svggradientid;
                        $this->svgdefsmode   = $svgdefsmode;
                        $this->svgdefs       = $svgdefs;
                        $this->svgclipmode   = $svgclipmode;
                        $this->svgclippaths  = $svgclippaths;
                        $this->svgcliptm     = $svgcliptm;
                        $this->svgclipid     = $svgclipid;
                        $this->svgtext       = $svgtext;
                        $this->svgtextmode   = $svgtextmode;
                    }
                    else {
                        $this->Image($img, $x, $y, $w, $h);
                    }
                    $this->StopTransform();
                }
                break;
            }
            // text
            case 'text':
            case 'tspan':
            {
                if (isset($this->svgtextmode['text-anchor']) and !empty($this->svgtext)) {
                    // @TODO: unsupported feature
                }
                // only basic support - advanced features must be implemented
                $this->svgtextmode['invisible'] = $invisible;
                if ($invisible) {
                    break;
                }
                array_push($this->svgstyles, $svgstyle);
                if (isset($attribs['x'])) {
                    $x = $this->fontSizeToUnits($attribs['x'], 0);
                }
                elseif ($name == 'tspan') {
                    $x = $this->x;
                }
                else {
                    $x = 0;
                }
                if (isset($attribs['dx'])) {
                    $x += $this->fontSizeToUnits($attribs['dx'], 0);
                }
                if (isset($attribs['y'])) {
                    $y = $this->fontSizeToUnits($attribs['y'], 0);
                }
                elseif ($name == 'tspan') {
                    $y = $this->y;
                }
                else {
                    $y = 0;
                }
                if (isset($attribs['dy'])) {
                    $y += $this->fontSizeToUnits($attribs['dy'], 0);
                }
                $svgstyle['text-color'] = $svgstyle['fill'];
                $this->svgtext          = '';
                if (isset($svgstyle['text-anchor'])) {
                    $this->svgtextmode['text-anchor'] = $svgstyle['text-anchor'];
                }
                else {
                    $this->svgtextmode['text-anchor'] = 'start';
                }
                if (isset($svgstyle['direction'])) {
                    if ($svgstyle['direction'] == 'rtl') {
                        $this->svgtextmode['rtl'] = true;
                    }
                    else {
                        $this->svgtextmode['rtl'] = false;
                    }
                }
                else {
                    $this->svgtextmode['rtl'] = false;
                }
                if (isset($svgstyle['stroke']) and ($svgstyle['stroke'] != 'none') and isset($svgstyle['stroke-width']) and ($svgstyle['stroke-width'] > 0)) {
                    $this->svgtextmode['stroke'] = $this->fontSizeToUnits($svgstyle['stroke-width'], 0);
                }
                else {
                    $this->svgtextmode['stroke'] = false;
                }
                $this->StartTransform();
                $this->SVGTransform($tm);
                $obstyle = $this->setSVGStyles($svgstyle, $prev_svgstyle, $x, $y, 1, 1);
                $this->x = $x;
                $this->y = $y;
                break;
            }
            // use
            case 'use':
            {
                if (isset($attribs['xlink:href']) and !empty($attribs['xlink:href'])) {
                    $svgdefid = substr($attribs['xlink:href'], 1);
                    if (isset($this->svgdefs[$svgdefid])) {
                        $use = $this->svgdefs[$svgdefid];
                        if (isset($attribs['xlink:href'])) {
                            unset($attribs['xlink:href']);
                        }
                        if (isset($attribs['id'])) {
                            unset($attribs['id']);
                        }
                        if (isset($use['attribs']['x']) and isset($attribs['x'])) {
                            $attribs['x'] += $use['attribs']['x'];
                        }
                        if (isset($use['attribs']['y']) and isset($attribs['y'])) {
                            $attribs['y'] += $use['attribs']['y'];
                        }
                        if (empty($attribs['style'])) {
                            $attribs['style'] = '';
                        }
                        if (!empty($use['attribs']['style'])) {
                            // merge styles
                            $attribs['style'] = str_replace(';;', ';', ';' . $use['attribs']['style'] . $attribs['style']);
                        }
                        $attribs = array_merge($use['attribs'], $attribs);
                        $this->startSVGElementHandler($parser, $use['name'], $attribs);
                        return;
                    }
                }
                break;
            }
            default:
            {
                break;
            }
        } // end of switch
        // process child elements
        if (!empty($attribs['child_elements'])) {
            $child_elements = $attribs['child_elements'];
            unset($attribs['child_elements']);
            foreach ($child_elements as $child_element) {
                if (empty($child_element['attribs']['closing_tag'])) {
                    $this->startSVGElementHandler('child-tag', $child_element['name'], $child_element['attribs']);
                }
                else {
                    if (isset($child_element['attribs']['content'])) {
                        $this->svgtext = $child_element['attribs']['content'];
                    }
                    $this->endSVGElementHandler('child-tag', $child_element['name']);
                }
            }
        }
    }

    /**
     * Sets the closing SVG element handler function for the XML parser.
     * @param resource|string $parser The first parameter, parser, is a reference to the XML parser calling the handler.
     * @param string          $name   The second parameter, name, contains the name of the element for which this handler is called. If case-folding is in effect for this parser, the element name will be in uppercase letters.
     * @author Nicola Asuni
     */
    protected function endSVGElementHandler($parser, $name)
    {
        $name = $this->removeTagNamespace($name);
        if ($this->svgdefsmode and !in_array($name, ['defs', 'clipPath', 'linearGradient', 'radialGradient', 'stop'])) {
            ;
            if (end($this->svgdefs) !== FALSE) {
                $last_svgdefs_id = key($this->svgdefs);
                if (isset($this->svgdefs[$last_svgdefs_id]['attribs']['child_elements'])) {
                    foreach ($this->svgdefs[$last_svgdefs_id]['attribs']['child_elements'] as $child_element) {
                        if (isset($child_element['attribs']['id']) and ($child_element['name'] == $name)) {
                            $this->svgdefs[$last_svgdefs_id]['attribs']['child_elements'][$child_element['attribs']['id'] . '_CLOSE'] = ['name' => $name, 'attribs' => ['closing_tag' => TRUE, 'content' => $this->svgtext]];
                            return;
                        }
                    }
                    if ($this->svgdefs[$last_svgdefs_id]['name'] == $name) {
                        $this->svgdefs[$last_svgdefs_id]['attribs']['child_elements'][$last_svgdefs_id . '_CLOSE'] = ['name' => $name, 'attribs' => ['closing_tag' => TRUE, 'content' => $this->svgtext]];
                        return;
                    }
                }
            }
            return;
        }
        switch ($name) {
            case 'defs':
            {
                $this->svgdefsmode = false;
                break;
            }
            // clipPath
            case 'clipPath':
            {
                $this->svgclipmode = false;
                break;
            }
            case 'svg':
            {
                if (--$this->svg_tag_depth <= 0) {
                    break;
                }
            }
            case 'g':
            {
                // ungroup: remove last style from array
                array_pop($this->svgstyles);
                $this->StopTransform();
                break;
            }
            case 'text':
            case 'tspan':
            {
                if ($this->svgtextmode['invisible']) {
                    // This implementation must be fixed to following the rule:
                    // If the 'visibility' property is set to hidden on a 'tspan', 'tref' or 'altGlyph' element, then the text is invisible but still takes up space in text layout calculations.
                    break;
                }
                // print text
                $text = $this->svgtext;
                //$text = $this->stringTrim($text);
                $textlen = $this->GetStringWidth($text);
                if ($this->svgtextmode['text-anchor'] != 'start') {
                    // check if string is RTL text
                    if ($this->svgtextmode['text-anchor'] == 'end') {
                        if ($this->svgtextmode['rtl']) {
                            $this->x += $textlen;
                        }
                        else {
                            $this->x -= $textlen;
                        }
                    }
                    elseif ($this->svgtextmode['text-anchor'] == 'middle') {
                        if ($this->svgtextmode['rtl']) {
                            $this->x += ($textlen / 2);
                        }
                        else {
                            $this->x -= ($textlen / 2);
                        }
                    }
                }
                $textrendermode  = $this->textrendermode;
                $textstrokewidth = $this->textstrokewidth;
                $this->setTextRenderingMode($this->svgtextmode['stroke'], true, false);
                if ($name == 'text') {
                    // store current coordinates
                    $tmpx = $this->x;
                    $tmpy = $this->y;
                }
                // print the text
                $this->Cell($textlen, 0, $text, 0, 0, '', false, '', 0, false, 'L', 'T');
                if ($name == 'text') {
                    // restore coordinates
                    $this->x = $tmpx;
                    $this->y = $tmpy;
                }
                // restore previous rendering mode
                $this->textrendermode  = $textrendermode;
                $this->textstrokewidth = $textstrokewidth;
                $this->svgtext         = '';
                $this->StopTransform();
                if (!$this->svgdefsmode) {
                    array_pop($this->svgstyles);
                }
                break;
            }
            default:
            {
                break;
            }
        }
    }

    /**
     * Sets the character data handler function for the XML parser.
     * @param resource $parser The first parameter, parser, is a reference to the XML parser calling the handler.
     * @param string   $data   The second parameter, data, contains the character data as a string.
     * @author Nicola Asuni
     */
    protected function segSVGContentHandler($parser, $data)
    {
        $this->svgtext .= $data;
    }

    /**
     * Return the tag name without the namespace
     * @param string $name Tag name
     */
    protected function removeTagNamespace($name)
    {
        if (strpos($name, ':') !== false) {
            $parts = explode(':', $name);
            return $parts[(sizeof($parts) - 1)];
        }
        return $name;
    }
}