<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 * @see         TCPDF (http://www.tcpdf.org) => Nicola Asuni
 * @see         FPDF (http://www.fpdf.org) => Olivier Plathey
 */

namespace THM\Organizer\Views\PDF\Library\Barcodes;

/**
 * Creates array representations for 2D barcodes.
 *    $code[
 *        'code'            => code to be printed on text label
 *        'rowCount'        => required number of rows
 *        'columnCount'     => required number of columns
 *        'barcode'[$row][$column] => value of the cell is $row and $column (0 = transparent, 1 = black)
 *   ]
 */
class Barcode2D
{
    use Barcoded;

    /**
     * Creates a 2D barcode.
     * @param string $code           code to print
     * @param string $type           type of barcode:
     *                               DATAMATRIX => Datamatrix (ISO/IEC 16022)
     *                               PDF417 => PDF417 (ISO/IEC 15438:2006)
     *                               ~PDF417,a,e,t,s,f,o0,o1,o2,o3,o4,o5,o6
     *                               t, s and f are required for a macro control block, all other parameters are optional
     *                               To use a comma character ',' on text options, replace it with the character 255: "\xff".
     *                               a => aspect ratio (width/height)
     *                               e => error correction level (0-8)
     *                               t => total number of macro segments
     *                               s => macro segment index (0-99998)
     *                               f => file ID; o0 = File Name (text)
     *                               o1 => Segment Count (numeric)
     *                               o2 => Time Stamp (numeric)
     *                               o3 = Sender (text)
     *                               o4 => Addressee (text)
     *                               o5 = File Size (numeric)
     *                               o6 => Checksum (numeric)
     *                               QRCODE/QRCODE,L => Low error correction
     *                               QRCODE,M => Medium error correction
     *                               QRCODE,Q => Better error correction
     *                               QRCODE,H => Best error correction
     *                               RAW => raw mode - comma-separated list of array rows
     *                               RAW2 => raw mode - array rows are surrounded by square parenthesis
     */
    public function __construct(string $code, string $type)
    {
        $mode = explode(',', $type);
        $type = strtoupper($mode[0]);

        switch ($type) {
            case 'DATAMATRIX':
                $qrcode                = new DataMatrix($code);
                $this->barcode         = $qrcode->barcode();
                $this->barcode['code'] = $code;
                break;
            case 'PDF417':
                $aspectRatio = empty($mode[1]) ? 2 : floatval($mode[1]);
                $ecl         = empty($mode[2]) ? -1 : intval($mode[2]);

                $macro = [];
                if (!empty($mode[3]) and !empty($mode[4]) and !empty($mode[5])) {
                    $macro['segment_total'] = intval($mode[3]);
                    $macro['segment_index'] = intval($mode[4]);
                    $macro['file_id']       = strtr($mode[5], "\xff", ',');
                    for ($index = 0; $index < 7; ++$index) {
                        $option = $index + 6;
                        if (isset($mode[$option]) and ($mode[$option] !== '')) {
                            $macro['option_' . $index] = strtr($mode[$option], "\xff", ',');
                        }
                    }
                }
                $qrcode                = new PDF417($code, $ecl, $aspectRatio, $macro);
                $this->barcode         = $qrcode->barcode();
                $this->barcode['code'] = $code;
                break;
            case 'QRCODE':
                if (!isset($mode[1]) or (!in_array($mode[1], ['L', 'M', 'Q', 'H']))) {
                    $mode[1] = 'L'; // default: Low error correction
                }
                $qrcode                = new QRCode($code, strtoupper($mode[1]));
                $this->barcode         = $qrcode->barcode();
                $this->barcode['code'] = $code;
                break;
            case 'RAW':
            case 'RAW2':
                $code = preg_replace('/\s*/i', '', $code);
                if (strlen($code) < 3) {
                    $this->barcode = [];
                    return;
                }
                if ($type == 'RAW') {
                    // comma-separated rows
                    $rows = explode(',', $code);
                }
                else { // RAW2
                    // rows enclosed in square parentheses
                    $code = substr($code, 1, -1);
                    $rows = explode('][', $code);
                }
                $this->barcode['rowCount']    = count($rows);
                $this->barcode['columnCount'] = strlen($rows[0]);
                $this->barcode['barcode']     = [];
                foreach ($rows as $r) {
                    $this->barcode['barcode'][] = str_split($r);
                }
                $this->barcode['code'] = $code;
                break;
            default:
                $this->barcode = [];
        }
    }

    /**
     * Return an SVG string representation of barcode.
     * @param int    $width  Width of a single rectangle element in user units.
     * @param int    $height Height of a single rectangle element in user units.
     * @param string $color  Foreground color (in SVG format) for bar elements (background is transparent).
     * @return string SVG code.
     */
    public function SVGCode(int $width = 3, int $height = 3, string $color = 'black'): string
    {
        // replace table for special characters
        $replace   = ["\0" => '', '&' => '&amp;', '<' => '&lt;', '>' => '&gt;'];
        $svgHeight = round(($this->barcode['rowCount'] * $height), 3);
        $svgWidth  = round(($this->barcode['columnCount'] * $width), 3);

        $svg = $this->SVGHeader($svgWidth, $svgHeight, $replace);
        $svg .= "\t" . '<g id="elements" fill="' . $color . '" stroke="none">' . "\n";
        // print barcode elements
        $y = 0;
        for ($row = 0; $row < $this->barcode['rowCount']; ++$row) {
            $x = 0;
            for ($column = 0; $column < $this->barcode['columnCount']; ++$column) {
                if ($this->barcode['barcode'][$row][$column] == 1) {
                    // draw a single barcode cell
                    $svg .= "\t\t" . '<rect x="' . $x . '" y="' . $y . '" width="' . $width . '" height="' . $height . '" />' . "\n";
                }
                $x += $width;
            }
            $y += $height;
        }
        $svg .= "\t" . '</g>' . "\n";
        $svg .= '</svg>' . "\n";
        return $svg;
    }

    /**
     * Return an HTML representation of barcode.
     * @param int    $width  Width of a single rectangle element in pixels.
     * @param int    $height Height of a single rectangle element in pixels.
     * @param string $color  Foreground color for bar elements (background is transparent).
     * @return string HTML code.
     */
    public function HTML(int $width = 10, int $height = 10, string $color = 'black'): string
    {
        $html = '<div style="font-size:0;position:relative;width:' . ($width * $this->barcode['columnCount']) . 'px;height:' . ($height * $this->barcode['rowCount']) . 'px;">' . "\n";
        // print barcode elements
        $y = 0;
        for ($row = 0; $row < $this->barcode['rowCount']; ++$row) {
            $x = 0;
            // for each column
            for ($column = 0; $column < $this->barcode['columnCount']; ++$column) {
                if ($this->barcode['barcode'][$row][$column] == 1) {
                    // draw a single barcode cell
                    $html .= '<div style="background-color:' . $color . ';width:' . $width . 'px;height:' . $height . 'px;position:absolute;left:' . $x . 'px;top:' . $y . 'px;">&nbsp;</div>' . "\n";
                }
                $x += $width;
            }
            $y += $height;
        }
        $html .= '</div>' . "\n";
        return $html;
    }

    /**
     * Send a PNG image representation of barcode.
     * @param int   $width  Width of a single rectangle element in pixels.
     * @param int   $height Height of a single rectangle element in pixels.
     * @param array $color  RGB (0-255) foreground color for bar elements (background is transparent).
     */
    public function PNG(int $width = 3, int $height = 3, array $color = [0, 0, 0]): void
    {
        $data = $this->PNGData($width, $height, $color);
        // send headers
        header('Content-Type: image/png');
        header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        //header('Content-Length: '.strlen($data));
        echo $data;
    }

    /**
     * Return a PNG image representation of barcode.
     * @param int   $width  Width of a single rectangle element in pixels.
     * @param int   $height Height of a single rectangle element in pixels.
     * @param array $color  RGB (0-255) foreground color for bar elements (background is transparent).
     * @return string|false image or false in case of error.
     */
    public function PNGData(int $width = 3, int $height = 3, array $color = [0, 0, 0]): string|false
    {
        $png     = imagecreate($this->barcode['columnCount'] * $width, $this->barcode['rowCount'] * $height);
        $bgColor = imagecolorallocate($png, 255, 255, 255);
        imagecolortransparent($png, $bgColor);
        $fgColor = imagecolorallocate($png, $color[0], $color[1], $color[2]);
        // print barcode elements
        $y = 0;
        // for each row
        for ($row = 0; $row < $this->barcode['rowCount']; ++$row) {
            $x = 0;
            // for each column
            for ($column = 0; $column < $this->barcode['columnCount']; ++$column) {
                if ($this->barcode['barcode'][$row][$column] == 1) {
                    imagefilledrectangle($png, $x, $y, ($x + $width - 1), ($y + $height - 1), $fgColor);
                }
                $x += $width;
            }
            $y += $height;
        }
        ob_start();
        imagepng($png);
        $imagedata = ob_get_clean();
        imagedestroy($png);
        return $imagedata;
    }
}