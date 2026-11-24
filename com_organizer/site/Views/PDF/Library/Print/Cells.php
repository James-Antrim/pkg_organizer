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

namespace THM\Organizer\Views\PDF\Library\Print;

use THM\Organizer\Views\PDF\Library\StaticMethods;

trait Cells
{
#region //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////DONE

    protected float $cellHeightRatio = 1.25;
    protected float $cellLeftMargin = 0;
    protected array $cellMargins = [self::BOTTOM => 0, self::LEFT => 0, self::RIGHT => 0, self::TOP => 0];
    protected array $cellPaddings = [self::BOTTOM => 0, self::LEFT => 0, self::RIGHT => 0, self::TOP => 0];
    protected float $cellRightMargin = 0;

    /**
     * Calculate the cell height
     * @param float $size    font size in user units
     * @param bool  $padding whether to pad the cell
     * @return float
     */
    public function cellHeight(float $size, bool $padding = true): float
    {
        $height = $size * $this->cellHeightRatio;

        if ($padding and !empty($this->cellPaddings)) {
            $height += $this->cellPaddings[self::BOTTOM] + $this->cellPaddings[self::TOP];
        }

        return round($height, 6);
    }

    /**
     * Set the internal Cell margins.
     * @param float|null $left   left margin
     * @param float|null $top    top margin
     * @param float|null $right  right margin
     * @param float|null $bottom bottom margin
     */
    public function cellMargins(float|null $left = null, float|null $top = null, float|null $right = null, float|null $bottom = null): array
    {
        if (is_float($left) and ($left >= 0)) {
            $this->cellMargins['L'] = $left;
        }
        if (is_float($top) and ($top >= 0)) {
            $this->cellMargins['T'] = $top;
        }
        if (is_float($right) and ($right >= 0)) {
            $this->cellMargins['R'] = $right;
        }
        if (is_float($bottom) and ($bottom >= 0)) {
            $this->cellMargins['B'] = $bottom;
        }

        return $this->cellMargins;
    }

    /**
     * Sets a uniform padding for all cell paddings.
     * @param float $padding
     * @see   cellPaddings()
     */
    public function cellPadding(float $padding): void
    {
        if ($padding >= 0) {
            $this->cellPaddings['L'] = $padding;
            $this->cellPaddings['T'] = $padding;
            $this->cellPaddings['R'] = $padding;
            $this->cellPaddings['B'] = $padding;
        }
    }

    /**
     * Getter / setter for specific cell paddings.
     * @param float|null $left   left padding
     * @param float|null $top    top padding
     * @param float|null $right  right padding
     * @param float|null $bottom bottom padding
     * @see   cellPadding()
     */
    public function cellPaddings(float|null $left = null, float|null $top = null, float|null $right = null, float|null $bottom = null): array
    {
        if (is_float($left) and ($left >= 0)) {
            $this->cellPaddings['L'] = $left;
        }
        if (is_float($top) and ($top >= 0)) {
            $this->cellPaddings['T'] = $top;
        }
        if (is_float($right) and ($right >= 0)) {
            $this->cellPaddings['R'] = $right;
        }
        if (is_float($bottom) and ($bottom >= 0)) {
            $this->cellPaddings['B'] = $bottom;
        }

        return $this->cellPaddings;
    }


#endregion
#region /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


    /**
     * Prints a cell (rectangular area) with optional borders, background color and character string. The upper-left corner of the cell corresponds to the current position. The text can be aligned or centered. After the call, the current position moves to the right or to the next line. It is possible to put a link on the text.<br />
     * If automatic page breaking is enabled and the cell goes beyond the limit, a page break is done before outputting.
     * @param float  $w                 Cell width. If 0, the cell extends up to the right margin.
     * @param float  $h                 Cell height. Default value: 0.
     * @param string $txt               String to print. Default value: empty string.
     * @param mixed  $border            Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK])
     * @param int    $ln                Indicates where the current position should go after the call. Possible values are:<ul><li>0: to the right</li><li>1: to the beginning of the next line</li><li>2: below</li></ul> Putting 1 is equivalent to putting 0 and calling Ln() just after. Default value: 0.
     * @param string $align             Allows to center or align the text. Possible values are:<ul><li>L or empty string: left align (default value)</li><li>C: center</li><li>R: right align</li><li>J: justify</li></ul>
     * @param bool   $fill              Indicates if the cell background must be painted (true) or transparent (false).
     * @param mixed  $link              URL or identifier returned by AddLink().
     * @param int    $stretch           font stretch mode: <ul><li>0 = disabled</li><li>1 = horizontal scaling only if text is larger than cell width</li><li>2 = forced horizontal scaling to fit cell width</li><li>3 = character spacing only if text is larger than cell width</li><li>4 = forced character spacing to fit cell width</li></ul> General font stretching and scaling values will be preserved when possible.
     * @param bool   $ignore_min_height if true ignore automatic minimum height value.
     * @param string $calign            cell vertical alignment relative to the specified Y value. Possible values are:<ul><li>T : cell top</li><li>C : center</li><li>B : cell bottom</li><li>A : font top</li><li>L : font baseline</li><li>D : font bottom</li></ul>
     * @param string $valign            text vertical alignment inside the cell. Possible values are:<ul><li>T : top</li><li>C : center</li><li>B : bottom</li></ul>
     * @see   setCurrentFont(), drawColor(), fillColor(), textColor(), lineWidth(), AddLink(), Ln(), multiCell(), Write(), autoPageBreak()
     */
    public function cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M')
    {
        $previousMargins  = $this->cellMargins;
        $previousPaddings = $this->cellPaddings;
        $this->adjustCellPadding($border);
        if (!$ignore_min_height) {
            $min_cell_height = $this->cellHeight($this->fontSize);
            if ($h < $min_cell_height) {
                $h = $min_cell_height;
            }
        }
        $this->checkPageBreak($h + $this->cellMargins['T'] + $this->cellMargins['B']);
        // apply text shadow if enabled
        if ($this->txtshadow['enabled']) {
            // save data
            $x     = $this->x;
            $y     = $this->y;
            $bc    = $this->bgColor;
            $fc    = $this->fgColor;
            $sc    = $this->strokeColor;
            $alpha = $this->alpha;
            // print shadow
            $this->x += $this->txtshadow['depth_w'];
            $this->y += $this->txtshadow['depth_h'];
            $this->fillColorArray($this->txtshadow['color']);
            $this->textColorArray($this->txtshadow['color']);
            $this->drawColorArray($this->txtshadow['color']);
            if ($this->txtshadow['opacity'] != $alpha['CA']) {
                $this->alpha($this->txtshadow['opacity'], $this->txtshadow['blend_mode']);
            }
            if ($this->state === self::READY) {
                $this->append($this->getCellCode($w, $h, $txt, $border, $ln, $align, $fill, $link, $stretch, true, $calign, $valign));
            }
            //restore data
            $this->x = $x;
            $this->y = $y;
            $this->fillColorArray($bc);
            $this->textColorArray($fc);
            $this->drawColorArray($sc);
            if ($this->txtshadow['opacity'] != $alpha['CA']) {
                $this->alpha($alpha['CA'], $alpha['BM'], $alpha['ca'], $alpha['AIS']);
            }
        }
        if ($this->state === self::READY) {
            $this->append($this->getCellCode($w, $h, $txt, $border, $ln, $align, $fill, $link, $stretch, true, $calign, $valign));
        }
        $this->cellPaddings = $previousPaddings;
        $this->cellMargins  = $previousMargins;
    }

#endregion
#region ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * bool flag to indicate if the border of the cell sides that cross the page should be removed.
     */
    protected $opencell = true;

    /**
     * Adjust the internal Cell padding array to take account of the line width.
     * @param string|array|int|bool $brd Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: ['LTRB' => ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK]]
     * @return void|array array of adjustments
     */
    protected function adjustCellPadding($brd = 0)
    {
        if (empty($brd)) {
            return;
        }
        if (is_string($brd)) {
            // convert string to array
            $slen   = strlen($brd);
            $newbrd = [];
            for ($i = 0; $i < $slen; ++$i) {
                $newbrd[$brd[$i]] = true;
            }
            $brd = $newbrd;
        }
        elseif (
            ($brd === 1)
            || ($brd === true)
            || (is_numeric($brd) && ((int) $brd > 0))
        ) {
            $brd = ['LRTB' => true];
        }
        if (!is_array($brd)) {
            return;
        }
        // store current cell padding
        $cp = $this->cellPaddings;
        // select border mode
        if (isset($brd['mode'])) {
            $mode = $brd['mode'];
            unset($brd['mode']);
        }
        else {
            $mode = 'normal';
        }
        // process borders
        foreach ($brd as $border => $style) {
            $line_width = $this->lineWidth;
            if (is_array($style) && isset($style['width'])) {
                // get border width
                $line_width = $style['width'];
            }
            $adj = 0; // line width inside the cell
            switch ($mode) {
                case 'ext':
                {
                    $adj = 0;
                    break;
                }
                case 'int':
                {
                    $adj = $line_width;
                    break;
                }
                case 'normal':
                default:
                {
                    $adj = ($line_width / 2);
                    break;
                }
            }
            // correct internal cell padding if required to avoid overlap between text and lines
            if (
                is_numeric($this->cellPaddings['T'])
                && ($this->cellPaddings['T'] < $adj)
                && (strpos($border, 'T') !== false)
            ) {
                $this->cellPaddings['T'] = $adj;
            }
            if (
                is_numeric($this->cellPaddings['R'])
                && ($this->cellPaddings['R'] < $adj)
                && (strpos($border, 'R') !== false)
            ) {
                $this->cellPaddings['R'] = $adj;
            }
            if (
                is_numeric($this->cellPaddings['B'])
                && ($this->cellPaddings['B'] < $adj)
                && (strpos($border, 'B') !== false)
            ) {
                $this->cellPaddings['B'] = $adj;
            }
            if (
                is_numeric($this->cellPaddings['L'])
                && ($this->cellPaddings['L'] < $adj)
                && (strpos($border, 'L') !== false)
            ) {
                $this->cellPaddings['L'] = $adj;
            }

        }

        return [
            'T' => $this->cellPaddings['T'] - $cp['T'],
            'R' => $this->cellPaddings['R'] - $cp['R'],
            'B' => $this->cellPaddings['B'] - $cp['B'],
            'L' => $this->cellPaddings['L'] - $cp['L'],
        ];
    }

    /**
     * Returns the PDF string code to print a cell (rectangular area) with optional borders, background color and character string. The upper-left corner of the cell corresponds to the current position. The text can be aligned or centered. After the call, the current position moves to the right or to the next line. It is possible to put a link on the text.<br />
     * If automatic page breaking is enabled and the cell goes beyond the limit, a page break is done before outputting.
     * @param float  $w                 Cell width. If 0, the cell extends up to the right margin.
     * @param float  $h                 Cell height. Default value: 0.
     * @param string $txt               String to print. Default value: empty string.
     * @param mixed  $border            Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK])
     * @param int    $ln                Indicates where the current position should go after the call. Possible values are:<ul><li>0: to the right</li><li>1: to the beginning of the next line</li><li>2: below</li></ul>Putting 1 is equivalent to putting 0 and calling Ln() just after. Default value: 0.
     * @param string $align             Allows to center or align the text. Possible values are:<ul><li>L or empty string: left align (default value)</li><li>C: center</li><li>R: right align</li><li>J: justify</li></ul>
     * @param bool   $fill              Indicates if the cell background must be painted (true) or transparent (false).
     * @param mixed  $link              URL or identifier returned by AddLink().
     * @param int    $stretch           font stretch mode: <ul><li>0 = disabled</li><li>1 = horizontal scaling only if text is larger than cell width</li><li>2 = forced horizontal scaling to fit cell width</li><li>3 = character spacing only if text is larger than cell width</li><li>4 = forced character spacing to fit cell width</li></ul> General font stretching and scaling values will be preserved when possible.
     * @param bool   $ignore_min_height if true ignore automatic minimum height value.
     * @param string $calign            cell vertical alignment relative to the specified Y value. Possible values are:<ul><li>T : cell top</li><li>C : center</li><li>B : cell bottom</li><li>A : font top</li><li>L : font baseline</li><li>D : font bottom</li></ul>
     * @param string $valign            text vertical alignment inside the cell. Possible values are:<ul><li>T : top</li><li>M : middle</li><li>B : bottom</li></ul>
     * @return string containing cell code
     * @see   cell()
     */
    protected function getCellCode($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M')
    {
        // replace 'NO-BREAK SPACE' (U+00A0) character with a simple space
        $txt              = is_null($txt) ? '' : $txt;
        $txt              = str_replace($this->unicodeCharacter(160), ' ', $txt);
        $previousMargins  = $this->cellMargins;
        $previousPaddings = $this->cellPaddings;
        $txt              = $this->removeSHY($txt);
        $rs               = ''; //string to be returned
        $this->adjustCellPadding($border);
        if (!$ignore_min_height) {
            $min_cell_height = $this->cellHeight($this->fontSize);
            if ($h < $min_cell_height) {
                $h = $min_cell_height;
            }
        }
        $k = $this->factor;
        // check page for no-write regions and adapt page margins if necessary
        list($this->x, $this->y) = $this->checkPageRegions($h, $this->x, $this->y);
        $x                  = $this->x + $this->cellMargins['L'];
        $y                  = $this->y + $this->cellMargins['T'];
        $previousSpacing    = $this->fontSpacing;
        $previousStretching = $this->fontStretching;
        // cell vertical alignment
        switch ($calign) {
            case 'A':
            {
                // font top
                switch ($valign) {
                    case 'T':
                    {
                        // top
                        $y -= $this->cellPaddings['T'];
                        break;
                    }
                    case 'B':
                    {
                        // bottom
                        $y -= ($h - $this->cellPaddings['B'] - $this->fontAscent - $this->fontDescent);
                        break;
                    }
                    default:
                    case 'C':
                    case 'M':
                    {
                        // center
                        $y -= (($h - $this->fontAscent - $this->fontDescent) / 2);
                        break;
                    }
                }
                break;
            }
            case 'L':
            {
                // font baseline
                switch ($valign) {
                    case 'T':
                    {
                        // top
                        $y -= ($this->cellPaddings['T'] + $this->fontAscent);
                        break;
                    }
                    case 'B':
                    {
                        // bottom
                        $y -= ($h - $this->cellPaddings['B'] - $this->fontDescent);
                        break;
                    }
                    default:
                    case 'C':
                    case 'M':
                    {
                        // center
                        $y -= (($h + $this->fontAscent - $this->fontDescent) / 2);
                        break;
                    }
                }
                break;
            }
            case 'D':
            {
                // font bottom
                switch ($valign) {
                    case 'T':
                    {
                        // top
                        $y -= ($this->cellPaddings['T'] + $this->fontAscent + $this->fontDescent);
                        break;
                    }
                    case 'B':
                    {
                        // bottom
                        $y -= ($h - $this->cellPaddings['B']);
                        break;
                    }
                    default:
                    case 'C':
                    case 'M':
                    {
                        // center
                        $y -= (($h + $this->fontAscent + $this->fontDescent) / 2);
                        break;
                    }
                }
                break;
            }
            case 'B':
            {
                // cell bottom
                $y -= $h;
                break;
            }
            case 'C':
            case 'M':
            {
                // cell center
                $y -= ($h / 2);
                break;
            }
            default:
            case 'T':
            {
                // cell top
                break;
            }
        }
        // text vertical alignment
        switch ($valign) {
            case 'T':
            {
                // top
                $yt = $y + $this->cellPaddings['T'];
                break;
            }
            case 'B':
            {
                // bottom
                $yt = $y + $h - $this->cellPaddings['B'] - $this->fontAscent - $this->fontDescent;
                break;
            }
            default:
            case 'C':
            case 'M':
            {
                // center
                $yt = $y + (($h - $this->fontAscent - $this->fontDescent) / 2);
                break;
            }
        }
        $basefonty = $yt + $this->fontAscent;
        if (StaticMethods::empty_string($w) or ($w <= 0)) {
            $w = $this->width - $this->rightMargin - $x;
        }
        $s = '';
        // fill and borders
        if (is_string($border) and (strlen($border) == 4)) {
            // full border
            $border = 1;
        }
        if ($fill or ($border == 1)) {
            if ($fill) {
                $op = ($border == 1) ? self::BOLD : self::FILL;
            }
            else {
                $op = 'S';
            }
            $xk = ($x * $k);
            $s  .= sprintf('%F %F %F %F re %s ', $xk, (($this->height - $y) * $k), ($w * $k), (-$h * $k), $op);
        }
        // draw borders
        $s .= $this->getCellBorder($x, $y, $w, $h, $border);
        if ($txt != '') {
            $txt2 = $txt;
            if (($this->currentFont['type'] == 'core') or ($this->currentFont['type'] == 'TrueType') or ($this->currentFont['type'] == 'Type1')) {
                $txt2 = $this->utf8ToLatin1($txt2);
            }
            else {
                $unicode = $this->utf8StringToArray($txt); // array of UTF-8 unicode values
                $txt2    = $this->utf8ArrayToUTF16BEString($unicode);
            }
            $txt2 = $this->escape($txt2);
            // get current text width (considering general font stretching and spacing)
            $txwidth = $this->stringWidth($txt);
            $width   = $txwidth;
            // check for stretch mode
            if ($stretch > 0) {
                // calculate ratio between cell width and text width
                if ($width <= 0) {
                    $ratio = 1;
                }
                else {
                    $ratio = (($w - $this->cellPaddings['L'] - $this->cellPaddings['R']) / $width);
                }
                // check if stretching is required
                if (($ratio < 1) or (($ratio > 1) and (($stretch % 2) == 0))) {
                    // the text will be stretched to fit cell width
                    if ($stretch > 2) {
                        // set new character spacing
                        $this->fontSpacing += ($w - $this->cellPaddings['L'] - $this->cellPaddings['R'] - $width) / (max(($this->GetNumChars($txt) - 1), 1) * ($this->fontStretching / 100));
                    }
                    else {
                        // set new horizontal stretching
                        $this->fontStretching *= $ratio;
                    }
                    // recalculate text width (the text fills the entire cell)
                    $width = $w - $this->cellPaddings['L'] - $this->cellPaddings['R'];
                    // reset alignment
                    $align = '';
                }
            }
            if ($this->fontStretching != 100) {
                // apply font stretching
                $rs .= sprintf('BT %F Tz ET ', $this->fontStretching);
            }
            if ($this->fontSpacing != 0) {
                // increase/decrease font spacing
                $rs .= sprintf('BT %F Tc ET ', ($this->fontSpacing * $this->factor));
            }
            if ($this->differentColors and ($this->textrendermode < 4)) {
                $s .= 'q ' . $this->textColor . ' ';
            }
            // rendering mode
            $s .= sprintf('BT %d Tr %F w ET ', $this->textrendermode, ($this->textstrokewidth * $this->factor));
            // count number of spaces
            $ns = substr_count($txt, chr(32));
            // Justification
            $spacewidth = 0;
            if (($align == self::JUSTIFY) and ($ns > 0)) {
                // get string width
                $width = $txwidth;
                // new space width
                $spacewidth = (($w - $width - $this->cellPaddings['L'] - $this->cellPaddings['R']) / ($ns ? $ns : 1)) * $this->factor;
                if ($this->fontStretching != 100) {
                    // word spacing (Tw) is affected by stretching
                    $spacewidth /= ($this->fontStretching / 100);
                }
                // set word spacing
                $rs    .= sprintf('BT %F Tw ET ', $spacewidth);
                $width = $w - $this->cellPaddings['L'] - $this->cellPaddings['R'];
            }
            // replace carriage return characters
            $txt2 = str_replace("\r", ' ', $txt2);
            $dx   = match ($align) {
                self::CENTER => ($w - $width) / 2,
                self::RIGHT => $w - $width - $this->cellPaddings['R'],
                default => $this->cellPaddings['L'],
            };
            $xdx  = $x + $dx;
            $xdk  = $xdx * $k;
            // print text
            $s .= sprintf('BT %F %F Td [(%s)] TJ ET', $xdk, (($this->height - $basefonty) * $k), $txt2);
            if (isset($uniblock)) { // @phpstan-ignore-line
                // print overlapping characters as separate string
                $xshift = 0; // horizontal shift
                $ty     = (($this->height - $basefonty + (0.2 * $this->fontSize)) * $k);
                $spw    = (($w - $txwidth - $this->cellPaddings['L'] - $this->cellPaddings['R']) / ($ns ? $ns : 1));
                foreach ($uniblock as $uk => $uniarr) { // @phpstan-ignore-line
                    if (($uk % 2) == 0) {
                        // x space to skip
                        if ($spacewidth != 0) {
                            // justification shift
                            $xshift += (count(array_keys($uniarr, 32)) * $spw);
                        }
                        $xshift += $this->characterWidths($uniarr); // + shift justification
                    }
                    else {
                        // character to print
                        $topchr = $this->utf8ArrayToUTF16BEString($uniarr);
                        $topchr = $this->escape($topchr);
                        $s      .= sprintf(' BT %F %F Td [(%s)] TJ ET', ($xdk + ($xshift * $k)), $ty, $topchr);
                    }
                }
            }
            if ($this->underline) {
                $s .= ' ' . $this->_dounderlinew($xdx, $basefonty, $width);
            }
            if ($this->lineThrough) {
                $s .= ' ' . $this->_dolinethroughw($xdx, $basefonty, $width);
            }
            if ($this->overline) {
                $s .= ' ' . $this->_dooverlinew($xdx, $basefonty, $width);
            }
            if ($this->differentColors and ($this->textrendermode < 4)) {
                $s .= ' Q';
            }
            if ($link) {
                $this->annotateLink($xdx, $yt, $width, ($this->fontAscent + $this->fontDescent), $link, $ns);
            }
        }
        // output cell
        if ($s) {
            // output cell
            $rs .= $s;
            if ($this->fontSpacing != 0) {
                // reset font spacing mode
                $rs .= ' BT 0 Tc ET';
            }
            if ($this->fontStretching != 100) {
                // reset font stretching mode
                $rs .= ' BT 100 Tz ET';
            }
        }
        // reset word spacing
        if ($align === self::JUSTIFY) {
            $rs .= ' BT 0 Tw ET';
        }
        // reset stretching and spacing
        $this->fontStretching        = $previousStretching;
        $this->fontSpacing           = $previousSpacing;
        $this->lastPrintedCellHeight = $h;
        if ($ln > 0) {
            //Go to the beginning of the next line
            $this->y = $y + $h + $this->cellMargins['B'];
            if ($ln == 1) {
                $this->x = $this->leftMargin;
            }
        }
        else {
            $this->x = $x + $w + $this->cellMargins['R'];
        }
        $gstyles            = '' . $this->lineWidthStyle . ' ' . $this->lineCapStyle . ' ' . $this->lineJoinStyle . ' ' . $this->lineDashStyle . ' ' . $this->drawColor . ' ' . $this->fillColor . "\n";
        $rs                 = $gstyles . $rs;
        $this->cellPaddings = $previousPaddings;
        $this->cellMargins  = $previousMargins;
        return $rs;
    }

    /**
     * Returns the code to draw the cell border
     * @param float            $x   X coordinate.
     * @param float            $y   Y coordinate.
     * @param float            $w   Cell width.
     * @param float            $h   Cell height.
     * @param string|array|int $brd Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK])
     * @return string containing cell border code
     * @see   lineStyle()
     */
    protected function getCellBorder($x, $y, $w, $h, $brd)
    {
        $s = ''; // string to be returned
        if (empty($brd)) {
            return $s;
        }
        if ($brd == 1) {
            $brd = ['LRTB' => true];
        }
        // calculate coordinates for border
        $k   = $this->factor;
        $xeL = $x * $k;
        $xeR = ($x + $w) * $k;
        $yeL = (($this->height - ($y + $h)) * $k);
        $yeT = (($this->height - $y) * $k);
        $xeT = $xeL;
        $xeB = $xeR;
        $yeR = $yeT;
        $yeB = $yeL;
        if (is_string($brd)) {
            // convert string to array
            $slen   = strlen($brd);
            $newbrd = [];
            for ($i = 0; $i < $slen; ++$i) {
                $newbrd[$brd[$i]] = ['cap' => 'square', 'join' => 'miter'];
            }
            $brd = $newbrd;
        }
        if (isset($brd['mode'])) {
            $mode = $brd['mode'];
            unset($brd['mode']);
        }
        else {
            $mode = 'normal';
        }
        foreach ($brd as $border => $style) {
            if (is_array($style) and !empty($style)) {
                // apply border style
                $prev_style = $this->lineWidthStyle . ' ' . $this->lineCapStyle . ' ' . $this->lineJoinStyle . ' ' . $this->lineDashStyle . ' ' . $this->drawColor . ' ';
                $s          .= $this->lineStyle($style, true) . "\n";
            }
            switch ($mode) {
                case 'ext':
                {
                    $off = (($this->lineWidth / 2) * $k);
                    $xL  = $xeL - $off;
                    $xR  = $xeR + $off;
                    $yT  = $yeT + $off;
                    $yL  = $yeL - $off;
                    $xT  = $xL;
                    $xB  = $xR;
                    $yR  = $yT;
                    $yB  = $yL;
                    $w   += $this->lineWidth;
                    $h   += $this->lineWidth;
                    break;
                }
                case 'int':
                {
                    $off = ($this->lineWidth / 2) * $k;
                    $xL  = $xeL + $off;
                    $xR  = $xeR - $off;
                    $yT  = $yeT - $off;
                    $yL  = $yeL + $off;
                    $xT  = $xL;
                    $xB  = $xR;
                    $yR  = $yT;
                    $yB  = $yL;
                    $w   -= $this->lineWidth;
                    $h   -= $this->lineWidth;
                    break;
                }
                case 'normal':
                default:
                {
                    $xL = $xeL;
                    $xT = $xeT;
                    $xB = $xeB;
                    $xR = $xeR;
                    $yL = $yeL;
                    $yT = $yeT;
                    $yB = $yeB;
                    $yR = $yeR;
                    break;
                }
            }
            // draw borders by case
            if (strlen($border) == 4) {
                $s .= sprintf('%F %F %F %F re S ', $xT, $yT, ($w * $k), (-$h * $k));
            }
            elseif (strlen($border) == 3) {
                if (strpos($border, 'B') === false) { // LTR
                    $s .= sprintf('%F %F m ', $xL, $yL);
                    $s .= sprintf('%F %F l ', $xT, $yT);
                    $s .= sprintf('%F %F l ', $xR, $yR);
                    $s .= sprintf('%F %F l ', $xB, $yB);
                    $s .= 'S ';
                }
                elseif (strpos($border, 'L') === false) { // TRB
                    $s .= sprintf('%F %F m ', $xT, $yT);
                    $s .= sprintf('%F %F l ', $xR, $yR);
                    $s .= sprintf('%F %F l ', $xB, $yB);
                    $s .= sprintf('%F %F l ', $xL, $yL);
                    $s .= 'S ';
                }
                elseif (strpos($border, 'T') === false) { // RBL
                    $s .= sprintf('%F %F m ', $xR, $yR);
                    $s .= sprintf('%F %F l ', $xB, $yB);
                    $s .= sprintf('%F %F l ', $xL, $yL);
                    $s .= sprintf('%F %F l ', $xT, $yT);
                    $s .= 'S ';
                }
                elseif (strpos($border, 'R') === false) { // BLT
                    $s .= sprintf('%F %F m ', $xB, $yB);
                    $s .= sprintf('%F %F l ', $xL, $yL);
                    $s .= sprintf('%F %F l ', $xT, $yT);
                    $s .= sprintf('%F %F l ', $xR, $yR);
                    $s .= 'S ';
                }
            }
            elseif (strlen($border) == 2) {
                if ((strpos($border, 'L') !== false) and (strpos($border, 'T') !== false)) { // LT
                    $s .= sprintf('%F %F m ', $xL, $yL);
                    $s .= sprintf('%F %F l ', $xT, $yT);
                    $s .= sprintf('%F %F l ', $xR, $yR);
                    $s .= 'S ';
                }
                elseif ((strpos($border, 'T') !== false) and (strpos($border, 'R') !== false)) { // TR
                    $s .= sprintf('%F %F m ', $xT, $yT);
                    $s .= sprintf('%F %F l ', $xR, $yR);
                    $s .= sprintf('%F %F l ', $xB, $yB);
                    $s .= 'S ';
                }
                elseif ((strpos($border, 'R') !== false) and (strpos($border, 'B') !== false)) { // RB
                    $s .= sprintf('%F %F m ', $xR, $yR);
                    $s .= sprintf('%F %F l ', $xB, $yB);
                    $s .= sprintf('%F %F l ', $xL, $yL);
                    $s .= 'S ';
                }
                elseif ((strpos($border, 'B') !== false) and (strpos($border, 'L') !== false)) { // BL
                    $s .= sprintf('%F %F m ', $xB, $yB);
                    $s .= sprintf('%F %F l ', $xL, $yL);
                    $s .= sprintf('%F %F l ', $xT, $yT);
                    $s .= 'S ';
                }
                elseif ((strpos($border, 'L') !== false) and (strpos($border, 'R') !== false)) { // LR
                    $s .= sprintf('%F %F m ', $xL, $yL);
                    $s .= sprintf('%F %F l ', $xT, $yT);
                    $s .= 'S ';
                    $s .= sprintf('%F %F m ', $xR, $yR);
                    $s .= sprintf('%F %F l ', $xB, $yB);
                    $s .= 'S ';
                }
                elseif ((strpos($border, 'T') !== false) and (strpos($border, 'B') !== false)) { // TB
                    $s .= sprintf('%F %F m ', $xT, $yT);
                    $s .= sprintf('%F %F l ', $xR, $yR);
                    $s .= 'S ';
                    $s .= sprintf('%F %F m ', $xB, $yB);
                    $s .= sprintf('%F %F l ', $xL, $yL);
                    $s .= 'S ';
                }
            }
            else { // strlen($border) == 1
                if (strpos($border, 'L') !== false) { // L
                    $s .= sprintf('%F %F m ', $xL, $yL);
                    $s .= sprintf('%F %F l ', $xT, $yT);
                    $s .= 'S ';
                }
                elseif (strpos($border, 'T') !== false) { // T
                    $s .= sprintf('%F %F m ', $xT, $yT);
                    $s .= sprintf('%F %F l ', $xR, $yR);
                    $s .= 'S ';
                }
                elseif (strpos($border, 'R') !== false) { // R
                    $s .= sprintf('%F %F m ', $xR, $yR);
                    $s .= sprintf('%F %F l ', $xB, $yB);
                    $s .= 'S ';
                }
                elseif (strpos($border, 'B') !== false) { // B
                    $s .= sprintf('%F %F m ', $xB, $yB);
                    $s .= sprintf('%F %F l ', $xL, $yL);
                    $s .= 'S ';
                }
            }
            if (is_array($style) and !empty($style)) {
                // reset border style to previous value
                $s .= "\n" . $this->lineWidthStyle . ' ' . $this->lineCapStyle . ' ' . $this->lineJoinStyle . ' ' . $this->lineDashStyle . ' ' . $this->drawColor . "\n";
            }
        }
        return $s;
    }

    /**
     * This method allows printing text with line breaks.
     * They can be automatic (as soon as the text reaches the right border of the cell) or explicit (via the \n character). As many cells as necessary are output, one below the other.<br />
     * Text can be aligned, centered or justified. The cell block can be framed and the background painted.
     * @param float      $w           Width of cells. If 0, they extend up to the right margin of the page.
     * @param float      $h           Cell minimum height. The cell extends automatically if needed.
     * @param string     $txt         String to print
     * @param mixed      $border      Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK])
     * @param string     $align       Allows to center or align the text. Possible values are:<ul><li>L or empty string: left align</li><li>C: center</li><li>R: right align</li><li>J: justification (default value when $ishtml=false)</li></ul>
     * @param bool       $fill        Indicates if the cell background must be painted (true) or transparent (false).
     * @param int        $ln          Indicates where the current position should go after the call. Possible values are:<ul><li>0: to the right</li><li>1: to the beginning of the next line [DEFAULT]</li><li>2: below</li></ul>
     * @param float|null $x           x position in user units
     * @param float|null $y           y position in user units
     * @param bool       $reseth      if true reset the last cell height (default true).
     * @param int        $stretch     font stretch mode: <ul><li>0 = disabled</li><li>1 = horizontal scaling only if text is larger than cell width</li><li>2 = forced horizontal scaling to fit cell width</li><li>3 = character spacing only if text is larger than cell width</li><li>4 = forced character spacing to fit cell width</li></ul> General font stretching and scaling values will be preserved when possible.
     * @param bool       $ishtml      INTERNAL USE ONLY -- set to true if $txt is HTML content (default = false). Never set this parameter to true, use instead writeHTMLCell() or writeHTML() methods.
     * @param bool       $autopadding if true, uses internal padding and automatically adjust it to account for line width.
     * @param float      $maxh        maximum height. It should be >= $h and less then remaining space to the bottom of the page, or 0 for disable this feature. This feature works only when $ishtml=false.
     * @param string     $valign      Vertical alignment of text (requires $maxh = $h > 0). Possible values are:<ul><li>T: TOP</li><li>M: middle</li><li>B: bottom</li></ul>. This feature works only when $ishtml=false and the cell must fit in a single page.
     * @param bool       $fitcell     if true attempt to fit all the text within the cell by reducing the font size (do not work in HTML mode). $maxh must be greater than 0 and equal to $h.
     * @return int Return the number of cells or 1 for html mode.
     * @see   setCurrentFont(), drawColor(), fillColor(), textColor(), lineWidth(), cell(), Write(), autoPageBreak()
     */
    public function multiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false, $ln = 1, $x = null, $y = null, $reseth = true, $stretch = 0, $ishtml = false, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false)
    {
        $previousMargins  = $this->cellMargins;
        $previousPaddings = $this->cellPaddings;
        // adjust internal padding
        $this->adjustCellPadding($border);
        $mc_padding              = $this->cellPaddings;
        $mc_margin               = $this->cellMargins;
        $this->cellPaddings['T'] = 0;
        $this->cellPaddings['B'] = 0;
        $this->cellMargins(0, 0, 0, 0);
        if (StaticMethods::empty_string($this->lastPrintedCellHeight) or $reseth) {
            // reset row height
            $this->resetLastH();
        }
        if (!StaticMethods::empty_string($y)) {
            $this->y($y); // set y in order to convert negative y values to positive ones
        }
        $y     = $this->y();
        $resth = 0;
        if (($h > 0) and $this->inPageBody() and (($y + $h + $mc_margin['T'] + $mc_margin['B']) > $this->breakTrigger)) {
            // spit cell in more pages/columns
            $newh  = ($this->breakTrigger - $y);
            $resth = ($h - $newh); // cell to be printed on the next page/column
            $h     = $newh;
        }
        // get current page number
        $startpage = $this->page;
        // get current column
        $startcolumn = $this->currentColumn;
        if (!StaticMethods::empty_string($x)) {
            $this->x($x);
        }
        else {
            $x = $this->x();
        }
        // check page for no-write regions and adapt page margins if necessary
        list($x, $y) = $this->checkPageRegions(0, $x, $y);
        // apply margins
        $oy      = $y + $mc_margin['T'];
        $ox      = ($x + $mc_margin['L']);
        $this->x = $ox;
        $this->y = $oy;
        // set width
        if (StaticMethods::empty_string($w) or ($w <= 0)) {
            $w = ($this->width - $this->x - $this->rightMargin - $mc_margin['R']);
        }
        // store original margin values
        $lMargin               = $this->leftMargin;
        $rMargin               = $this->rightMargin;
        $this->leftMargin      = ($this->x);
        $this->rightMargin     = ($this->width - $this->x - $w);
        $this->cellLeftMargin  = $this->leftMargin;
        $this->cellRightMargin = $this->rightMargin;
        if ($autopadding) {
            // add top padding
            $this->y += $mc_padding['T'];
        }
        if ($ishtml) { // ******* Write HTML text
            $this->writeHTML($txt, true, false, $reseth, true, $align);
            $nl = 1;
        }
        else { // ******* Write simple text
            $previousFontSizePT = $this->fontSizePT;
            if ($fitcell) {
                // ajust height values
                $tobottom = ($this->height - $this->y - $this->bottomMargin - $this->cellPaddings['T'] - $this->cellPaddings['B']);
                $h        = $maxh = max(min($h, $tobottom), min($maxh, $tobottom));
            }
            // vertical alignment
            if ($maxh > 0) {
                // get text height
                $text_height = $this->getStringHeight($w, $txt, $reseth, $autopadding, $mc_padding, $border);
                if ($fitcell and ($text_height > $maxh) and ($this->fontSizePT > 1)) {
                    // try to reduce font size to fit text on cell (use a quick search algorithm)
                    $fmin         = 1;
                    $fmax         = $this->fontSizePT;
                    $diff_epsilon = (1 / $this->factor); // one point (min resolution)
                    $maxit        = (2 * min(100, max(10, intval($fmax)))); // max number of iterations
                    while ($maxit >= 0) {
                        $fmid = (($fmax + $fmin) / 2);
                        $this->fontSize($fmid, false);
                        $this->resetLastH();
                        $text_height = $this->getStringHeight($w, $txt, $reseth, $autopadding, $mc_padding, $border);
                        $diff        = ($maxh - $text_height);
                        if ($diff >= 0) {
                            if ($diff <= $diff_epsilon) {
                                break;
                            }
                            $fmin = $fmid;
                        }
                        else {
                            $fmax = $fmid;
                        }
                        --$maxit;
                    }
                    if ($maxit < 0) {
                        // premature exit, we get the minimum font value to fit the cell
                        $this->fontSize($fmin);
                        $this->resetLastH();
                        $text_height = $this->getStringHeight($w, $txt, $reseth, $autopadding, $mc_padding, $border);
                    }
                    else {
                        $this->fontSize($fmid);
                        $this->resetLastH();
                    }
                }
                if ($text_height < $maxh) {
                    if ($valign == 'M') {
                        // text vertically centered
                        $this->y += (($maxh - $text_height) / 2);
                    }
                    elseif ($valign == 'B') {
                        // text vertically aligned on bottom
                        $this->y += ($maxh - $text_height);
                    }
                }
            }
            $nl = $this->write($this->lastPrintedCellHeight, $txt, '', 0, $align, true, $stretch, false, true, $maxh, 0, $mc_margin);
            if ($fitcell) {
                // restore font size
                $this->fontSize($previousFontSizePT);
            }
        }
        if ($autopadding) {
            // add bottom padding
            $this->y += $mc_padding['B'];
        }
        // Get end-of-text Y position
        $currentY = $this->y;
        // get latest page number
        $endpage = $this->page;
        if ($resth > 0) {
            $skip     = ($endpage - $startpage);
            $tmpresth = $resth;
            while ($tmpresth > 0) {
                if ($skip <= 0) {
                    // add a page (or trig AcceptPageBreak() for multicolumn mode)
                    $this->checkPageBreak($this->breakTrigger + 1);
                }
                if ($this->columnCount > 1) {
                    $tmpresth -= ($this->height - $this->y - $this->bottomMargin);
                }
                else {
                    $tmpresth -= ($this->height - $this->topMargin - $this->bottomMargin);
                }
                --$skip;
            }
            $currentY = $this->y;
            $endpage  = $this->page;
        }
        // get latest column
        $endcolumn = $this->currentColumn;
        if ($this->columnCount == 0) {
            $this->columnCount = 1;
        }
        // disable page regions check
        $check_page_regions       = $this->check_page_regions;
        $this->check_page_regions = false;
        // get border modes
        $border_start  = StaticMethods::getBorderMode($border, $position = 'start', $this->opencell);
        $border_end    = StaticMethods::getBorderMode($border, $position = 'end', $this->opencell);
        $border_middle = StaticMethods::getBorderMode($border, $position = 'middle', $this->opencell);
        // design borders around HTML cells.
        for ($page = $startpage; $page <= $endpage; ++$page) { // for each page
            $ccode = '';
            $this->turnToPage($page);
            if ($this->columnCount < 2) {
                // single-column mode
                $this->x($x);
                $this->y = $this->topMargin;
            }
            // account for margin changes
            if ($page > $startpage) {
                if ($this->pageDimensions[$page]['olm'] !== $this->pageDimensions[$startpage]['olm']) {
                    $this->x += ($this->pageDimensions[$page]['olm'] - $this->pageDimensions[$startpage]['olm']);
                }
            }
            if ($startpage == $endpage) {
                // single page
                for ($column = $startcolumn; $column <= $endcolumn; ++$column) { // for each column
                    if ($column != $this->currentColumn) {
                        $this->selectColumn($column);
                    }
                    $this->x += $mc_margin['L'];
                    if ($startcolumn == $endcolumn) { // single column
                        $cborder = $border;
                        $h       = max($h, ($currentY - $oy));
                        $this->y = $oy;
                    }
                    elseif ($column == $startcolumn) { // first column
                        $cborder = $border_start;
                        $this->y = $oy;
                        $h       = $this->height - $this->y - $this->bottomMargin;
                    }
                    elseif ($column == $endcolumn) { // end column
                        $cborder = $border_end;
                        $h       = $currentY - $this->y;
                        if ($resth > $h) {
                            $h = $resth;
                        }
                    }
                    else { // middle column
                        $cborder = $border_middle;
                        $h       = $this->height - $this->y - $this->bottomMargin;
                        $resth   -= $h;
                    }
                    $ccode .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true) . "\n";
                } // end for each column
            }
            elseif ($page == $startpage) { // first page
                for ($column = $startcolumn; $column < $this->columnCount; ++$column) { // for each column
                    if ($column != $this->currentColumn) {
                        $this->selectColumn($column);
                    }
                    $this->x += $mc_margin['L'];
                    if ($column == $startcolumn) { // first column
                        $cborder = $border_start;
                        $this->y = $oy;
                        $h       = $this->height - $this->y - $this->bottomMargin;
                    }
                    else { // middle column
                        $cborder = $border_middle;
                        $h       = $this->height - $this->y - $this->bottomMargin;
                        $resth   -= $h;
                    }
                    $ccode .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true) . "\n";
                } // end for each column
            }
            elseif ($page == $endpage) { // last page
                for ($column = 0; $column <= $endcolumn; ++$column) { // for each column
                    if ($column != $this->currentColumn) {
                        $this->selectColumn($column);
                    }
                    $this->x += $mc_margin['L'];
                    if ($column == $endcolumn) {
                        // end column
                        $cborder = $border_end;
                        $h       = $currentY - $this->y;
                        if ($resth > $h) {
                            $h = $resth;
                        }
                    }
                    else {
                        // middle column
                        $cborder = $border_middle;
                        $h       = $this->height - $this->y - $this->bottomMargin;
                        $resth   -= $h;
                    }
                    $ccode .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true) . "\n";
                } // end for each column
            }
            else { // middle page
                for ($column = 0; $column < $this->columnCount; ++$column) { // for each column
                    $this->selectColumn($column);
                    $this->x += $mc_margin['L'];
                    $cborder = $border_middle;
                    $h       = $this->height - $this->y - $this->bottomMargin;
                    $resth   -= $h;
                    $ccode   .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true) . "\n";
                } // end for each column
            }
            if ($cborder or $fill) {
                $offsetlen = strlen($ccode);
                // draw border and fill
                if ($this->inXObject) {
                    // we are inside an XObject template
                    if (end($this->xObjects[$this->xObjectID]['transpositions']) !== false) {
                        $pagemarkkey                                                      = key($this->xObjects[$this->xObjectID]['transpositions']);
                        $pagemark                                                         = $this->xObjects[$this->xObjectID]['transpositions'][$pagemarkkey];
                        $this->xObjects[$this->xObjectID]['transpositions'][$pagemarkkey] += $offsetlen;
                    }
                    else {
                        $pagemark                                   = $this->xObjects[$this->xObjectID]['intmrk'];
                        $this->xObjects[$this->xObjectID]['intmrk'] += $offsetlen;
                    }
                    $pagebuff                                    = $this->xObjects[$this->xObjectID]['content'];
                    $pstart                                      = substr($pagebuff, 0, $pagemark);
                    $pend                                        = substr($pagebuff, $pagemark);
                    $this->xObjects[$this->xObjectID]['content'] = $pstart . $ccode . $pend;
                }
                else {
                    if (end($this->transpositions[$this->page]) !== false) {
                        $pagemarkkey                                     = key($this->transpositions[$this->page]);
                        $pagemark                                        = $this->transpositions[$this->page][$pagemarkkey];
                        $this->transpositions[$this->page][$pagemarkkey] += $offsetlen;
                    }
                    elseif ($this->inFooter) {
                        $pagemark                           = $this->footerPositions[$this->page];
                        $this->footerPositions[$this->page] += $offsetlen;
                    }
                    else {
                        $pagemark                  = $this->intmrk[$this->page];
                        $this->intmrk[$this->page] += $offsetlen;
                    }
                    $pagebuff = $this->pageBuffer($this->page);
                    $pstart   = substr($pagebuff, 0, $pagemark);
                    $pend     = substr($pagebuff, $pagemark);
                    $this->pageBuffer($this->page, $pstart . $ccode . $pend);
                }
            }
        } // end for each page
        // restore page regions check
        $this->check_page_regions = $check_page_regions;
        // Get end-of-cell Y position
        $currentY = $this->y();
        // restore previous values
        if ($this->columnCount > 1) {
            $this->selectColumn();
        }
        else {
            // restore original margins
            $this->leftMargin  = $lMargin;
            $this->rightMargin = $rMargin;
            if ($this->page > $startpage) {
                // check for margin variations between pages (i.e. booklet mode)
                $dl = ($this->pageDimensions[$this->page]['olm'] - $this->pageDimensions[$startpage]['olm']);
                $dr = ($this->pageDimensions[$this->page]['orm'] - $this->pageDimensions[$startpage]['orm']);
                if (($dl != 0) or ($dr != 0)) {
                    $this->leftMargin  += $dl;
                    $this->rightMargin += $dr;
                }
            }
        }
        if ($ln > 0) {
            //Go to the beginning of the next line
            $this->y($currentY + $mc_margin['B']);
            if ($ln == 2) {
                $this->x($x + $w + $mc_margin['L'] + $mc_margin['R']);
            }
        }
        else {
            // go left or right by case
            $this->turnToPage($startpage);
            $this->y = $y;
            $this->x($x + $w + $mc_margin['L'] + $mc_margin['R']);
        }
        $this->setContentMark();
        $this->cellPaddings    = $previousPaddings;
        $this->cellMargins     = $previousMargins;
        $this->cellLeftMargin  = $this->leftMargin;
        $this->cellRightMargin = $this->rightMargin;
        return $nl;
    }

#endregion
}