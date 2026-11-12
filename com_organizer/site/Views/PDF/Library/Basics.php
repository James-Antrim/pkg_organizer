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

/** Character and string manipulation methods. */
trait Basics
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


    /** Static cache used for speed up unicodeOrd performances */
    protected static array $utf8Map = [];

    /**
     * Converts UTF-8 character to integer value.
     * @param string $character character string to process.
     * @return int
     */
    public static function unicodeOrd(string $character): int
    {
        if (isset(self::$utf8Map[$character])) {
            return self::$utf8Map[$character];
        }

        if (function_exists('mb_convert_encoding')) {
            list(, $value) = @unpack('N', mb_convert_encoding($character, 'UCS-4BE', 'UTF-8'));
            if (is_int($value) and $value >= 0) {
                self::$utf8Map[$character] = $value;
                return $value;
            }
        }

        // Replacement character
        $byte   = 0xFFFD;
        $bytes  = [];
        $length = strlen($character);

        $countedBytes = 0;
        $neededBytes  = 1;

        for ($outer = 0; $outer < $length; ++$outer) {

            // get one character byte at time
            $byte = ord($character[$outer]);

            // first byte
            if ($countedBytes === 0) {
                // ASCII, only one byte
                if ($byte <= 0x7F) {
                    break;
                }

                // 2 bytes character (0x06 = 110 BIN)
                if (($byte >> 0x05) === 0x06) {
                    $bytes[] = ($byte - 0xC0) << 0x06;
                    ++$countedBytes;
                    $neededBytes = 2;
                }
                // 3 bytes character (0x0E = 1110 BIN)
                elseif (($byte >> 0x04) == 0x0E) {
                    $bytes[] = ($byte - 0xE0) << 0x0C;
                    ++$countedBytes;
                    $neededBytes = 3;
                }
                // 4 bytes character (0x1E = 11110 BIN)
                elseif (($byte >> 0x03) == 0x1E) {
                    $bytes[] = ($byte - 0xF0) << 0x12;
                    ++$countedBytes;
                    $neededBytes = 4;
                }
                // Invalid
                else {
                    $byte = 0xFFFD;
                    break;
                }
            }
            // bytes 2, 3 and 4 must start with 0x02 = 10 BIN
            elseif (($byte >> 0x06) === 0x02) {

                $bytes[] = $byte - 0x80;
                ++$countedBytes;

                if ($countedBytes === $neededBytes) {

                    // compose UTF-8 bytes to a single Unicode value
                    $byte = $bytes[0];
                    for ($inner = 1; $inner < $neededBytes; ++$inner) {
                        $byte += ($bytes[$inner] << (($neededBytes - $inner - 1) * 0x06));
                    }

                    // Invalid or done
                    $byte = (($byte >= 0xD800 and $byte <= 0xDFFF) or $byte >= 0x10FFFF) ? 0xFFFD : $byte;
                    break;
                }
            }
            // Invalid
            else {
                $byte = 0xFFFD;
                break;
            }
        }

        self::$utf8Map[$character] = $byte;
        return $byte;
    }

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Update the CIDToGIDMap string with a new value.
     * @param string $map CIDToGIDMap.
     * @param int    $cid CID value.
     * @param int    $gid GID value.
     * @return string CIDToGIDMap.
     * @author Nicola Asuni
     */
    public static function updateCIDtoGIDmap($map, $cid, $gid)
    {
        if (($cid >= 0) and ($cid <= 0xFFFF) and ($gid >= 0)) {
            if ($gid > 0xFFFF) {
                $gid -= 0x10000;
            }
            $map[($cid * 2)]       = chr($gid >> 8);
            $map[(($cid * 2) + 1)] = chr($gid & 0xFF);
        }
        return $map;
    }

    /**
     * Returns the unicode caracter specified by the value
     * @param int     $c       UTF-8 value
     * @param boolean $unicode True if we are in unicode mode, false otherwise.
     * @return string Returns the specified character.
     */
    public static function unichr($c, $unicode = true)
    {
        $c = intval($c);
        if (!$unicode) {
            return chr($c);
        }
        elseif ($c <= 0x7F) {
            // one byte
            return chr($c);
        }
        elseif ($c <= 0x7FF) {
            // two bytes
            return chr(0xC0 | $c >> 6) . chr(0x80 | $c & 0x3F);
        }
        elseif ($c <= 0xFFFF) {
            // three bytes
            return chr(0xE0 | $c >> 12) . chr(0x80 | $c >> 6 & 0x3F) . chr(0x80 | $c & 0x3F);
        }
        elseif ($c <= 0x10FFFF) {
            // four bytes
            return chr(0xF0 | $c >> 18) . chr(0x80 | $c >> 12 & 0x3F) . chr(0x80 | $c >> 6 & 0x3F) . chr(0x80 | $c & 0x3F);
        }
        else {
            return '';
        }
    }

    /**
     * Returns the unicode caracter specified by UTF-8 value
     * @param int $c UTF-8 value
     * @return string Returns the specified character.
     */
    public static function unichrUnicode($c)
    {
        return self::unichr($c, true);
    }

    /**
     * Returns the unicode caracter specified by ASCII value
     * @param int $c UTF-8 value
     * @return string Returns the specified character.
     */
    public static function unichrASCII($c)
    {
        return self::unichr($c, false);
    }

    /**
     * Converts array of UTF-8 characters to UTF16-BE string.<br>
     * Based on: http://www.faqs.org/rfcs/rfc2781.html
     * <pre>
     *   Encoding UTF-16:
     *
     *   Encoding of a single character from an ISO 10646 character value to
     *    UTF-16 proceeds as follows. Let U be the character number, no greater
     *    than 0x10FFFF.
     *
     *    1) If U < 0x10000, encode U as a 16-bit unsigned integer and
     *       terminate.
     *
     *    2) Let U' = U - 0x10000. Because U is less than or equal to 0x10FFFF,
     *       U' must be less than or equal to 0xFFFFF. That is, U' can be
     *       represented in 20 bits.
     *
     *    3) Initialize two 16-bit unsigned integers, W1 and W2, to 0xD800 and
     *       0xDC00, respectively. These integers each have 10 bits free to
     *       encode the character value, for a total of 20 bits.
     *
     *    4) Assign the 10 high-order bits of the 20-bit U' to the 10 low-order
     *       bits of W1 and the 10 low-order bits of U' to the 10 low-order
     *       bits of W2. Terminate.
     *
     *    Graphically, steps 2 through 4 look like:
     *    U' = yyyyyyyyyyxxxxxxxxxx
     *    W1 = 110110yyyyyyyyyy
     *    W2 = 110111xxxxxxxxxx
     * </pre>
     * @param array   $unicode array containing UTF-8 unicode values
     * @param boolean $setBOM  if true set the Byte Order Mark (BOM = 0xFEFF)
     * @return string
     * @author Nicola Asuni
     */
    public static function arrUTF8ToUTF16BE($unicode, $setBOM = false)
    {
        $outstr = ''; // string to be returned
        if ($setBOM) {
            $outstr .= "\xFE\xFF"; // Byte Order Mark (BOM)
        }
        foreach ($unicode as $char) {
            if ($char == 0x200b) {
                // skip Unicode Character 'ZERO WIDTH SPACE' (DEC:8203, U+200B)
            }
            elseif ($char == 0xFFFD) {
                $outstr .= "\xFF\xFD"; // replacement character
            }
            elseif ($char < 0x10000) {
                $outstr .= chr($char >> 0x08);
                $outstr .= chr($char & 0xFF);
            }
            else {
                $char   -= 0x10000;
                $w1     = 0xD800 | ($char >> 0x0a);
                $w2     = 0xDC00 | ($char & 0x3FF);
                $outstr .= chr($w1 >> 0x08);
                $outstr .= chr($w1 & 0xFF);
                $outstr .= chr($w2 >> 0x08);
                $outstr .= chr($w2 & 0xFF);
            }
        }
        return $outstr;
    }

    /**
     * Convert an array of UTF8 values to array of unicode characters
     * @param array   $ta        The input array of UTF8 values.
     * @param boolean $isunicode True for Unicode mode, false otherwise.
     * @return array Return array of unicode characters
     */
    public static function UTF8ArrayToUniArray($ta, $isunicode = true)
    {
        if ($isunicode) {
            return array_map(get_called_class() . '::unichrUnicode', $ta);
        }
        return array_map(get_called_class() . '::unichrASCII', $ta);
    }

    /**
     * Extract a slice of the $strarr array and return it as string.
     * @param string[] $strarr  The input array of characters.
     * @param int      $start   the starting element of $strarr.
     * @param int      $end     first element that will not be returned.
     * @param boolean  $unicode True if we are in unicode mode, false otherwise.
     * @return string Return part of a string
     */
    public static function UTF8ArrSubString($strarr, $start = '', $end = '', $unicode = true)
    {
        if (strlen($start) == 0) {
            $start = 0;
        }
        if (strlen($end) == 0) {
            $end = count($strarr);
        }
        $string = '';
        for ($i = $start; $i < $end; ++$i) {
            $string .= self::unichr($strarr[$i], $unicode);
        }
        return $string;
    }

    /**
     * Extract a slice of the $uniarr array and return it as string.
     * @param string[] $uniarr The input array of characters.
     * @param int      $start  the starting element of $strarr.
     * @param int      $end    first element that will not be returned.
     * @return string Return part of a string
     */
    public static function UniArrSubString($uniarr, $start = '', $end = '')
    {
        if (strlen($start) == 0) {
            $start = 0;
        }
        if (strlen($end) == 0) {
            $end = count($uniarr);
        }
        $string = '';
        for ($i = $start; $i < $end; ++$i) {
            $string .= $uniarr[$i];
        }
        return $string;
    }

    /**
     * Converts UTF-8 characters array to array of Latin1 characters array<br>
     * @param array $unicode array containing UTF-8 unicode values
     * @return array
     * @author Nicola Asuni
     */
    public static function UTF8ArrToLatin1Arr($unicode)
    {
        $outarr = []; // array to be returned
        foreach ($unicode as $char) {
            if ($char < 256) {
                $outarr[] = $char;
            }
            elseif (array_key_exists($char, self::UTF_LATIN1)) {
                // map from UTF-8
                $outarr[] = self::UTF_LATIN1[$char];
            }
            elseif ($char == 0xFFFD) {
                // skip
            }
            else {
                $outarr[] = 63; // '?' character
            }
        }
        return $outarr;
    }

    /**
     * Converts UTF-8 characters array to Latin1 string<br>
     * @param array $unicode array containing UTF-8 unicode values
     * @return string
     * @author Nicola Asuni
     */
    public static function UTF8ArrToLatin1($unicode)
    {
        $outstr = ''; // string to be returned
        foreach ($unicode as $char) {
            if ($char < 256) {
                $outstr .= chr($char);
            }
            elseif (array_key_exists($char, self::UTF_LATIN1)) {
                // map from UTF-8
                $outstr .= chr(self::UTF_LATIN1[$char]);
            }
            elseif ($char == 0xFFFD) {
                // skip
            }
            else {
                $outstr .= '?';
            }
        }
        return $outstr;
    }

    /**
     * Converts UTF-8 strings to codepoints array.<br>
     * Invalid byte sequences will be replaced with 0xFFFD (replacement character)<br>
     * @param string  $string      string to process.
     * @param boolean $isunicode   True when the documetn is in Unicode mode, false otherwise.
     * @param array   $currentfont Reference to current font array.
     * @return array containing codepoints (UTF-8 characters values)
     * @author Nicola Asuni
     */
    public static function UTF8StringToArray($string, $isunicode, &$currentfont)
    {
        $string = is_null($string) ? '' : $string;
        if ($isunicode) {
            // requires PCRE unicode support turned on
            $chars = StaticMethods::pregSplit('//', 'u', $string, -1, PREG_SPLIT_NO_EMPTY);
            $carr  = array_map(get_called_class() . '::unicodeOrd', $chars);
        }
        else {
            $chars = str_split($string);
            $carr  = array_map('ord', $chars);
        }
        if (is_array($currentfont['subsetCharacters']) && is_array($carr)) {
            $currentfont['subsetCharacters'] += array_fill_keys($carr, true);
        }
        else {
            $currentfont['subsetCharacters'] = array_merge($currentfont['subsetCharacters'], $carr);
        }
        return $carr;
    }

    /**
     * Converts UTF-8 strings to Latin1 when using the standard 14 core fonts.<br>
     * @param string  $str         string to process.
     * @param boolean $isunicode   True when the documetn is in Unicode mode, false otherwise.
     * @param array   $currentfont Reference to current font array.
     * @return string
     */
    public static function UTF8ToLatin1($str, $isunicode, &$currentfont)
    {
        $unicode = self::UTF8StringToArray($str, $isunicode, $currentfont); // array containing UTF-8 unicode values
        return self::UTF8ArrToLatin1($unicode);
    }

    /**
     * Converts UTF-8 strings to UTF16-BE.<br>
     * @param string  $str         string to process.
     * @param boolean $setBOM      if true set the Byte Order Mark (BOM = 0xFEFF)
     * @param boolean $isunicode   True when the documetn is in Unicode mode, false otherwise.
     * @param array   $currentfont Reference to current font array.
     * @return string
     * @author Nicola Asuni
     */
    public static function UTF8ToUTF16BE($str, $setBOM, $isunicode, &$currentfont)
    {
        if (!$isunicode) {
            return $str; // string is not in unicode
        }
        $unicode = self::UTF8StringToArray($str, $isunicode, $currentfont); // array containing UTF-8 unicode values
        return self::arrUTF8ToUTF16BE($unicode, $setBOM);
    }

    /**
     * Reverse the RLT substrings using the Bidirectional Algorithm (http://unicode.org/reports/tr9/).
     * @param string  $str         string to manipulate.
     * @param bool    $setBOM      if true set the Byte Order Mark (BOM = 0xFEFF)
     * @param bool    $forcertl    if true forces RTL text direction
     * @param boolean $isunicode   True if the document is in Unicode mode, false otherwise.
     * @param array   $currentfont Reference to current font array.
     * @return string
     * @author Nicola Asuni
     */
    public static function utf8StrRev($str, $setBOM, $forcertl, $isunicode, &$currentfont)
    {
        return self::utf8StrArrRev(self::UTF8StringToArray($str, $isunicode, $currentfont), $str, $setBOM, $forcertl, $isunicode, $currentfont);
    }

    /**
     * Reverse the RLT substrings array using the Bidirectional Algorithm (http://unicode.org/reports/tr9/).
     * @param array   $utf8Values  array of unicode values.
     * @param string  $string      string to manipulate (or empty value).
     * @param bool    $setBOM      if true set the Byte Order Mark (BOM = 0xFEFF)
     * @param bool    $forceRTL    if true forces RTL text direction
     * @param boolean $isUnicode   True if the document is in Unicode mode, false otherwise.
     * @param array   $currentFont Reference to current font array.
     * @return string
     * @author Nicola Asuni
     */
    public static function utf8StrArrRev(array $utf8Values, string $string, bool $setBOM, bool $forceRTL, bool $isUnicode, array &$currentFont)
    {
        return self::arrUTF8ToUTF16BE(self::utf8Bidirectional($utf8Values, $string, $forceRTL, $isUnicode, $currentFont), $setBOM);
    }

    /**
     * Reverse the RLT substrings using the Bidirectional Algorithm (http://unicode.org/reports/tr9/).
     * @param array   $utf8Values  array of characters composing the string.
     * @param string  $string      string to process
     * @param bool    $forceRTL    if 'R' forces RTL, if 'L' forces LTR
     * @param boolean $isUnicode   True if the document is in Unicode mode, false otherwise.
     * @param array   $currentFont Reference to current font array.
     * @return array of unicode chars
     * @author Nicola Asuni
     */
    public static function utf8Bidirectional(array $utf8Values, string $string, bool $forceRTL, bool $isUnicode, array &$currentFont): array
    {
        // paragraph embedding level
        $pel = 0;
        // max level
        $maxlevel = 0;
        if (StaticMethods::empty_string($string)) {
            // create string from array
            $string = self::UTF8ArrSubString($utf8Values, '', '', $isUnicode);
        }
        // check if string contains RTL text
        if (!($forceRTL)) {
            return $utf8Values;
        }

        // get number of chars
        $numchars = count($utf8Values);

        if ($forceRTL == 'R') {
            $pel = 1;
        }
        elseif ($forceRTL == 'L') {
            $pel = 0;
        }
        else {
            // P2. In each paragraph, find the first character of type L, AL, or R.
            // P3. If a character is found in P2 and it is of type AL or R, then set the paragraph embedding level to one; otherwise, set it to zero.
            for ($i = 0; $i < $numchars; ++$i) {
                $type = self::UNICODE_TYPES[$utf8Values[$i]];
                if ($type == 'L') {
                    $pel = 0;
                    break;
                }
                elseif (($type == 'AL') or ($type == 'R')) {
                    $pel = 1;
                    break;
                }
            }
        }

        // Current Embedding Level
        $cel = $pel;
        // directional override status
        $dos      = 'N';
        $remember = [];
        // start-of-level-run
        $sor = $pel % 2 ? 'R' : 'L';
        $eor = $sor;

        // Array of characters data
        $chardata = array();

        // X1. Begin by setting the current embedding level to the paragraph embedding level. Set the directional override status to neutral. Process each character iteratively, applying rules X2 through X9. Only embedding levels from 0 to 61 are valid in this phase.
        // In the resolution of levels in rules I1 and I2, the maximum embedding level of 62 can be reached.
        for ($i = 0; $i < $numchars; ++$i) {
            if ($utf8Values[$i] == self::RTL_EMBEDDING) {
                // X2. With each RLE, compute the least greater odd embedding level.
                //	a. If this new level would be valid, then this embedding code is valid. Remember (push) the current embedding level and override status. Reset the current level to this new level, and reset the override status to neutral.
                //	b. If the new level would not be valid, then this code is invalid. Do not change the current level or override status.
                $next_level = $cel + ($cel % 2) + 1;
                if ($next_level < 62) {
                    $remember[] = array('num' => self::RTL_EMBEDDING, 'cel' => $cel, 'dos' => $dos);
                    $cel        = $next_level;
                    $dos        = 'N';
                    $sor        = $eor;
                    $eor        = $cel % 2 ? 'R' : 'L';
                }
            }
            elseif ($utf8Values[$i] == self::LTR_EMBEDDING) {
                // X3. With each LRE, compute the least greater even embedding level.
                //	a. If this new level would be valid, then this embedding code is valid. Remember (push) the current embedding level and override status. Reset the current level to this new level, and reset the override status to neutral.
                //	b. If the new level would not be valid, then this code is invalid. Do not change the current level or override status.
                $next_level = $cel + 2 - ($cel % 2);
                if ($next_level < 62) {
                    $remember[] = array('num' => self::LTR_EMBEDDING, 'cel' => $cel, 'dos' => $dos);
                    $cel        = $next_level;
                    $dos        = 'N';
                    $sor        = $eor;
                    $eor        = $cel % 2 ? 'R' : 'L';
                }
            }
            elseif ($utf8Values[$i] == self::RTL_OVERRIDE) {
                // X4. With each RLO, compute the least greater odd embedding level.
                //	a. If this new level would be valid, then this embedding code is valid. Remember (push) the current embedding level and override status. Reset the current level to this new level, and reset the override status to right-to-left.
                //	b. If the new level would not be valid, then this code is invalid. Do not change the current level or override status.
                $next_level = $cel + ($cel % 2) + 1;
                if ($next_level < 62) {
                    $remember[] = array('num' => self::RTL_OVERRIDE, 'cel' => $cel, 'dos' => $dos);
                    $cel        = $next_level;
                    $dos        = 'R';
                    $sor        = $eor;
                    $eor        = $cel % 2 ? 'R' : 'L';
                }
            }
            elseif ($utf8Values[$i] == self::LTR_OVERRIDE) {
                // X5. With each LRO, compute the least greater even embedding level.
                //	a. If this new level would be valid, then this embedding code is valid. Remember (push) the current embedding level and override status. Reset the current level to this new level, and reset the override status to left-to-right.
                //	b. If the new level would not be valid, then this code is invalid. Do not change the current level or override status.
                $next_level = $cel + 2 - ($cel % 2);
                if ($next_level < 62) {
                    $remember[] = array('num' => self::LTR_OVERRIDE, 'cel' => $cel, 'dos' => $dos);
                    $cel        = $next_level;
                    $dos        = 'L';
                    $sor        = $eor;
                    $eor        = $cel % 2 ? 'R' : 'L';
                }
            }
            elseif ($utf8Values[$i] == self::POP_DIRECTIONAL_FORMAT) {
                // X7. With each PDF, determine the matching embedding or override code. If there was a valid matching code, restore (pop) the last remembered (pushed) embedding level and directional override.
                if (count($remember)) {
                    $last = count($remember) - 1;
                    if (($remember[$last]['num'] == self::RTL_EMBEDDING) or
                        ($remember[$last]['num'] == self::LTR_EMBEDDING) or
                        ($remember[$last]['num'] == self::RTL_OVERRIDE) or
                        ($remember[$last]['num'] == self::LTR_OVERRIDE)) {
                        $match = array_pop($remember);
                        $cel   = $match['cel'];
                        $dos   = $match['dos'];
                        $sor   = $eor;
                        $eor   = ($cel > $match['cel'] ? $cel : $match['cel']) % 2 ? 'R' : 'L';
                    }
                }
            }
            elseif (($utf8Values[$i] != self::RTL_EMBEDDING) and
                ($utf8Values[$i] != self::LTR_EMBEDDING) and
                ($utf8Values[$i] != self::RTL_OVERRIDE) and
                ($utf8Values[$i] != self::LTR_OVERRIDE) and
                ($utf8Values[$i] != self::POP_DIRECTIONAL_FORMAT)) {
                // X6. For all types besides RLE, LRE, RLO, LRO, and PDF:
                //	a. Set the level of the current character to the current embedding level.
                //	b. Whenever the directional override status is not neutral, reset the current character type to the directional override status.
                if ($dos != 'N') {
                    $chardir = $dos;
                }
                else {
                    if (isset(self::UNICODE_TYPES[$utf8Values[$i]])) {
                        $chardir = self::UNICODE_TYPES[$utf8Values[$i]];
                    }
                    else {
                        $chardir = 'L';
                    }
                }
                // stores string characters and other information
                $chardata[] = array('char' => $utf8Values[$i], 'level' => $cel, 'type' => $chardir, 'sor' => $sor, 'eor' => $eor);
            }
        } // end for each char

        // X8. All explicit directional embeddings and overrides are completely terminated at the end of each paragraph. Paragraph separators are not included in the embedding.
        // X9. Remove all RLE, LRE, RLO, LRO, PDF, and BN codes.
        // X10. The remaining rules are applied to each run of characters at the same level. For each run, determine the start-of-level-run (sor) and end-of-level-run (eor) type, either L or R. This depends on the higher of the two levels on either side of the boundary (at the start or end of the paragraph, the level of the 'other' run is the base embedding level). If the higher level is odd, the type is R; otherwise, it is L.

        // 3.3.3 Resolving Weak Types
        // Weak types are now resolved one level run at a time. At level run boundaries where the type of the character on the other side of the boundary is required, the type assigned to sor or eor is used.
        // Nonspacing marks are now resolved based on the previous characters.
        $numchars = count($chardata);

        // W1. Examine each nonspacing mark (NSM) in the level run, and change the type of the NSM to the type of the previous character. If the NSM is at the start of the level run, it will get the type of sor.
        $prevlevel = -1; // track level changes
        $levcount  = 0; // counts consecutive chars at the same level
        for ($i = 0; $i < $numchars; ++$i) {
            if ($chardata[$i]['type'] == 'NSM') {
                if ($levcount) {
                    $chardata[$i]['type'] = $chardata[$i]['sor'];
                }
                elseif ($i > 0) {
                    $chardata[$i]['type'] = $chardata[($i - 1)]['type'];
                }
            }
            if ($chardata[$i]['level'] != $prevlevel) {
                $levcount = 0;
            }
            else {
                ++$levcount;
            }
            $prevlevel = $chardata[$i]['level'];
        }

        // W2. Search backward from each instance of a European number until the first strong type (R, L, AL, or sor) is found. If an AL is found, change the type of the European number to Arabic number.
        $prevlevel = -1;
        $levcount  = 0;
        for ($i = 0; $i < $numchars; ++$i) {
            if ($chardata[$i]['char'] == 'EN') {
                for ($j = $levcount; $j >= 0; $j--) {
                    if ($chardata[$j]['type'] == 'AL') {
                        $chardata[$i]['type'] = 'AN';
                    }
                    elseif (($chardata[$j]['type'] == 'L') or ($chardata[$j]['type'] == 'R')) {
                        break;
                    }
                }
            }
            if ($chardata[$i]['level'] != $prevlevel) {
                $levcount = 0;
            }
            else {
                ++$levcount;
            }
            $prevlevel = $chardata[$i]['level'];
        }

        // W3. Change all ALs to R.
        for ($i = 0; $i < $numchars; ++$i) {
            if ($chardata[$i]['type'] == 'AL') {
                $chardata[$i]['type'] = 'R';
            }
        }

        // W4. A single European separator between two European numbers changes to a European number. A single common separator between two numbers of the same type changes to that type.
        $prevlevel = -1;
        $levcount  = 0;
        for ($i = 0; $i < $numchars; ++$i) {
            if (($levcount > 0) and (($i + 1) < $numchars) and ($chardata[($i + 1)]['level'] == $prevlevel)) {
                if (($chardata[$i]['type'] == 'ES') and ($chardata[($i - 1)]['type'] == 'EN') and ($chardata[($i + 1)]['type'] == 'EN')) {
                    $chardata[$i]['type'] = 'EN';
                }
                elseif (($chardata[$i]['type'] == 'CS') and ($chardata[($i - 1)]['type'] == 'EN') and ($chardata[($i + 1)]['type'] == 'EN')) {
                    $chardata[$i]['type'] = 'EN';
                }
                elseif (($chardata[$i]['type'] == 'CS') and ($chardata[($i - 1)]['type'] == 'AN') and ($chardata[($i + 1)]['type'] == 'AN')) {
                    $chardata[$i]['type'] = 'AN';
                }
            }
            if ($chardata[$i]['level'] != $prevlevel) {
                $levcount = 0;
            }
            else {
                ++$levcount;
            }
            $prevlevel = $chardata[$i]['level'];
        }

        // W5. A sequence of European terminators adjacent to European numbers changes to all European numbers.
        $prevlevel = -1;
        $levcount  = 0;
        for ($i = 0; $i < $numchars; ++$i) {
            if ($chardata[$i]['type'] == 'ET') {
                if (($levcount > 0) and ($chardata[($i - 1)]['type'] == 'EN')) {
                    $chardata[$i]['type'] = 'EN';
                }
                else {
                    $j = $i + 1;
                    while (($j < $numchars) and ($chardata[$j]['level'] == $prevlevel)) {
                        if ($chardata[$j]['type'] == 'EN') {
                            $chardata[$i]['type'] = 'EN';
                            break;
                        }
                        elseif ($chardata[$j]['type'] != 'ET') {
                            break;
                        }
                        ++$j;
                    }
                }
            }
            if ($chardata[$i]['level'] != $prevlevel) {
                $levcount = 0;
            }
            else {
                ++$levcount;
            }
            $prevlevel = $chardata[$i]['level'];
        }

        // W6. Otherwise, separators and terminators change to Other Neutral.
        $prevlevel = -1;
        $levcount  = 0;
        for ($i = 0; $i < $numchars; ++$i) {
            if (($chardata[$i]['type'] == 'ET') or ($chardata[$i]['type'] == 'ES') or ($chardata[$i]['type'] == 'CS')) {
                $chardata[$i]['type'] = 'ON';
            }
            if ($chardata[$i]['level'] != $prevlevel) {
                $levcount = 0;
            }
            else {
                ++$levcount;
            }
            $prevlevel = $chardata[$i]['level'];
        }

        //W7. Search backward from each instance of a European number until the first strong type (R, L, or sor) is found. If an L is found, then change the type of the European number to L.
        $prevlevel = -1;
        $levcount  = 0;
        for ($i = 0; $i < $numchars; ++$i) {
            if ($chardata[$i]['char'] == 'EN') {
                for ($j = $levcount; $j >= 0; $j--) {
                    if ($chardata[$j]['type'] == 'L') {
                        $chardata[$i]['type'] = 'L';
                    }
                    elseif ($chardata[$j]['type'] == 'R') {
                        break;
                    }
                }
            }
            if ($chardata[$i]['level'] != $prevlevel) {
                $levcount = 0;
            }
            else {
                ++$levcount;
            }
            $prevlevel = $chardata[$i]['level'];
        }

        // N1. A sequence of neutrals takes the direction of the surrounding strong text if the text on both sides has the same direction. Numbers act as if they were R in terms of their influence on neutrals. Start-of-level-run (sor) and end-of-level-run (eor) are used at level run boundaries.
        $prevlevel = -1;
        $levcount  = 0;
        for ($i = 0; $i < $numchars; ++$i) {
            if (($levcount > 0) and (($i + 1) < $numchars) and ($chardata[($i + 1)]['level'] == $prevlevel)) {
                if (($chardata[$i]['type'] == 'N') and ($chardata[($i - 1)]['type'] == 'L') and ($chardata[($i + 1)]['type'] == 'L')) {
                    $chardata[$i]['type'] = 'L';
                }
                elseif (($chardata[$i]['type'] == 'N') and
                    (($chardata[($i - 1)]['type'] == 'R') or ($chardata[($i - 1)]['type'] == 'EN') or ($chardata[($i - 1)]['type'] == 'AN')) and
                    (($chardata[($i + 1)]['type'] == 'R') or ($chardata[($i + 1)]['type'] == 'EN') or ($chardata[($i + 1)]['type'] == 'AN'))) {
                    $chardata[$i]['type'] = 'R';
                }
                elseif ($chardata[$i]['type'] == 'N') {
                    // N2. Any remaining neutrals take the embedding direction
                    $chardata[$i]['type'] = $chardata[$i]['sor'];
                }
            }
            elseif (($levcount == 0) and (($i + 1) < $numchars) and ($chardata[($i + 1)]['level'] == $prevlevel)) {
                // first char
                if (($chardata[$i]['type'] == 'N') and ($chardata[$i]['sor'] == 'L') and ($chardata[($i + 1)]['type'] == 'L')) {
                    $chardata[$i]['type'] = 'L';
                }
                elseif (($chardata[$i]['type'] == 'N') and
                    (($chardata[$i]['sor'] == 'R') or ($chardata[$i]['sor'] == 'EN') or ($chardata[$i]['sor'] == 'AN')) and
                    (($chardata[($i + 1)]['type'] == 'R') or ($chardata[($i + 1)]['type'] == 'EN') or ($chardata[($i + 1)]['type'] == 'AN'))) {
                    $chardata[$i]['type'] = 'R';
                }
                elseif ($chardata[$i]['type'] == 'N') {
                    // N2. Any remaining neutrals take the embedding direction
                    $chardata[$i]['type'] = $chardata[$i]['sor'];
                }
            }
            elseif (($levcount > 0) and ((($i + 1) == $numchars) or (($i + 1) < $numchars) and ($chardata[($i + 1)]['level'] != $prevlevel))) {
                //last char
                if (($chardata[$i]['type'] == 'N') and ($chardata[($i - 1)]['type'] == 'L') and ($chardata[$i]['eor'] == 'L')) {
                    $chardata[$i]['type'] = 'L';
                }
                elseif (($chardata[$i]['type'] == 'N') and
                    (($chardata[($i - 1)]['type'] == 'R') or ($chardata[($i - 1)]['type'] == 'EN') or ($chardata[($i - 1)]['type'] == 'AN')) and
                    (($chardata[$i]['eor'] == 'R') or ($chardata[$i]['eor'] == 'EN') or ($chardata[$i]['eor'] == 'AN'))) {
                    $chardata[$i]['type'] = 'R';
                }
                elseif ($chardata[$i]['type'] == 'N') {
                    // N2. Any remaining neutrals take the embedding direction
                    $chardata[$i]['type'] = $chardata[$i]['sor'];
                }
            }
            elseif ($chardata[$i]['type'] == 'N') {
                // N2. Any remaining neutrals take the embedding direction
                $chardata[$i]['type'] = $chardata[$i]['sor'];
            }
            if ($chardata[$i]['level'] != $prevlevel) {
                $levcount = 0;
            }
            else {
                ++$levcount;
            }
            $prevlevel = $chardata[$i]['level'];
        }

        // I1. For all characters with an even (left-to-right) embedding direction, those of type R go up one level and those of type AN or EN go up two levels.
        // I2. For all characters with an odd (right-to-left) embedding direction, those of type L, EN or AN go up one level.
        for ($i = 0; $i < $numchars; ++$i) {
            $odd = $chardata[$i]['level'] % 2;
            if ($odd) {
                if (($chardata[$i]['type'] == 'L') or ($chardata[$i]['type'] == 'AN') or ($chardata[$i]['type'] == 'EN')) {
                    $chardata[$i]['level'] += 1;
                }
            }
            else {
                if ($chardata[$i]['type'] == 'R') {
                    $chardata[$i]['level'] += 1;
                }
                elseif (($chardata[$i]['type'] == 'AN') or ($chardata[$i]['type'] == 'EN')) {
                    $chardata[$i]['level'] += 2;
                }
            }
            $maxlevel = max($chardata[$i]['level'], $maxlevel);
        }

        // L1. On each line, reset the embedding level of the following characters to the paragraph embedding level:
        //	1. Segment separators,
        //	2. Paragraph separators,
        //	3. Any sequence of whitespace characters preceding a segment separator or paragraph separator, and
        //	4. Any sequence of white space characters at the end of the line.
        for ($i = 0; $i < $numchars; ++$i) {
            if (($chardata[$i]['type'] == 'B') or ($chardata[$i]['type'] == 'S')) {
                $chardata[$i]['level'] = $pel;
            }
            elseif ($chardata[$i]['type'] == 'WS') {
                $j = $i + 1;
                while ($j < $numchars) {
                    if ((($chardata[$j]['type'] == 'B') or ($chardata[$j]['type'] == 'S')) or
                        (($j == ($numchars - 1)) and ($chardata[$j]['type'] == 'WS'))) {
                        $chardata[$i]['level'] = $pel;
                        break;
                    }
                    elseif ($chardata[$j]['type'] != 'WS') {
                        break;
                    }
                    ++$j;
                }
            }
        }

        // L2. From the highest level found in the text to the lowest odd level on each line, including intermediate levels not actually present in the text, reverse any contiguous sequence of characters that are at that level or higher.
        for ($j = $maxlevel; $j > 0; $j--) {
            $ordarray = array();
            $revarr   = array();
            $onlevel  = false;
            for ($i = 0; $i < $numchars; ++$i) {
                if ($chardata[$i]['level'] >= $j) {
                    $onlevel = true;
                    if (isset(self::MIRROR_CHARACTERS[$chardata[$i]['char']])) {
                        // L4. A character is depicted by a mirrored glyph if and only if (a) the resolved directionality of that character is R, and (b) the Bidi_Mirrored property value of that character is true.
                        $chardata[$i]['char'] = self::MIRROR_CHARACTERS[$chardata[$i]['char']];
                    }
                    $revarr[] = $chardata[$i];
                }
                else {
                    if ($onlevel) {
                        $revarr   = array_reverse($revarr);
                        $ordarray = array_merge($ordarray, $revarr);
                        $revarr   = array();
                        $onlevel  = false;
                    }
                    $ordarray[] = $chardata[$i];
                }
            }
            if ($onlevel) {
                $revarr   = array_reverse($revarr);
                $ordarray = array_merge($ordarray, $revarr);
            }
            $chardata = $ordarray;
        }
        $ordarray = [];
        foreach ($chardata as $cd) {
            $ordarray[] = $cd['char'];
            // store char values for subsetting
            $currentFont['subsetCharacters'][$cd['char']] = true;
        }
        return $ordarray;
    }
}