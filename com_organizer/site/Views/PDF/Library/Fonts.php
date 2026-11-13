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
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////DONE

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

    /** Array of used fonts. */
    protected array $fonts = [];

    /** Current font size in user configured units.*/
    protected float $fontSize;

    /** Current font size in points.*/
    protected float $fontSizePT = 12;

    /** Current font style.*/
    protected string $fontStyle = '';

    protected array $footerFont = ['helvetica', '', 8];

    protected array $headerFont = [self::DEFAULT_FONT, '', 10];

    /** Boolean flag set to true when the input text is Unicode (require Unicode fonts). */
    protected bool $isUnicode = true;

    /**
     * Getter / setter for font buffer content
     * @param string $font font key
     * @param array  $data font data
     */
    protected function fontBuffer(string $font, array|null $data = null): array|false
    {
        if (!is_null($data)) {
            $this->fonts[$font] = $data;

            if (!in_array($font, $this->fontKeys)) {
                $this->fontKeys[] = $font;

                // store object ID for current font
                ++$this->n;
                $this->font_obj_ids[$font] = $this->n;
                $this->fontSubBuffer($font, 'n', $this->n);
            }
        }

        return $this->fonts[$font] ?? false;
    }

    /**
     * Returns the first valid font family name from a (potentially) comma-separated list of fonts in a string.
     * @param string $fonts property string containing font family names
     * @return string
     */
    public function fontFamily(string $fonts): string
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
     * @see    self::CORE_FONTS, AddFont(), self::FONT_STYLES, SetFontSize()
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
        $this->setFontSize($size, $append);
    }

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


    /**
     * Default font style to add to html links.
     */
    protected $htmlLinkFontStyle = 'U';

    /**
     * Store the font object IDs.
     */
    protected $font_obj_ids = [];

    /**
     * Default monospace font.
     */
    protected $default_monospaced_font = 'courier';

    /**
     * bool flag: if true enables font subsetting by default.
     */
    protected $font_subsetting = true;

    /**
     * Percentage of character stretching.
     */
    protected $font_stretching = 100;

    /**
     * Increases or decreases the space between characters in a text by the specified amount (tracking).
     */
    protected $font_spacing = 0;

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
                $indexToLoc[$i] = $this->unsignedLong($font, $offset);
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
            $encodingTables[$i]['offset']     = $this->unsignedLong($font, $offset);
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
                        if (isset($characters[$c])) {
                            $g                = StaticMethods::_getBYTE($font, $offset);
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
                    $firstCode  = StaticMethods::_getUSHORT($font, $offset);
                    $offset     += 2;
                    $entryCount = StaticMethods::_getUSHORT($font, $offset);
                    $offset     += 2;
                    for ($k = 0; $k < $entryCount; ++$k) {
                        $c = ($k + $firstCode);
                        if (isset($characters[$c])) {
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
                        $is32[$k] = StaticMethods::_getBYTE($font, $offset);
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


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Output fonts.
     * @author Nicola Asuni
     */
    protected function _putfonts(): void
    {
        $nf = $this->n;
        foreach ($this->diffs as $diff) {
            //Encodings
            $this->_newobj();
            $this->_out('<< /Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences [' . $diff . '] >>' . "\n" . 'endobj');
        }
        foreach ($this->fontFiles as $file => $info) {
            // search and get font file to embedd
            $fontfile = self::FONT_PATH . $file;
            if (!StaticMethods::empty_string($fontfile)) {
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
                $this->fontFiles[$file]['n'] = $this->n;
                $stream                      = $this->_getrawstream($font);
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
                $this->_out($out);
            }
        }
        foreach ($this->fontKeys as $k) {
            //Font objects
            $font = $this->fontBuffer($k);
            $type = $font['type'];
            $name = $font['name'];
            if ($type == 'core') {
                // standard core font
                $out = $this->_getobj($this->font_obj_ids[$k]) . "\n";
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
                $this->_out($out);
            }
            elseif (($type == 'Type1') or ($type == 'TrueType')) {
                // additional Type1 or TrueType font
                $out = $this->_getobj($this->font_obj_ids[$k]) . "\n";
                $out .= '<</Type /Font';
                $out .= ' /Subtype /' . $type;
                $out .= ' /BaseFont /' . $name;
                $out .= ' /Name /F' . $font['i'];
                $out .= ' /FirstChar 32 /LastChar 255';
                $out .= ' /Widths ' . ($this->n + 1) . ' 0 R';
                $out .= ' /FontDescriptor ' . ($this->n + 2) . ' 0 R';
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
                $this->_out($out);
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
                $this->_out($s);
                //Descriptor
                $this->_newobj();
                $s = '<</Type /FontDescriptor /FontName /' . $name;
                foreach ($font['desc'] as $fdk => $fdv) {
                    if (is_float($fdv)) {
                        $fdv = sprintf('%F', $fdv);
                    }
                    $s .= ' /' . $fdk . ' ' . $fdv . '';
                }
                if (!StaticMethods::empty_string($font['file'])) {
                    $s .= ' /FontFile' . ($type == 'Type1' ? '' : '2') . ' ' . $this->fontFiles[$font['file']]['n'] . ' 0 R';
                }
                $s .= '>>';
                $s .= "\n" . 'endobj';
                $this->_out($s);
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
            $subset = $this->font_subsetting;
        }
        if (StaticMethods::empty_string($family)) {
            if (!StaticMethods::empty_string($this->fontFamily)) {
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
            $this->linethrough = true;
        }
        else {
            $this->linethrough = false;
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
        $bistyle    = $style;
        $fontKey    = $family . $style;
        $font_style = $style . ($this->underline ? 'U' : '') . ($this->linethrough ? 'D' : '') . ($this->overline ? 'O' : '');
        $fontdata   = array('fontKey' => $fontKey, 'family' => $family, 'style' => $font_style);
        // check if the font has been already added
        $fb = $this->fontBuffer($fontKey);
        if ($fb !== false) {
            if ($this->inxobj) {
                // we are inside an XObject template
                $this->xobjects[$this->xobjid]['fonts'][$fontKey] = $fb['i'];
            }
            return $fontdata;
        }
        // get specified font directory (if any)
        $fontdir = false;
        if (!StaticMethods::empty_string($fontfile)) {
            $fontdir = dirname($fontfile);
            if (StaticMethods::empty_string($fontdir) or ($fontdir == '.')) {
                $fontdir = '';
            }
            else {
                $fontdir .= '/';
            }
        }
        // true when the font style variation is missing
        $missing_style = false;
        // search and include font file
        if (StaticMethods::empty_string($fontfile) or (!@StaticMethods::file_exists($fontfile))) {
            // build a standard filenames for specified font
            $tmp_fontfile = str_replace(' ', '', $family) . strtolower($style) . '.php';
            $fontfile     = self::FONT_PATH . $tmp_fontfile;
        }
        // include font file
        if (!StaticMethods::empty_string($fontfile) and (@StaticMethods::file_exists($fontfile))) {
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
        if (!isset($file) or StaticMethods::empty_string($file)) {
            $file = '';
        }
        if (!isset($enc) or StaticMethods::empty_string($enc)) {
            $enc = '';
        }
        if (!isset($cidinfo) or StaticMethods::empty_string($cidinfo)) {
            $cidinfo            = array('Registry' => 'Adobe', 'Ordering' => 'Identity', 'Supplement' => 0);
            $cidinfo['uni2cid'] = [];
        }
        if (!isset($ctg) or StaticMethods::empty_string($ctg)) {
            $ctg = '';
        }
        if (!isset($desc) or StaticMethods::empty_string($desc)) {
            $desc = [];
        }
        if (!isset($up) or StaticMethods::empty_string($up)) {
            $up = -100;
        }
        if (!isset($ut) or StaticMethods::empty_string($ut)) {
            $ut = 50;
        }
        if (!isset($cw) or StaticMethods::empty_string($cw)) {
            $cw = [];
        }
        if (!isset($dw) or StaticMethods::empty_string($dw)) {
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
            $styles = array('' => '', 'B' => ',Bold', 'I' => ',Italic', 'BI' => ',BoldItalic');
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
        $this->fontBuffer($fontKey, array('fontKey' => $fontKey, 'i' => $this->fontCount, 'type' => $type, 'name' => $name, 'desc' => $desc, 'up' => $up, 'ut' => $ut, 'cw' => $cw, 'cbbox' => $cbbox, 'dw' => $dw, 'enc' => $enc, 'cidinfo' => $cidinfo, 'file' => $file, 'ctg' => $ctg, 'subset' => $subset, 'subsetCharacters' => $subsetCharacters));
        if ($this->inxobj) {
            // we are inside an XObject template
            $this->xobjects[$this->xobjid]['fonts'][$fontKey] = $this->fontCount;
        }
        if (isset($diff) and (!empty($diff))) {
            //Search existing encodings
            $d  = 0;
            $nb = count($this->diffs);
            for ($i = 1; $i <= $nb; ++$i) {
                if ($this->diffs[$i] == $diff) {
                    $d = $i;
                    break;
                }
            }
            if ($d == 0) {
                $d               = $nb + 1;
                $this->diffs[$d] = $diff;
            }
            $this->fontSubBuffer($fontKey, 'diff', $d);
        }
        if (!StaticMethods::empty_string($file)) {
            if (!isset($this->fontFiles[$file])) {
                if ((strcasecmp($type, 'TrueType') == 0) or (strcasecmp($type, 'TrueTypeUnicode') == 0)) {
                    $this->fontFiles[$file] = array('length1' => $originalsize, 'fontdir' => $fontdir, 'subset' => $subset, 'fontKeys' => array($fontKey));
                }
                elseif ($type != 'core') {
                    $this->fontFiles[$file] = array('length1' => $size1, 'length2' => $size2, 'fontdir' => $fontdir, 'subset' => $subset, 'fontKeys' => array($fontKey));
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
     * Defines the size of the current font.
     * @param float $size The font size in points.
     * @param bool  $out  if true output the font size command, otherwise only set the font properties.
     * @see   setCurrentFont()
     */
    public function setFontSize($size, $out = true)
    {
        $size = (float) $size;
        // font size in points
        $this->fontSizePT = $size;
        // font size in user units
        $this->fontSize = $size / $this->k;
        // calculate some font metrics
        if (isset($this->currentFont['desc']['FontBBox'])) {
            $bbox        = explode(' ', substr($this->currentFont['desc']['FontBBox'], 1, -1));
            $font_height = ((intval($bbox[3]) - intval($bbox[1])) * $size / 1000);
        }
        else {
            $font_height = $size * 1.219;
        }
        if (isset($this->currentFont['desc']['Ascent']) and ($this->currentFont['desc']['Ascent'] > 0)) {
            $font_ascent = ($this->currentFont['desc']['Ascent'] * $size / 1000);
        }
        if (isset($this->currentFont['desc']['Descent']) and ($this->currentFont['desc']['Descent'] <= 0)) {
            $font_descent = (-$this->currentFont['desc']['Descent'] * $size / 1000);
        }
        if (!isset($font_ascent) and !isset($font_descent)) {
            // core font
            $font_ascent  = 0.76 * $font_height;
            $font_descent = $font_height - $font_ascent;
        }
        elseif (!isset($font_descent)) {
            $font_descent = $font_height - $font_ascent;
        }
        elseif (!isset($font_ascent)) {
            $font_ascent = $font_height - $font_descent;
        }
        $this->fontAscent  = ($font_ascent / $this->k);
        $this->fontDescent = ($font_descent / $this->k);
        if ($out and ($this->page > 0) and (isset($this->currentFont['i'])) and ($this->state == 2)) {
            $this->_out(sprintf('BT /F%d %F Tf ET', $this->currentFont['i'], $this->fontSizePT));
        }
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
            $fbbox   = array_map(array($this, 'getAbsFontMeasure'), $tmpbbox);
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
     * Convert a relative font measure into absolute value.
     * @param int $s Font measure.
     * @return float Absolute measure.
     */
    public function getAbsFontMeasure($s)
    {
        return ($s * $this->fontSize / 1000);
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
        return ($descent / $this->k);
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
        return ($ascent / $this->k);
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