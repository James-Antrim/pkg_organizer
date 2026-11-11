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

/** Functions for repetitious handling binary information. */
trait BinaryTools
{

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////DONE

    /**
     * Get unsigned long (big endian 32-bit unsigned integer) from string.
     * @param string $string string from where to extract value
     * @param int    $offset point from where to read the data
     * @return int
     */
    public function unsignedLong(string $string, int $offset): int
    {
        $v = unpack('Ni', substr($string, $offset, 4));
        return $v['i'];
    }


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Get BYTE from string (8-bit unsigned integer).
     * @param string $str    String from where to extract value.
     * @param int    $offset Point from where to read the data.
     * @return int 8 bit value
     * @author Nicola Asuni
     */
    public static function _getBYTE($str, $offset)
    {
        $v = unpack('Ci', substr($str, $offset, 1));
        return $v['i'];
    }

    /**
     * Read a 4-byte (32 bit) integer from file.
     * @param resource $f file resource.
     * @return int 4-byte integer
     */
    public static function _freadint($f)
    {
        $a = unpack('Ni', fread($f, 4));
        return $a['i'];
    }

    /**
     * Get USHORT from string (Big Endian 16-bit unsigned integer).
     * @param string $str    string from where to extract value
     * @param int    $offset point from where to read the data
     * @return int 16 bit value
     * @author Nicola Asuni
     */
    public static function _getUSHORT($str, $offset)
    {
        $v = unpack('ni', substr($str, $offset, 2));
        return $v['i'];
    }

    /**
     * Get SHORT from string (Big Endian 16-bit signed integer).
     * @param string $str    String from where to extract value.
     * @param int    $offset Point from where to read the data.
     * @return int 16 bit value
     * @author Nicola Asuni
     */
    public static function _getSHORT($str, $offset)
    {
        $v = unpack('si', substr($str, $offset, 2));
        return $v['i'];
    }

    /**
     * Get FWORD from string (Big Endian 16-bit signed integer).
     * @param string $str    String from where to extract value.
     * @param int    $offset Point from where to read the data.
     * @return int 16 bit value
     * @author Nicola Asuni
     */
    public static function _getFWORD($str, $offset)
    {
        $v = self::_getUSHORT($str, $offset);
        if ($v > 0x7fff) {
            $v -= 0x10000;
        }
        return $v;
    }

    /**
     * Get UFWORD from string (Big Endian 16-bit unsigned integer).
     * @param string $str    string from where to extract value
     * @param int    $offset point from where to read the data
     * @return int 16 bit value
     * @author Nicola Asuni
     */
    public static function _getUFWORD($str, $offset)
    {
        $v = self::_getUSHORT($str, $offset);
        return $v;
    }

    /**
     * Get FIXED from string (32-bit signed fixed-point number (16.16).
     * @param string $str    string from where to extract value
     * @param int    $offset point from where to read the data
     * @return int 16 bit value
     * @author Nicola Asuni
     */
    public static function _getFIXED($str, $offset)
    {
        // mantissa
        $m = self::_getFWORD($str, $offset);
        // fraction
        $f = self::_getUSHORT($str, ($offset + 2));
        $v = floatval('' . $m . '.' . $f . '');
        return $v;
    }


}