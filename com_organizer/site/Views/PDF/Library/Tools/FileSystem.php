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

namespace THM\Organizer\Views\PDF\Library\Tools;

trait FileSystem
{
    /**
     * Wrapper to use fopen with local files
     * @param string $fileName Name of the file to open
     * @param string $mode
     * @return resource|false
     */
    public static function openLocal(string $fileName, string $mode)
    {
        if (!str_contains($fileName, '://')) {
            $fileName = 'file://' . $fileName;
        }

        if (!stream_is_local($fileName)) {
            return false;
        }

        return fopen($fileName, $mode);
    }

    /**
     * Reads entire file into a string.
     * @param string $file
     * @return string|false
     */
    public function readContents(string $file): string|false
    {
        if (!@file_exists($file) or !$content = @file_get_contents($file)) {
            return false;
        }

        return $content;
    }

    /**
     * Read a 32-bit integer from a resource
     * @param resource $resource .
     * @return int
     */
    public function readLong($resource): int
    {
        $a = unpack('Ni', fread($resource, 4));
        return $a['i'];
    }

    /**
     * Binary-safe and URL-safe file read. Reads up to length bytes from the file pointer referenced by handle. Reading stops as soon as one of the
     * following conditions is met: length bytes have been read; EOF (end of file) is reached.
     * @param resource $resource
     * @param int      $length
     * @return string|false
     */
    public function readFile($resource, int $length): string|false
    {
        $data = fread($resource, $length);
        if ($data === false) {
            return false;
        }

        $rest = ($length - strlen($data));

        if ($rest > 0 and !feof($resource)) {
            $data .= $this->readFile($resource, $rest);
        }

        return $data;
    }
}