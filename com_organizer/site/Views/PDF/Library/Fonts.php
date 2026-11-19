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

/** Unicode data and font methods. */
trait Fonts
{
    /** List of fonts used on form fields (fontname => fontKey) */
    protected array $annotationFonts = [];

    /** Current font configuration */
    protected array $currentFont = [];

    /** Current font ascent (distance between font top and baseline) */
    protected float $fontAscent;

    protected int $fontCount = 0;

    /** Current font descent (distance between font bottom and baseline) */
    protected float $fontDescent;

    /** Current font family.*/
    protected string $fontFamily = self::DEFAULT_FONT;

    protected array $fontFiles = [];

    /** Redundant? array of font names */
    protected array $fontKeys = [];

    /** List of available fonts on filesystem. */
    protected array $fontList = [];

    protected array $fontObjectIDs = [];

    /** Array of used fonts. */
    protected array $fonts = [];

    /** Current font size in user configured units.*/
    protected float $fontSize;

    /** Current font size in points.*/
    protected float $fontSizePT = 12;

    /** Increases or decreases the space between characters in a text by the specified amount (tracking). */
    protected float $fontSpacing = 0;

    /** Percentage of character stretching. */
    protected float $fontStretching = 100;

    /** Current font style.*/
    protected string $fontStyle = '';

    protected bool $fontSubsetting = true;

    protected array $footerFont = ['helvetica', '', 8];

    protected array $headerFont = [self::DEFAULT_FONT, '', 10];

    /** Boolean flag set to true when the input text is Unicode (require Unicode fonts). */
    protected bool $isUnicode = true;

    /**
     * Getter / setter for font buffer content
     * @param string     $font font key
     * @param array|null $data font data
     * @return array|false
     */
    protected function fontBuffer(string $font, array|null $data = null): array|false
    {
        if (!is_null($data)) {
            $this->fonts[$font] = $data;

            if (!in_array($font, $this->fontKeys)) {
                $this->fontKeys[] = $font;

                // store object ID for current font
                ++$this->objectNumber;
                $this->fontObjectIDs[$font] = $this->objectNumber;
                $this->fontSubBuffer($font, 'n', $this->objectNumber);
            }
        }

        return $this->fonts[$font] ?? false;
    }

    /**
     * Returns the first valid font family name from a (potentially) comma-separated list of fonts in a string.
     * @param string $fonts property string containing font family names
     * @return string
     */
    public function fontFamily(string $fonts = ''): string
    {
        // Remove spaces and symbols
        $fonts = preg_replace('/[^a-z0-9_,]/', '', strtolower($fonts));

        // Separate potential multiple font declarations
        $fonts = explode(',', $fonts);

        foreach ($fonts as $font) {

            // Replace font variations
            $font = preg_replace('/regular$/', '', $font);
            $font = preg_replace('/italic$/', 'I', $font);
            $font = preg_replace('/oblique$/', 'I', $font);
            $font = preg_replace('/bold([I]?)$/', 'B\\1', $font);

            // Replace common family names and core fonts
            $pattern     = ['/^serif|^cursive|^fantasy|^timesnewroman/', '/^sansserif/', '/^monospace/'];
            $replacement = ['times', 'helvetica', 'courier'];

            $font = preg_replace($pattern, $replacement, $font);

            // Stop on first valid font
            if (in_array(strtolower($font), $this->fontList) or in_array($font, $this->fontKeys)) {
                return $font;
            }
        }

        return $this->currentFont['fontKey'];
    }

    /**
     * Convert a string containing font size and unit or measure to user's units or points.
     * @param string $fontSize
     * @param float  $referenceValue (points)
     * @param string $unit
     * @param bool   $points         true if return value should be in points
     * @return float
     * @see self::WEB_UNITS
     */
    public function fontSizeToUnits(string $fontSize, float $referenceValue = 1, string $unit = self::PIXEL, bool $points = false): float
    {
        $k     = $points ? 1 : self::FACTOR;
        $unit  = in_array($unit, self::WEB_UNITS) ? $unit : self::PIXEL;
        $value = 0;

        if (is_numeric($fontSize)) {
            $value = (float) $fontSize;
        }
        elseif (preg_match('/([0-9.\-+]+) ?([a-z%]+)?/', $fontSize, $matches)) {
            $value = (float) $matches[1];
            if (isset($matches[2]) and in_array($matches[2], self::WEB_UNITS)) {
                $unit = $matches[2];
            }
        }

        return match ($unit) {
            self::PERCENT => ($value * $referenceValue) / 100,
            self::ELEMENT => $value * $referenceValue,
            self::RELATIVE_X => $value * ($referenceValue / 2),
            self::INCH => ($value * self::DPI) / $k,
            self::CENTIMETER => ($value / 2.54 * self::DPI) / $k,
            self::MILLIMETER => ($value / 25.4 * self::DPI) / $k,
            self::PICA => ($value * 12) / $k,
            self::POINT => $value / $k,
            self::PIXEL => $points ? $this->pixelsToUnits($value) * self::FACTOR : $this->pixelsToUnits($value),
            default => 0,
        };
    }

    /**
     * Set font sub-buffer content
     * @param string    $font font key
     * @param string    $key  font sub-key
     * @param array|int $data font data
     */
    protected function fontSubBuffer(string $font, string $key, array|int $data): void
    {
        if (!isset($this->fonts[$font])) {
            $this->fontBuffer($font, []);
        }

        $this->fonts[$font][$key] = $data;
    }

    /**
     * Getter / setter for the footer font, mostly to ensure the correct configuration type.
     * @param array $fontConfiguration basic font parameters: [string family, array|string style, float size]
     * @return array
     */
    public function footerFont(array $fontConfiguration = []): array
    {
        if ($fontConfiguration) {
            $this->footerFont = $fontConfiguration;
        }

        return $this->footerFont;
    }

    /**
     * Getter / setter for the header font, mostly to ensure the correct configuration type.
     * @param array $fontConfiguration basic font parameters: [string family, array|string style, float size]
     * @return array
     */
    public function headerFont(array $fontConfiguration = []): array
    {
        if ($fontConfiguration) {
            $this->headerFont = $fontConfiguration;
        }

        return $this->headerFont;
    }

    /**
     * Getter / setter for the font size, returns the font size value in user units.
     * @param float|null $size   (points)
     * @param bool       $append whether to append the font size command
     * @return float
     */
    public function fontSize(float|null $size = null, bool $append = true): float
    {
        if (is_float($size)) {
            $this->fontSize   = $size / self::FACTOR;
            $this->fontSizePT = $size;

            // calculate some font metrics
            if (isset($this->currentFont['desc']['FontBBox'])) {
                $bbox   = explode(' ', substr($this->currentFont['desc']['FontBBox'], 1, -1));
                $height = ((intval($bbox[3]) - intval($bbox[1])) * $size / 1000);
            }
            else {
                $height = $size * 1.219;
            }

            $ascent = $descent = 0;
            if (isset($this->currentFont['desc']['Ascent']) and ($this->currentFont['desc']['Ascent'] > 0)) {
                $ascent = ($this->currentFont['desc']['Ascent'] * $size / 1000);
            }

            if (isset($this->currentFont['desc']['Descent']) and ($this->currentFont['desc']['Descent'] <= 0)) {
                $descent = (-$this->currentFont['desc']['Descent'] * $size / 1000);
            }

            if (empty($ascent) and empty($descent)) {
                // core font
                $ascent  = 0.76 * $height;
                $descent = $height - $ascent;
            }
            elseif (empty($ascent)) {
                $ascent = $height - $descent;
            }
            elseif (empty($descent)) {
                $descent = $height - $ascent;
            }

            $this->fontAscent  = $ascent / self::FACTOR;
            $this->fontDescent = $descent / self::FACTOR;

            if ($append and $this->page > 0 and isset($this->currentFont['i']) and $this->state === self::READY) {
                $this->append(sprintf('BT /F%d %F Tf ET', $this->currentFont['i'], $this->fontSizePT));
            }
        }

        return $this->fontSize;
    }

    /**
     * Returns the current font size in points.
     * @return float
     */
    public function fontSizePT(): float
    {
        return $this->fontSizePT;
    }

    /**
     * Returns the current font style.
     * @return string current font style
     */
    public function fontStyle(): string
    {
        return $this->fontStyle;
    }

    /** Fills the $fontList with available fonts. */
    protected function populateFonts(): void
    {
        if (($fonts = opendir(self::FONT_PATH)) !== false) {
            while (($file = readdir($fonts)) !== false) {
                if (str_ends_with($file, '.php')) {
                    $this->fontList[] = strtolower(basename($file, '.php'));
                }
            }
            closedir($fonts);
        }
    }

    /**
     * Sets the current font used to print character strings.
     * @param string     $family
     * @param string     $style
     * @param float|null $size   points, default current
     * @param string     $file
     * @param mixed      $subset if true embed only a subset of the font (stores only the information related to the used characters); if false embed full font; if 'default' uses the default value set using setFontSubsetting(). This option is valid only for TrueTypeUnicode fonts. If you want to enable users to change the document, set this parameter to false. If you subset the font, the person who receives your PDF would need to have your same font in order to make changes to your PDF. The file size of the PDF would also be smaller because you are embedding only part of a font.
     * @param bool       $append appends the command to the document, defaults to true
     * @see    self::CORE_FONTS, AddFont(), self::FONT_STYLES, fontSize()
     */
    public function setCurrentFont(string $family, string $style = self::REGULAR, float|null $size = null, string $file = '', bool|string $subset = 'default', bool $append = true): void
    {
        $size = $size === null ? $this->fontSizePT : $size;
        $size = max($size, 0);

        // try to add font (if not already added)
        $font = $this->AddFont($family, $style, $file, $subset);

        $this->fontFamily = $font['family'];
        $this->fontStyle  = $font['style'];

        if (isset($this->currentFont['fontKey']) and isset($this->currentFont['subsetCharacters'])) {
            // save subset chars of the previous font
            $this->fontSubBuffer($this->currentFont['fontKey'], 'subsetCharacters', $this->currentFont['subsetCharacters']);
        }

        $this->currentFont = $this->fontBuffer($font['fontKey']);
        $this->fontSize($size, $append);
    }

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


    /**
     * Default monospace font.
     */
    protected $default_monospaced_font = 'courier';

    /**
     * Returns a subset of the TrueType font data without the unused glyphs.
     * @param string $font       TrueType font data.
     * @param array  $characters used characters (glyphs to keep)
     * @return string A subset of TrueType font data without the unused glyphs.
     * @author Nicola Asuni
     */
    public function trueTypeSubset(string $font, array $characters): string
    {
        ksort($characters);
        $offset = 0; // offset position of the font data
        if ($this->unsignedLong($font, $offset) != 0x10000) {
            // sfnt version must be 0x00010000 for TrueType version 1.0.
            return $font;
        }
        $c      = 0;
        $offset += 4;
        // get number of tables
        $numTables = $this->unsignedShort($font, $offset);
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
            $table[$tag]['checkSum'] = $this->unsignedLong($font, $offset);
            $offset                  += 4;
            $table[$tag]['offset']   = $this->unsignedLong($font, $offset);
            $offset                  += 4;
            $table[$tag]['length']   = $this->unsignedLong($font, $offset);
            $offset                  += 4;
        }
        // check magicNumber
        $offset = $table['head']['offset'] + 12;
        if ($this->unsignedLong($font, $offset) != 0x5F0F3CF5) {
            // magicNumber must be 0x5F0F3CF5
            return $font;
        }
        $offset += 4;
        // get offset mode (indexToLocFormat : 0 = short, 1 = long)
        $offset       = $table['head']['offset'] + 50;
        $short_offset = ($this->signedShort($font, $offset) == 0);
        $offset       += 2;
        // get the offsets to the locations of the glyphs in the font, relative to the beginning of the glyphData table
        $indexToLoc = [];
        $offset     = $table['loca']['offset'];
        if ($short_offset) {
            // short version
            $tot_num_glyphs = floor($table['loca']['length'] / 2); // numGlyphs + 1
            for ($i = 0; $i < $tot_num_glyphs; ++$i) {
                $indexToLoc[$i] = $this->unsignedShort($font, $offset) * 2;
                $offset         += 2;
            }
        }
        else {
            // long version
            $tot_num_glyphs = ($table['loca']['length'] / 4); // numGlyphs + 1
            for ($i = 0; $i < $tot_num_glyphs; ++$i) {
                $indexToLoc[$i] = $this->unsignedLong($font, $offset);
                $offset         += 4;
            }
        }
        // get glyphs indexes of chars from cmap table
        $subsetglyphs      = []; // glyph IDs on key
        $subsetglyphs[0]   = true; // character codes that do not correspond to any glyph in the font should be mapped to glyph index 0
        $offset            = $table['cmap']['offset'] + 2;
        $numEncodingTables = $this->unsignedShort($font, $offset);
        $offset            += 2;
        $encodingTables    = [];
        for ($i = 0; $i < $numEncodingTables; ++$i) {
            $encodingTables[$i]['platformID'] = $this->unsignedShort($font, $offset);
            $offset                           += 2;
            $encodingTables[$i]['encodingID'] = $this->unsignedShort($font, $offset);
            $offset                           += 2;
            $encodingTables[$i]['offset']     = $this->unsignedLong($font, $offset);
            $offset                           += 4;
        }
        foreach ($encodingTables as $enctable) {
            // get all platforms and encodings
            $offset = $table['cmap']['offset'] + $enctable['offset'];
            $format = $this->unsignedShort($font, $offset);
            $offset += 2;
            switch ($format) {
                case 0:
                { // Format 0: Byte encoding table
                    $offset += 4; // skip length and version/language
                    for ($c = 0; $c < 256; ++$c) {
                        if (isset($characters[$c])) {
                            $g                = $this->byte($font, $offset);
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
                        $subHeaderKeys[$i] = ($this->unsignedShort($font, $offset) / 8);
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
                        $subHeaders[$k]['firstCode']     = $this->unsignedShort($font, $offset);
                        $offset                          += 2;
                        $subHeaders[$k]['entryCount']    = $this->unsignedShort($font, $offset);
                        $offset                          += 2;
                        $subHeaders[$k]['idDelta']       = $this->unsignedShort($font, $offset);
                        $offset                          += 2;
                        $subHeaders[$k]['idRangeOffset'] = $this->unsignedShort($font, $offset);
                        $offset                          += 2;
                        $subHeaders[$k]['idRangeOffset'] -= (2 + (($numSubHeaders - $k - 1) * 8));
                        $subHeaders[$k]['idRangeOffset'] /= 2;
                        $numGlyphIndexArray              += $subHeaders[$k]['entryCount'];
                    }
                    for ($k = 0; $k < $numGlyphIndexArray; ++$k) {
                        $glyphIndexArray[$k] = $this->unsignedShort($font, $offset);
                        $offset              += 2;
                    }
                    for ($i = 0; $i < 256; ++$i) {
                        $k = $subHeaderKeys[$i];
                        if ($k == 0) {
                            // one byte code
                            $c = $i;
                            if (isset($characters[$c])) {
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
                                if (isset($characters[$c])) {
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
                    $length   = $this->unsignedShort($font, $offset);
                    $offset   += 2;
                    $offset   += 2; // skip version/language
                    $segCount = floor($this->unsignedShort($font, $offset) / 2);
                    $offset   += 2;
                    $offset   += 6; // skip searchRange, entrySelector, rangeShift
                    $endCount = []; // array of end character codes for each segment
                    for ($k = 0; $k < $segCount; ++$k) {
                        $endCount[$k] = $this->unsignedShort($font, $offset);
                        $offset       += 2;
                    }
                    $offset     += 2; // skip reservedPad
                    $startCount = []; // array of start character codes for each segment
                    for ($k = 0; $k < $segCount; ++$k) {
                        $startCount[$k] = $this->unsignedShort($font, $offset);
                        $offset         += 2;
                    }
                    $idDelta = []; // delta for all character codes in segment
                    for ($k = 0; $k < $segCount; ++$k) {
                        $idDelta[$k] = $this->unsignedShort($font, $offset);
                        $offset      += 2;
                    }
                    $idRangeOffset = []; // Offsets into glyphIdArray or 0
                    for ($k = 0; $k < $segCount; ++$k) {
                        $idRangeOffset[$k] = $this->unsignedShort($font, $offset);
                        $offset            += 2;
                    }
                    $gidlen       = (floor($length / 2) - 8 - (4 * $segCount));
                    $glyphIdArray = []; // glyph index array
                    for ($k = 0; $k < $gidlen; ++$k) {
                        $glyphIdArray[$k] = $this->unsignedShort($font, $offset);
                        $offset           += 2;
                    }
                    for ($k = 0; $k < $segCount; ++$k) {
                        for ($c = $startCount[$k]; $c <= $endCount[$k]; ++$c) {
                            if (isset($characters[$c])) {
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
                    $firstCode  = $this->unsignedShort($font, $offset);
                    $offset     += 2;
                    $entryCount = $this->unsignedShort($font, $offset);
                    $offset     += 2;
                    for ($k = 0; $k < $entryCount; ++$k) {
                        $c = ($k + $firstCode);
                        if (isset($characters[$c])) {
                            $g                = $this->unsignedShort($font, $offset);
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
                        $is32[$k] = $this->byte($font, $offset);
                        ++$offset;
                    }
                    $nGroups = $this->unsignedLong($font, $offset);
                    $offset  += 4;
                    for ($i = 0; $i < $nGroups; ++$i) {
                        $startCharCode = $this->unsignedLong($font, $offset);
                        $offset        += 4;
                        $endCharCode   = $this->unsignedLong($font, $offset);
                        $offset        += 4;
                        $startGlyphID  = $this->unsignedLong($font, $offset);
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
                            if (isset($characters[$c])) {
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
                    $startCharCode = $this->unsignedLong($font, $offset);
                    $offset        += 4;
                    $numChars      = $this->unsignedLong($font, $offset);
                    $offset        += 4;
                    for ($k = 0; $k < $numChars; ++$k) {
                        $c = ($k + $startCharCode);
                        if (isset($characters[$c])) {
                            $g                = $this->unsignedShort($font, $offset);
                            $subsetglyphs[$g] = true;
                        }
                        $offset += 2;
                    }
                    break;
                }
                case 12:
                { // Format 12: Segmented coverage
                    $offset  += 10; // skip length and version/language
                    $nGroups = $this->unsignedLong($font, $offset);
                    $offset  += 4;
                    for ($k = 0; $k < $nGroups; ++$k) {
                        $startCharCode  = $this->unsignedLong($font, $offset);
                        $offset         += 4;
                        $endCharCode    = $this->unsignedLong($font, $offset);
                        $offset         += 4;
                        $startGlyphCode = $this->unsignedLong($font, $offset);
                        $offset         += 4;
                        for ($c = $startCharCode; $c <= $endCharCode; ++$c) {
                            if (isset($characters[$c])) {
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
                    $numberOfContours = $this->signedShort($font, $offset);
                    $offset           += 2;
                    if ($numberOfContours < 0) { // composite glyph
                        $offset += 8; // skip xMin, yMin, xMax, yMax
                        do {
                            $flags      = $this->unsignedShort($font, $offset);
                            $offset     += 2;
                            $glyphIndex = $this->unsignedShort($font, $offset);
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


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Return fonts path
     * @return string
     * @public static
     */
    public static function _getfontpath()
    {
        if (!defined('K_PATH_FONTS') and is_dir($fdir = realpath(dirname(__FILE__) . '/../fonts'))) {
            if (substr($fdir, -1) != '/') {
                $fdir .= '/';
            }
            define('K_PATH_FONTS', $fdir);
        }
        return defined('K_PATH_FONTS') ? K_PATH_FONTS : '';
    }

    /**
     * Returns a subset of the TrueType font data without the unused glyphs.
     * @param string $font        TrueType font data.
     * @param array  $subsetchars Array of used characters (the glyphs to keep).
     * @return string A subset of TrueType font data without the unused glyphs.
     * @author Nicola Asuni
     * @since  5.2.000 (2010-06-02)
     * @public static
     */
    public static function _getTrueTypeFontSubset($font, $subsetchars)
    {
        ksort($subsetchars);
        $offset = 0; // offset position of the font data
        if (TCPDF_STATIC::_getULONG($font, $offset) != 0x10000) {
            // sfnt version must be 0x00010000 for TrueType version 1.0.
            return $font;
        }
        $c      = 0;
        $offset += 4;
        // get number of tables
        $numTables = TCPDF_STATIC::_getUSHORT($font, $offset);
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
            $table[$tag]['checkSum'] = TCPDF_STATIC::_getULONG($font, $offset);
            $offset                  += 4;
            $table[$tag]['offset']   = TCPDF_STATIC::_getULONG($font, $offset);
            $offset                  += 4;
            $table[$tag]['length']   = TCPDF_STATIC::_getULONG($font, $offset);
            $offset                  += 4;
        }
        // check magicNumber
        $offset = $table['head']['offset'] + 12;
        if (TCPDF_STATIC::_getULONG($font, $offset) != 0x5F0F3CF5) {
            // magicNumber must be 0x5F0F3CF5
            return $font;
        }
        $offset += 4;
        // get offset mode (indexToLocFormat : 0 = short, 1 = long)
        $offset       = $table['head']['offset'] + 50;
        $short_offset = (TCPDF_STATIC::_getSHORT($font, $offset) == 0);
        $offset       += 2;
        // get the offsets to the locations of the glyphs in the font, relative to the beginning of the glyphData table
        $indexToLoc = [];
        $offset     = $table['loca']['offset'];
        if ($short_offset) {
            // short version
            $tot_num_glyphs = floor($table['loca']['length'] / 2); // numGlyphs + 1
            for ($i = 0; $i < $tot_num_glyphs; ++$i) {
                $indexToLoc[$i] = TCPDF_STATIC::_getUSHORT($font, $offset) * 2;
                $offset         += 2;
            }
        }
        else {
            // long version
            $tot_num_glyphs = ($table['loca']['length'] / 4); // numGlyphs + 1
            for ($i = 0; $i < $tot_num_glyphs; ++$i) {
                $indexToLoc[$i] = TCPDF_STATIC::_getULONG($font, $offset);
                $offset         += 4;
            }
        }
        // get glyphs indexes of chars from cmap table
        $subsetglyphs      = []; // glyph IDs on key
        $subsetglyphs[0]   = true; // character codes that do not correspond to any glyph in the font should be mapped to glyph index 0
        $offset            = $table['cmap']['offset'] + 2;
        $numEncodingTables = TCPDF_STATIC::_getUSHORT($font, $offset);
        $offset            += 2;
        $encodingTables    = [];
        for ($i = 0; $i < $numEncodingTables; ++$i) {
            $encodingTables[$i]['platformID'] = TCPDF_STATIC::_getUSHORT($font, $offset);
            $offset                           += 2;
            $encodingTables[$i]['encodingID'] = TCPDF_STATIC::_getUSHORT($font, $offset);
            $offset                           += 2;
            $encodingTables[$i]['offset']     = TCPDF_STATIC::_getULONG($font, $offset);
            $offset                           += 4;
        }
        foreach ($encodingTables as $enctable) {
            // get all platforms and encodings
            $offset = $table['cmap']['offset'] + $enctable['offset'];
            $format = TCPDF_STATIC::_getUSHORT($font, $offset);
            $offset += 2;
            switch ($format) {
                case 0:
                { // Format 0: Byte encoding table
                    $offset += 4; // skip length and version/language
                    for ($c = 0; $c < 256; ++$c) {
                        if (isset($subsetchars[$c])) {
                            $g                = TCPDF_STATIC::_getBYTE($font, $offset);
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
                        $subHeaderKeys[$i] = (TCPDF_STATIC::_getUSHORT($font, $offset) / 8);
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
                        $subHeaders[$k]['firstCode']     = TCPDF_STATIC::_getUSHORT($font, $offset);
                        $offset                          += 2;
                        $subHeaders[$k]['entryCount']    = TCPDF_STATIC::_getUSHORT($font, $offset);
                        $offset                          += 2;
                        $subHeaders[$k]['idDelta']       = TCPDF_STATIC::_getUSHORT($font, $offset);
                        $offset                          += 2;
                        $subHeaders[$k]['idRangeOffset'] = TCPDF_STATIC::_getUSHORT($font, $offset);
                        $offset                          += 2;
                        $subHeaders[$k]['idRangeOffset'] -= (2 + (($numSubHeaders - $k - 1) * 8));
                        $subHeaders[$k]['idRangeOffset'] /= 2;
                        $numGlyphIndexArray              += $subHeaders[$k]['entryCount'];
                    }
                    for ($k = 0; $k < $numGlyphIndexArray; ++$k) {
                        $glyphIndexArray[$k] = TCPDF_STATIC::_getUSHORT($font, $offset);
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
                    $length   = TCPDF_STATIC::_getUSHORT($font, $offset);
                    $offset   += 2;
                    $offset   += 2; // skip version/language
                    $segCount = floor(TCPDF_STATIC::_getUSHORT($font, $offset) / 2);
                    $offset   += 2;
                    $offset   += 6; // skip searchRange, entrySelector, rangeShift
                    $endCount = []; // array of end character codes for each segment
                    for ($k = 0; $k < $segCount; ++$k) {
                        $endCount[$k] = TCPDF_STATIC::_getUSHORT($font, $offset);
                        $offset       += 2;
                    }
                    $offset     += 2; // skip reservedPad
                    $startCount = []; // array of start character codes for each segment
                    for ($k = 0; $k < $segCount; ++$k) {
                        $startCount[$k] = TCPDF_STATIC::_getUSHORT($font, $offset);
                        $offset         += 2;
                    }
                    $idDelta = []; // delta for all character codes in segment
                    for ($k = 0; $k < $segCount; ++$k) {
                        $idDelta[$k] = TCPDF_STATIC::_getUSHORT($font, $offset);
                        $offset      += 2;
                    }
                    $idRangeOffset = []; // Offsets into glyphIdArray or 0
                    for ($k = 0; $k < $segCount; ++$k) {
                        $idRangeOffset[$k] = TCPDF_STATIC::_getUSHORT($font, $offset);
                        $offset            += 2;
                    }
                    $gidlen       = (floor($length / 2) - 8 - (4 * $segCount));
                    $glyphIdArray = []; // glyph index array
                    for ($k = 0; $k < $gidlen; ++$k) {
                        $glyphIdArray[$k] = TCPDF_STATIC::_getUSHORT($font, $offset);
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
                    $firstCode  = TCPDF_STATIC::_getUSHORT($font, $offset);
                    $offset     += 2;
                    $entryCount = TCPDF_STATIC::_getUSHORT($font, $offset);
                    $offset     += 2;
                    for ($k = 0; $k < $entryCount; ++$k) {
                        $c = ($k + $firstCode);
                        if (isset($subsetchars[$c])) {
                            $g                = TCPDF_STATIC::_getUSHORT($font, $offset);
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
                        $is32[$k] = TCPDF_STATIC::_getBYTE($font, $offset);
                        ++$offset;
                    }
                    $nGroups = TCPDF_STATIC::_getULONG($font, $offset);
                    $offset  += 4;
                    for ($i = 0; $i < $nGroups; ++$i) {
                        $startCharCode = TCPDF_STATIC::_getULONG($font, $offset);
                        $offset        += 4;
                        $endCharCode   = TCPDF_STATIC::_getULONG($font, $offset);
                        $offset        += 4;
                        $startGlyphID  = TCPDF_STATIC::_getULONG($font, $offset);
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
                    $startCharCode = TCPDF_STATIC::_getULONG($font, $offset);
                    $offset        += 4;
                    $numChars      = TCPDF_STATIC::_getULONG($font, $offset);
                    $offset        += 4;
                    for ($k = 0; $k < $numChars; ++$k) {
                        $c = ($k + $startCharCode);
                        if (isset($subsetchars[$c])) {
                            $g                = TCPDF_STATIC::_getUSHORT($font, $offset);
                            $subsetglyphs[$g] = true;
                        }
                        $offset += 2;
                    }
                    break;
                }
                case 12:
                { // Format 12: Segmented coverage
                    $offset  += 10; // skip length and version/language
                    $nGroups = TCPDF_STATIC::_getULONG($font, $offset);
                    $offset  += 4;
                    for ($k = 0; $k < $nGroups; ++$k) {
                        $startCharCode  = TCPDF_STATIC::_getULONG($font, $offset);
                        $offset         += 4;
                        $endCharCode    = TCPDF_STATIC::_getULONG($font, $offset);
                        $offset         += 4;
                        $startGlyphCode = TCPDF_STATIC::_getULONG($font, $offset);
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
                    $numberOfContours = TCPDF_STATIC::_getSHORT($font, $offset);
                    $offset           += 2;
                    if ($numberOfContours < 0) { // composite glyph
                        $offset += 8; // skip xMin, yMin, xMax, yMax
                        do {
                            $flags      = TCPDF_STATIC::_getUSHORT($font, $offset);
                            $offset     += 2;
                            $glyphIndex = TCPDF_STATIC::_getUSHORT($font, $offset);
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
        $table_names = array('head', 'hhea', 'hmtx', 'maxp', 'cvt ', 'fpgm', 'prep'); // minimum required table names
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
     * Returs the checksum of a TTF table.
     * @param string $table  table to check
     * @param int    $length length of table in bytes
     * @return int checksum
     * @author Nicola Asuni
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
     * Output fonts.
     * @author Nicola Asuni
     */
    protected function _putfonts(): void
    {
        $nf = $this->objectNumber;
        foreach ($this->encodingDeltas as $diff) {
            //Encodings
            $this->_newobj();
            $this->append('<< /Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences [' . $diff . '] >>' . "\n" . 'endobj');
        }
        foreach ($this->fontFiles as $file => $info) {
            // search and get font file to embedd
            $fontfile = self::FONT_PATH . $file;
            if (!empty($fontfile)) {
                $font       = file_get_contents($fontfile);
                $compressed = (substr($file, -2) == '.z');
                if ((!$compressed) and (isset($info['length2']))) {
                    $header = (ord($font[0]) == 128);
                    if ($header) {
                        // strip first binary header
                        $font = substr($font, 6);
                    }
                    if ($header and (ord($font[$info['length1']]) == 128)) {
                        // strip second binary header
                        $font = substr($font, 0, $info['length1']) . substr($font, ($info['length1'] + 6));
                    }
                }
                elseif ($info['subset'] and ((!$compressed) or ($compressed and function_exists('gzcompress')))) {
                    if ($compressed) {
                        // uncompress font
                        $font = gzuncompress($font);
                    }
                    // merge subset characters
                    $subset = []; // used chars
                    foreach ($info['fontKeys'] as $fontKey) {
                        $fontinfo = $this->fontBuffer($fontKey);
                        $subset   += $fontinfo['subsetCharacters'];
                    }
                    // rebuild a font subset
                    $font = $this->trueTypeSubset($font, $subset);
                    // calculate new font length
                    $info['length1'] = strlen($font);
                    if ($compressed) {
                        // recompress font
                        $font = gzcompress($font);
                    }
                }
                $this->_newobj();
                $this->fontFiles[$file]['n'] = $this->objectNumber;
                $stream                      = $font;
                $out                         = '<< /Length ' . strlen($stream);
                if ($compressed) {
                    $out .= ' /Filter /FlateDecode';
                }
                $out .= ' /Length1 ' . $info['length1'];
                if (isset($info['length2'])) {
                    $out .= ' /Length2 ' . $info['length2'] . ' /Length3 0';
                }
                $out .= ' >>';
                $out .= ' stream' . "\n" . $stream . "\n" . 'endstream';
                $out .= "\n" . 'endobj';
                $this->append($out);
            }
        }
        foreach ($this->fontKeys as $k) {
            //Font objects
            $font = $this->fontBuffer($k);
            $type = $font['type'];
            $name = $font['name'];
            if ($type == 'core') {
                // standard core font
                $out = $this->_getobj($this->fontObjectIDs[$k]) . "\n";
                $out .= '<</Type /Font';
                $out .= ' /Subtype /Type1';
                $out .= ' /BaseFont /' . $name;
                $out .= ' /Name /F' . $font['i'];
                if ((strtolower($name) != 'symbol') and (strtolower($name) != 'zapfdingbats')) {
                    $out .= ' /Encoding /WinAnsiEncoding';
                }
                if ($k == 'helvetica') {
                    // add default font for annotations
                    $this->annotationFonts[$k] = $font['i'];
                }
                $out .= ' >>';
                $out .= "\n" . 'endobj';
                $this->append($out);
            }
            elseif (($type == 'Type1') or ($type == 'TrueType')) {
                // additional Type1 or TrueType font
                $out = $this->_getobj($this->fontObjectIDs[$k]) . "\n";
                $out .= '<</Type /Font';
                $out .= ' /Subtype /' . $type;
                $out .= ' /BaseFont /' . $name;
                $out .= ' /Name /F' . $font['i'];
                $out .= ' /FirstChar 32 /LastChar 255';
                $out .= ' /Widths ' . ($this->objectNumber + 1) . ' 0 R';
                $out .= ' /FontDescriptor ' . ($this->objectNumber + 2) . ' 0 R';
                if ($font['enc']) {
                    if (isset($font['diff'])) {
                        $out .= ' /Encoding ' . ($nf + $font['diff']) . ' 0 R';
                    }
                    else {
                        $out .= ' /Encoding /WinAnsiEncoding';
                    }
                }
                $out .= ' >>';
                $out .= "\n" . 'endobj';
                $this->append($out);
                // Widths
                $this->_newobj();
                $s = '[';
                for ($i = 32; $i < 256; ++$i) {
                    if (isset($font['cw'][$i])) {
                        $s .= $font['cw'][$i] . ' ';
                    }
                    else {
                        $s .= $font['dw'] . ' ';
                    }
                }
                $s .= ']';
                $s .= "\n" . 'endobj';
                $this->append($s);
                //Descriptor
                $this->_newobj();
                $s = '<</Type /FontDescriptor /FontName /' . $name;
                foreach ($font['desc'] as $fdk => $fdv) {
                    if (is_float($fdv)) {
                        $fdv = sprintf('%F', $fdv);
                    }
                    $s .= ' /' . $fdk . ' ' . $fdv . '';
                }
                if (!empty($font['file'])) {
                    $s .= ' /FontFile' . ($type == 'Type1' ? '' : '2') . ' ' . $this->fontFiles[$font['file']]['n'] . ' 0 R';
                }
                $s .= '>>';
                $s .= "\n" . 'endobj';
                $this->append($s);
            }
            else {
                // additional types
                $mtd = '_put' . strtolower($type);
                if (!method_exists($this, $mtd)) {
                    $this->Error('Unsupported font type: ' . $type);
                }
                $this->$mtd($font);
            }
        }
    }

    /**
     * Outputs font widths
     * @param array $font      font data
     * @param int   $cidoffset offset for CID values
     * @return string PDF command string for font widths
     * @author Nicola Asuni
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
            if ($font['subset'] and (!isset($font['subsetCharacters'][$cid]))) {
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
     * Convert and add the selected TrueType or Type1 font to the fonts folder (that must be writeable).
     * @param string  $fontfile Font file (full path).
     * @param string  $fonttype Font type. Leave empty for autodetect mode. Valid values are: TrueTypeUnicode, TrueType, Type1, CID0JP = CID-0 Japanese, CID0KR = CID-0 Korean, CID0CS = CID-0 Chinese Simplified, CID0CT = CID-0 Chinese Traditional.
     * @param string  $enc      Name of the encoding table to use. Leave empty for default mode. Omit this parameter for TrueType Unicode and symbolic fonts like Symbol or ZapfDingBats.
     * @param int     $flags    Unsigned 32-bit integer containing flags specifying various characteristics of the font (PDF32000:2008 - 9.8.2 Font Descriptor Flags): +1 for fixed font; +4 for symbol or +32 for non-symbol; +64 for italic. Fixed and Italic mode are generally autodetected so you have to set it to 32 = non-symbolic font (default) or 4 = symbolic font.
     * @param string  $outpath  Output path for generated font files (must be writeable by the web server). Leave empty for default font folder.
     * @param int     $platid   Platform ID for CMAP table to extract (when building a Unicode font for Windows this value should be 3, for Macintosh should be 1).
     * @param int     $encid    Encoding ID for CMAP table to extract (when building a Unicode font for Windows this value should be 1, for Macintosh should be 0). When Platform ID is 3, legal values for Encoding ID are: 0=Symbol, 1=Unicode, 2=ShiftJIS, 3=PRC, 4=Big5, 5=Wansung, 6=Johab, 7=Reserved, 8=Reserved, 9=Reserved, 10=UCS-4.
     * @param boolean $addcbbox If true includes the character bounding box information on the php font file.
     * @param boolean $link     If true link to system font instead of copying the font data (not transportable) - Note: do not work with Type1 fonts.
     * @return string|false TCPDF font name or boolean false in case of error.
     * @author Nicola Asuni
     * @since  5.9.123 (2010-09-30)
     * @public static
     */
    public static function addTTFfont($fontfile, $fonttype = '', $enc = '', $flags = 32, $outpath = '', $platid = 3, $encid = 1, $addcbbox = false, $link = false)
    {
        if (!TCPDF_STATIC::file_exists($fontfile)) {
            // Could not find file
            return false;
        }
        // font metrics
        $fmetric = [];
        // build new font name for TCPDF compatibility
        $font_path_parts = pathinfo($fontfile);
        if (!isset($font_path_parts['filename'])) {
            $font_path_parts['filename'] = substr($font_path_parts['basename'], 0, -(strlen($font_path_parts['extension']) + 1));
        }
        $font_name = strtolower($font_path_parts['filename']);
        $font_name = preg_replace('/[^a-z0-9_]/', '', $font_name);
        $search    = array('bold', 'oblique', 'italic', 'regular');
        $replace   = array('b', 'i', 'i', '');
        $font_name = str_replace($search, $replace, $font_name);
        if (empty($font_name)) {
            // set generic name
            $font_name = 'tcpdffont';
        }
        // set output path
        if (empty($outpath)) {
            $outpath = self::_getfontpath();
        }
        // check if this font already exist
        if (@TCPDF_STATIC::file_exists($outpath . $font_name . '.php')) {
            // this font already exist (delete it from fonts folder to rebuild it)
            return $font_name;
        }
        $fmetric['file'] = $font_name;
        $fmetric['ctg']  = $font_name . '.ctg.z';
        // get font data
        $font                    = file_get_contents($fontfile);
        $fmetric['originalsize'] = strlen($font);
        // autodetect font type
        if (empty($fonttype)) {
            if (TCPDF_STATIC::_getULONG($font, 0) == 0x10000) {
                // True Type (Unicode or not)
                $fonttype = 'TrueTypeUnicode';
            }
            elseif (substr($font, 0, 4) == 'OTTO') {
                // Open Type (Unicode or not)
                //Unsupported font format: OpenType with CFF data
                return false;
            }
            else {
                // Type 1
                $fonttype = 'Type1';
            }
        }
        // set font type
        switch ($fonttype) {
            case 'CID0CT':
            case 'CID0CS':
            case 'CID0KR':
            case 'CID0JP':
            {
                $fmetric['type'] = 'cidfont0';
                break;
            }
            case 'Type1':
            {
                $fmetric['type'] = 'Type1';
                if (empty($enc) and (($flags & 4) == 0)) {
                    $enc = 'cp1252';
                }
                break;
            }
            case 'TrueType':
            {
                $fmetric['type'] = 'TrueType';
                break;
            }
            case 'TrueTypeUnicode':
            default:
            {
                $fmetric['type'] = 'TrueTypeUnicode';
                break;
            }
        }
        // set encoding maps (if any)
        $fmetric['enc']  = preg_replace('/[^A-Za-z0-9_\-]/', '', $enc);
        $fmetric['diff'] = '';
        if (($fmetric['type'] == 'TrueType') or ($fmetric['type'] == 'Type1')) {
            if (!empty($enc) and ($enc != 'cp1252') and isset(TCPDF_FONT_DATA::$encmap[$enc])) {
                // build differences from reference encoding
                $enc_ref    = TCPDF_FONT_DATA::$encmap['cp1252'];
                $enc_target = TCPDF_FONT_DATA::$encmap[$enc];
                $last       = 0;
                for ($i = 32; $i <= 255; ++$i) {
                    if ($enc_target[$i] != $enc_ref[$i]) {
                        if ($i != ($last + 1)) {
                            $fmetric['diff'] .= $i . ' ';
                        }
                        $last            = $i;
                        $fmetric['diff'] .= '/' . $enc_target[$i] . ' ';
                    }
                }
            }
        }
        // parse the font by type
        if ($fmetric['type'] == 'Type1') {
            // ---------- TYPE 1 ----------
            // read first segment
            $a = unpack('Cmarker/Ctype/Vsize', substr($font, 0, 6));
            if ($a['marker'] != 128) {
                // Font file is not a valid binary Type1
                return false;
            }
            $fmetric['size1'] = $a['size'];
            $data             = substr($font, 6, $fmetric['size1']);
            // read second segment
            $a = unpack('Cmarker/Ctype/Vsize', substr($font, (6 + $fmetric['size1']), 6));
            if ($a['marker'] != 128) {
                // Font file is not a valid binary Type1
                return false;
            }
            $fmetric['size2'] = $a['size'];
            $encrypted        = substr($font, (12 + $fmetric['size1']), $fmetric['size2']);
            $data             .= $encrypted;
            // store compressed font
            $fmetric['file'] .= '.z';
            $fp              = TCPDF_STATIC::fopenLocal($outpath . $fmetric['file'], 'wb');
            fwrite($fp, gzcompress($data));
            fclose($fp);
            // get font info
            $fmetric['Flags'] = $flags;
            preg_match('#/FullName[\s]*\(([^\)]*)#', $font, $matches);
            $fmetric['name'] = preg_replace('/[^a-zA-Z0-9_\-]/', '', $matches[1]);
            preg_match('#/FontBBox[\s]*{([^}]*)#', $font, $matches);
            $fmetric['bbox']    = trim($matches[1]);
            $bv                 = explode(' ', $fmetric['bbox']);
            $fmetric['Ascent']  = intval($bv[3]);
            $fmetric['Descent'] = intval($bv[1]);
            preg_match('#/ItalicAngle[\s]*([0-9\+\-]*)#', $font, $matches);
            $fmetric['italicAngle'] = intval($matches[1]);
            if ($fmetric['italicAngle'] != 0) {
                $fmetric['Flags'] |= 64;
            }
            preg_match('#/UnderlinePosition[\s]*([0-9\+\-]*)#', $font, $matches);
            $fmetric['underlinePosition'] = intval($matches[1]);
            preg_match('#/UnderlineThickness[\s]*([0-9\+\-]*)#', $font, $matches);
            $fmetric['underlineThickness'] = intval($matches[1]);
            preg_match('#/isFixedPitch[\s]*([^\s]*)#', $font, $matches);
            if ($matches[1] == 'true') {
                $fmetric['Flags'] |= 1;
            }
            // get internal map
            $imap = [];
            if (preg_match_all('#dup[\s]([0-9]+)[\s]*/([^\s]*)[\s]put#sU', $font, $fmap, PREG_SET_ORDER) > 0) {
                foreach ($fmap as $v) {
                    $imap[$v[2]] = $v[1];
                }
            }
            // decrypt eexec encrypted part
            $r      = 55665; // eexec encryption constant
            $c1     = 52845;
            $c2     = 22719;
            $elen   = strlen($encrypted);
            $eplain = '';
            for ($i = 0; $i < $elen; ++$i) {
                $chr    = ord($encrypted[$i]);
                $eplain .= chr($chr ^ ($r >> 8));
                $r      = ((($chr + $r) * $c1 + $c2) % 65536);
            }
            if (preg_match('#/ForceBold[\s]*([^\s]*)#', $eplain, $matches) > 0) {
                if ($matches[1] == 'true') {
                    $fmetric['Flags'] |= 0x40000;
                }
            }
            if (preg_match('#/StdVW[\s]*\[([^\]]*)#', $eplain, $matches) > 0) {
                $fmetric['StemV'] = intval($matches[1]);
            }
            else {
                $fmetric['StemV'] = 70;
            }
            if (preg_match('#/StdHW[\s]*\[([^\]]*)#', $eplain, $matches) > 0) {
                $fmetric['StemH'] = intval($matches[1]);
            }
            else {
                $fmetric['StemH'] = 30;
            }
            if (preg_match('#/BlueValues[\s]*\[([^\]]*)#', $eplain, $matches) > 0) {
                $bv = explode(' ', $matches[1]);
                if (count($bv) >= 6) {
                    $v1 = intval($bv[2]);
                    $v2 = intval($bv[4]);
                    if ($v1 <= $v2) {
                        $fmetric['XHeight']   = $v1;
                        $fmetric['CapHeight'] = $v2;
                    }
                    else {
                        $fmetric['XHeight']   = $v2;
                        $fmetric['CapHeight'] = $v1;
                    }
                }
                else {
                    $fmetric['XHeight']   = 450;
                    $fmetric['CapHeight'] = 700;
                }
            }
            else {
                $fmetric['XHeight']   = 450;
                $fmetric['CapHeight'] = 700;
            }
            // get the number of random bytes at the beginning of charstrings
            if (preg_match('#/lenIV[\s]*([0-9]*)#', $eplain, $matches) > 0) {
                $lenIV = intval($matches[1]);
            }
            else {
                $lenIV = 4;
            }
            $fmetric['Leading'] = 0;
            // get charstring data
            $eplain = substr($eplain, (strpos($eplain, '/CharStrings') + 1));
            preg_match_all('#/([A-Za-z0-9\.]*)[\s][0-9]+[\s]RD[\s](.*)[\s]ND#sU', $eplain, $matches, PREG_SET_ORDER);
            if (!empty($enc) and isset(TCPDF_FONT_DATA::$encmap[$enc])) {
                $enc_map = TCPDF_FONT_DATA::$encmap[$enc];
            }
            else {
                $enc_map = false;
            }
            $fmetric['cw']       = '';
            $fmetric['MaxWidth'] = 0;
            $cwidths             = [];
            foreach ($matches as $k => $v) {
                $cid = 0;
                if (isset($imap[$v[1]])) {
                    $cid = $imap[$v[1]];
                }
                elseif ($enc_map !== false) {
                    $cid = array_search($v[1], $enc_map);
                    if ($cid === false) {
                        $cid = 0;
                    }
                    elseif ($cid > 1000) {
                        $cid -= 1000;
                    }
                }
                // decrypt charstring encrypted part
                $r    = 4330; // charstring encryption constant
                $c1   = 52845;
                $c2   = 22719;
                $cd   = $v[2];
                $clen = strlen($cd);
                $ccom = [];
                for ($i = 0; $i < $clen; ++$i) {
                    $chr    = ord($cd[$i]);
                    $ccom[] = ($chr ^ ($r >> 8));
                    $r      = ((($chr + $r) * $c1 + $c2) % 65536);
                }
                // decode numbers
                $cdec = [];
                $ck   = 0;
                $i    = $lenIV;
                while ($i < $clen) {
                    if ($ccom[$i] < 32) {
                        $cdec[$ck] = $ccom[$i];
                        if (($ck > 0) and ($cdec[$ck] == 13)) {
                            // hsbw command: update width
                            $cwidths[$cid] = $cdec[($ck - 1)];
                        }
                        ++$i;
                    }
                    elseif (($ccom[$i] >= 32) and ($ccom[$i] <= 246)) {
                        $cdec[$ck] = ($ccom[$i] - 139);
                        ++$i;
                    }
                    elseif (($ccom[$i] >= 247) and ($ccom[$i] <= 250)) {
                        $cdec[$ck] = ((($ccom[$i] - 247) * 256) + $ccom[($i + 1)] + 108);
                        $i         += 2;
                    }
                    elseif (($ccom[$i] >= 251) and ($ccom[$i] <= 254)) {
                        $cdec[$ck] = ((-($ccom[$i] - 251) * 256) - $ccom[($i + 1)] - 108);
                        $i         += 2;
                    }
                    elseif ($ccom[$i] == 255) {
                        $sval      = chr($ccom[($i + 1)]) . chr($ccom[($i + 2)]) . chr($ccom[($i + 3)]) . chr($ccom[($i + 4)]);
                        $vsval     = unpack('li', $sval);
                        $cdec[$ck] = $vsval['i'];
                        $i         += 5;
                    }
                    ++$ck;
                }
            } // end for each matches
            $fmetric['MissingWidth'] = $cwidths[0];
            $fmetric['MaxWidth']     = $fmetric['MissingWidth'];
            $fmetric['AvgWidth']     = 0;
            // set chars widths
            for ($cid = 0; $cid <= 255; ++$cid) {
                if (isset($cwidths[$cid])) {
                    if ($cwidths[$cid] > $fmetric['MaxWidth']) {
                        $fmetric['MaxWidth'] = $cwidths[$cid];
                    }
                    $fmetric['AvgWidth'] += $cwidths[$cid];
                    $fmetric['cw']       .= ',' . $cid . '=>' . $cwidths[$cid];
                }
                else {
                    $fmetric['cw'] .= ',' . $cid . '=>' . $fmetric['MissingWidth'];
                }
            }
            $fmetric['AvgWidth'] = round($fmetric['AvgWidth'] / count($cwidths));
        }
        else {
            // ---------- TRUE TYPE ----------
            $offset = 0; // offset position of the font data
            if (TCPDF_STATIC::_getULONG($font, $offset) != 0x10000) {
                // sfnt version must be 0x00010000 for TrueType version 1.0.
                return false;
            }
            if ($fmetric['type'] != 'cidfont0') {
                if ($link) {
                    // creates a symbolic link to the existing font
                    symlink($fontfile, $outpath . $fmetric['file']);
                }
                else {
                    // store compressed font
                    $fmetric['file'] .= '.z';
                    $fp              = TCPDF_STATIC::fopenLocal($outpath . $fmetric['file'], 'wb');
                    fwrite($fp, gzcompress($font));
                    fclose($fp);
                }
            }
            $offset += 4;
            // get number of tables
            $numTables = TCPDF_STATIC::_getUSHORT($font, $offset);
            $offset    += 2;
            // skip searchRange, entrySelector and rangeShift
            $offset += 6;
            // tables array
            $table = [];
            // ---------- get tables ----------
            for ($i = 0; $i < $numTables; ++$i) {
                // get table info
                $tag                     = substr($font, $offset, 4);
                $offset                  += 4;
                $table[$tag]             = [];
                $table[$tag]['checkSum'] = TCPDF_STATIC::_getULONG($font, $offset);
                $offset                  += 4;
                $table[$tag]['offset']   = TCPDF_STATIC::_getULONG($font, $offset);
                $offset                  += 4;
                $table[$tag]['length']   = TCPDF_STATIC::_getULONG($font, $offset);
                $offset                  += 4;
            }
            // check magicNumber
            $offset = $table['head']['offset'] + 12;
            if (TCPDF_STATIC::_getULONG($font, $offset) != 0x5F0F3CF5) {
                // magicNumber must be 0x5F0F3CF5
                return false;
            }
            $offset += 4;
            $offset += 2; // skip flags
            // get FUnits
            $fmetric['unitsPerEm'] = TCPDF_STATIC::_getUSHORT($font, $offset);
            $offset                += 2;
            // units ratio constant
            $urk             = (1000 / $fmetric['unitsPerEm']);
            $offset          += 16; // skip created, modified
            $xMin            = round(TCPDF_STATIC::_getFWORD($font, $offset) * $urk);
            $offset          += 2;
            $yMin            = round(TCPDF_STATIC::_getFWORD($font, $offset) * $urk);
            $offset          += 2;
            $xMax            = round(TCPDF_STATIC::_getFWORD($font, $offset) * $urk);
            $offset          += 2;
            $yMax            = round(TCPDF_STATIC::_getFWORD($font, $offset) * $urk);
            $offset          += 2;
            $fmetric['bbox'] = '' . $xMin . ' ' . $yMin . ' ' . $xMax . ' ' . $yMax . '';
            $macStyle        = TCPDF_STATIC::_getUSHORT($font, $offset);
            $offset          += 2;
            // PDF font flags
            $fmetric['Flags'] = $flags;
            if (($macStyle & 2) == 2) {
                // italic flag
                $fmetric['Flags'] |= 64;
            }
            // get offset mode (indexToLocFormat : 0 = short, 1 = long)
            $offset       = $table['head']['offset'] + 50;
            $short_offset = (TCPDF_STATIC::_getSHORT($font, $offset) == 0);
            $offset       += 2;
            // get the offsets to the locations of the glyphs in the font, relative to the beginning of the glyphData table
            $indexToLoc = [];
            $offset     = $table['loca']['offset'];
            if ($short_offset) {
                // short version
                $tot_num_glyphs = floor($table['loca']['length'] / 2); // numGlyphs + 1
                for ($i = 0; $i < $tot_num_glyphs; ++$i) {
                    $indexToLoc[$i] = TCPDF_STATIC::_getUSHORT($font, $offset) * 2;
                    if (isset($indexToLoc[($i - 1)]) && ($indexToLoc[$i] == $indexToLoc[($i - 1)])) {
                        // the last glyph didn't have an outline
                        unset($indexToLoc[($i - 1)]);
                    }
                    $offset += 2;
                }
            }
            else {
                // long version
                $tot_num_glyphs = floor($table['loca']['length'] / 4); // numGlyphs + 1
                for ($i = 0; $i < $tot_num_glyphs; ++$i) {
                    $indexToLoc[$i] = TCPDF_STATIC::_getULONG($font, $offset);
                    if (isset($indexToLoc[($i - 1)]) && ($indexToLoc[$i] == $indexToLoc[($i - 1)])) {
                        // the last glyph didn't have an outline
                        unset($indexToLoc[($i - 1)]);
                    }
                    $offset += 4;
                }
            }
            // get glyphs indexes of chars from cmap table
            $offset            = $table['cmap']['offset'] + 2;
            $numEncodingTables = TCPDF_STATIC::_getUSHORT($font, $offset);
            $offset            += 2;
            $encodingTables    = [];
            for ($i = 0; $i < $numEncodingTables; ++$i) {
                $encodingTables[$i]['platformID'] = TCPDF_STATIC::_getUSHORT($font, $offset);
                $offset                           += 2;
                $encodingTables[$i]['encodingID'] = TCPDF_STATIC::_getUSHORT($font, $offset);
                $offset                           += 2;
                $encodingTables[$i]['offset']     = TCPDF_STATIC::_getULONG($font, $offset);
                $offset                           += 4;
            }
            // ---------- get os/2 metrics ----------
            $offset = $table['OS/2']['offset'];
            $offset += 2; // skip version
            // xAvgCharWidth
            $fmetric['AvgWidth'] = round(TCPDF_STATIC::_getFWORD($font, $offset) * $urk);
            $offset              += 2;
            // usWeightClass
            $usWeightClass = round(TCPDF_STATIC::_getUFWORD($font, $offset) * $urk);
            // estimate StemV and StemH (400 = usWeightClass for Normal - Regular font)
            $fmetric['StemV'] = round((70 * $usWeightClass) / 400);
            $fmetric['StemH'] = round((30 * $usWeightClass) / 400);
            $offset           += 2;
            $offset           += 2; // usWidthClass
            $fsType           = TCPDF_STATIC::_getSHORT($font, $offset);
            $offset           += 2;
            if ($fsType == 2) {
                // This Font cannot be modified, embedded or exchanged in any manner without first obtaining permission of the legal owner.
                return false;
            }
            // ---------- get font name ----------
            $fmetric['name'] = '';
            $offset          = $table['name']['offset'];
            $offset          += 2; // skip Format selector (=0).
            // Number of NameRecords that follow n.
            $numNameRecords = TCPDF_STATIC::_getUSHORT($font, $offset);
            $offset         += 2;
            // Offset to start of string storage (from start of table).
            $stringStorageOffset = TCPDF_STATIC::_getUSHORT($font, $offset);
            $offset              += 2;
            for ($i = 0; $i < $numNameRecords; ++$i) {
                $offset += 6; // skip Platform ID, Platform-specific encoding ID, Language ID.
                // Name ID.
                $nameID = TCPDF_STATIC::_getUSHORT($font, $offset);
                $offset += 2;
                if ($nameID == 6) {
                    // String length (in bytes).
                    $stringLength = TCPDF_STATIC::_getUSHORT($font, $offset);
                    $offset       += 2;
                    // String offset from start of storage area (in bytes).
                    $stringOffset    = TCPDF_STATIC::_getUSHORT($font, $offset);
                    $offset          += 2;
                    $offset          = ($table['name']['offset'] + $stringStorageOffset + $stringOffset);
                    $fmetric['name'] = substr($font, $offset, $stringLength);
                    $fmetric['name'] = preg_replace('/[^a-zA-Z0-9_\-]/', '', $fmetric['name']);
                    break;
                }
                else {
                    $offset += 4; // skip String length, String offset
                }
            }
            if (empty($fmetric['name'])) {
                $fmetric['name'] = $font_name;
            }
            // ---------- get post data ----------
            $offset                        = $table['post']['offset'];
            $offset                        += 4; // skip Format Type
            $fmetric['italicAngle']        = TCPDF_STATIC::_getFIXED($font, $offset);
            $offset                        += 4;
            $fmetric['underlinePosition']  = round(TCPDF_STATIC::_getFWORD($font, $offset) * $urk);
            $offset                        += 2;
            $fmetric['underlineThickness'] = round(TCPDF_STATIC::_getFWORD($font, $offset) * $urk);
            $offset                        += 2;
            $isFixedPitch                  = (TCPDF_STATIC::_getULONG($font, $offset) == 0) ? false : true;
            $offset                        += 2;
            if ($isFixedPitch) {
                $fmetric['Flags'] |= 1;
            }
            // ---------- get hhea data ----------
            $offset = $table['hhea']['offset'];
            $offset += 4; // skip Table version number
            // Ascender
            $fmetric['Ascent'] = round(TCPDF_STATIC::_getFWORD($font, $offset) * $urk);
            $offset            += 2;
            // Descender
            $fmetric['Descent'] = round(TCPDF_STATIC::_getFWORD($font, $offset) * $urk);
            $offset             += 2;
            // LineGap
            $fmetric['Leading'] = round(TCPDF_STATIC::_getFWORD($font, $offset) * $urk);
            $offset             += 2;
            // advanceWidthMax
            $fmetric['MaxWidth'] = round(TCPDF_STATIC::_getUFWORD($font, $offset) * $urk);
            $offset              += 2;
            $offset              += 22; // skip some values
            // get the number of hMetric entries in hmtx table
            $numberOfHMetrics = TCPDF_STATIC::_getUSHORT($font, $offset);
            // ---------- get maxp data ----------
            $offset = $table['maxp']['offset'];
            $offset += 4; // skip Table version number
            // get the the number of glyphs in the font.
            $numGlyphs = TCPDF_STATIC::_getUSHORT($font, $offset);
            // ---------- get CIDToGIDMap ----------
            $ctg = [];
            $c   = 0;
            foreach ($encodingTables as $enctable) {
                // get only specified Platform ID and Encoding ID
                if (($enctable['platformID'] == $platid) and ($enctable['encodingID'] == $encid)) {
                    $offset = $table['cmap']['offset'] + $enctable['offset'];
                    $format = TCPDF_STATIC::_getUSHORT($font, $offset);
                    $offset += 2;
                    switch ($format) {
                        case 0:
                        { // Format 0: Byte encoding table
                            $offset += 4; // skip length and version/language
                            for ($c = 0; $c < 256; ++$c) {
                                $g       = TCPDF_STATIC::_getBYTE($font, $offset);
                                $ctg[$c] = $g;
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
                                $subHeaderKeys[$i] = (TCPDF_STATIC::_getUSHORT($font, $offset) / 8);
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
                                $subHeaders[$k]['firstCode']     = TCPDF_STATIC::_getUSHORT($font, $offset);
                                $offset                          += 2;
                                $subHeaders[$k]['entryCount']    = TCPDF_STATIC::_getUSHORT($font, $offset);
                                $offset                          += 2;
                                $subHeaders[$k]['idDelta']       = TCPDF_STATIC::_getUSHORT($font, $offset);
                                $offset                          += 2;
                                $subHeaders[$k]['idRangeOffset'] = TCPDF_STATIC::_getUSHORT($font, $offset);
                                $offset                          += 2;
                                $subHeaders[$k]['idRangeOffset'] -= (2 + (($numSubHeaders - $k - 1) * 8));
                                $subHeaders[$k]['idRangeOffset'] /= 2;
                                $numGlyphIndexArray              += $subHeaders[$k]['entryCount'];
                            }
                            for ($k = 0; $k < $numGlyphIndexArray; ++$k) {
                                $glyphIndexArray[$k] = TCPDF_STATIC::_getUSHORT($font, $offset);
                                $offset              += 2;
                            }
                            for ($i = 0; $i < 256; ++$i) {
                                $k = $subHeaderKeys[$i];
                                if ($k == 0) {
                                    // one byte code
                                    $c       = $i;
                                    $g       = $glyphIndexArray[0];
                                    $ctg[$c] = $g;
                                }
                                else {
                                    // two bytes code
                                    $start_byte = $subHeaders[$k]['firstCode'];
                                    $end_byte   = $start_byte + $subHeaders[$k]['entryCount'];
                                    for ($j = $start_byte; $j < $end_byte; ++$j) {
                                        // combine high and low bytes
                                        $c             = (($i << 8) + $j);
                                        $idRangeOffset = ($subHeaders[$k]['idRangeOffset'] + $j - $subHeaders[$k]['firstCode']);
                                        $g             = ($glyphIndexArray[$idRangeOffset] + $subHeaders[$k]['idDelta']) % 65536;
                                        if ($g < 0) {
                                            $g = 0;
                                        }
                                        $ctg[$c] = $g;
                                    }
                                }
                            }
                            break;
                        }
                        case 4:
                        { // Format 4: Segment mapping to delta values
                            $length   = TCPDF_STATIC::_getUSHORT($font, $offset);
                            $offset   += 2;
                            $offset   += 2; // skip version/language
                            $segCount = floor(TCPDF_STATIC::_getUSHORT($font, $offset) / 2);
                            $offset   += 2;
                            $offset   += 6; // skip searchRange, entrySelector, rangeShift
                            $endCount = []; // array of end character codes for each segment
                            for ($k = 0; $k < $segCount; ++$k) {
                                $endCount[$k] = TCPDF_STATIC::_getUSHORT($font, $offset);
                                $offset       += 2;
                            }
                            $offset     += 2; // skip reservedPad
                            $startCount = []; // array of start character codes for each segment
                            for ($k = 0; $k < $segCount; ++$k) {
                                $startCount[$k] = TCPDF_STATIC::_getUSHORT($font, $offset);
                                $offset         += 2;
                            }
                            $idDelta = []; // delta for all character codes in segment
                            for ($k = 0; $k < $segCount; ++$k) {
                                $idDelta[$k] = TCPDF_STATIC::_getUSHORT($font, $offset);
                                $offset      += 2;
                            }
                            $idRangeOffset = []; // Offsets into glyphIdArray or 0
                            for ($k = 0; $k < $segCount; ++$k) {
                                $idRangeOffset[$k] = TCPDF_STATIC::_getUSHORT($font, $offset);
                                $offset            += 2;
                            }
                            $gidlen       = (floor($length / 2) - 8 - (4 * $segCount));
                            $glyphIdArray = []; // glyph index array
                            for ($k = 0; $k < $gidlen; ++$k) {
                                $glyphIdArray[$k] = TCPDF_STATIC::_getUSHORT($font, $offset);
                                $offset           += 2;
                            }
                            for ($k = 0; $k < $segCount - 1; ++$k) {
                                for ($c = $startCount[$k]; $c <= $endCount[$k]; ++$c) {
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
                                    $ctg[$c] = $g;
                                }
                            }
                            break;
                        }
                        case 6:
                        { // Format 6: Trimmed table mapping
                            $offset     += 4; // skip length and version/language
                            $firstCode  = TCPDF_STATIC::_getUSHORT($font, $offset);
                            $offset     += 2;
                            $entryCount = TCPDF_STATIC::_getUSHORT($font, $offset);
                            $offset     += 2;
                            for ($k = 0; $k < $entryCount; ++$k) {
                                $c       = ($k + $firstCode);
                                $g       = TCPDF_STATIC::_getUSHORT($font, $offset);
                                $offset  += 2;
                                $ctg[$c] = $g;
                            }
                            break;
                        }
                        case 8:
                        { // Format 8: Mixed 16-bit and 32-bit coverage
                            $offset += 10; // skip reserved, length and version/language
                            for ($k = 0; $k < 8192; ++$k) {
                                $is32[$k] = TCPDF_STATIC::_getBYTE($font, $offset);
                                ++$offset;
                            }
                            $nGroups = TCPDF_STATIC::_getULONG($font, $offset);
                            $offset  += 4;
                            for ($i = 0; $i < $nGroups; ++$i) {
                                $startCharCode = TCPDF_STATIC::_getULONG($font, $offset);
                                $offset        += 4;
                                $endCharCode   = TCPDF_STATIC::_getULONG($font, $offset);
                                $offset        += 4;
                                $startGlyphID  = TCPDF_STATIC::_getULONG($font, $offset);
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
                                    $ctg[$c] = 0;
                                    ++$startGlyphID;
                                }
                            }
                            break;
                        }
                        case 10:
                        { // Format 10: Trimmed array
                            $offset        += 10; // skip reserved, length and version/language
                            $startCharCode = TCPDF_STATIC::_getULONG($font, $offset);
                            $offset        += 4;
                            $numChars      = TCPDF_STATIC::_getULONG($font, $offset);
                            $offset        += 4;
                            for ($k = 0; $k < $numChars; ++$k) {
                                $c       = ($k + $startCharCode);
                                $g       = TCPDF_STATIC::_getUSHORT($font, $offset);
                                $ctg[$c] = $g;
                                $offset  += 2;
                            }
                            break;
                        }
                        case 12:
                        { // Format 12: Segmented coverage
                            $offset  += 10; // skip length and version/language
                            $nGroups = TCPDF_STATIC::_getULONG($font, $offset);
                            $offset  += 4;
                            for ($k = 0; $k < $nGroups; ++$k) {
                                $startCharCode  = TCPDF_STATIC::_getULONG($font, $offset);
                                $offset         += 4;
                                $endCharCode    = TCPDF_STATIC::_getULONG($font, $offset);
                                $offset         += 4;
                                $startGlyphCode = TCPDF_STATIC::_getULONG($font, $offset);
                                $offset         += 4;
                                for ($c = $startCharCode; $c <= $endCharCode; ++$c) {
                                    $ctg[$c] = $startGlyphCode;
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
            }
            if (!isset($ctg[0])) {
                $ctg[0] = 0;
            }
            // get xHeight (height of x)
            $offset             = ($table['glyf']['offset'] + $indexToLoc[$ctg[120]] + 4);
            $yMin               = TCPDF_STATIC::_getFWORD($font, $offset);
            $offset             += 4;
            $yMax               = TCPDF_STATIC::_getFWORD($font, $offset);
            $offset             += 2;
            $fmetric['XHeight'] = round(($yMax - $yMin) * $urk);
            // get CapHeight (height of H)
            $offset               = ($table['glyf']['offset'] + $indexToLoc[$ctg[72]] + 4);
            $yMin                 = TCPDF_STATIC::_getFWORD($font, $offset);
            $offset               += 4;
            $yMax                 = TCPDF_STATIC::_getFWORD($font, $offset);
            $offset               += 2;
            $fmetric['CapHeight'] = round(($yMax - $yMin) * $urk);
            // ceate widths array
            $cw     = [];
            $offset = $table['hmtx']['offset'];
            for ($i = 0; $i < $numberOfHMetrics; ++$i) {
                $cw[$i] = round(TCPDF_STATIC::_getUFWORD($font, $offset) * $urk);
                $offset += 4; // skip lsb
            }
            if ($numberOfHMetrics < $numGlyphs) {
                // fill missing widths with the last value
                $cw = array_pad($cw, $numGlyphs, $cw[($numberOfHMetrics - 1)]);
            }
            $fmetric['MissingWidth'] = $cw[0];
            $fmetric['cw']           = '';
            $fmetric['cbbox']        = '';
            for ($cid = 0; $cid <= 65535; ++$cid) {
                if (isset($ctg[$cid])) {
                    if (isset($cw[$ctg[$cid]])) {
                        $fmetric['cw'] .= ',' . $cid . '=>' . $cw[$ctg[$cid]];
                    }
                    if ($addcbbox and isset($indexToLoc[$ctg[$cid]])) {
                        $offset           = ($table['glyf']['offset'] + $indexToLoc[$ctg[$cid]]);
                        $xMin             = round(TCPDF_STATIC::_getFWORD($font, $offset + 2) * $urk);
                        $yMin             = round(TCPDF_STATIC::_getFWORD($font, $offset + 4) * $urk);
                        $xMax             = round(TCPDF_STATIC::_getFWORD($font, $offset + 6) * $urk);
                        $yMax             = round(TCPDF_STATIC::_getFWORD($font, $offset + 8) * $urk);
                        $fmetric['cbbox'] .= ',' . $cid . '=>array(' . $xMin . ',' . $yMin . ',' . $xMax . ',' . $yMax . ')';
                    }
                }
            }
        } // end of true type
        if (($fmetric['type'] == 'TrueTypeUnicode') and (count($ctg) == 256)) {
            $fmetric['type'] = 'TrueType';
        }
        // ---------- create php font file ----------
        $pfile = '<' . '?' . 'php' . "\n";
        $pfile .= '// TCPDF FONT FILE DESCRIPTION' . "\n";
        $pfile .= '$type=\'' . $fmetric['type'] . '\';' . "\n";
        $pfile .= '$name=\'' . $fmetric['name'] . '\';' . "\n";
        $pfile .= '$up=' . $fmetric['underlinePosition'] . ';' . "\n";
        $pfile .= '$ut=' . $fmetric['underlineThickness'] . ';' . "\n";
        if ($fmetric['MissingWidth'] > 0) {
            $pfile .= '$dw=' . $fmetric['MissingWidth'] . ';' . "\n";
        }
        else {
            $pfile .= '$dw=' . $fmetric['AvgWidth'] . ';' . "\n";
        }
        $pfile .= '$diff=\'' . $fmetric['diff'] . '\';' . "\n";
        if ($fmetric['type'] == 'Type1') {
            // Type 1
            $pfile .= '$enc=\'' . $fmetric['enc'] . '\';' . "\n";
            $pfile .= '$file=\'' . $fmetric['file'] . '\';' . "\n";
            $pfile .= '$size1=' . $fmetric['size1'] . ';' . "\n";
            $pfile .= '$size2=' . $fmetric['size2'] . ';' . "\n";
        }
        else {
            $pfile .= '$originalsize=' . $fmetric['originalsize'] . ';' . "\n";
            if ($fmetric['type'] == 'cidfont0') {
                // CID-0
                switch ($fonttype) {
                    case 'CID0JP':
                    {
                        $pfile .= '// Japanese' . "\n";
                        $pfile .= '$enc=\'UniJIS-UTF16-H\';' . "\n";
                        $pfile .= '$cidinfo=array(\'Registry\'=>\'Adobe\', \'Ordering\'=>\'Japan1\',\'Supplement\'=>5);' . "\n";
                        $pfile .= 'include(dirname(__FILE__).\'/uni2cid_aj16.php\');' . "\n";
                        break;
                    }
                    case 'CID0KR':
                    {
                        $pfile .= '// Korean' . "\n";
                        $pfile .= '$enc=\'UniKS-UTF16-H\';' . "\n";
                        $pfile .= '$cidinfo=array(\'Registry\'=>\'Adobe\', \'Ordering\'=>\'Korea1\',\'Supplement\'=>0);' . "\n";
                        $pfile .= 'include(dirname(__FILE__).\'/uni2cid_ak12.php\');' . "\n";
                        break;
                    }
                    case 'CID0CS':
                    {
                        $pfile .= '// Chinese Simplified' . "\n";
                        $pfile .= '$enc=\'UniGB-UTF16-H\';' . "\n";
                        $pfile .= '$cidinfo=array(\'Registry\'=>\'Adobe\', \'Ordering\'=>\'GB1\',\'Supplement\'=>2);' . "\n";
                        $pfile .= 'include(dirname(__FILE__).\'/uni2cid_ag15.php\');' . "\n";
                        break;
                    }
                    case 'CID0CT':
                    default:
                    {
                        $pfile .= '// Chinese Traditional' . "\n";
                        $pfile .= '$enc=\'UniCNS-UTF16-H\';' . "\n";
                        $pfile .= '$cidinfo=array(\'Registry\'=>\'Adobe\', \'Ordering\'=>\'CNS1\',\'Supplement\'=>0);' . "\n";
                        $pfile .= 'include(dirname(__FILE__).\'/uni2cid_aj16.php\');' . "\n";
                        break;
                    }
                }
            }
            else {
                // TrueType
                $pfile .= '$enc=\'' . $fmetric['enc'] . '\';' . "\n";
                $pfile .= '$file=\'' . $fmetric['file'] . '\';' . "\n";
                $pfile .= '$ctg=\'' . $fmetric['ctg'] . '\';' . "\n";
                // create CIDToGIDMap
                $cidtogidmap = str_pad('', 131072, "\x00"); // (256 * 256 * 2) = 131072
                foreach ($ctg as $cid => $gid) {
                    $cidtogidmap = self::updateCIDtoGIDmap($cidtogidmap, $cid, $ctg[$cid]);
                }
                // store compressed CIDToGIDMap
                $fp = TCPDF_STATIC::fopenLocal($outpath . $fmetric['ctg'], 'wb');
                fwrite($fp, gzcompress($cidtogidmap));
                fclose($fp);
            }
        }
        $pfile .= '$desc=array(';
        $pfile .= '\'Flags\'=>' . $fmetric['Flags'] . ',';
        $pfile .= '\'FontBBox\'=>\'[' . $fmetric['bbox'] . ']\',';
        $pfile .= '\'ItalicAngle\'=>' . $fmetric['italicAngle'] . ',';
        $pfile .= '\'Ascent\'=>' . $fmetric['Ascent'] . ',';
        $pfile .= '\'Descent\'=>' . $fmetric['Descent'] . ',';
        $pfile .= '\'Leading\'=>' . $fmetric['Leading'] . ',';
        $pfile .= '\'CapHeight\'=>' . $fmetric['CapHeight'] . ',';
        $pfile .= '\'XHeight\'=>' . $fmetric['XHeight'] . ',';
        $pfile .= '\'StemV\'=>' . $fmetric['StemV'] . ',';
        $pfile .= '\'StemH\'=>' . $fmetric['StemH'] . ',';
        $pfile .= '\'AvgWidth\'=>' . $fmetric['AvgWidth'] . ',';
        $pfile .= '\'MaxWidth\'=>' . $fmetric['MaxWidth'] . ',';
        $pfile .= '\'MissingWidth\'=>' . $fmetric['MissingWidth'] . '';
        $pfile .= ');' . "\n";
        if (!empty($fmetric['cbbox'])) {
            $pfile .= '$cbbox=array(' . substr($fmetric['cbbox'], 1) . ');' . "\n";
        }
        $pfile .= '$cw=array(' . substr($fmetric['cw'], 1) . ');' . "\n";
        $pfile .= '// --- EOF ---' . "\n";
        // store file
        $fp = TCPDF_STATIC::fopenLocal($outpath . $font_name . '.php', 'w');
        fwrite($fp, $pfile);
        fclose($fp);
        // return TCPDF font name
        return $font_name;
    }

    /**
     * Imports a TrueType, Type1, or core font and makes it available.
     * It is necessary to generate a font definition file first (read /fonts/utils/README.TXT).
     * The definition file (and the font file itself when embedding) must be present either in the fonts directory. If it could not be found, the error "Could not include font definition file" is generated.
     * @param string $family   Font family. The name can be chosen arbitrarily. If it is a standard family name, it will override the corresponding font.
     * @param string $style    Font style. Possible values are (case insensitive):<ul><li>empty string: regular (default)</li><li>B: bold</li><li>I: italic</li><li>BI or IB: bold italic</li></ul>
     * @param string $fontfile The font definition file. By default, the name is built from the family and style, in lower case with no spaces.
     * @param mixed  $subset   if true embedd only a subset of the font (stores only the information related to the used characters); if false embedd full font; if 'default' uses the default value set using setFontSubsetting(). This option is valid only for TrueTypeUnicode fonts. If you want to enable users to change the document, set this parameter to false. If you subset the font, the person who receives your PDF would need to have your same font in order to make changes to your PDF. The file size of the PDF would also be smaller because you are embedding only part of a font.
     * @return array|false array containing the font data, or false in case of error.
     * @see   setCurrentFont(), setFontSubsetting()
     */
    public function AddFont($family, $style = '', $fontfile = '', $subset = 'default')
    {
        if ($subset === 'default') {
            $subset = $this->fontSubsetting;
        }
        if (empty($family)) {
            if (!empty($this->fontFamily)) {
                $family = $this->fontFamily;
            }
            else {
                $this->Error('Empty font family');
            }
        }
        // move embedded styles on $style
        if (substr($family, -1) == 'I') {
            $style  .= 'I';
            $family = substr($family, 0, -1);
        }
        if (substr($family, -1) == 'B') {
            $style  .= 'B';
            $family = substr($family, 0, -1);
        }
        // normalize family name
        $family = strtolower($family);
        if ((!$this->isUnicode) and ($family == 'arial')) {
            $family = 'helvetica';
        }
        if (($family == 'symbol') or ($family == 'zapfdingbats')) {
            $style = '';
        }
        $tempstyle = strtoupper($style === null ? '' : $style);
        $style     = '';
        // underline
        if (strpos($tempstyle, 'U') !== false) {
            $this->underline = true;
        }
        else {
            $this->underline = false;
        }
        // line-through (deleted)
        if (strpos($tempstyle, 'D') !== false) {
            $this->lineThrough = true;
        }
        else {
            $this->lineThrough = false;
        }
        // overline
        if (strpos($tempstyle, 'O') !== false) {
            $this->overline = true;
        }
        else {
            $this->overline = false;
        }
        // bold
        if (strpos($tempstyle, 'B') !== false) {
            $style .= 'B';
        }
        // oblique
        if (strpos($tempstyle, 'I') !== false) {
            $style .= 'I';
        }
        $bistyle   = $style;
        $fontKey   = $family . $style;
        $fontStyle = $style . ($this->underline ? 'U' : '') . ($this->lineThrough ? 'D' : '') . ($this->overline ? 'O' : '');
        $fontdata  = ['fontKey' => $fontKey, 'family' => $family, 'style' => $fontStyle];
        // check if the font has been already added
        $fb = $this->fontBuffer($fontKey);
        if ($fb !== false) {
            if ($this->inXObject) {
                // we are inside an XObject template
                $this->xObjects[$this->xObjectID]['fonts'][$fontKey] = $fb['i'];
            }
            return $fontdata;
        }
        // get specified font directory (if any)
        $fontdir = false;
        if (!empty($fontfile)) {
            $fontdir = dirname($fontfile);
            if (empty($fontdir) or ($fontdir == '.')) {
                $fontdir = '';
            }
            else {
                $fontdir .= '/';
            }
        }
        // true when the font style variation is missing
        $missing_style = false;
        // search and include font file
        if (empty($fontfile) or !@file_exists($fontfile)) {
            // build a standard filenames for specified font
            $tmp_fontfile = str_replace(' ', '', $family) . strtolower($style) . '.php';
            $fontfile     = self::FONT_PATH . $tmp_fontfile;
        }
        // include font file
        if (!empty($fontfile) and (@file_exists($fontfile))) {
            $type         = null;
            $name         = null;
            $desc         = null;
            $up           = -null;
            $ut           = null;
            $cw           = null;
            $cbbox        = null;
            $dw           = null;
            $enc          = null;
            $cidinfo      = null;
            $file         = null;
            $ctg          = null;
            $diff         = null;
            $originalsize = null;
            $size1        = null;
            $size2        = null;
            include($fontfile);
        }
        else {
            $this->Error('Could not include font definition file: ' . $family . '');
        }
        // check font parameters
        if ((!isset($type)) or (!isset($cw))) {
            $this->Error('The font definition file has a bad format: ' . $fontfile . '');
        }
        // SET default parameters
        if (!isset($file) or empty($file)) {
            $file = '';
        }
        if (!isset($enc) or empty($enc)) {
            $enc = '';
        }
        if (!isset($cidinfo) or empty($cidinfo)) {
            $cidinfo            = ['Registry' => 'Adobe', 'Ordering' => 'Identity', 'Supplement' => 0];
            $cidinfo['uni2cid'] = [];
        }
        if (!isset($ctg) or empty($ctg)) {
            $ctg = '';
        }
        if (!isset($desc) or empty($desc)) {
            $desc = [];
        }
        if (!isset($up) or empty($up)) {
            $up = -100;
        }
        if (!isset($ut) or empty($ut)) {
            $ut = 50;
        }
        if (!isset($cw) or empty($cw)) {
            $cw = [];
        }
        if (!isset($dw) or empty($dw)) {
            // set default width
            if (isset($desc['MissingWidth']) and ($desc['MissingWidth'] > 0)) {
                $dw = $desc['MissingWidth'];
            }
            elseif (isset($cw[32])) {
                $dw = $cw[32];
            }
            else {
                $dw = 600;
            }
        }
        ++$this->fontCount;
        if ($type == 'core') {
            $name   = self::CORE_FONTS[$fontKey];
            $subset = false;
        }
        elseif (($type == 'TrueType') or ($type == 'Type1')) {
            $subset = false;
        }
        elseif ($type == 'TrueTypeUnicode') {
            $enc = 'Identity-H';
        }
        else {
            $this->Error('Unknow font type: ' . $type . '');
        }
        // set name if unset
        if (!isset($name) or empty($name)) {
            $name = $fontKey;
        }
        // create artificial font style variations if missing (only works with non-embedded fonts)
        if (($type != 'core') and $missing_style) {
            // style variations
            $styles = ['' => '', 'B' => ',Bold', 'I' => ',Italic', 'BI' => ',BoldItalic'];
            $name   .= $styles[$bistyle];
            // artificial bold
            if (strpos($bistyle, 'B') !== false) {
                if (isset($desc['StemV'])) {
                    // from normal to bold
                    $desc['StemV'] = round($desc['StemV'] * 1.75);
                }
                else {
                    // bold
                    $desc['StemV'] = 123;
                }
            }
            // artificial italic
            if (strpos($bistyle, 'I') !== false) {
                if (isset($desc['ItalicAngle'])) {
                    $desc['ItalicAngle'] -= 11;
                }
                else {
                    $desc['ItalicAngle'] = -11;
                }
                if (isset($desc['Flags'])) {
                    $desc['Flags'] |= 64; //bit 7
                }
                else {
                    $desc['Flags'] = 64;
                }
            }
        }
        // check if the array of characters bounding boxes is defined
        if (!isset($cbbox)) {
            $cbbox = [];
        }

        $subsetCharacters = array_fill(0, 255, true);
        $this->fontBuffer($fontKey, ['fontKey' => $fontKey, 'i' => $this->fontCount, 'type' => $type, 'name' => $name, 'desc' => $desc, 'up' => $up, 'ut' => $ut, 'cw' => $cw, 'cbbox' => $cbbox, 'dw' => $dw, 'enc' => $enc, 'cidinfo' => $cidinfo, 'file' => $file, 'ctg' => $ctg, 'subset' => $subset, 'subsetCharacters' => $subsetCharacters]);
        if ($this->inXObject) {
            // we are inside an XObject template
            $this->xObjects[$this->xObjectID]['fonts'][$fontKey] = $this->fontCount;
        }
        if (isset($diff) and (!empty($diff))) {
            //Search existing encodings
            $d  = 0;
            $nb = count($this->encodingDeltas);
            for ($i = 1; $i <= $nb; ++$i) {
                if ($this->encodingDeltas[$i] == $diff) {
                    $d = $i;
                    break;
                }
            }
            if ($d == 0) {
                $d                        = $nb + 1;
                $this->encodingDeltas[$d] = $diff;
            }
            $this->fontSubBuffer($fontKey, 'diff', $d);
        }
        if (!empty($file)) {
            if (!isset($this->fontFiles[$file])) {
                if ((strcasecmp($type, 'TrueType') == 0) or (strcasecmp($type, 'TrueTypeUnicode') == 0)) {
                    $this->fontFiles[$file] = ['length1' => $originalsize, 'fontdir' => $fontdir, 'subset' => $subset, 'fontKeys' => [$fontKey]];
                }
                elseif ($type != 'core') {
                    $this->fontFiles[$file] = ['length1' => $size1, 'length2' => $size2, 'fontdir' => $fontdir, 'subset' => $subset, 'fontKeys' => [$fontKey]];
                }
            }
            else {
                // update fontKeys that are sharing this font file
                $this->fontFiles[$file]['subset'] = ($this->fontFiles[$file]['subset'] and $subset);
                if (!in_array($fontKey, $this->fontFiles[$file]['fontKeys'])) {
                    $this->fontFiles[$file]['fontKeys'][] = $fontKey;
                }
            }
        }
        return $fontdata;
    }

    /**
     * Convert a relative font measure into absolute value.
     * @param int $s Font measure.
     * @return float Absolute measure.
     */
    public function getAbsFontMeasure($s)
    {
        return ($s * $this->fontSize / 1000);
    }

    /**
     * Return the font ascent value.
     * @param string $font  font name
     * @param string $style font style
     * @param float  $size  The size (in points)
     * @return int font ascent
     * @author Nicola Asuni
     */
    public function getFontAscent($font, $style = '', $size = 0)
    {
        $fontdata = $this->AddFont($font, $style);
        $fontinfo = $this->fontBuffer($fontdata['fontKey']);
        if (isset($fontinfo['desc']['Ascent']) and ($fontinfo['desc']['Ascent'] > 0)) {
            $ascent = ($fontinfo['desc']['Ascent'] * $size / 1000);
        }
        else {
            $ascent = 1.219 * 0.76 * $size;
        }
        return ($ascent / self::FACTOR);
    }

    /**
     * Returns the bounding box of the current font in user units.
     * @return array
     */
    public function getFontBBox()
    {
        $fbbox = [];
        if (isset($this->currentFont['desc']['FontBBox'])) {
            $tmpbbox = explode(' ', substr($this->currentFont['desc']['FontBBox'], 1, -1));
            $fbbox   = array_map([$this, 'getAbsFontMeasure'], $tmpbbox);
        }
        else {
            // Find max width
            if (isset($this->currentFont['desc']['MaxWidth'])) {
                $maxw = $this->getAbsFontMeasure(intval($this->currentFont['desc']['MaxWidth']));
            }
            else {
                $maxw = 0;
                if (isset($this->currentFont['desc']['MissingWidth'])) {
                    $maxw = max($maxw, $this->currentFont['desc']['MissingWidth']);
                }
                if (isset($this->currentFont['desc']['AvgWidth'])) {
                    $maxw = max($maxw, $this->currentFont['desc']['AvgWidth']);
                }
                if (isset($this->currentFont['dw'])) {
                    $maxw = max($maxw, $this->currentFont['dw']);
                }
                foreach ($this->currentFont['cw'] as $char => $w) {
                    $maxw = max($maxw, $w);
                }
                if ($maxw == 0) {
                    $maxw = 600;
                }
                $maxw = $this->getAbsFontMeasure($maxw);
            }
            $fbbox = array(0, (0 - $this->fontDescent), $maxw, $this->fontAscent);
        }
        return $fbbox;
    }

    /**
     * Return the font descent value
     * @param string $font  font name
     * @param string $style font style
     * @param float  $size  The size (in points)
     * @return int font descent
     * @author Nicola Asuni
     */
    public function getFontDescent($font, $style = '', $size = 0)
    {
        $fontdata = $this->AddFont($font, $style);
        $fontinfo = $this->fontBuffer($fontdata['fontKey']);
        if (isset($fontinfo['desc']['Descent']) and ($fontinfo['desc']['Descent'] <= 0)) {
            $descent = (-$fontinfo['desc']['Descent'] * $size / 1000);
        }
        else {
            $descent = (1.219 * 0.24 * $size);
        }
        return ($descent / self::FACTOR);
    }

    /**
     * Returns the current font family name.
     * @return string current font family name
     */
    public function getFontFamily()
    {
        return $this->fontFamily;
    }

    /**
     * Return the default option for font subsetting.
     * @return bool default font subsetting state.
     * @author Nicola Asuni
     */
    public function getFontSubsetting()
    {
        return $this->fontSubsetting;
    }

    /**
     * Return font full path
     * @param string $file    Font file name.
     * @param string $fontdir Font directory (set to false fto search on default directories)
     * @return string Font full path or empty string
     * @author Nicola Asuni
     * @since  6.0.025
     * @public static
     */
    public static function getFontFullPath($file, $fontdir = false)
    {
        $fontfile = '';
        // search files on various directories
        if (($fontdir !== false) and @TCPDF_STATIC::file_exists($fontdir . $file)) {
            $fontfile = $fontdir . $file;
        }
        elseif (@TCPDF_STATIC::file_exists(self::_getfontpath() . $file)) {
            $fontfile = self::_getfontpath() . $file;
        }
        elseif (@TCPDF_STATIC::file_exists($file)) {
            $fontfile = $file;
        }
        return $fontfile;
    }

    /**
     * Enable or disable default option for font subsetting.
     * @param bool $enable if true enable font subsetting by default.
     * @author Nicola Asuni
     */
    public function setFontSubsetting($enable = true)
    {
        $this->fontSubsetting = $enable ? true : false;
    }

    /**
     * Update the CIDToGIDMap string with a new value.
     * @param string $map CIDToGIDMap.
     * @param int    $cid CID value.
     * @param int    $gid GID value.
     * @return string CIDToGIDMap.
     * @author Nicola Asuni
     * @since  5.9.123 (2011-09-29)
     * @public static
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
}