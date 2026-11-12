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

    protected array $currentFont = [];

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


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