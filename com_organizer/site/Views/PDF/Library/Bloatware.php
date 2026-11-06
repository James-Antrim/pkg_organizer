<?php

namespace THM\Organizer\Views\PDF\Library;

/** Storage space for currently unused functions. */
trait Bloatware
{

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////COLORS-PROCESSED


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////COLORS-UNPROCESSED


    /**
     * Paints color transition registration bars
     * @param float  $x          abscissa of the top left corner of the rectangle.
     * @param float  $y          ordinate of the top left corner of the rectangle.
     * @param float  $w          width of the rectangle.
     * @param float  $h          height of the rectangle.
     * @param bool   $transition if true prints tcolor transitions to white.
     * @param bool   $vertical   if true prints bar vertically.
     * @param string $colors     colors to print separated by comma. Valid values are: A,W,R,G,B,C,M,Y,K,RGB,CMYK,ALL,ALLSPOT,<SPOT_COLOR_NAME>. Where: A = grayscale black, W = grayscale white, R = RGB red, G RGB green, B RGB blue, C = CMYK cyan, M = CMYK magenta, Y = CMYK yellow, K = CMYK key/black, RGB = RGB registration color, CMYK = CMYK registration color, ALL = Spot registration color, ALLSPOT = print all defined spot colors, <SPOT_COLOR_NAME> = name of the spot color to print.
     * @author Nicola Asuni
     */
    public function colorRegistrationBar($x, $y, $w, $h, $transition = true, $vertical = false, $colors = 'A,R,G,B,C,M,Y,K')
    {
        if (strpos($colors, 'ALLSPOT') !== false) {
            // expand spot colors
            $spot_colors = '';
            foreach ($this->spotColors as $spot_color_name => $v) {
                $spot_colors .= ',' . $spot_color_name;
            }
            if (!empty($spot_colors)) {
                $spot_colors = substr($spot_colors, 1);
                $colors      = str_replace('ALLSPOT', $spot_colors, $colors);
            }
            else {
                $colors = str_replace('ALLSPOT', 'NONE', $colors);
            }
        }
        $bars    = explode(',', $colors);
        $numbars = count($bars); // number of bars to print
        if ($numbars <= 0) {
            return;
        }
        // set bar measures
        if ($vertical) {
            $coords = [0, 0, 0, 1];
            $wb     = $w / $numbars; // bar width
            $hb     = $h; // bar height
            $xd     = $wb; // delta x
            $yd     = 0; // delta y
        }
        else {
            $coords = [1, 0, 0, 0];
            $wb     = $w; // bar width
            $hb     = $h / $numbars; // bar height
            $xd     = 0; // delta x
            $yd     = $hb; // delta y
        }
        $xb = $x;
        $yb = $y;
        foreach ($bars as $col) {
            switch ($col) {
                // set transition colors
                case 'A':
                { // BLACK (GRAYSCALE)
                    $col_a = [255];
                    $col_b = [0];
                    break;
                }
                case 'W':
                { // WHITE (GRAYSCALE)
                    $col_a = [0];
                    $col_b = [255];
                    break;
                }
                case 'R':
                { // RED (RGB)
                    $col_a = [255, 255, 255];
                    $col_b = [255, 0, 0];
                    break;
                }
                case 'G':
                { // GREEN (RGB)
                    $col_a = [255, 255, 255];
                    $col_b = [0, 255, 0];
                    break;
                }
                case 'B':
                { // BLUE (RGB)
                    $col_a = [255, 255, 255];
                    $col_b = [0, 0, 255];
                    break;
                }
                case 'C':
                { // CYAN (CMYK)
                    $col_a = [0, 0, 0, 0];
                    $col_b = [100, 0, 0, 0];
                    break;
                }
                case 'M':
                { // MAGENTA (CMYK)
                    $col_a = [0, 0, 0, 0];
                    $col_b = [0, 100, 0, 0];
                    break;
                }
                case 'Y':
                { // YELLOW (CMYK)
                    $col_a = [0, 0, 0, 0];
                    $col_b = [0, 0, 100, 0];
                    break;
                }
                case 'K':
                { // KEY - BLACK (CMYK)
                    $col_a = [0, 0, 0, 0];
                    $col_b = [0, 0, 0, 100];
                    break;
                }
                case 'RGB':
                { // BLACK REGISTRATION (RGB)
                    $col_a = [255, 255, 255];
                    $col_b = [0, 0, 0];
                    break;
                }
                case 'CMYK':
                { // BLACK REGISTRATION (CMYK)
                    $col_a = [0, 0, 0, 0];
                    $col_b = [100, 100, 100, 100];
                    break;
                }
                case 'ALL':
                { // SPOT COLOR REGISTRATION
                    $col_a = [0, 0, 0, 0, 'None'];
                    $col_b = [100, 100, 100, 100, 'All'];
                    break;
                }
                case 'NONE':
                { // SKIP THIS COLOR
                    $col_a = [0, 0, 0, 0, 'None'];
                    $col_b = [0, 0, 0, 0, 'None'];
                    break;
                }
                default:
                { // SPECIFIC SPOT COLOR NAME
                    $col_a = [0, 0, 0, 0, 'None'];
                    $col_b = $this->spotColor($col);
                    if ($col_b === false) {
                        // in case of error defaults to the registration color
                        $col_b = [100, 100, 100, 100, 'All'];
                    }
                    break;
                }
            }
            if ($col != 'NONE') {
                if ($transition) {
                    // color gradient
                    $this->LinearGradient($xb, $yb, $wb, $hb, $col_a, $col_b, $coords);
                }
                else {
                    $this->fillColorArray($col_b);
                    // colored rectangle
                    $this->Rect($xb, $yb, $wb, $hb, 'F', []);
                }
                $xb += $xd;
                $yb += $yd;
            }
        }
    }


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////FONTS-PROCESSED


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////FONTS-UNPROCESSED


    /**
     * Convert and add the selected TrueType or Type1 font to the fonts folder (that must be writeable).
     * @param string  $fontfile Font file (full path).
     * @param string  $fonttype Font type. Leave empty for autodetect mode. Valid values are: TrueTypeUnicode, TrueType, Type1.
     * @param string  $enc      Name of the encoding table to use. Leave empty for default mode. Omit this parameter for TrueType Unicode and symbolic fonts like Symbol or ZapfDingBats.
     * @param int     $flags    Unsigned 32-bit integer containing flags specifying various characteristics of the font (PDF32000:2008 - 9.8.2 Font Descriptor Flags): +1 for fixed font; +4 for symbol or +32 for non-symbol; +64 for italic. Fixed and Italic mode are generally autodetected so you have to set it to 32 = non-symbolic font (default) or 4 = symbolic font.
     * @param string  $outpath  Output path for generated font files (must be writeable by the web server). Leave empty for default font folder.
     * @param int     $platid   Platform ID for CMAP table to extract (when building a Unicode font for Windows this value should be 3, for Macintosh should be 1).
     * @param int     $encid    Encoding ID for CMAP table to extract (when building a Unicode font for Windows this value should be 1, for Macintosh should be 0). When Platform ID is 3, legal values for Encoding ID are: 0=Symbol, 1=Unicode, 2=ShiftJIS, 3=PRC, 4=Big5, 5=Wansung, 6=Johab, 7=Reserved, 8=Reserved, 9=Reserved, 10=UCS-4.
     * @param boolean $addcbbox If true includes the character bounding box information on the php font file.
     * @param boolean $link     If true link to system font instead of copying the font data (not transportable) - Note: do not work with Type1 fonts.
     * @return string|false TCPDF font name or boolean false in case of error.
     * @author Nicola Asuni
     */
    public function addTTFfont($fontfile, $fonttype = '', $enc = '', $flags = 32, $outpath = '', $platid = 3, $encid = 1, $addcbbox = false, $link = false)
    {
        if (!StaticMethods::file_exists($fontfile)) {
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
            $outpath = PDF::FONT_PATH;
        }
        // check if this font already exist
        if (@StaticMethods::file_exists($outpath . $font_name . '.php')) {
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
            if ($this->unsignedLong($font, 0) == 0x10000) {
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
            if (!empty($enc) and ($enc != 'cp1252') and isset(FontData::$encmap[$enc])) {
                // build differences from reference encoding
                $enc_ref    = FontData::$encmap['cp1252'];
                $enc_target = FontData::$encmap[$enc];
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
            $fp              = StaticMethods::fopenLocal($outpath . $fmetric['file'], 'wb');
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
            if (!empty($enc) and isset(FontData::$encmap[$enc])) {
                $enc_map = FontData::$encmap[$enc];
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
            if ($this->unsignedLong($font, $offset) != 0x10000) {
                // sfnt version must be 0x00010000 for TrueType version 1.0.
                return false;
            }
            if ($link) {
                // creates a symbolic link to the existing font
                symlink($fontfile, $outpath . $fmetric['file']);
            }
            else {
                // store compressed font
                $fmetric['file'] .= '.z';
                $fp              = StaticMethods::fopenLocal($outpath . $fmetric['file'], 'wb');
                fwrite($fp, gzcompress($font));
                fclose($fp);
            }
            $offset += 4;
            // get number of tables
            $numTables = StaticMethods::_getUSHORT($font, $offset);
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
                return false;
            }
            $offset += 4;
            $offset += 2; // skip flags
            // get FUnits
            $fmetric['unitsPerEm'] = StaticMethods::_getUSHORT($font, $offset);
            $offset                += 2;
            // units ratio constant
            $urk             = (1000 / $fmetric['unitsPerEm']);
            $offset          += 16; // skip created, modified
            $xMin            = round(StaticMethods::_getFWORD($font, $offset) * $urk);
            $offset          += 2;
            $yMin            = round(StaticMethods::_getFWORD($font, $offset) * $urk);
            $offset          += 2;
            $xMax            = round(StaticMethods::_getFWORD($font, $offset) * $urk);
            $offset          += 2;
            $yMax            = round(StaticMethods::_getFWORD($font, $offset) * $urk);
            $offset          += 2;
            $fmetric['bbox'] = '' . $xMin . ' ' . $yMin . ' ' . $xMax . ' ' . $yMax . '';
            $macStyle        = StaticMethods::_getUSHORT($font, $offset);
            $offset          += 2;
            // PDF font flags
            $fmetric['Flags'] = $flags;
            if (($macStyle & 2) == 2) {
                // italic flag
                $fmetric['Flags'] |= 64;
            }
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
                    $indexToLoc[$i] = $this->unsignedLong($font, $offset);
                    if (isset($indexToLoc[($i - 1)]) && ($indexToLoc[$i] == $indexToLoc[($i - 1)])) {
                        // the last glyph didn't have an outline
                        unset($indexToLoc[($i - 1)]);
                    }
                    $offset += 4;
                }
            }
            // get glyphs indexes of chars from cmap table
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
            // ---------- get os/2 metrics ----------
            $offset = $table['OS/2']['offset'];
            $offset += 2; // skip version
            // xAvgCharWidth
            $fmetric['AvgWidth'] = round(StaticMethods::_getFWORD($font, $offset) * $urk);
            $offset              += 2;
            // usWeightClass
            $usWeightClass = round(StaticMethods::_getUFWORD($font, $offset) * $urk);
            // estimate StemV and StemH (400 = usWeightClass for Normal - Regular font)
            $fmetric['StemV'] = round((70 * $usWeightClass) / 400);
            $fmetric['StemH'] = round((30 * $usWeightClass) / 400);
            $offset           += 2;
            $offset           += 2; // usWidthClass
            $fsType           = StaticMethods::_getSHORT($font, $offset);
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
            $numNameRecords = StaticMethods::_getUSHORT($font, $offset);
            $offset         += 2;
            // Offset to start of string storage (from start of table).
            $stringStorageOffset = StaticMethods::_getUSHORT($font, $offset);
            $offset              += 2;
            for ($i = 0; $i < $numNameRecords; ++$i) {
                $offset += 6; // skip Platform ID, Platform-specific encoding ID, Language ID.
                // Name ID.
                $nameID = StaticMethods::_getUSHORT($font, $offset);
                $offset += 2;
                if ($nameID == 6) {
                    // String length (in bytes).
                    $stringLength = StaticMethods::_getUSHORT($font, $offset);
                    $offset       += 2;
                    // String offset from start of storage area (in bytes).
                    $stringOffset    = StaticMethods::_getUSHORT($font, $offset);
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
            $fmetric['italicAngle']        = StaticMethods::_getFIXED($font, $offset);
            $offset                        += 4;
            $fmetric['underlinePosition']  = round(StaticMethods::_getFWORD($font, $offset) * $urk);
            $offset                        += 2;
            $fmetric['underlineThickness'] = round(StaticMethods::_getFWORD($font, $offset) * $urk);
            $offset                        += 2;
            $isFixedPitch                  = ($this->unsignedLong($font, $offset) == 0) ? false : true;
            $offset                        += 2;
            if ($isFixedPitch) {
                $fmetric['Flags'] |= 1;
            }
            // ---------- get hhea data ----------
            $offset = $table['hhea']['offset'];
            $offset += 4; // skip Table version number
            // Ascender
            $fmetric['Ascent'] = round(StaticMethods::_getFWORD($font, $offset) * $urk);
            $offset            += 2;
            // Descender
            $fmetric['Descent'] = round(StaticMethods::_getFWORD($font, $offset) * $urk);
            $offset             += 2;
            // LineGap
            $fmetric['Leading'] = round(StaticMethods::_getFWORD($font, $offset) * $urk);
            $offset             += 2;
            // advanceWidthMax
            $fmetric['MaxWidth'] = round(StaticMethods::_getUFWORD($font, $offset) * $urk);
            $offset              += 2;
            $offset              += 22; // skip some values
            // get the number of hMetric entries in hmtx table
            $numberOfHMetrics = StaticMethods::_getUSHORT($font, $offset);
            // ---------- get maxp data ----------
            $offset = $table['maxp']['offset'];
            $offset += 4; // skip Table version number
            // get the the number of glyphs in the font.
            $numGlyphs = StaticMethods::_getUSHORT($font, $offset);
            // ---------- get CIDToGIDMap ----------
            $ctg = [];
            $c   = 0;
            foreach ($encodingTables as $enctable) {
                // get only specified Platform ID and Encoding ID
                if (($enctable['platformID'] == $platid) and ($enctable['encodingID'] == $encid)) {
                    $offset = $table['cmap']['offset'] + $enctable['offset'];
                    $format = StaticMethods::_getUSHORT($font, $offset);
                    $offset += 2;
                    switch ($format) {
                        case 0:
                        { // Format 0: Byte encoding table
                            $offset += 4; // skip length and version/language
                            for ($c = 0; $c < 256; ++$c) {
                                $g       = StaticMethods::_getBYTE($font, $offset);
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
                            $firstCode  = StaticMethods::_getUSHORT($font, $offset);
                            $offset     += 2;
                            $entryCount = StaticMethods::_getUSHORT($font, $offset);
                            $offset     += 2;
                            for ($k = 0; $k < $entryCount; ++$k) {
                                $c       = ($k + $firstCode);
                                $g       = StaticMethods::_getUSHORT($font, $offset);
                                $offset  += 2;
                                $ctg[$c] = $g;
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
                                    $ctg[$c] = 0;
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
                                $c       = ($k + $startCharCode);
                                $g       = StaticMethods::_getUSHORT($font, $offset);
                                $ctg[$c] = $g;
                                $offset  += 2;
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
            $yMin               = StaticMethods::_getFWORD($font, $offset);
            $offset             += 4;
            $yMax               = StaticMethods::_getFWORD($font, $offset);
            $offset             += 2;
            $fmetric['XHeight'] = round(($yMax - $yMin) * $urk);
            // get CapHeight (height of H)
            $offset               = ($table['glyf']['offset'] + $indexToLoc[$ctg[72]] + 4);
            $yMin                 = StaticMethods::_getFWORD($font, $offset);
            $offset               += 4;
            $yMax                 = StaticMethods::_getFWORD($font, $offset);
            $offset               += 2;
            $fmetric['CapHeight'] = round(($yMax - $yMin) * $urk);
            // ceate widths array
            $cw     = [];
            $offset = $table['hmtx']['offset'];
            for ($i = 0; $i < $numberOfHMetrics; ++$i) {
                $cw[$i] = round(StaticMethods::_getUFWORD($font, $offset) * $urk);
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
                        $xMin             = round(StaticMethods::_getFWORD($font, $offset + 2) * $urk);
                        $yMin             = round(StaticMethods::_getFWORD($font, $offset + 4) * $urk);
                        $xMax             = round(StaticMethods::_getFWORD($font, $offset + 6) * $urk);
                        $yMax             = round(StaticMethods::_getFWORD($font, $offset + 8) * $urk);
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
            $fp = StaticMethods::fopenLocal($outpath . $fmetric['ctg'], 'wb');
            fwrite($fp, gzcompress($cidtogidmap));
            fclose($fp);
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
        $fp = StaticMethods::fopenLocal($outpath . $font_name . '.php', 'w');
        fwrite($fp, $pfile);
        fclose($fp);
        // return TCPDF font name
        return $font_name;
    }


}