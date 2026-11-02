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

namespace THM\Organizer\Views\PDF\Library;

/** Unicode data and font methods. */
trait Fonts
{
    private string $fontPath = JPATH_SITE . '/components/com_organizer/Views/PDF/Library/Fonts/';

    /** Cache used for speed up Unicode ord performances. */
    private array $utf8Map = [];

    /**
     * Return font full path
     * @param string $file Font file name.
     * @return string Font full path or empty string
     */
    public function fontPath(string $file): string
    {
        $fontPath = '';
        if (StaticMethods::fileExists($this->fontPath . $file)) {
            $fontPath = $this->fontPath . $file;
        }
        elseif (StaticMethods::fileExists($file)) {
            $fontPath = $file;
        }

        return $fontPath;
    }

    /**
     * Reverse the RLT substrings array using the Bidirectional Algorithm (http://unicode.org/reports/tr9/).
     * @param array  $utf8Values
     * @param string $string
     * @param bool   $setBOM      set the Byte Order Mark (BOM = 0xFEFF)
     * @param bool   $forceRTL
     * @param bool   $isUnicode
     * @param array  $currentFont
     * @return string
     */
    public function reverseRTLSubstrings(array $utf8Values, string $string, bool $setBOM, bool $forceRTL, bool $isUnicode, array &$currentFont): string
    {
        return self::utf8ArrayToUTF16BEString($this->utf8Bidirectional($utf8Values, $string, $forceRTL, $isUnicode, $currentFont), $setBOM);
    }

    /**
     * Returns the Unicode character specified by ASCII value
     * @param int $value UTF-8 value
     * @return string Returns the specified character.
     */
    public function unicodeByASCII(int $value): string
    {
        return $this->unicodeCharacter($value, false);
    }

    /**
     * Returns the Unicode character specified by the value
     * @param int  $value     UTF-8 value
     * @param bool $isUnicode True if we are in Unicode mode, false otherwise.
     * @return string Returns the specified character.
     */
    public function unicodeCharacter(int $value, bool $isUnicode = true): string
    {
        if (!$isUnicode) {
            return chr($value);
        }

        return match (true) {
            ($value <= 0x7F) => chr($value),
            ($value <= 0x7FF) => chr(0xC0 | $value >> 6) . chr(0x80 | $value & 0x3F),
            ($value <= 0xFFFF) => chr(0xE0 | $value >> 12) . chr(0x80 | $value >> 6 & 0x3F) . chr(0x80 | $value & 0x3F),
            ($value <= 0x10FFFF) => chr(0xF0 | $value >> 18) . chr(0x80 | $value >> 12 & 0x3F) . chr(0x80 | $value >> 6 & 0x3F) . chr(0x80 | $value & 0x3F),
            default => '',
        };
    }

    /**
     * Converts UTF-8 character to integer value.
     * @param string $character character string to process.
     * @return int
     */
    private function unicodeOrd(string $character): int
    {
        if (isset($this->utf8Map[$character])) {
            return $this->utf8Map[$character];
        }

        if (function_exists('mb_convert_encoding')) {
            list(, $value) = @unpack('N', mb_convert_encoding($character, 'UCS-4BE', 'UTF-8'));
            if (is_int($value) and $value >= 0) {
                $this->utf8Map[$character] = $value;
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

        $this->utf8Map[$character] = $byte;
        return $byte;
    }

    /**
     * Extracts a slice of the $characters array and return it as string.
     * @param string[] $characters
     * @param int      $start      (inclusive)
     * @param int      $end        (exclusive)
     * @return string
     */
    public static function unicodeSliceToString(array $characters, int $start = 0, int $end = 0): string
    {
        $end = $end ?: count($characters);
        $return = '';
        for ($index = $start; $index < $end; ++$index) {
            $return .= $characters[$index];
        }
        return $return;
    }

    /**
     * Converts UTF-8 character values array to array of Latin1 character values array.
     * @param array $utf8Values
     * @return array
     */
    public function utf8ArrayToLatin1Array(array $utf8Values): array
    {
        $latin1Values = [];
        foreach ($utf8Values as $char) {
            if ($char < 256) {
                $latin1Values[] = $char;
            }
            elseif (array_key_exists($char, FontData::$utf8ToLatin1)) {
                $latin1Values[] = FontData::$utf8ToLatin1[$char];
            }
            elseif ($char !== 0xFFFD) {
                $latin1Values[] = 63; // '?' character
            }
        }
        return $latin1Values;
    }

    /**
     * Convert an array of UTF8 values to array of Unicode characters.
     * @param array $utf8Values
     * @param bool  $isUnicode
     * @return array
     */
    public function utf8ArrayToUnicodeArray(array $utf8Values, bool $isUnicode = true): array
    {
        if ($isUnicode) {
            return array_map([$this, 'unicodeCharacter'], $utf8Values);
        }

        return array_map([$this, 'unicodeByASCII'], $utf8Values);
    }

    /**
     * Converts a UTF-8 character array to a Latin1 string.
     * @param array $utf8Values
     * @return string
     */
    public function utf8ArrayToLatin1String(array $utf8Values): string
    {
        $return = '';
        foreach ($utf8Values as $value) {
            if ($value < 256) {
                $return .= chr($value);
            }
            elseif (array_key_exists($value, FontData::$utf8ToLatin1)) {
                // map from UTF-8
                $return .= chr(FontData::$utf8ToLatin1[$value]);
            }
            elseif ($value == 0xFFFD) {
                continue;
            }
            else {
                $return .= '?';
            }
        }
        return $return;
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
     * @param array $utf8Values array containing UTF-8 Unicode values
     * @param bool  $setBOM     if true set the Byte Order Mark (BOM = 0xFEFF)
     * @return string
     */
    public static function utf8ArrayToUTF16BEString(array $utf8Values, bool $setBOM = false): string
    {
        $return = ''; // string to be returned
        if ($setBOM) {
            $return .= "\xFE\xFF"; // Byte Order Mark (BOM)
        }
        foreach ($utf8Values as $value) {
            if ($value == 0x200b) {
                // skip Unicode Character 'ZERO WIDTH SPACE' (DEC:8203, U+200B)
                continue;
            }
            elseif ($value == 0xFFFD) {
                $return .= "\xFF\xFD"; // replacement character
            }
            elseif ($value < 0x10000) {
                $return .= chr($value >> 0x08);
                $return .= chr($value & 0xFF);
            }
            else {
                $value   -= 0x10000;
                $w1     = 0xD800 | ($value >> 0x0a);
                $w2     = 0xDC00 | ($value & 0x3FF);
                $return .= chr($w1 >> 0x08);
                $return .= chr($w1 & 0xFF);
                $return .= chr($w2 >> 0x08);
                $return .= chr($w2 & 0xFF);
            }
        }
        return $return;
    }

    /**
     * Reverse the RLT substrings using the Bidirectional Algorithm (http://unicode.org/reports/tr9/).
     * @param array  $utf8Values
     * @param string $string
     * @param bool   $forceRTL if 'R' forces RTL, if 'L' forces LTR
     * @param bool   $isUnicode
     * @param array  $currentFont
     * @return array of Unicode chars
     */
    public function utf8Bidirectional(array $utf8Values, string $string, bool $forceRTL, bool $isUnicode, array &$currentFont): array
    {
        // paragraph embedding level
        $pel = 0;
        // max level
        $maxlevel = 0;
        if (empty($string)) {
            // create string from array
            $string = $this->utf8SliceToString($utf8Values, 0, 0, $isUnicode);
        }
        // check if string contains arabic text
        if (preg_match(FontData::$arabidTestPattern, $string)) {
            $arabic = true;
        }
        else {
            $arabic = false;
        }
        // check if string contains RTL text
        if (!($forceRTL or $arabic or preg_match(FontData::$rtlTestPattern, $string))) {
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
                $type = FontData::$types[$utf8Values[$i]];
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
        $chardata = [];

        // X1. Begin by setting the current embedding level to the paragraph embedding level. Set the directional override status to neutral. Process each character iteratively, applying rules X2 through X9. Only embedding levels from 0 to 61 are valid in this phase.
        // In the resolution of levels in rules I1 and I2, the maximum embedding level of 62 can be reached.
        for ($i = 0; $i < $numchars; ++$i) {
            if ($utf8Values[$i] == FontData::$rtlEmbedding) {
                // X2. With each RLE, compute the least greater odd embedding level.
                //	a. If this new level would be valid, then this embedding code is valid. Remember (push) the current embedding level and override status. Reset the current level to this new level, and reset the override status to neutral.
                //	b. If the new level would not be valid, then this code is invalid. Do not change the current level or override status.
                $next_level = $cel + ($cel % 2) + 1;
                if ($next_level < 62) {
                    $remember[] = ['num' => FontData::$rtlEmbedding, 'cel' => $cel, 'dos' => $dos];
                    $cel        = $next_level;
                    $dos        = 'N';
                    $sor        = $eor;
                    $eor        = $cel % 2 ? 'R' : 'L';
                }
            }
            elseif ($utf8Values[$i] == FontData::$ltrEmbedding) {
                // X3. With each LRE, compute the least greater even embedding level.
                //	a. If this new level would be valid, then this embedding code is valid. Remember (push) the current embedding level and override status. Reset the current level to this new level, and reset the override status to neutral.
                //	b. If the new level would not be valid, then this code is invalid. Do not change the current level or override status.
                $next_level = $cel + 2 - ($cel % 2);
                if ($next_level < 62) {
                    $remember[] = ['num' => FontData::$ltrEmbedding, 'cel' => $cel, 'dos' => $dos];
                    $cel        = $next_level;
                    $dos        = 'N';
                    $sor        = $eor;
                    $eor        = $cel % 2 ? 'R' : 'L';
                }
            }
            elseif ($utf8Values[$i] == FontData::$rtlOverride) {
                // X4. With each RLO, compute the least greater odd embedding level.
                //	a. If this new level would be valid, then this embedding code is valid. Remember (push) the current embedding level and override status. Reset the current level to this new level, and reset the override status to right-to-left.
                //	b. If the new level would not be valid, then this code is invalid. Do not change the current level or override status.
                $next_level = $cel + ($cel % 2) + 1;
                if ($next_level < 62) {
                    $remember[] = ['num' => FontData::$rtlOverride, 'cel' => $cel, 'dos' => $dos];
                    $cel        = $next_level;
                    $dos        = 'R';
                    $sor        = $eor;
                    $eor        = $cel % 2 ? 'R' : 'L';
                }
            }
            elseif ($utf8Values[$i] == FontData::$ltrOverride) {
                // X5. With each LRO, compute the least greater even embedding level.
                //	a. If this new level would be valid, then this embedding code is valid. Remember (push) the current embedding level and override status. Reset the current level to this new level, and reset the override status to left-to-right.
                //	b. If the new level would not be valid, then this code is invalid. Do not change the current level or override status.
                $next_level = $cel + 2 - ($cel % 2);
                if ($next_level < 62) {
                    $remember[] = ['num' => FontData::$ltrOverride, 'cel' => $cel, 'dos' => $dos];
                    $cel        = $next_level;
                    $dos        = 'L';
                    $sor        = $eor;
                    $eor        = $cel % 2 ? 'R' : 'L';
                }
            }
            elseif ($utf8Values[$i] == FontData::$pdf) {
                // X7. With each PDF, determine the matching embedding or override code. If there was a valid matching code, restore (pop) the last remembered (pushed) embedding level and directional override.
                if (count($remember)) {
                    $last = count($remember) - 1;
                    if (($remember[$last]['num'] == FontData::$rtlEmbedding) or
                        ($remember[$last]['num'] == FontData::$ltrEmbedding) or
                        ($remember[$last]['num'] == FontData::$rtlOverride) or
                        ($remember[$last]['num'] == FontData::$ltrOverride)) {
                        $match = array_pop($remember);
                        $cel   = $match['cel'];
                        $dos   = $match['dos'];
                        $sor   = $eor;
                        $eor   = ($cel > $match['cel'] ? $cel : $match['cel']) % 2 ? 'R' : 'L';
                    }
                }
            }
            elseif (($utf8Values[$i] != FontData::$rtlEmbedding) and
                ($utf8Values[$i] != FontData::$ltrEmbedding) and
                ($utf8Values[$i] != FontData::$rtlOverride) and
                ($utf8Values[$i] != FontData::$ltrOverride) and
                ($utf8Values[$i] != FontData::$pdf)) {
                // X6. For all types besides RLE, LRE, RLO, LRO, and PDF:
                //	a. Set the level of the current character to the current embedding level.
                //	b. Whenever the directional override status is not neutral, reset the current character type to the directional override status.
                if ($dos != 'N') {
                    $chardir = $dos;
                }
                else {
                    if (isset(FontData::$types[$utf8Values[$i]])) {
                        $chardir = FontData::$types[$utf8Values[$i]];
                    }
                    else {
                        $chardir = 'L';
                    }
                }
                // stores string characters and other information
                $chardata[] = ['char' => $utf8Values[$i], 'level' => $cel, 'type' => $chardir, 'sor' => $sor, 'eor' => $eor];
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

        // N1. A sequence of neutrals takes the direction of the surrounding strong text if the text on both sides has the same direction. European and Arabic numbers act as if they were R in terms of their influence on neutrals. Start-of-level-run (sor) and end-of-level-run (eor) are used at level run boundaries.
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

        // Arabic Shaping
        // Cursively connected scripts, such as Arabic or Syriac, require the selection of positional character shapes that depend on adjacent characters. Shaping is logically applied after the Bidirectional Algorithm is used and is limited to characters within the same directional run.
        if ($arabic) {
            $endedletter = [1569, 1570, 1571, 1572, 1573, 1575, 1577, 1583, 1584, 1585, 1586, 1608, 1688];
            $alfletter   = [1570, 1571, 1573, 1575];
            $chardata2   = $chardata;
            $laaletter   = false;
            $charAL      = [];
            $x           = 0;
            for ($i = 0; $i < $numchars; ++$i) {
                if ((FontData::$types[$chardata[$i]['char']] == 'AL') or ($chardata[$i]['char'] == 32) or ($chardata[$i]['char'] == 8204)) {
                    $charAL[$x]        = $chardata[$i];
                    $charAL[$x]['i']   = $i;
                    $chardata[$i]['x'] = $x;
                    ++$x;
                }
            }
            $numAL = $x;
            for ($i = 0; $i < $numchars; ++$i) {
                $thischar = $chardata[$i];
                if ($i > 0) {
                    $prevchar = $chardata[($i - 1)];
                }
                else {
                    $prevchar = false;
                }
                if (($i + 1) < $numchars) {
                    $nextchar = $chardata[($i + 1)];
                }
                else {
                    $nextchar = false;
                }
                if (FontData::$types[$thischar['char']] == 'AL') {
                    $x = $thischar['x'];
                    if ($x > 0) {
                        $prevchar = $charAL[($x - 1)];
                    }
                    else {
                        $prevchar = false;
                    }
                    if (($x + 1) < $numAL) {
                        $nextchar = $charAL[($x + 1)];
                    }
                    else {
                        $nextchar = false;
                    }
                    // if laa letter
                    if (($prevchar !== false) and ($prevchar['char'] == 1604) and (in_array($thischar['char'], $alfletter))) {
                        $arabicarr = FontData::$arabicLaCharacter;
                        $laaletter = true;
                        if ($x > 1) {
                            $prevchar = $charAL[($x - 2)];
                        }
                        else {
                            $prevchar = false;
                        }
                    }
                    else {
                        $arabicarr = FontData::$arabicShapeSubstitutions;
                        $laaletter = false;
                    }
                    if (($prevchar !== false) and ($nextchar !== false) and
                        ((FontData::$types[$prevchar['char']] == 'AL') or (FontData::$types[$prevchar['char']] == 'NSM')) and
                        ((FontData::$types[$nextchar['char']] == 'AL') or (FontData::$types[$nextchar['char']] == 'NSM')) and
                        ($prevchar['type'] == $thischar['type']) and
                        ($nextchar['type'] == $thischar['type']) and
                        ($nextchar['char'] != 1567)) {
                        if (in_array($prevchar['char'], $endedletter)) {
                            if (isset($arabicarr[$thischar['char']][2])) {
                                // initial
                                $chardata2[$i]['char'] = $arabicarr[$thischar['char']][2];
                            }
                        }
                        else {
                            if (isset($arabicarr[$thischar['char']][3])) {
                                // medial
                                $chardata2[$i]['char'] = $arabicarr[$thischar['char']][3];
                            }
                        }
                    }
                    elseif (($nextchar !== false) and
                        ((FontData::$types[$nextchar['char']] == 'AL') or (FontData::$types[$nextchar['char']] == 'NSM')) and
                        ($nextchar['type'] == $thischar['type']) and
                        ($nextchar['char'] != 1567)) {
                        if (isset($arabicarr[$chardata[$i]['char']][2])) {
                            // initial
                            $chardata2[$i]['char'] = $arabicarr[$thischar['char']][2];
                        }
                    }
                    elseif ((($prevchar !== false) and
                            ((FontData::$types[$prevchar['char']] == 'AL') or (FontData::$types[$prevchar['char']] == 'NSM')) and
                            ($prevchar['type'] == $thischar['type'])) or
                        (($nextchar !== false) and ($nextchar['char'] == 1567))) {
                        // final
                        if (($i > 1) and ($thischar['char'] == 1607) and
                            ($chardata[$i - 1]['char'] == 1604) and
                            ($chardata[$i - 2]['char'] == 1604)) {
                            //Allah Word
                            // mark characters to delete with false
                            $chardata2[$i - 2]['char'] = false;
                            $chardata2[$i - 1]['char'] = false;
                            $chardata2[$i]['char']     = 65010;
                        }
                        else {
                            if (($prevchar !== false) and in_array($prevchar['char'], $endedletter)) {
                                if (isset($arabicarr[$thischar['char']][0])) {
                                    // isolated
                                    $chardata2[$i]['char'] = $arabicarr[$thischar['char']][0];
                                }
                            }
                            else {
                                if (isset($arabicarr[$thischar['char']][1])) {
                                    // final
                                    $chardata2[$i]['char'] = $arabicarr[$thischar['char']][1];
                                }
                            }
                        }
                    }
                    elseif (isset($arabicarr[$thischar['char']][0])) {
                        // isolated
                        $chardata2[$i]['char'] = $arabicarr[$thischar['char']][0];
                    }
                    // if laa letter
                    if ($laaletter) {
                        // mark characters to delete with false
                        $chardata2[($charAL[($x - 1)]['i'])]['char'] = false;
                    }
                } // end if AL (Arabic Letter)
            } // end for each char
            /*
			 * Combining characters that can occur with Arabic Shadda (0651 HEX, 1617 DEC) are replaced.
			 * Putting the combining mark and shadda in the same glyph allows us to avoid the two marks overlapping each other in an illegible manner.
			 */
            for ($i = 0; $i < ($numchars - 1); ++$i) {
                if (($chardata2[$i]['char'] == 1617) and (isset(FontData::$diacriticSubstitutions[($chardata2[$i + 1]['char'])]))) {
                    // check if the subtitution font is defined on current font
                    if (isset($currentFont['cw'][(FontData::$diacriticSubstitutions[($chardata2[$i + 1]['char'])])])) {
                        $chardata2[$i]['char']     = false;
                        $chardata2[$i + 1]['char'] = FontData::$diacriticSubstitutions[($chardata2[$i + 1]['char'])];
                    }
                }
            }
            // remove marked characters
            foreach ($chardata2 as $key => $value) {
                if ($value['char'] === false) {
                    unset($chardata2[$key]);
                }
            }
            $chardata = array_values($chardata2);
            $numchars = count($chardata);
            unset($chardata2);
            unset($arabicarr);
            unset($laaletter);
            unset($charAL);
        }

        // L2. From the highest level found in the text to the lowest odd level on each line, including intermediate levels not actually present in the text, reverse any contiguous sequence of characters that are at that level or higher.
        for ($j = $maxlevel; $j > 0; $j--) {
            $ordarray = [];
            $revarr   = [];
            $onlevel  = false;
            for ($i = 0; $i < $numchars; ++$i) {
                if ($chardata[$i]['level'] >= $j) {
                    $onlevel = true;
                    if (isset(FontData::$mirrorCharacters[$chardata[$i]['char']])) {
                        // L4. A character is depicted by a mirrored glyph if and only if (a) the resolved directionality of that character is R, and (b) the Bidi_Mirrored property value of that character is true.
                        $chardata[$i]['char'] = FontData::$mirrorCharacters[$chardata[$i]['char']];
                    }
                    $revarr[] = $chardata[$i];
                }
                else {
                    if ($onlevel) {
                        $revarr   = array_reverse($revarr);
                        $ordarray = array_merge($ordarray, $revarr);
                        $revarr   = [];
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
            $currentFont['subsetchars'][$cd['char']] = true;
        }
        return $ordarray;
    }

    /**
     * Extract a slice of the $utf8Values array converted to characters and returned as a string.
     * @param int[] $utf8Values
     * @param int   $start      (inclusive)
     * @param int   $end       (exclusive)
     * @param bool  $isUnicode
     * @return string
     */
    public function utf8SliceToString(array $utf8Values, int $start = 0, int $end = 0, bool $isUnicode = true): string
    {
        $end = $end > 0 ? $end : count($utf8Values);
        $string = '';
        for ($index = $start; $index < $end; ++$index) {
            $string .= $this->unicodeCharacter($utf8Values[$index], $isUnicode);
        }
        return $string;
    }

    /**
     * Reverse the RLT substrings using the Bidirectional Algorithm (http://unicode.org/reports/tr9/).
     * @param string  $string
     * @param bool    $setBOM   if true set the Byte Order Mark (BOM = 0xFEFF)
     * @param bool    $forceRTL if true forces RTL text direction
     * @param bool    $isUnicode
     * @param array  &$currentFont
     * @return string
     */
    public function utf8StringReverse(string $string, bool $setBOM, bool $forceRTL, bool $isUnicode, array &$currentFont): string
    {
        return $this->reverseRTLSubstrings($this->utf8StringToArray($string, $isUnicode, $currentFont), $string, $setBOM, $forceRTL, $isUnicode, $currentFont);
    }

    /**
     * Converts UTF-8 strings to codepoints (character values) array. Invalid byte sequences will be replaced with 0xFFFD (replacement character).
     * @param string  $string
     * @param bool    $isUnicode
     * @param array  &$currentFont Reference to current font array.
     * @return array
     */
    public function utf8StringToArray(string $string, bool $isUnicode, array &$currentFont): array
    {
        if ($isUnicode) {
            $chars = preg_split('//u', $string);
            $carr  = array_map([$this, 'unicodeOrd'], $chars);
        }
        else {
            $chars = str_split($string);
            $carr  = array_map('ord', $chars);
        }

        $currentFont['subsetchars'] = is_array($currentFont['subsetchars']) ?
            $currentFont['subsetchars'] + array_fill_keys($carr, true) : array_merge($currentFont['subsetchars'], $carr);
        return $carr;
    }

    /**
     * Converts UTF-8 strings to Latin1 strings when using the standard 14 core fonts.
     * @param string  $string
     * @param bool    $isUnicode
     * @param array  &$currentFont
     * @return string
     */
    public function utf8ToLatin1(string $string, bool $isUnicode, array &$currentFont): string
    {
        $unicode = $this->utf8StringToArray($string, $isUnicode, $currentFont); // array containing UTF-8 Unicode values
        return $this->utf8ArrayToLatin1String($unicode);
    }

    /**
     * Converts UTF-8 strings to UTF16-BE string.
     * @param string  $string
     * @param bool    $setBOM if true set the Byte Order Mark (BOM = 0xFEFF)
     * @param bool    $isUnicode
     * @param array  &$currentFont
     * @return string
     */
    public function utf8ToUTF16BE(string $string, bool $setBOM, bool $isUnicode, array &$currentFont): string
    {
        if (!$isUnicode) {
            return $string;
        }

        $unicode = $this->utf8StringToArray($string, true, $currentFont);
        return self::utf8ArrayToUTF16BEString($unicode, $setBOM);
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////TODO

    /**
     * Returs the checksum of a TTF table.
     * @param string $table  table to check
     * @param int    $length length of table in bytes
     * @return int checksum
     */
    public static function _getTTFtableChecksum($table, $length)
    {
        $sum    = 0;
        $tlen   = ($length / 4);
        $offset = 0;
        for ($i = 0; $i < $tlen; ++$i) {
            $v      = unpack('Ni', substr($table, $offset, 4));
            $sum    += $v['i'];
            $offset += 4;
        }
        $sum = unpack('Ni', pack('N', $sum));
        return $sum['i'];
    }

    /**
     * Returns a subset of the TrueType font data without the unused glyphs.
     * @param string $font        TrueType font data.
     * @param array  $subsetchars Array of used characters (the glyphs to keep).
     * @return string A subset of TrueType font data without the unused glyphs.
     */
    public static function _getTrueTypeFontSubset($font, $subsetchars)
    {
        ksort($subsetchars);
        $offset = 0; // offset position of the font data
        if (StaticMethods::_getULONG($font, $offset) != 0x10000) {
            // sfnt version must be 0x00010000 for TrueType version 1.0.
            return $font;
        }
        $c      = 0;
        $offset += 4;
        // get number of tables
        $numTables = StaticMethods::_getUSHORT($font, $offset);
        $offset    += 2;
        // skip searchRange, entrySelector and rangeShift
        $offset += 6;
        // tables array
        $table = [];
        // for each table
        for ($i = 0; $i < $numTables; ++$i) {
            // get table info
            $tag                     = substr($font, $offset, 4);
            $offset                  += 4;
            $table[$tag]             = [];
            $table[$tag]['checkSum'] = StaticMethods::_getULONG($font, $offset);
            $offset                  += 4;
            $table[$tag]['offset']   = StaticMethods::_getULONG($font, $offset);
            $offset                  += 4;
            $table[$tag]['length']   = StaticMethods::_getULONG($font, $offset);
            $offset                  += 4;
        }
        // check magicNumber
        $offset = $table['head']['offset'] + 12;
        if (StaticMethods::_getULONG($font, $offset) != 0x5F0F3CF5) {
            // magicNumber must be 0x5F0F3CF5
            return $font;
        }
        $offset += 4;
        // get offset mode (indexToLocFormat : 0 = short, 1 = long)
        $offset       = $table['head']['offset'] + 50;
        $short_offset = (StaticMethods::_getSHORT($font, $offset) == 0);
        $offset       += 2;
        // get the offsets to the locations of the glyphs in the font, relative to the beginning of the glyphData table
        $indexToLoc = [];
        $offset     = $table['loca']['offset'];
        if ($short_offset) {
            // short version
            $tot_num_glyphs = floor($table['loca']['length'] / 2); // numGlyphs + 1
            for ($i = 0; $i < $tot_num_glyphs; ++$i) {
                $indexToLoc[$i] = StaticMethods::_getUSHORT($font, $offset) * 2;
                $offset         += 2;
            }
        }
        else {
            // long version
            $tot_num_glyphs = ($table['loca']['length'] / 4); // numGlyphs + 1
            for ($i = 0; $i < $tot_num_glyphs; ++$i) {
                $indexToLoc[$i] = StaticMethods::_getULONG($font, $offset);
                $offset         += 4;
            }
        }
        // get glyphs indexes of chars from cmap table
        $subsetglyphs      = []; // glyph IDs on key
        $subsetglyphs[0]   = true; // character codes that do not correspond to any glyph in the font should be mapped to glyph index 0
        $offset            = $table['cmap']['offset'] + 2;
        $numEncodingTables = StaticMethods::_getUSHORT($font, $offset);
        $offset            += 2;
        $encodingTables    = [];
        for ($i = 0; $i < $numEncodingTables; ++$i) {
            $encodingTables[$i]['platformID'] = StaticMethods::_getUSHORT($font, $offset);
            $offset                           += 2;
            $encodingTables[$i]['encodingID'] = StaticMethods::_getUSHORT($font, $offset);
            $offset                           += 2;
            $encodingTables[$i]['offset']     = StaticMethods::_getULONG($font, $offset);
            $offset                           += 4;
        }
        foreach ($encodingTables as $enctable) {
            // get all platforms and encodings
            $offset = $table['cmap']['offset'] + $enctable['offset'];
            $format = StaticMethods::_getUSHORT($font, $offset);
            $offset += 2;
            switch ($format) {
                case 0:
                { // Format 0: Byte encoding table
                    $offset += 4; // skip length and version/language
                    for ($c = 0; $c < 256; ++$c) {
                        if (isset($subsetchars[$c])) {
                            $g                = StaticMethods::byte($font, $offset);
                            $subsetglyphs[$g] = true;
                        }
                        ++$offset;
                    }
                    break;
                }
                case 2:
                { // Format 2: High-byte mapping through table
                    $offset        += 4; // skip length and version/language
                    $numSubHeaders = 0;
                    for ($i = 0; $i < 256; ++$i) {
                        // Array that maps high bytes to subHeaders: value is subHeader index * 8.
                        $subHeaderKeys[$i] = (StaticMethods::_getUSHORT($font, $offset) / 8);
                        $offset            += 2;
                        if ($numSubHeaders < $subHeaderKeys[$i]) {
                            $numSubHeaders = $subHeaderKeys[$i];
                        }
                    }
                    // the number of subHeaders is equal to the max of subHeaderKeys + 1
                    ++$numSubHeaders;
                    // read subHeader structures
                    $subHeaders         = [];
                    $numGlyphIndexArray = 0;
                    for ($k = 0; $k < $numSubHeaders; ++$k) {
                        $subHeaders[$k]['firstCode']     = StaticMethods::_getUSHORT($font, $offset);
                        $offset                          += 2;
                        $subHeaders[$k]['entryCount']    = StaticMethods::_getUSHORT($font, $offset);
                        $offset                          += 2;
                        $subHeaders[$k]['idDelta']       = StaticMethods::_getUSHORT($font, $offset);
                        $offset                          += 2;
                        $subHeaders[$k]['idRangeOffset'] = StaticMethods::_getUSHORT($font, $offset);
                        $offset                          += 2;
                        $subHeaders[$k]['idRangeOffset'] -= (2 + (($numSubHeaders - $k - 1) * 8));
                        $subHeaders[$k]['idRangeOffset'] /= 2;
                        $numGlyphIndexArray              += $subHeaders[$k]['entryCount'];
                    }
                    for ($k = 0; $k < $numGlyphIndexArray; ++$k) {
                        $glyphIndexArray[$k] = StaticMethods::_getUSHORT($font, $offset);
                        $offset              += 2;
                    }
                    for ($i = 0; $i < 256; ++$i) {
                        $k = $subHeaderKeys[$i];
                        if ($k == 0) {
                            // one byte code
                            $c = $i;
                            if (isset($subsetchars[$c])) {
                                $g                = $glyphIndexArray[0];
                                $subsetglyphs[$g] = true;
                            }
                        }
                        else {
                            // two bytes code
                            $start_byte = $subHeaders[$k]['firstCode'];
                            $end_byte   = $start_byte + $subHeaders[$k]['entryCount'];
                            for ($j = $start_byte; $j < $end_byte; ++$j) {
                                // combine high and low bytes
                                $c = (($i << 8) + $j);
                                if (isset($subsetchars[$c])) {
                                    $idRangeOffset = ($subHeaders[$k]['idRangeOffset'] + $j - $subHeaders[$k]['firstCode']);
                                    $g             = ($glyphIndexArray[$idRangeOffset] + $subHeaders[$k]['idDelta']) % 65536;
                                    if ($g < 0) {
                                        $g = 0;
                                    }
                                    $subsetglyphs[$g] = true;
                                }
                            }
                        }
                    }
                    break;
                }
                case 4:
                { // Format 4: Segment mapping to delta values
                    $length   = StaticMethods::_getUSHORT($font, $offset);
                    $offset   += 2;
                    $offset   += 2; // skip version/language
                    $segCount = floor(StaticMethods::_getUSHORT($font, $offset) / 2);
                    $offset   += 2;
                    $offset   += 6; // skip searchRange, entrySelector, rangeShift
                    $endCount = []; // array of end character codes for each segment
                    for ($k = 0; $k < $segCount; ++$k) {
                        $endCount[$k] = StaticMethods::_getUSHORT($font, $offset);
                        $offset       += 2;
                    }
                    $offset     += 2; // skip reservedPad
                    $startCount = []; // array of start character codes for each segment
                    for ($k = 0; $k < $segCount; ++$k) {
                        $startCount[$k] = StaticMethods::_getUSHORT($font, $offset);
                        $offset         += 2;
                    }
                    $idDelta = []; // delta for all character codes in segment
                    for ($k = 0; $k < $segCount; ++$k) {
                        $idDelta[$k] = StaticMethods::_getUSHORT($font, $offset);
                        $offset      += 2;
                    }
                    $idRangeOffset = []; // Offsets into glyphIdArray or 0
                    for ($k = 0; $k < $segCount; ++$k) {
                        $idRangeOffset[$k] = StaticMethods::_getUSHORT($font, $offset);
                        $offset            += 2;
                    }
                    $gidlen       = (floor($length / 2) - 8 - (4 * $segCount));
                    $glyphIdArray = []; // glyph index array
                    for ($k = 0; $k < $gidlen; ++$k) {
                        $glyphIdArray[$k] = StaticMethods::_getUSHORT($font, $offset);
                        $offset           += 2;
                    }
                    for ($k = 0; $k < $segCount; ++$k) {
                        for ($c = $startCount[$k]; $c <= $endCount[$k]; ++$c) {
                            if (isset($subsetchars[$c])) {
                                if ($idRangeOffset[$k] == 0) {
                                    $g = ($idDelta[$k] + $c) % 65536;
                                }
                                else {
                                    $gid = (floor($idRangeOffset[$k] / 2) + ($c - $startCount[$k]) - ($segCount - $k));
                                    $g   = ($glyphIdArray[$gid] + $idDelta[$k]) % 65536;
                                }
                                if ($g < 0) {
                                    $g = 0;
                                }
                                $subsetglyphs[$g] = true;
                            }
                        }
                    }
                    break;
                }
                case 6:
                { // Format 6: Trimmed table mapping
                    $offset     += 4; // skip length and version/language
                    $firstCode  = StaticMethods::_getUSHORT($font, $offset);
                    $offset     += 2;
                    $entryCount = StaticMethods::_getUSHORT($font, $offset);
                    $offset     += 2;
                    for ($k = 0; $k < $entryCount; ++$k) {
                        $c = ($k + $firstCode);
                        if (isset($subsetchars[$c])) {
                            $g                = StaticMethods::_getUSHORT($font, $offset);
                            $subsetglyphs[$g] = true;
                        }
                        $offset += 2;
                    }
                    break;
                }
                case 8:
                { // Format 8: Mixed 16-bit and 32-bit coverage
                    $offset += 10; // skip reserved, length and version/language
                    for ($k = 0; $k < 8192; ++$k) {
                        $is32[$k] = StaticMethods::byte($font, $offset);
                        ++$offset;
                    }
                    $nGroups = StaticMethods::_getULONG($font, $offset);
                    $offset  += 4;
                    for ($i = 0; $i < $nGroups; ++$i) {
                        $startCharCode = StaticMethods::_getULONG($font, $offset);
                        $offset        += 4;
                        $endCharCode   = StaticMethods::_getULONG($font, $offset);
                        $offset        += 4;
                        $startGlyphID  = StaticMethods::_getULONG($font, $offset);
                        $offset        += 4;
                        for ($k = $startCharCode; $k <= $endCharCode; ++$k) {
                            $is32idx = floor($c / 8);
                            if ((isset($is32[$is32idx])) and (($is32[$is32idx] & (1 << (7 - ($c % 8)))) == 0)) {
                                $c = $k;
                            }
                            else {
                                // 32 bit format
                                // convert to decimal (http://www.unicode.org/faq//utf_bom.html#utf16-4)
                                //LEAD_OFFSET = (0xD800 - (0x10000 >> 10)) = 55232
                                //SURROGATE_OFFSET = (0x10000 - (0xD800 << 10) - 0xDC00) = -56613888
                                $c = ((55232 + ($k >> 10)) << 10) + (0xDC00 + ($k & 0x3FF)) - 56613888;
                            }
                            if (isset($subsetchars[$c])) {
                                $subsetglyphs[$startGlyphID] = true;
                            }
                            ++$startGlyphID;
                        }
                    }
                    break;
                }
                case 10:
                { // Format 10: Trimmed array
                    $offset        += 10; // skip reserved, length and version/language
                    $startCharCode = StaticMethods::_getULONG($font, $offset);
                    $offset        += 4;
                    $numChars      = StaticMethods::_getULONG($font, $offset);
                    $offset        += 4;
                    for ($k = 0; $k < $numChars; ++$k) {
                        $c = ($k + $startCharCode);
                        if (isset($subsetchars[$c])) {
                            $g                = StaticMethods::_getUSHORT($font, $offset);
                            $subsetglyphs[$g] = true;
                        }
                        $offset += 2;
                    }
                    break;
                }
                case 12:
                { // Format 12: Segmented coverage
                    $offset  += 10; // skip length and version/language
                    $nGroups = StaticMethods::_getULONG($font, $offset);
                    $offset  += 4;
                    for ($k = 0; $k < $nGroups; ++$k) {
                        $startCharCode  = StaticMethods::_getULONG($font, $offset);
                        $offset         += 4;
                        $endCharCode    = StaticMethods::_getULONG($font, $offset);
                        $offset         += 4;
                        $startGlyphCode = StaticMethods::_getULONG($font, $offset);
                        $offset         += 4;
                        for ($c = $startCharCode; $c <= $endCharCode; ++$c) {
                            if (isset($subsetchars[$c])) {
                                $subsetglyphs[$startGlyphCode] = true;
                            }
                            ++$startGlyphCode;
                        }
                    }
                    break;
                }
                case 13:
                { // Format 13: Many-to-one range mappings
                    // to be implemented ...
                    break;
                }
                case 14:
                { // Format 14: Unicode Variation Sequences
                    // to be implemented ...
                    break;
                }
            }
        }
        // include all parts of composite glyphs
        $new_sga = $subsetglyphs;
        while (!empty($new_sga)) {
            $sga     = $new_sga;
            $new_sga = [];
            foreach ($sga as $key => $val) {
                if (isset($indexToLoc[$key])) {
                    $offset           = ($table['glyf']['offset'] + $indexToLoc[$key]);
                    $numberOfContours = StaticMethods::_getSHORT($font, $offset);
                    $offset           += 2;
                    if ($numberOfContours < 0) { // composite glyph
                        $offset += 8; // skip xMin, yMin, xMax, yMax
                        do {
                            $flags      = StaticMethods::_getUSHORT($font, $offset);
                            $offset     += 2;
                            $glyphIndex = StaticMethods::_getUSHORT($font, $offset);
                            $offset     += 2;
                            if (!isset($subsetglyphs[$glyphIndex])) {
                                // add missing glyphs
                                $new_sga[$glyphIndex] = true;
                            }
                            // skip some bytes by case
                            if ($flags & 1) {
                                $offset += 4;
                            }
                            else {
                                $offset += 2;
                            }
                            if ($flags & 8) {
                                $offset += 2;
                            }
                            elseif ($flags & 64) {
                                $offset += 4;
                            }
                            elseif ($flags & 128) {
                                $offset += 8;
                            }
                        } while ($flags & 32);
                    }
                }
            }
            $subsetglyphs += $new_sga;
        }
        // sort glyphs by key (and remove duplicates)
        ksort($subsetglyphs);
        // build new glyf and loca tables
        $glyf        = '';
        $loca        = '';
        $offset      = 0;
        $glyf_offset = $table['glyf']['offset'];
        for ($i = 0; $i < $tot_num_glyphs; ++$i) {
            if (isset($subsetglyphs[$i])) {
                $length = ($indexToLoc[($i + 1)] - $indexToLoc[$i]);
                $glyf   .= substr($font, ($glyf_offset + $indexToLoc[$i]), $length);
            }
            else {
                $length = 0;
            }
            if ($short_offset) {
                $loca .= pack('n', floor($offset / 2));
            }
            else {
                $loca .= pack('N', $offset);
            }
            $offset += $length;
        }
        // array of table names to preserve (loca and glyf tables will be added later)
        // the cmap table is not needed and shall not be present, since the mapping from character codes to glyph descriptions is provided separately
        $table_names = ['head', 'hhea', 'hmtx', 'maxp', 'cvt ', 'fpgm', 'prep']; // minimum required table names
        // get the tables to preserve
        $offset = 12;
        foreach ($table as $tag => $val) {
            if (in_array($tag, $table_names)) {
                $table[$tag]['data'] = substr($font, $table[$tag]['offset'], $table[$tag]['length']);
                if ($tag == 'head') {
                    // set the checkSumAdjustment to 0
                    $table[$tag]['data'] = substr($table[$tag]['data'], 0, 8) . "\x0\x0\x0\x0" . substr($table[$tag]['data'], 12);
                }
                $table[$tag]['offset'] = $offset;
                $offset                += $table[$tag]['length'];
                $numPad                = ($offset + 3 & ~3) - $offset;
                if ($numPad > 0) {
                    $table[$tag]['data'] .= str_repeat("\x0", $numPad);
                    $offset              += $numPad;
                }
                // check sum is not changed (so keep the following line commented)
                //$table[$tag]['checkSum'] = self::_getTTFtableChecksum($table[$tag]['data'], $table[$tag]['length'] + $numPad);
            }
            else {
                unset($table[$tag]);
            }
        }
        // add loca
        $table['loca']           = [];
        $table['loca']['data']   = $loca;
        $table['loca']['length'] = strlen($loca);
        $table['loca']['offset'] = $offset;
        $offset                  += $table['loca']['length'];
        $numPad                  = ($offset + 3 & ~3) - $offset;
        if ($numPad > 0) {
            $table['loca']['data'] .= str_repeat("\x0", $numPad);
            $offset                += $numPad;
        }
        $table['loca']['checkSum'] = self::_getTTFtableChecksum($table['loca']['data'], $table['loca']['length'] + $numPad);
        // add glyf
        $table['glyf']           = [];
        $table['glyf']['data']   = $glyf;
        $table['glyf']['length'] = strlen($glyf);
        $table['glyf']['offset'] = $offset;
        $offset                  += $table['glyf']['length'];
        $numPad                  = ($offset + 3 & ~3) - $offset;
        if ($numPad > 0) {
            $table['glyf']['data'] .= str_repeat("\x0", $numPad);
            $offset                += $numPad;
        }
        $table['glyf']['checkSum'] = self::_getTTFtableChecksum($table['glyf']['data'], $table['glyf']['length'] + $numPad);
        // rebuild font
        $font          = '';
        $font          .= pack('N', 0x10000); // sfnt version
        $numTables     = count($table);
        $font          .= pack('n', $numTables); // numTables
        $entrySelector = floor(log($numTables, 2));
        $searchRange   = pow(2, $entrySelector) * 16;
        $rangeShift    = ($numTables * 16) - $searchRange;
        $font          .= pack('n', $searchRange); // searchRange
        $font          .= pack('n', $entrySelector); // entrySelector
        $font          .= pack('n', $rangeShift); // rangeShift
        $offset        = ($numTables * 16);
        foreach ($table as $tag => $data) {
            $font .= $tag; // tag
            $font .= pack('N', $data['checkSum']); // checkSum
            $font .= pack('N', ($data['offset'] + $offset)); // offset
            $font .= pack('N', $data['length']); // length
        }
        foreach ($table as $data) {
            $font .= $data['data'];
        }
        // set checkSumAdjustment on head table
        $checkSumAdjustment = 0xB1B0AFBA - self::_getTTFtableChecksum($font, strlen($font));
        $font               = substr($font, 0, $table['head']['offset'] + $offset + 8) . pack('N', $checkSumAdjustment) . substr($font, $table['head']['offset'] + $offset + 12);
        return $font;
    }

    /**
     * Outputs font widths
     * @param array $font      font data
     * @param int   $cidoffset offset for CID values
     * @return string PDF command string for font widths
     */
    public static function _putfontwidths($font, $cidoffset = 0)
    {
        ksort($font['cw']);
        $rangeid   = 0;
        $range     = [];
        $prevcid   = -2;
        $prevwidth = -1;
        $interval  = false;
        // for each character
        foreach ($font['cw'] as $cid => $width) {
            $cid -= $cidoffset;
            if ($font['subset'] and (!isset($font['subsetchars'][$cid]))) {
                // ignore the unused characters (font subsetting)
                continue;
            }
            if ($width != $font['dw']) {
                if ($cid == ($prevcid + 1)) {
                    // consecutive CID
                    if ($width == $prevwidth) {
                        if ($width == $range[$rangeid][0]) {
                            $range[$rangeid][] = $width;
                        }
                        else {
                            array_pop($range[$rangeid]);
                            // new range
                            $rangeid           = $prevcid;
                            $range[$rangeid]   = [];
                            $range[$rangeid][] = $prevwidth;
                            $range[$rangeid][] = $width;
                        }
                        $interval                    = true;
                        $range[$rangeid]['interval'] = true;
                    }
                    else {
                        if ($interval) {
                            // new range
                            $rangeid           = $cid;
                            $range[$rangeid]   = [];
                            $range[$rangeid][] = $width;
                        }
                        else {
                            $range[$rangeid][] = $width;
                        }
                        $interval = false;
                    }
                }
                else {
                    // new range
                    $rangeid           = $cid;
                    $range[$rangeid]   = [];
                    $range[$rangeid][] = $width;
                    $interval          = false;
                }
                $prevcid   = $cid;
                $prevwidth = $width;
            }
        }
        // optimize ranges
        $prevk   = -1;
        $nextk   = -1;
        $prevint = false;
        foreach ($range as $k => $ws) {
            $cws = count($ws);
            if (($k == $nextk) and (!$prevint) and ((!isset($ws['interval'])) or ($cws < 4))) {
                if (isset($range[$k]['interval'])) {
                    unset($range[$k]['interval']);
                }
                $range[$prevk] = array_merge($range[$prevk], $range[$k]);
                unset($range[$k]);
            }
            else {
                $prevk = $k;
            }
            $nextk = $k + $cws;
            if (isset($ws['interval'])) {
                if ($cws > 3) {
                    $prevint = true;
                }
                else {
                    $prevint = false;
                }
                if (isset($range[$k]['interval'])) {
                    unset($range[$k]['interval']);
                }
                --$nextk;
            }
            else {
                $prevint = false;
            }
        }
        // output data
        $w = '';
        foreach ($range as $k => $ws) {
            if (count(array_count_values($ws)) == 1) {
                // interval mode is more compact
                $w .= ' ' . $k . ' ' . ($k + count($ws) - 1) . ' ' . $ws[0];
            }
            else {
                // range mode
                $w .= ' ' . $k . ' [ ' . implode(' ', $ws) . ' ]';
            }
        }
        return '/W [' . $w . ' ]';
    }

    /**
     * Update the CIDToGIDMap string with a new value.
     * @param string $map CIDToGIDMap.
     * @param int    $cid CID value.
     * @param int    $gid GID value.
     * @return string CIDToGIDMap.
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
     * Get a reference font size.
     * @param string $size    String containing font size value.
     * @param float  $refsize Reference font size in points.
     * @return float value in points
     */
    public static function getFontRefSize($size, $refsize = 12)
    {
        switch ($size) {
            case 'xx-small':
            {
                $size = ($refsize - 4);
                break;
            }
            case 'x-small':
            {
                $size = ($refsize - 3);
                break;
            }
            case 'small':
            {
                $size = ($refsize - 2);
                break;
            }
            case 'medium':
            {
                $size = $refsize;
                break;
            }
            case 'large':
            {
                $size = ($refsize + 2);
                break;
            }
            case 'x-large':
            {
                $size = ($refsize + 4);
                break;
            }
            case 'xx-large':
            {
                $size = ($refsize + 6);
                break;
            }
            case 'smaller':
            {
                $size = ($refsize - 3);
                break;
            }
            case 'larger':
            {
                $size = ($refsize + 3);
                break;
            }
        }
        return $size;
    }
}