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

trait Barcoded
{
    /**
     * Array representation of barcode.
     */
    protected array $barcode = [];

    /**
     * Return an array representations of barcode.
     * @return array
     */
    public function barcode(): array
    {
        return $this->barcode;
    }

    /**
     * Send barcode as SVG image object to the standard output.
     * @param int    $width  Width of a single rectangle element in user units.
     * @param int    $height Height of a single rectangle element in user units.
     * @param string $color  Foreground color (in SVG format) for bar elements (background is transparent).
     * @return void
     */
    public function SVG(int $width = 3, int $height = 3, string $color = 'black'): void
    {
        // send headers
        $code = $this->SVGCode($width, $height, $color);
        header('Content-Type: application/svg+xml');
        header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Content-Disposition: inline; filename="' . md5($code) . '.svg";');
        echo $code;
    }

    /**
     * Creates a standard header for svg barcodes.
     *
     * @param int   $width   width of a single rectangle element in user units.
     * @param int   $height  height of a single rectangle element in user units.
     * @param array $replace an array of special characters to replace.
     * @return string
     */
    private function SVGHeader(int $width, int $height, array $replace): string
    {
        $svg = '<' . '?' . 'xml version="1.0" standalone="no"' . '?' . '>' . "\n";
        $svg .= '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' . "\n";
        $svg .= '<svg width="' . $width . '" height="' . $height . '" version="1.1" xmlns="http://www.w3.org/2000/svg">' . "\n";
        return $svg . "\t" . '<desc>' . strtr($this->barcode['code'], $replace) . '</desc>' . "\n";
    }
}