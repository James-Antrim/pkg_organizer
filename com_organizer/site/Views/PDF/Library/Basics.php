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
    /** Static cache used for speed up unicodeOrd performances */
    protected array $utf8Map = [];

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
    public static function unicodeCharacter(int $value, bool $isUnicode = true): string
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
    public function unicodeOrd(string $character): int
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
     * @param int      $start (inclusive)
     * @param int      $end   (exclusive)
     * @return string
     */
    public function unicodeSliceToString(array $characters, int $start = 0, int $end = 0): string
    {
        $end    = $end ?: count($characters);
        $return = '';
        for ($index = $start; $index < $end; ++$index) {
            $return .= $characters[$index];
        }
        return $return;
    }

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

    /**
     * Converts UTF-8 character values array to array of Latin1 character values array.
     * @param array $utf8Values
     * @return array
     */
    public static function utf8ArrayToLatin1Array(array $utf8Values): array
    {
        $latin1Values = [];
        foreach ($utf8Values as $char) {
            if ($char < 256) {
                $latin1Values[] = $char;
            }
            elseif (array_key_exists($char, self::UTF_LATIN1)) {
                $latin1Values[] = self::UTF_LATIN1[$char];
            }
            elseif ($char !== 0xFFFD) {
                $latin1Values[] = 63; // '?' character
            }
        }
        return $latin1Values;
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
            elseif (array_key_exists($value, self::UTF_LATIN1)) {
                // map from UTF-8
                $return .= chr(self::UTF_LATIN1[$value]);
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
     * Convert an array of UTF8 values to array of Unicode characters.
     * @param array $utf8Values
     * @param bool  $isUnicode
     * @return array
     */
    public function utf8ArrayToUnicodeArray(array $utf8Values, bool $isUnicode = true): array
    {
        return $isUnicode ? array_map([$this, 'unicodeCharacter'], $utf8Values) : array_map([$this, 'unicodeByASCII'], $utf8Values);
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
                $value  -= 0x10000;
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
     * Extract a slice of the $utf8Values array converted to characters and returned as a string.
     * @param int[] $utf8Values
     * @param int   $start (inclusive)
     * @param int   $end   (exclusive)
     * @param bool  $isUnicode
     * @return string
     */
    public function utf8SliceToString(array $utf8Values, int $start = 0, int $end = 0, bool $isUnicode = true): string
    {
        $end    = $end > 0 ? $end : count($utf8Values);
        $string = '';
        for ($index = $start; $index < $end; ++$index) {
            $string .= $this->unicodeCharacter($utf8Values[$index], $isUnicode);
        }
        return $string;
    }

    /**
     * Converts UTF-8 strings to codepoints (character values) array. Invalid byte sequences will be replaced with 0xFFFD (replacement character).
     * @param string $string
     * @return array
     */
    public function utf8StringToArray(string $string): array
    {
        if ($this->isUnicode) {
            // Normal PHP preg_split does some wild shit here.
            $characters = StaticMethods::pregSplit('//', 'u', $string, -1, PREG_SPLIT_NO_EMPTY);
            $numbers    = array_map([$this, 'unicodeOrd'], $characters);
        }
        else {
            $characters = str_split($string);
            $numbers    = array_map('ord', $characters);
        }

        $this->currentFont['subsetCharacters'] = is_array($this->currentFont['subsetCharacters']) ?
            $this->currentFont['subsetCharacters'] + array_fill_keys($numbers, true) : array_merge($this->currentFont['subsetCharacters'], $numbers);
        return $numbers;
    }

    /**
     * Converts UTF-8 strings to Latin1 strings when using the standard 14 core fonts.
     * @param string $string
     * @return string
     */
    public function utf8ToLatin1(string $string): string
    {
        return $this->utf8ArrayToLatin1String($this->utf8StringToArray($string));
    }

    /**
     * Converts UTF-8 strings to UTF16-BE string.
     * @param string $string
     * @param bool   $setBOM if true set the Byte Order Mark (BOM = 0xFEFF)
     * @return string
     */
    public function utf8ToUTF16BE(string $string, bool $setBOM = false): string
    {
        if (!$this->isUnicode) {
            return $string;
        }

        return $this->utf8ArrayToUTF16BEString($this->utf8StringToArray($string), $setBOM);
    }


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED

}