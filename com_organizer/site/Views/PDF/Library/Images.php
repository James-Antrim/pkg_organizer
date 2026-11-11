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
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////DONE


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
     * Extract structure containing the image data from a PNG file. PNG-Alpha returns 'alpha'. Errors return false.
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

        $bpc = ord(fread($file, 1));
        $ct  = ord(fread($file, 1));

        if (array_key_exists($ct, self::COLOR_SCHEMES)) {
            $scheme = self::COLOR_SCHEMES[$ct];
        }
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

        $channels   = $ct === self::RGB ? 3 : 1;
        $parameters = '/DecodeParms << /Predictor 15 /Colors ' . $channels . ' /BitsPerComponent ' . $bpc . ' /Columns ' . $width . ' >>';

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
                    switch ($ct) {
                        case self::CMYK:
                            if ($integer > 0) {
                                $transparency = [];
                                for ($i = 0; $i < $integer; ++$i) {
                                    $transparency[] = ord($t[$i]);
                                }
                            }
                            break;
                        case self::GRAYSCALE:
                            $transparency = array(ord($t[1]));
                            break;
                        case self::RGB:
                            $transparency = array(ord($t[1]), ord($t[3]), ord($t[5]));
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

        if ($scheme === self::COLOR_SCHEMES[self::CMYK] and empty($palette)) {
            // Missing palette
            return false;
        }

        return [
            'bpc'   => $bpc,
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

        // Get PNG image data?
        $return = $this->parsePNG($temp);

        // tidy up by removing temporary image
        unlink($temp);

        return $return;
    }




/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Set the transparency for the given GD image.
     * @param resource $new_image GD image object
     * @param resource $image     GD image object.
     * @return resource GD image object $new_image
     */
    public static function setGDImageTransparency($new_image, $image)
    {
        // default transparency color (white)
        $tcol = array('red' => 255, 'green' => 255, 'blue' => 255);
        // transparency index
        $tid        = imagecolortransparent($image);
        $palletsize = imagecolorstotal($image);
        if (($tid >= 0) and ($tid < $palletsize)) {
            // get the colors for the transparency index
            $tcol = imagecolorsforindex($image, $tid);
        }
        $tid = imagecolorallocate($new_image, $tcol['red'], $tcol['green'], $tcol['blue']);
        imagefill($new_image, 0, 0, $tid);
        imagecolortransparent($new_image, $tid);
        return $new_image;
    }

    /**
     * Convert the loaded image to a JPEG and then return a structure for the PDF creator.
     * This function requires GD library and write access to the directory defined on K_PATH_CACHE constant.
     * @param resource $image    Image object.
     * @param int      $quality  JPEG quality.
     * @param string   $tempfile Temporary file name.
     *                           return array|false image JPEG image object.
     */
    public static function _toJPEG($image, $quality, $tempfile)
    {
        imagejpeg($image, $tempfile, $quality);
        imagedestroy($image);
        $retvars = self::_parsejpeg($tempfile);
        // tidy up by removing temporary image
        unlink($tempfile);
        return $retvars;
    }

    /**
     * Extract info from a JPEG file without using the GD library.
     * @param string $file image file to parse
     * @return array|false structure containing the image data
     */
    public static function _parsejpeg($file)
    {
        // check if is a local file
        if (!@StaticMethods::file_exists($file)) {
            return false;
        }
        $a = getimagesize($file);
        if (empty($a)) {
            //Missing or incorrect image file
            return false;
        }
        if ($a[2] != 2) {
            // Not a JPEG file
            return false;
        }
        // bits per pixel
        $bpc = isset($a['bits']) ? intval($a['bits']) : 8;
        // number of image channels
        if (!isset($a['channels'])) {
            $channels = 3;
        }
        else {
            $channels = intval($a['channels']);
        }
        // default colour space
        switch ($channels) {
            case 1:
            {
                $colspace = 'DeviceGray';
                break;
            }
            case 3:
            {
                $colspace = 'DeviceRGB';
                break;
            }
            case 4:
            {
                $colspace = 'DeviceCMYK';
                break;
            }
            default:
            {
                $channels = 3;
                $colspace = 'DeviceRGB';
                break;
            }
        }
        // get file content
        $data = file_get_contents($file);
        // check for embedded ICC profile
        $icc    = [];
        $offset = 0;
        while (($pos = strpos($data, "ICC_PROFILE\0", $offset)) !== false) {
            // get ICC sequence length
            $length = (StaticMethods::_getUSHORT($data, ($pos - 2)) - 16);
            // marker sequence number
            $msn = max(1, ord($data[($pos + 12)]));
            // number of markers (total of APP2 used)
            $nom = max(1, ord($data[($pos + 13)]));
            // get sequence segment
            $icc[($msn - 1)] = substr($data, ($pos + 14), $length);
            // move forward to next sequence
            $offset = ($pos + 14 + $length);
        }
        // order and compact ICC segments
        if (count($icc) > 0) {
            ksort($icc);
            $icc = implode('', $icc);
            if ((ord($icc[36]) != 0x61) or (ord($icc[37]) != 0x63) or (ord($icc[38]) != 0x73) or (ord($icc[39]) != 0x70)) {
                // invalid ICC profile
                $icc = false;
            }
        }
        else {
            $icc = false;
        }
        return array('w' => $a[0], 'h' => $a[1], 'ch' => $channels, 'icc' => $icc, 'cs' => $colspace, 'bpc' => $bpc, 'f' => 'DCTDecode', 'data' => $data);
    }
}