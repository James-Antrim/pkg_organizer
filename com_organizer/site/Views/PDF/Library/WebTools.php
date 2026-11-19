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

trait WebTools
{
    /** @var int[] RGB */
    protected array $linkColor = self::BLUE;

    /** Default font style to add to html links. */
    protected string $linkStyle = 'U';

    protected string $javascript = '';

    /**
     * Set the color and style for HTML links.
     * @param array  $color     RGB array of colors
     * @param string $fontStyle additional font styles to add
     */
    public function linkStyle(array $color = self::BLUE, string $fontStyle = 'U'): void
    {
        $this->linkColor = $color;
        $this->linkStyle = $fontStyle;
    }


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Output anchor link.
     * @param string     $url        link URL or internal link (i.e.: &lt;a href="#23,4.5"&gt;link to page 23 at 4.5 Y position&lt;/a&gt;)
     * @param string     $name       link name
     * @param bool       $fill       Indicates if the cell background must be painted (true) or transparent (false).
     * @param bool       $firstline  if true prints only the first line and return the remaining string.
     * @param array|null $color      array of RGB text color
     * @param string     $style      font style (U, D, B, I)
     * @param bool       $firstblock if true the string is the starting of a line.
     * @return int the number of cells used or the remaining text if $firstline = true;
     */
    public function addHtmlLink($url, $name, $fill = false, $firstline = false, $color = null, $style = -1, $firstblock = false)
    {
        if (isset($url[1]) and ($url[0] == '#') and is_numeric($url[1])) {
            // convert url to internal link
            $lnkdata = explode(',', $url);
            if (isset($lnkdata[0])) {
                $page = substr($lnkdata[0], 1);
                if (isset($lnkdata[1]) and (strlen($lnkdata[1]) > 0)) {
                    $lnky = floatval($lnkdata[1]);
                }
                else {
                    $lnky = 0;
                }
                $url = $this->AddLink();
                $this->setLink($url, $lnky, $page);
            }
        }
        // store current settings
        $prevcolor = $this->fgColor;
        $prevstyle = $this->fontStyle;
        if (empty($color)) {
            $this->textColorArray($this->linkColor);
        }
        else {
            $this->textColorArray($color);
        }
        if ($style == -1) {
            $this->font('', $this->fontStyle . $this->linkStyle);
        }
        else {
            $this->font('', $this->fontStyle . $style);
        }
        $ret = $this->Write($this->lastPrintedCellHeight, $name, $url, $fill, '', false, 0, $firstline, $firstblock, 0);
        // restore settings
        $this->font('', $prevstyle);
        $this->textColorArray($prevcolor);
        return $ret;
    }

    /**
     * Output a Table Of Content Index (TOC) using HTML templates.
     * This method must be called after all Bookmarks were set.
     * Before calling this method you have to open the page using the addTOCPage() method.
     * After calling this method you have to call endTOCPage() to close the TOC page.
     * @param int|null $page          page number where this TOC should be inserted (leave empty for current page).
     * @param string   $toc_name      name to use for TOC bookmark.
     * @param array    $templates     array of html templates. Use: "#TOC_DESCRIPTION#" for bookmark title, "#TOC_PAGE_NUMBER#" for page number.
     * @param bool     $correct_align if true correct the number alignment (numbers must be in monospaced font like courier and right aligned)
     * @param string   $style         Font style for title: B = Bold, I = Italic, BI = Bold + Italic.
     * @param array    $color         RGB color array for title (values from 0 to 255).
     * @author Nicola Asuni
     * @see    addTOCPage(), endTOCPage(), addTOC()
     */
    public function addHTMLTOC($page = null, $toc_name = 'TOC', $templates = [], $correct_align = true, $style = '', $color = self::BLACK)
    {
        $filler            = ' ';
        $previousLinkColor = $this->linkColor;
        $previousLinkStyle = $this->linkStyle;
        // set new style for link
        $this->linkColor = [];
        $this->linkStyle = '';
        $page_first      = $this->getPage();
        $page_fill_start = false;
        $page_fill_end   = false;
        // get the font type used for numbers in each template
        $current_font = $this->fontFamily;
        foreach ($templates as $level => $html) {
            $dom = $this->getHtmlDomArray($html);
            foreach ($dom as $key => $value) {
                if ($value['value'] == '#TOC_PAGE_NUMBER#') {
                    $this->font($dom[($key - 1)]['fontname']);
                    $templates['F' . $level] = false;
                }
            }
        }
        $this->font($current_font);
        $maxpage = 0; //used for pages on attached documents
        foreach ($this->outlines as $key => $outline) {
            // get HTML template
            $row = $templates[$outline['l']];
            if (StaticMethods::empty_string($page)) {
                $pagenum = $outline['p'];
            }
            else {
                // placemark to be replaced with the correct number
                $pagenum = '{#' . ($outline['p']) . '}';
                if (isset($templates['F' . $outline['l']]) && $templates['F' . $outline['l']]) {
                    $pagenum = '{' . $pagenum . '}';
                }
                $maxpage = max($maxpage, $outline['p']);
            }
            // replace templates with current values
            $row = str_replace('#TOC_DESCRIPTION#', $outline['t'], $row);
            $row = str_replace('#TOC_PAGE_NUMBER#', $pagenum, $row);
            // add link to page
            $row = '<a href="#' . $outline['p'] . ',' . $outline['y'] . '">' . $row . '</a>';
            // write bookmark entry
            $this->writeHTML($row, false, false, true, false, '');
        }
        // restore link styles
        $this->linkColor = $previousLinkColor;
        $this->linkStyle = $previousLinkStyle;
        // move TOC page and replace numbers
        $page_last = $this->getPage();
        $numpages  = ($page_last - $page_first + 1);
        // account for booklet mode
        if ($this->booklet) {
            // check if a blank page is required before TOC
            $page_fill_start = ((($page_first % 2) == 0) xor (($page % 2) == 0));
            $page_fill_end   = (!((($numpages % 2) == 0) xor ($page_fill_start)));
            if ($page_fill_start) {
                // add a page at the end (to be moved before TOC)
                $this->addPage();
                ++$page_last;
                ++$numpages;
            }
            if ($page_fill_end) {
                // add a page at the end
                $this->addPage();
                ++$page_last;
                ++$numpages;
            }
        }
        $maxpage = max($maxpage, $page_last);
        if (!StaticMethods::empty_string($page)) {
            for ($p = $page_first; $p <= $page_last; ++$p) {
                // get page data
                $temppage = $this->pageBuffer($p);
                for ($n = 1; $n <= $maxpage; ++$n) {
                    // update page numbers
                    $a = '{#' . $n . '}';
                    // get page number aliases
                    $pnalias = $this->getInternalPageNumberAliases($a);
                    // calculate replacement number
                    if ($n >= $page) {
                        $np = $n + $numpages;
                    }
                    else {
                        $np = $n;
                    }
                    $na = StaticMethods::formatTOCPageNumber(($this->starting_page_number + $np - 1));
                    $nu = $this->utf8ToUTF16BE($na);
                    // replace aliases with numbers
                    foreach ($pnalias['u'] as $u) {
                        if ($correct_align) {
                            $sfill = str_repeat($filler, (strlen($u) - strlen($nu . ' ')));
                            $nr    = $this->utf8ToUTF16BE($sfill . ' ') . $nu;
                        }
                        else {
                            $nr = $nu;
                        }
                        $temppage = str_replace($u, $nr, $temppage);
                    }
                    foreach ($pnalias['a'] as $a) {
                        if ($correct_align) {
                            $sfill = str_repeat($filler, (strlen($a) - strlen($na . ' ')));
                            $nr    = $sfill . ' ' . $na;
                        }
                        else {
                            $nr = $na;
                        }
                        $temppage = str_replace($a, $nr, $temppage);
                    }
                }
                // save changes
                $this->pageBuffer($p, $temppage);
            }
            // move pages
            $this->Bookmark($toc_name, 0, 0, $page_first, $style, $color);
            if ($page_fill_start) {
                $this->movePage($page_last, $page_first);
            }
            for ($i = 0; $i < $numpages; ++$i) {
                $this->movePage($page_last, $page);
            }
        }
    }

    /**
     * Output an HTML list bullet or ordered item symbol
     * @param int    $listdepth list nesting level
     * @param string $listtype  type of list
     * @param float  $size      current font size
     */
    protected function putHtmlListBullet($listdepth, $listtype = '', $size = 10)
    {
        if ($this->state !== self::READY) {
            return;
        }
        $size        /= $this->factor;
        $fill        = '';
        $bgColor     = $this->bgColor;
        $color       = $this->fgColor;
        $strokecolor = $this->strokeColor;
        $width       = 0;
        $textitem    = '';
        $tmpx        = $this->x;
        $lspace      = $this->GetStringWidth('  ');
        if ($listtype == '^') {
            // special symbol used for avoid justification of rect bullet
            $this->lispacer = '';
            return;
        }
        elseif ($listtype == '!') {
            // set default list type for unordered list
            $deftypes = ['disc', 'circle', 'square'];
            $listtype = $deftypes[($listdepth - 1) % 3];
        }
        elseif ($listtype == '#') {
            // set default list type for ordered list
            $listtype = 'decimal';
        }
        elseif (substr($listtype, 0, 4) == 'img|') {
            // custom image type ('img|type|width|height|image.ext')
            $img      = explode('|', $listtype);
            $listtype = 'img';
        }
        switch ($listtype) {
            // unordered types
            case 'none':
            {
                break;
            }
            case 'disc':
            {
                $r       = $size / 6;
                $lspace  += (2 * $r);
                $this->x -= $lspace;
                $this->circle(($this->x + $r), ($this->y + ($this->lastPrintedCellHeight / 2)), $r, 0, 360, 'F', [], $color, 8);
                break;
            }
            case 'circle':
            {
                $r               = $size / 6;
                $lspace          += (2 * $r);
                $this->x         -= $lspace;
                $prev_line_style = $this->lineWidthStyle . ' ' . $this->lineCapStyle . ' ' . $this->lineJoinStyle . ' ' . $this->lineDashStyle . ' ' . $this->drawColor;
                $new_line_style  = array('width' => ($r / 3), 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'phase' => 0, 'color' => $color);
                $this->circle(($this->x + $r), ($this->y + ($this->lastPrintedCellHeight / 2)), ($r * (1 - (1 / 6))), 0, 360, 'D', $new_line_style, [], 8);
                $this->append($prev_line_style); // restore line settings
                break;
            }
            case 'square':
            {
                $l       = $size / 3;
                $lspace  += $l;
                $this->x -= $lspace;
                $this->rectangle($this->x, ($this->y + (($this->lastPrintedCellHeight - $l) / 2)), $l, $l, 'F', [], $color);
                break;
            }
            case 'img':
            {
                // 1=>type, 2=>width, 3=>height, 4=>image.ext
                $lspace  += $img[2];
                $this->x -= $lspace;
                $imgtype = strtolower($img[1]);
                $prev_y  = $this->y;
                switch ($imgtype) {
                    case 'svg':
                    {
                        $this->ImageSVG($img[4], $this->x, ($this->y + (($this->lastPrintedCellHeight - $img[3]) / 2)), $img[2], $img[3], '', 'T', '', 0, false);
                        break;
                    }
                    case 'ai':
                    case 'eps':
                    {
                        $this->ImageEps($img[4], $this->x, ($this->y + (($this->lastPrintedCellHeight - $img[3]) / 2)), $img[2], $img[3], '', true, 'T', '', 0, false);
                        break;
                    }
                    default:
                    {
                        $this->image($img[4], $this->x, ($this->y + (($this->lastPrintedCellHeight - $img[3]) / 2)), $img[2], $img[3], $img[1], '', 'T', false, 300, '', false, false, 0, false, false, false);
                        break;
                    }
                }
                $this->y = $prev_y;
                break;
            }
            // ordered types
            // $this->listcount[$this->listnum];
            // $textitem
            case '1':
            case 'decimal':
            {
                $textitem = $this->listcount[$this->listnum];
                break;
            }
            case 'decimal-leading-zero':
            {
                $textitem = sprintf('%02d', $this->listcount[$this->listnum]);
                break;
            }
            case 'i':
            case 'lower-roman':
            {
                $textitem = strtolower($this->intToRoman($this->listcount[$this->listnum]));
                break;
            }
            case 'I':
            case 'upper-roman':
            {
                $textitem = $this->intToRoman($this->listcount[$this->listnum]);
                break;
            }
            case 'a':
            case 'lower-alpha':
            case 'lower-latin':
            {
                $textitem = chr(97 + $this->listcount[$this->listnum] - 1);
                break;
            }
            case 'A':
            case 'upper-alpha':
            case 'upper-latin':
            {
                $textitem = chr(65 + $this->listcount[$this->listnum] - 1);
                break;
            }
            case 'lower-greek':
            {
                $textitem = $this->unicodeCharacter((945 + $this->listcount[$this->listnum] - 1), $this->isUnicode);
                break;
            }
            /*
			// Types to be implemented (special handling)
			case 'hebrew': {
				break;
			}
			case 'armenian': {
				break;
			}
			case 'georgian': {
				break;
			}
			case 'cjk-ideographic': {
				break;
			}
			case 'hiragana': {
				break;
			}
			case 'katakana': {
				break;
			}
			case 'hiragana-iroha': {
				break;
			}
			case 'katakana-iroha': {
				break;
			}
			*/
            default:
            {
                $textitem = $this->listcount[$this->listnum];
            }
        }
        if (!StaticMethods::empty_string($textitem)) {
            // Check whether we need a new page or new column
            $prev_y = $this->y;
            $h      = $this->getCellHeight($this->fontSize);
            if ($this->checkPageBreak($h) or ($this->y < $prev_y)) {
                $tmpx = $this->x;
            }
            // print ordered item
            $textitem = $textitem . '.';
            $lspace   += $this->GetStringWidth($textitem);
            $this->x  -= $lspace;
            $this->Write($this->lastPrintedCellHeight, $textitem, '', false, '', false, 0, false);
        }
        $this->x        = $tmpx;
        $this->lispacer = '^';
        // restore colors
        $this->fillColorArray($bgColor);
        $this->drawColorArray($strokecolor);
        $this->textColorArray($color);
    }

    /**
     * Prints a cell (rectangular area) with optional borders, background color and html text string.
     * The upper-left corner of the cell corresponds to the current position. After the call, the current position moves to the right or to the next line.<br />
     * If automatic page breaking is enabled and the cell goes beyond the limit, a page break is done before outputting.
     * IMPORTANT: The HTML must be well formatted - try to clean-up it using an application like HTML-Tidy before submitting.
     * Supported tags are: a, b, blockquote, br, dd, del, div, dl, dt, em, font, h1, h2, h3, h4, h5, h6, hr, i, img, li, ol, p, pre, small, span, strong, sub, sup, table, tcpdf, td, th, thead, tr, tt, u, ul
     * NOTE: all the HTML attributes must be enclosed in double-quote.
     * @param float      $w           Cell width. If 0, the cell extends up to the right margin.
     * @param float      $h           Cell minimum height. The cell extends automatically if needed.
     * @param float|null $x           upper-left corner X coordinate
     * @param float|null $y           upper-left corner Y coordinate
     * @param string     $html        html text to print. Default value: empty string.
     * @param mixed      $border      Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK])
     * @param int        $ln          Indicates where the current position should go after the call. Possible values are:<ul><li>0: to the right</li><li>1: to the beginning of the next line</li><li>2: below</li></ul>
     *                                Putting 1 is equivalent to putting 0 and calling Ln() just after. Default value: 0.
     * @param bool       $fill        Indicates if the cell background must be painted (true) or transparent (false).
     * @param bool       $reseth      if true reset the last cell height (default true).
     * @param string     $align       Allows to center or align the text. Possible values are:<ul><li>L : left align</li><li>C : center</li><li>R : right align</li><li>'' : empty string : left</li></ul>
     * @param bool       $autopadding if true, uses internal padding and automatically adjust it to account for line width.
     * @see Multicell(), writeHTML()
     */
    public function writeHTMLCell($w, $h, $x, $y, $html = '', $border = 0, $ln = 0, $fill = false, $reseth = true, $align = '', $autopadding = true)
    {
        return $this->MultiCell($w, $h, $html, $border, $align, $fill, $ln, $x, $y, $reseth, 0, true, $autopadding, 0, 'T', false);
    }

    /**
     * Allows to preserve some HTML formatting (limited support).<br />
     * IMPORTANT: The HTML must be well formatted - try to clean-up it using an application like HTML-Tidy before submitting.
     * Supported tags are: a, b, blockquote, br, dd, del, div, dl, dt, em, font, h1, h2, h3, h4, h5, h6, hr, i, img, li, ol, p, pre, small, span, strong, sub, sup, table, tcpdf, td, th, thead, tr, tt, u, ul
     * NOTE: all the HTML attributes must be enclosed in double-quote.
     * @param string $html   text to display
     * @param bool   $ln     if true add a new line after text (default = true)
     * @param bool   $fill   Indicates if the background must be painted (true) or transparent (false).
     * @param bool   $reseth if true reset the last cell height (default false).
     * @param bool   $cell   if true add the current left padding to each Write (default false).
     * @param string $align  Allows to center or align the text. Possible values are:<ul><li>L : left align</li><li>C : center</li><li>R : right align</li><li>'' : empty string : left</li></ul>
     */
    public function writeHTML($html, $ln = true, $fill = false, $reseth = false, $cell = false, $align = '')
    {
        $gvars = $this->graphicsConfiguration();
        // store current values
        $previousMargins  = $this->cellMargins;
        $previousPaddings = $this->cellPaddings;
        $prevPage         = $this->page;
        $prevlMargin      = $this->leftMargin;
        $prevrMargin      = $this->rightMargin;
        $curfontname      = $this->fontFamily;
        $curfontstyle     = $this->fontStyle;
        $curfontsize      = $this->fontSizePT;
        $curfontascent    = $this->getFontAscent($curfontname, $curfontstyle, $curfontsize);
        $curfontdescent   = $this->getFontDescent($curfontname, $curfontstyle, $curfontsize);
        $curfontstretcing = $this->fontStretching;
        $curfonttracking  = $this->fontSpacing;
        $this->newLine    = true;
        $newline          = true;
        $startlinepage    = $this->page;
        $minstartliney    = $this->y;
        $maxbottomliney   = 0;
        $startlinex       = $this->x;
        $startliney       = $this->y;
        $yshift           = 0;
        $loop             = 0;
        $curpos           = 0;
        $this_method_vars = [];
        $undo             = false;
        $fontaligned      = false;
        $this->premode    = false;
        if ($this->inXObject) {
            // we are inside an XObject template
            $pask = count($this->xObjects[$this->xObjectID]['annotations']);
        }
        elseif (isset($this->pageAnnotations[$this->page])) {
            $pask = count($this->pageAnnotations[$this->page]);
        }
        else {
            $pask = 0;
        }
        if ($this->inXObject) {
            // we are inside an XObject template
            $startlinepos = strlen($this->xObjects[$this->xObjectID]['content']);
        }
        elseif (!$this->inFooter) {
            if (isset($this->footerLengths[$this->page])) {
                $this->footerpos[$this->page] = $this->pageLengths[$this->page] - $this->footerLengths[$this->page];
            }
            else {
                $this->footerpos[$this->page] = $this->pageLengths[$this->page];
            }
            $startlinepos = $this->footerpos[$this->page];
        }
        else {
            // we are inside the footer
            $startlinepos = $this->pageLengths[$this->page];
        }
        $lalign  = $align;
        $plalign = $align;
        $w       = $this->width - $this->rightMargin - $this->x;
        $w       -= ($this->cellPaddings['L'] + $this->cellPaddings['R']);
        if ($cell) {
            $this->x           += $this->cellPaddings['L'];
            $this->rightMargin += $this->cellPaddings['R'];
        }
        if ($this->customlistindent >= 0) {
            $this->listindent = $this->customlistindent;
        }
        else {
            $this->listindent = $this->GetStringWidth('000000');
        }
        $this->listindentlevel = 0;
        // save previous states
        $previousHeightRatio = $this->cellHeightRatio;
        $prev_listnum        = $this->listnum;
        $prev_listordered    = $this->listordered;
        $prev_listcount      = $this->listcount;
        $prev_lispacer       = $this->lispacer;
        $this->listnum       = 0;
        $this->listordered   = [];
        $this->listcount     = [];
        $this->lispacer      = '';
        if ((StaticMethods::empty_string($this->lastPrintedCellHeight)) or ($reseth)) {
            // reset row height
            $this->resetLastH();
        }
        $dom   = $this->getHtmlDomArray($html);
        $maxel = count($dom);
        $key   = 0;
        while ($key < $maxel) {
            if ($dom[$key]['tag'] and $dom[$key]['opening'] and $dom[$key]['hide']) {
                // store the node key
                $hidden_node_key = $key;
                if ($dom[$key]['self']) {
                    // skip just this self-closing tag
                    ++$key;
                }
                else {
                    // skip this and all children tags
                    while (($key < $maxel) and (!$dom[$key]['tag'] or $dom[$key]['opening'] or ($dom[$key]['parent'] != $hidden_node_key))) {
                        // skip hidden objects
                        ++$key;
                    }
                    ++$key;
                }
            }
            if ($key == $maxel) break;
            if ($dom[$key]['tag'] and isset($dom[$key]['attribute']['pagebreak'])) {
                // check for pagebreak
                if (($dom[$key]['attribute']['pagebreak'] == 'true') or ($dom[$key]['attribute']['pagebreak'] == 'left') or ($dom[$key]['attribute']['pagebreak'] == 'right')) {
                    // add a page (or trig AcceptPageBreak() for multicolumn mode)
                    $this->checkPageBreak($this->breakTrigger + 1);
                    $this->htmlvspace = ($this->breakTrigger + 1);
                }
                if (($dom[$key]['attribute']['pagebreak'] === 'left' and $this->page % 2 === 0) or ($dom[$key]['attribute']['pagebreak'] === 'right' and $this->page % 2 !== 0)) {
                    // add a page (or trig AcceptPageBreak() for multicolumn mode)
                    $this->checkPageBreak($this->breakTrigger + 1);
                    $this->htmlvspace = ($this->breakTrigger + 1);
                }
            }
            if ($dom[$key]['tag'] and $dom[$key]['opening'] and isset($dom[$key]['attribute']['nobr']) and ($dom[$key]['attribute']['nobr'] == 'true')) {
                if (isset($dom[($dom[$key]['parent'])]['attribute']['nobr']) and ($dom[($dom[$key]['parent'])]['attribute']['nobr'] == 'true')) {
                    $dom[$key]['attribute']['nobr'] = false;
                }
                else {
                    // store current object
                    $this->startTransaction();
                    // save this method vars
                    $this_method_vars['html']                = $html;
                    $this_method_vars['ln']                  = $ln;
                    $this_method_vars['fill']                = $fill;
                    $this_method_vars['reseth']              = $reseth;
                    $this_method_vars['cell']                = $cell;
                    $this_method_vars['align']               = $align;
                    $this_method_vars['gvars']               = $gvars;
                    $this_method_vars['prevPage']            = $prevPage;
                    $this_method_vars['previousMargins']     = $previousMargins;
                    $this_method_vars['previousPaddings']    = $previousPaddings;
                    $this_method_vars['prevlMargin']         = $prevlMargin;
                    $this_method_vars['prevrMargin']         = $prevrMargin;
                    $this_method_vars['curfontname']         = $curfontname;
                    $this_method_vars['curfontstyle']        = $curfontstyle;
                    $this_method_vars['curfontsize']         = $curfontsize;
                    $this_method_vars['curfontascent']       = $curfontascent;
                    $this_method_vars['curfontdescent']      = $curfontdescent;
                    $this_method_vars['curfontstretcing']    = $curfontstretcing;
                    $this_method_vars['curfonttracking']     = $curfonttracking;
                    $this_method_vars['minstartliney']       = $minstartliney;
                    $this_method_vars['maxbottomliney']      = $maxbottomliney;
                    $this_method_vars['yshift']              = $yshift;
                    $this_method_vars['startlinepage']       = $startlinepage;
                    $this_method_vars['startlinepos']        = $startlinepos;
                    $this_method_vars['startlinex']          = $startlinex;
                    $this_method_vars['startliney']          = $startliney;
                    $this_method_vars['newline']             = $newline;
                    $this_method_vars['loop']                = $loop;
                    $this_method_vars['curpos']              = $curpos;
                    $this_method_vars['pask']                = $pask;
                    $this_method_vars['lalign']              = $lalign;
                    $this_method_vars['plalign']             = $plalign;
                    $this_method_vars['w']                   = $w;
                    $this_method_vars['previousHeightRatio'] = $previousHeightRatio;
                    $this_method_vars['prev_listnum']        = $prev_listnum;
                    $this_method_vars['prev_listordered']    = $prev_listordered;
                    $this_method_vars['prev_listcount']      = $prev_listcount;
                    $this_method_vars['prev_lispacer']       = $prev_lispacer;
                    $this_method_vars['fontaligned']         = $fontaligned;
                    $this_method_vars['key']                 = $key;
                    $this_method_vars['dom']                 = $dom;
                }
            }
            // print THEAD block
            if (($dom[$key]['value'] == 'tr') and isset($dom[$key]['thead']) and $dom[$key]['thead']) {
                if (isset($dom[$key]['parent']) and isset($dom[$dom[$key]['parent']]['thead']) and !StaticMethods::empty_string($dom[$dom[$key]['parent']]['thead'])) {
                    $this->inthead = true;
                    // print table header (thead)
                    $this->writeHTML($this->headerTemplate, false, false, false, false, '');
                    // check if we are on a new page or on a new column
                    if (($this->y < $this->start_transaction_y) or ($this->checkPageBreak($this->lastPrintedCellHeight, '', false))) {
                        // we are on a new page or on a new column and the total object height is less than the available vertical space.
                        // restore previous object
                        $this->rollbackTransaction(true);
                        // restore previous values
                        foreach ($this_method_vars as $vkey => $vval) {
                            $$vkey = $vval;
                        }
                        // disable table header
                        $tmp_thead            = $this->headerTemplate;
                        $this->headerTemplate = '';
                        // add a page (or trig AcceptPageBreak() for multicolumn mode)
                        $pre_y = $this->y;
                        if ((!$this->checkPageBreak($this->breakTrigger + 1)) and ($this->y < $pre_y)) {
                            // fix for multicolumn mode
                            $startliney = $this->y;
                        }
                        $this->start_transaction_page = $this->page;
                        $this->start_transaction_y    = $this->y;
                        // restore table header
                        $this->headerTemplate = $tmp_thead;
                        // fix table border properties
                        if (isset($dom[$dom[$key]['parent']]['attribute']['cellspacing'])) {
                            $tmp_cellspacing = $this->fontSizeToUnits($dom[$dom[$key]['parent']]['attribute']['cellspacing']);
                        }
                        elseif (isset($dom[$dom[$key]['parent']]['border-spacing'])) {
                            $tmp_cellspacing = $dom[$dom[$key]['parent']]['border-spacing']['V'];
                        }
                        else {
                            $tmp_cellspacing = 0;
                        }
                        $dom[$dom[$key]['parent']]['borderposition']['page']   = $this->page;
                        $dom[$dom[$key]['parent']]['borderposition']['column'] = $this->current_column;
                        $dom[$dom[$key]['parent']]['borderposition']['y']      = $this->y + $tmp_cellspacing;
                        $xoffset                                               = ($this->x - $dom[$dom[$key]['parent']]['borderposition']['x']);
                        $dom[$dom[$key]['parent']]['borderposition']['x']      += $xoffset;
                        $dom[$dom[$key]['parent']]['borderposition']['xmax']   += $xoffset;
                        // print table header (thead)
                        $this->writeHTML($this->headerTemplate, false, false, false, false, '');
                    }
                }
                // move $key index forward to skip THEAD block
                while (($key < $maxel) and (!(
                        ($dom[$key]['tag'] and $dom[$key]['opening'] and ($dom[$key]['value'] == 'tr') and (!isset($dom[$key]['thead']) or !$dom[$key]['thead']))
                        or ($dom[$key]['tag'] and (!$dom[$key]['opening']) and ($dom[$key]['value'] == 'table'))))) {
                    ++$key;
                }
            }
            if ($dom[$key]['tag'] or ($key == 0)) {
                if ((($dom[$key]['value'] == 'table') or ($dom[$key]['value'] == 'tr')) and (isset($dom[$key]['align']))) {
                    $dom[$key]['align'] = 'L';
                }
                // vertically align image in line
                if ((!$this->newLine) and ($dom[$key]['value'] == 'img') and (isset($dom[$key]['height'])) and ($dom[$key]['height'] > 0)) {
                    // get image height
                    $imgh          = $this->fontSizeToUnits($dom[$key]['height'], ($dom[$key]['fontsize'] / $this->factor));
                    $autolinebreak = false;
                    if (!empty($dom[$key]['width'])) {
                        $imgw = $this->fontSizeToUnits($dom[$key]['width'], ($dom[$key]['fontsize'] / $this->factor));
                        if (($imgw <= ($this->width - $this->leftMargin - $this->rightMargin - $this->cellPaddings['L'] - $this->cellPaddings['R'])) and ($this->x + $imgw) > ($this->width - $this->rightMargin - $this->cellPaddings['R'])) {
                            // add automatic line break
                            $autolinebreak = true;
                            $this->Ln('', $cell);
                            if ((!$dom[($key - 1)]['tag']) and ($dom[($key - 1)]['value'] == ' ')) {
                                // go back to evaluate this line break
                                --$key;
                            }
                        }
                    }
                    if (!$autolinebreak) {
                        if ($this->inPageBody()) {
                            $pre_y = $this->y;
                            // check for page break
                            if ((!$this->checkPageBreak($imgh)) and ($this->y < $pre_y)) {
                                // fix for multicolumn mode
                                $startliney = $this->y;
                            }
                        }
                        if ($this->page > $startlinepage) {
                            // fix line splitted over two pages
                            if (isset($this->footerLengths[$startlinepage])) {
                                $curpos = $this->pageLengths[$startlinepage] - $this->footerLengths[$startlinepage];
                            }
                            // line to be moved one page forward
                            $pagebuff = $this->pageBuffer($startlinepage);
                            $linebeg  = substr($pagebuff, $startlinepos, ($curpos - $startlinepos));
                            $tstart   = substr($pagebuff, 0, $startlinepos);
                            $tend     = substr($this->pageBuffer($startlinepage), $curpos);
                            // remove line from previous page
                            $this->pageBuffer($startlinepage, $tstart . '' . $tend);
                            $pagebuff = $this->pageBuffer($this->page);
                            $tstart   = substr($pagebuff, 0, $this->cntmrk[$this->page]);
                            $tend     = substr($pagebuff, $this->cntmrk[$this->page]);
                            // add line start to current page
                            $yshift = ($minstartliney - $this->y);
                            if ($fontaligned) {
                                $yshift += ($curfontsize / $this->factor);
                            }
                            $try = sprintf('1 0 0 1 0 %F cm', ($yshift * $this->factor));
                            $this->pageBuffer($this->page, $tstart . "\nq\n" . $try . "\n" . $linebeg . "\nQ\n" . $tend);
                            // shift the annotations and links
                            if (isset($this->pageAnnotations[$this->page])) {
                                $next_pask = count($this->pageAnnotations[$this->page]);
                            }
                            else {
                                $next_pask = 0;
                            }
                            if (isset($this->pageAnnotations[$startlinepage])) {
                                foreach ($this->pageAnnotations[$startlinepage] as $pak => $pac) {
                                    if ($pak >= $pask) {
                                        $this->pageAnnotations[$this->page][] = $pac;
                                        unset($this->pageAnnotations[$startlinepage][$pak]);
                                        $npak                                           = count($this->pageAnnotations[$this->page]) - 1;
                                        $this->pageAnnotations[$this->page][$npak]['y'] -= $yshift;
                                    }
                                }
                            }
                            $pask          = $next_pask;
                            $startlinepos  = $this->cntmrk[$this->page];
                            $startlinepage = $this->page;
                            $startliney    = $this->y;
                            $this->newLine = false;
                        }
                        $this->y        += ($this->getCellHeight($curfontsize / $this->factor) - ($curfontdescent * $this->cellHeightRatio) - $imgh);
                        $minstartliney  = min($this->y, $minstartliney);
                        $maxbottomliney = ($startliney + $this->getCellHeight($curfontsize / $this->factor));
                    }
                }
                elseif (isset($dom[$key]['fontname']) or isset($dom[$key]['fontstyle']) or isset($dom[$key]['fontsize']) or isset($dom[$key]['line-height'])) {
                    // account for different font size
                    $pfontname   = $curfontname;
                    $pfontstyle  = $curfontstyle;
                    $pfontsize   = $curfontsize;
                    $fontname    = (isset($dom[$key]['fontname']) ? $dom[$key]['fontname'] : $curfontname);
                    $fontstyle   = (isset($dom[$key]['fontstyle']) ? $dom[$key]['fontstyle'] : $curfontstyle);
                    $fontsize    = (isset($dom[$key]['fontsize']) ? $dom[$key]['fontsize'] : $curfontsize);
                    $fontascent  = $this->getFontAscent($fontname, $fontstyle, $fontsize);
                    $fontdescent = $this->getFontDescent($fontname, $fontstyle, $fontsize);
                    if (($fontname != $curfontname) or ($fontstyle != $curfontstyle) or ($fontsize != $curfontsize)
                        or ($this->cellHeightRatio != $dom[$key]['line-height'])
                        or ($dom[$key]['tag'] and $dom[$key]['opening'] and ($dom[$key]['value'] == 'li'))) {
                        if (($key < ($maxel - 1)) and (
                                ($dom[$key]['tag'] and $dom[$key]['opening'] and ($dom[$key]['value'] == 'li'))
                                or ($this->cellHeightRatio != $dom[$key]['line-height'])
                                or (!$this->newLine and is_numeric($fontsize) and is_numeric($curfontsize)
                                    and ($fontsize >= 0) and ($curfontsize >= 0)
                                    and (($fontsize != $curfontsize) or ($fontstyle != $curfontstyle) or ($fontname != $curfontname)))
                            )) {
                            if ($this->page > $startlinepage) {
                                // fix lines splitted over two pages
                                if (isset($this->footerLengths[$startlinepage])) {
                                    $curpos = $this->pageLengths[$startlinepage] - $this->footerLengths[$startlinepage];
                                }
                                // line to be moved one page forward
                                $pagebuff = $this->pageBuffer($startlinepage);
                                $linebeg  = substr($pagebuff, $startlinepos, ($curpos - $startlinepos));
                                $tstart   = substr($pagebuff, 0, $startlinepos);
                                $tend     = substr($this->pageBuffer($startlinepage), $curpos);
                                // remove line start from previous page
                                $this->pageBuffer($startlinepage, $tstart . '' . $tend);
                                $pagebuff = $this->pageBuffer($this->page);
                                $tstart   = substr($pagebuff, 0, $this->cntmrk[$this->page]);
                                $tend     = substr($pagebuff, $this->cntmrk[$this->page]);
                                // add line start to current page
                                $yshift = ($minstartliney - $this->y);
                                $try    = sprintf('1 0 0 1 0 %F cm', ($yshift * $this->factor));
                                $this->pageBuffer($this->page, $tstart . "\nq\n" . $try . "\n" . $linebeg . "\nQ\n" . $tend);
                                // shift the annotations and links
                                if (isset($this->pageAnnotations[$this->page])) {
                                    $next_pask = count($this->pageAnnotations[$this->page]);
                                }
                                else {
                                    $next_pask = 0;
                                }
                                if (isset($this->pageAnnotations[$startlinepage])) {
                                    foreach ($this->pageAnnotations[$startlinepage] as $pak => $pac) {
                                        if ($pak >= $pask) {
                                            $this->pageAnnotations[$this->page][] = $pac;
                                            unset($this->pageAnnotations[$startlinepage][$pak]);
                                            $npak                                           = count($this->pageAnnotations[$this->page]) - 1;
                                            $this->pageAnnotations[$this->page][$npak]['y'] -= $yshift;
                                        }
                                    }
                                }
                                $pask          = $next_pask;
                                $startlinepos  = $this->cntmrk[$this->page];
                                $startlinepage = $this->page;
                                $startliney    = $this->y;
                            }
                            if (!isset($dom[$key]['line-height'])) {
                                $dom[$key]['line-height'] = $this->cellHeightRatio;
                            }
                            if (!$dom[$key]['block']) {
                                if (!(isset($dom[($key + 1)]) and $dom[($key + 1)]['tag'] and (!$dom[($key + 1)]['opening']) and ($dom[($key + 1)]['value'] != 'li') and $dom[$key]['tag'] and (!$dom[$key]['opening']))) {
                                    $this->y += (((($curfontsize * $this->cellHeightRatio) - ($fontsize * $dom[$key]['line-height'])) / $this->factor) + $curfontascent - $fontascent - $curfontdescent + $fontdescent) / 2;
                                }
                                if (($dom[$key]['value'] != 'sup') and ($dom[$key]['value'] != 'sub')) {
                                    $current_line_align_data = [$key, $minstartliney, $maxbottomliney];
                                    if (isset($line_align_data) and (($line_align_data[0] == ($key - 1)) or (($line_align_data[0] == ($key - 2)) and (isset($dom[($key - 1)])) and (preg_match('/^([\s]+)$/', $dom[($key - 1)]['value']) > 0)))) {
                                        $minstartliney  = min($this->y, $line_align_data[1]);
                                        $maxbottomliney = max(($this->y + $this->getCellHeight($fontsize / $this->factor)), $line_align_data[2]);
                                    }
                                    else {
                                        $minstartliney  = min($this->y, $minstartliney);
                                        $maxbottomliney = max(($this->y + $this->getCellHeight($fontsize / $this->factor)), $maxbottomliney);
                                    }
                                    $line_align_data = $current_line_align_data;
                                }
                            }
                            $this->cellHeightRatio = $dom[$key]['line-height'];
                            $fontaligned           = true;
                        }
                        $this->font($fontname, $fontstyle, $fontsize);
                        // reset row height
                        $this->resetLastH();
                        $curfontname    = $fontname;
                        $curfontstyle   = $fontstyle;
                        $curfontsize    = $fontsize;
                        $curfontascent  = $fontascent;
                        $curfontdescent = $fontdescent;
                    }
                }
                // set text rendering mode
                $textstroke = isset($dom[$key]['stroke']) ? $dom[$key]['stroke'] : $this->textstrokewidth;
                $textfill   = isset($dom[$key]['fill']) ? $dom[$key]['fill'] : (($this->textrendermode % 2) == 0);
                $textclip   = isset($dom[$key]['clip']) ? $dom[$key]['clip'] : ($this->textrendermode > 3);
                $this->setTextRenderingMode($textstroke, $textfill, $textclip);
                if (isset($dom[$key]['font-stretch']) and ($dom[$key]['font-stretch'] !== false)) {
                    $this->setFontStretching($dom[$key]['font-stretch']);
                }
                if (isset($dom[$key]['letter-spacing']) and ($dom[$key]['letter-spacing'] !== false)) {
                    $this->setFontSpacing($dom[$key]['letter-spacing']);
                }
                if (($plalign == 'J') and $dom[$key]['block']) {
                    $plalign = '';
                }
                // get current position on page buffer
                $curpos = $this->pageLengths[$startlinepage];
                if (isset($dom[$key]['bgColor']) and ($dom[$key]['bgColor'] !== false)) {
                    $this->fillColorArray($dom[$key]['bgColor']);
                    $wfill = true;
                }
                else {
                    $wfill = $fill | false;
                }
                if (isset($dom[$key]['fgColor']) and ($dom[$key]['fgColor'] !== false)) {
                    $this->textColorArray($dom[$key]['fgColor']);
                }
                if (isset($dom[$key]['strokecolor']) and ($dom[$key]['strokecolor'] !== false)) {
                    $this->drawColorArray($dom[$key]['strokecolor']);
                }
                if (isset($dom[$key]['align'])) {
                    $lalign = $dom[$key]['align'];
                }
                if (StaticMethods::empty_string($lalign)) {
                    $lalign = $align;
                }
            }
            // align lines
            if ($this->newLine and (strlen($dom[$key]['value']) > 0) and ($dom[$key]['value'] != 'td') and ($dom[$key]['value'] != 'th')) {
                $newline     = true;
                $fontaligned = false;
                // we are at the beginning of a new line
                if (isset($startlinex)) {
                    $yshift = ($minstartliney - $startliney);
                    if (($yshift > 0) or ($this->page > $startlinepage)) {
                        $yshift = 0;
                    }
                    $t_x = 0;
                    // the last line must be shifted to be aligned as requested
                    $linew = abs($this->endlinex - $startlinex);
                    if ($this->inXObject) {
                        // we are inside an XObject template
                        $pstart = substr($this->xObjects[$this->xObjectID]['content'], 0, $startlinepos);
                        if (isset($opentagpos)) {
                            $midpos = $opentagpos;
                        }
                        else {
                            $midpos = 0;
                        }
                        if ($midpos > 0) {
                            $pmid = substr($this->xObjects[$this->xObjectID]['content'], $startlinepos, ($midpos - $startlinepos));
                            $pend = substr($this->xObjects[$this->xObjectID]['content'], $midpos);
                        }
                        else {
                            $pmid = substr($this->xObjects[$this->xObjectID]['content'], $startlinepos);
                            $pend = '';
                        }
                    }
                    else {
                        $pstart = substr($this->pageBuffer($startlinepage), 0, $startlinepos);
                        if (isset($opentagpos) and isset($this->footerLengths[$startlinepage]) and (!$this->inFooter)) {
                            $this->footerpos[$startlinepage] = $this->pageLengths[$startlinepage] - $this->footerLengths[$startlinepage];
                            $midpos                          = min($opentagpos, $this->footerpos[$startlinepage]);
                        }
                        elseif (isset($opentagpos)) {
                            $midpos = $opentagpos;
                        }
                        elseif (isset($this->footerLengths[$startlinepage]) and (!$this->inFooter)) {
                            $this->footerpos[$startlinepage] = $this->pageLengths[$startlinepage] - $this->footerLengths[$startlinepage];
                            $midpos                          = $this->footerpos[$startlinepage];
                        }
                        else {
                            $midpos = 0;
                        }
                        if ($midpos > 0) {
                            $pmid = substr($this->pageBuffer($startlinepage), $startlinepos, ($midpos - $startlinepos));
                            $pend = substr($this->pageBuffer($startlinepage), $midpos);
                        }
                        else {
                            $pmid = substr($this->pageBuffer($startlinepage), $startlinepos);
                            $pend = '';
                        }
                    }
                    if (((($plalign == 'C' or $plalign == 'J' or $plalign == 'R')))) {
                        // calculate shifting amount
                        $tw = $w;
                        if ($this->leftMargin != $prevlMargin) {
                            $tw += ($prevlMargin - $this->leftMargin);
                        }
                        if ($this->rightMargin != $prevrMargin) {
                            $tw += ($prevrMargin - $this->rightMargin);
                        }
                        $one_space_width = $this->GetStringWidth(chr(32));
                        $no              = 0; // number of spaces on a line contained on a single block
                        // remove right space if exist
                        $pos1 = $this->lastStringPosition($pmid, ')]');
                        if ($pos1 > 0) {
                            $pos1     = intval($pos1);
                            $pos2     = intval($this->lastStringPosition($pmid, chr(32) . ')]')) + 1;
                            $spacelen = 1;
                            if ($pos1 == $pos2) {
                                $pmid  = substr($pmid, 0, ($pos1 - $spacelen)) . substr($pmid, $pos1);
                                $linew -= $one_space_width;
                            }
                        }
                        $mdiff = ($tw - $linew);
                        if ($plalign == 'C') {
                            $t_x = ($mdiff / 2);
                        }
                        elseif ($plalign == 'R') {
                            // right alignment on LTR document
                            $t_x = $mdiff;
                        }
                        elseif (($plalign == 'J') and ($plalign == $lalign)) {
                            // Justification
                            $ns       = 0; // number of spaces
                            $pmidtemp = $pmid;
                            // escape special characters
                            $pmidtemp = preg_replace('/[\\\][\(]/x', '\\#!#OP#!#', $pmidtemp);
                            $pmidtemp = preg_replace('/[\\\][\)]/x', '\\#!#CP#!#', $pmidtemp);
                            // search spaces
                            if (preg_match_all('/\[\(([^\)]*)\)\]/x', $pmidtemp, $lnstring, PREG_PATTERN_ORDER)) {
                                $spacestr = $this->getSpaceString();
                                $maxkk    = count($lnstring[1]) - 1;
                                for ($kk = 0; $kk <= $maxkk; ++$kk) {
                                    // restore special characters
                                    $lnstring[1][$kk] = str_replace('#!#OP#!#', '(', $lnstring[1][$kk]);
                                    $lnstring[1][$kk] = str_replace('#!#CP#!#', ')', $lnstring[1][$kk]);
                                    // store number of spaces on the strings
                                    $lnstring[2][$kk] = substr_count($lnstring[1][$kk], $spacestr);
                                    // count total spaces on line
                                    $ns               += $lnstring[2][$kk];
                                    $lnstring[3][$kk] = $ns;
                                }
                                if ($ns == 0) {
                                    $ns = 1;
                                }
                                // calculate additional space to add to each existing space
                                $spacewidth = ($mdiff / ($ns - $no)) * $this->factor;
                                if ($this->fontSize <= 0) {
                                    $this->fontSize = 1;
                                }
                                $spacewidthu = -1000 * ($mdiff + (($ns + $no) * $one_space_width)) / $ns / $this->fontSize;
                                if ($this->fontSpacing != 0) {
                                    // fixed spacing mode
                                    $osw         = -1000 * $this->fontSpacing / $this->fontSize;
                                    $spacewidthu += $osw;
                                }
                                $nsmax = $ns;
                                $ns    = 0;
                                reset($lnstring);
                                $offset         = 0;
                                $strcount       = 0;
                                $prev_epsposbeg = 0;
                                $textpos        = 0;
                                while (preg_match('/([0-9\.\+\-]*)[\s](Td|cm|m|l|c|re)[\s]/x', $pmid, $strpiece, PREG_OFFSET_CAPTURE, $offset) == 1) {
                                    // check if we are inside a string section '[( ... )]'
                                    $stroffset = strpos($pmid, '[(', $offset);
                                    if (($stroffset !== false) and ($stroffset <= $strpiece[2][1])) {
                                        // set offset to the end of string section
                                        $offset = strpos($pmid, ')]', $stroffset);
                                        while (($offset !== false) and ($pmid[($offset - 1)] == '\\')) {
                                            $offset = strpos($pmid, ')]', ($offset + 1));
                                        }
                                        if ($offset === false) {
                                            $this->Error('HTML Justification: malformed PDF code.');
                                        }
                                        continue;
                                    }
                                    $spacew    = ($spacewidth * $ns);
                                    $offset    = $strpiece[2][1] + strlen($strpiece[2][0]);
                                    $epsposend = strpos($pmid, $this->epsmarker . 'Q', $offset);
                                    if ($epsposend !== null) {
                                        $epsposend += strlen($this->epsmarker . 'Q');
                                        $epsposbeg = strpos($pmid, 'q' . $this->epsmarker, $offset);
                                        if ($epsposbeg === null) {
                                            $epsposbeg      = strpos($pmid, 'q' . $this->epsmarker, ($prev_epsposbeg - 6));
                                            $prev_epsposbeg = $epsposbeg;
                                        }
                                        if (($epsposbeg > 0) and ($epsposend > 0) and ($offset > $epsposbeg) and ($offset < $epsposend)) {
                                            // shift EPS images
                                            $trx    = sprintf('1 0 0 1 %F 0 cm', $spacew);
                                            $pmid_b = substr($pmid, 0, $epsposbeg);
                                            $pmid_m = substr($pmid, $epsposbeg, ($epsposend - $epsposbeg));
                                            $pmid_e = substr($pmid, $epsposend);
                                            $pmid   = $pmid_b . "\nq\n" . $trx . "\n" . $pmid_m . "\nQ\n" . $pmid_e;
                                            $offset = $epsposend;
                                            continue;
                                        }
                                    }
                                    $currentxpos = 0;
                                    // shift blocks of code
                                    switch ($strpiece[2][0]) {
                                        case 'Td':
                                        case 'cm':
                                        case 'm':
                                        case 'l':
                                        {
                                            // get current X position
                                            preg_match('/([0-9\.\+\-]*)[\s](' . $strpiece[1][0] . ')[\s](' . $strpiece[2][0] . ')([\s]*)/x', $pmid, $xmatches);
                                            if (!isset($xmatches[1])) {
                                                break;
                                            }
                                            $currentxpos = $xmatches[1];
                                            $textpos     = $currentxpos;
                                            if (($strcount <= $maxkk) and ($strpiece[2][0] == 'Td')) {
                                                $ns = $lnstring[3][$strcount];
                                                ++$strcount;
                                            }
                                            // justify block
                                            if (preg_match('/([0-9\.\+\-]*)[\s](' . $strpiece[1][0] . ')[\s](' . $strpiece[2][0] . ')([\s]*)/x', $pmid, $pmatch) == 1) {
                                                $newpmid = sprintf('%F', (floatval($pmatch[1]) + $spacew)) . ' ' . $pmatch[2] . ' x*#!#*x' . $pmatch[3] . $pmatch[4];
                                                $pmid    = str_replace($pmatch[0], $newpmid, $pmid);
                                                unset($pmatch, $newpmid);
                                            }
                                            break;
                                        }
                                        case 're':
                                        {
                                            // justify block
                                            if (!StaticMethods::empty_string($this->lispacer)) {
                                                $this->lispacer = '';
                                                break;
                                            }
                                            preg_match('/([0-9\.\+\-]*)[\s]([0-9\.\+\-]*)[\s]([0-9\.\+\-]*)[\s](' . $strpiece[1][0] . ')[\s](re)([\s]*)/x', $pmid, $xmatches);
                                            if (!isset($xmatches[1])) {
                                                break;
                                            }
                                            $currentxpos = $xmatches[1];
                                            $x_diff      = 0;
                                            $w_diff      = 0;
                                            if ($currentxpos > $textpos) {
                                                if ($strcount > 0) {
                                                    $x_diff = ($spacewidth * $lnstring[3][($strcount - 1)]);
                                                }
                                                $w_diff = ($spacewidth * $lnstring[2][$strcount]);
                                            }
                                            else {
                                                if ($strcount > 1) {
                                                    $x_diff = ($spacewidth * $lnstring[3][($strcount - 2)]);
                                                }
                                                if ($strcount > 0) {
                                                    $w_diff = ($spacewidth * $lnstring[2][($strcount - 1)]);
                                                }
                                            }
                                            if (preg_match('/(' . $xmatches[1] . ')[\s](' . $xmatches[2] . ')[\s](' . $xmatches[3] . ')[\s](' . $strpiece[1][0] . ')[\s](re)([\s]*)/x', $pmid, $pmatch) == 1) {
                                                $newx    = sprintf('%F', (floatval($pmatch[1]) + $x_diff));
                                                $neww    = sprintf('%F', (floatval($pmatch[3]) + $w_diff));
                                                $newpmid = $newx . ' ' . $pmatch[2] . ' ' . $neww . ' ' . $pmatch[4] . ' x*#!#*x' . $pmatch[5] . $pmatch[6];
                                                $pmid    = str_replace($pmatch[0], $newpmid, $pmid);
                                                unset($pmatch, $newpmid, $newx, $neww);
                                            }
                                            break;
                                        }
                                        case 'c':
                                        {
                                            // get current X position
                                            preg_match('/([0-9\.\+\-]*)[\s]([0-9\.\+\-]*)[\s]([0-9\.\+\-]*)[\s]([0-9\.\+\-]*)[\s]([0-9\.\+\-]*)[\s](' . $strpiece[1][0] . ')[\s](c)([\s]*)/x', $pmid, $xmatches);
                                            if (!isset($xmatches[1])) {
                                                break;
                                            }
                                            $currentxpos = $xmatches[1];
                                            // justify block
                                            if (preg_match('/(' . $xmatches[1] . ')[\s](' . $xmatches[2] . ')[\s](' . $xmatches[3] . ')[\s](' . $xmatches[4] . ')[\s](' . $xmatches[5] . ')[\s](' . $strpiece[1][0] . ')[\s](c)([\s]*)/x', $pmid, $pmatch) == 1) {
                                                $newx1   = sprintf('%F', (floatval($pmatch[1]) + $spacew));
                                                $newx2   = sprintf('%F', (floatval($pmatch[3]) + $spacew));
                                                $newx3   = sprintf('%F', (floatval($pmatch[5]) + $spacew));
                                                $newpmid = $newx1 . ' ' . $pmatch[2] . ' ' . $newx2 . ' ' . $pmatch[4] . ' ' . $newx3 . ' ' . $pmatch[6] . ' x*#!#*x' . $pmatch[7] . $pmatch[8];
                                                $pmid    = str_replace($pmatch[0], $newpmid, $pmid);
                                                unset($pmatch, $newpmid, $newx1, $newx2, $newx3);
                                            }
                                            break;
                                        }
                                    }
                                    // shift the annotations and links
                                    $cxpos = ($currentxpos / $this->factor);
                                    $lmpos = ($this->leftMargin + $this->cellPaddings['L'] + self::EPSILON);
                                    if ($this->inXObject) {
                                        // we are inside an XObject template
                                        foreach ($this->xObjects[$this->xObjectID]['annotations'] as $pak => $pac) {
                                            if (($pac['y'] >= $minstartliney) and (($pac['x'] * $this->factor) >= ($currentxpos - self::EPSILON)) and (($pac['x'] * $this->factor) <= ($currentxpos + self::EPSILON))) {
                                                if ($cxpos > $lmpos) {
                                                    $this->xObjects[$this->xObjectID]['annotations'][$pak]['x'] += ($spacew / $this->factor);
                                                    $this->xObjects[$this->xObjectID]['annotations'][$pak]['w'] += (($spacewidth * $pac['numspaces']) / $this->factor);
                                                }
                                                else {
                                                    $this->xObjects[$this->xObjectID]['annotations'][$pak]['w'] += (($spacewidth * $pac['numspaces']) / $this->factor);
                                                }
                                                break;
                                            }
                                        }
                                    }
                                    elseif (isset($this->pageAnnotations[$this->page])) {
                                        foreach ($this->pageAnnotations[$this->page] as $pak => $pac) {
                                            if (($pac['y'] >= $minstartliney) and (($pac['x'] * $this->factor) >= ($currentxpos - self::EPSILON)) and (($pac['x'] * $this->factor) <= ($currentxpos + self::EPSILON))) {
                                                if ($cxpos > $lmpos) {
                                                    $this->pageAnnotations[$this->page][$pak]['x'] += ($spacew / $this->factor);
                                                    $this->pageAnnotations[$this->page][$pak]['w'] += (($spacewidth * $pac['numspaces']) / $this->factor);
                                                }
                                                else {
                                                    $this->pageAnnotations[$this->page][$pak]['w'] += (($spacewidth * $pac['numspaces']) / $this->factor);
                                                }
                                                break;
                                            }
                                        }
                                    }
                                } // end of while
                                // remove markers
                                $pmid = str_replace('x*#!#*x', '', $pmid);
                                // non-unicode (single-byte characters)
                                if ($this->fontStretching != 100) {
                                    // word spacing (Tw) is affected by stretching
                                    $spacewidth /= ($this->fontStretching / 100);
                                }
                                $rs   = sprintf('%F Tw', $spacewidth);
                                $pmid = preg_replace("/\[\(/x", $rs . ' [(', $pmid);
                                if ($this->inXObject) {
                                    // we are inside an XObject template
                                    $this->xObjects[$this->xObjectID]['content'] = $pstart . "\n" . $pmid . "\nBT 0 Tw ET\n" . $pend;
                                }
                                else {
                                    $this->pageBuffer($startlinepage, $pstart . "\n" . $pmid . "\nBT 0 Tw ET\n" . $pend);
                                }
                                $endlinepos = strlen($pstart . "\n" . $pmid . "\nBT 0 Tw ET\n");
                            }
                        } // end of J
                    } // end if $startlinex
                    if (($t_x != 0) or ($yshift < 0)) {
                        // shift the line
                        $trx        = sprintf('1 0 0 1 %F %F cm', ($t_x * $this->factor), ($yshift * $this->factor));
                        $pstart     .= "\nq\n" . $trx . "\n" . $pmid . "\nQ\n";
                        $endlinepos = strlen($pstart);
                        if ($this->inXObject) {
                            // we are inside an XObject template
                            $this->xObjects[$this->xObjectID]['content'] = $pstart . $pend;
                            foreach ($this->xObjects[$this->xObjectID]['annotations'] as $pak => $pac) {
                                if ($pak >= $pask) {
                                    $this->xObjects[$this->xObjectID]['annotations'][$pak]['x'] += $t_x;
                                    $this->xObjects[$this->xObjectID]['annotations'][$pak]['y'] -= $yshift;
                                }
                            }
                        }
                        else {
                            $this->pageBuffer($startlinepage, $pstart . $pend);
                            // shift the annotations and links
                            if (isset($this->pageAnnotations[$this->page])) {
                                foreach ($this->pageAnnotations[$this->page] as $pak => $pac) {
                                    if ($pak >= $pask) {
                                        $this->pageAnnotations[$this->page][$pak]['x'] += $t_x;
                                        $this->pageAnnotations[$this->page][$pak]['y'] -= $yshift;
                                    }
                                }
                            }
                        }
                        $this->y -= $yshift;
                    }
                }
                $pbrk          = $this->checkPageBreak($this->lastPrintedCellHeight);
                $this->newLine = false;
                $startlinex    = $this->x;
                $startliney    = $this->y;
                if ($dom[$dom[$key]['parent']]['value'] == 'sup') {
                    $startliney -= ((0.3 * $this->fontSizePT) / $this->factor);
                }
                elseif ($dom[$dom[$key]['parent']]['value'] == 'sub') {
                    $startliney -= (($this->fontSizePT / 0.7) / $this->factor);
                }
                else {
                    $minstartliney  = $startliney;
                    $maxbottomliney = ($this->y + $this->getCellHeight($fontsize / $this->factor));
                }
                $startlinepage = $this->page;
                if (isset($endlinepos) and (!$pbrk)) {
                    $startlinepos = $endlinepos;
                }
                else {
                    if ($this->inXObject) {
                        // we are inside an XObject template
                        $startlinepos = strlen($this->xObjects[$this->xObjectID]['content']);
                    }
                    elseif (!$this->inFooter) {
                        if (isset($this->footerLengths[$this->page])) {
                            $this->footerpos[$this->page] = $this->pageLengths[$this->page] - $this->footerLengths[$this->page];
                        }
                        else {
                            $this->footerpos[$this->page] = $this->pageLengths[$this->page];
                        }
                        $startlinepos = $this->footerpos[$this->page];
                    }
                    else {
                        $startlinepos = $this->pageLengths[$this->page];
                    }
                }
                unset($endlinepos);
                $plalign = $lalign;
                if (isset($this->pageAnnotations[$this->page])) {
                    $pask = count($this->pageAnnotations[$this->page]);
                }
                else {
                    $pask = 0;
                }
                if (!($dom[$key]['tag'] and !$dom[$key]['opening'] and ($dom[$key]['value'] == 'table')
                    and (isset($this->emptypagemrk[$this->page]))
                    and ($this->emptypagemrk[$this->page] == $this->pageLengths[$this->page]))) {
                    $this->font($fontname, $fontstyle, $fontsize);
                    if ($wfill) {
                        $this->fillColorArray($this->bgColor);
                    }
                }
            } // end newline
            if (isset($opentagpos)) {
                unset($opentagpos);
            }
            if ($dom[$key]['tag']) {
                if ($dom[$key]['opening']) {
                    // get text indentation (if any)
                    if (isset($dom[$key]['text-indent']) and $dom[$key]['block']) {
                        $this->textindent = $dom[$key]['text-indent'];
                        $this->newLine    = true;
                    }
                    // table
                    if (($dom[$key]['value'] == 'table') and isset($dom[$key]['cols']) and ($dom[$key]['cols'] > 0)) {
                        // available page width
                        $wtmp = $this->width - $this->rightMargin - $this->x;
                        // get cell spacing
                        if (isset($dom[$key]['attribute']['cellspacing'])) {
                            $clsp        = $this->fontSizeToUnits($dom[$key]['attribute']['cellspacing']);
                            $cellspacing = ['H' => $clsp, 'V' => $clsp];
                        }
                        elseif (isset($dom[$key]['border-spacing'])) {
                            $cellspacing = $dom[$key]['border-spacing'];
                        }
                        else {
                            $cellspacing = ['H' => 0, 'V' => 0];
                        }
                        // table width
                        if (isset($dom[$key]['width'])) {
                            $table_width = $this->fontSizeToUnits($dom[$key]['width'], $wtmp);
                        }
                        else {
                            $table_width = $wtmp;
                        }
                        $table_width -= (2 * $cellspacing['H']);
                        if (!$this->inthead) {
                            $this->y += $cellspacing['V'];
                        }
                        $cellspacingx = $cellspacing['H'];
                        // total table width without cellspaces
                        $table_columns_width = ($table_width - ($cellspacing['H'] * ($dom[$key]['cols'] - 1)));
                        // minimum column width
                        $table_min_column_width = ($table_columns_width / $dom[$key]['cols']);
                        // array of custom column widths
                        $table_colwidths = array_fill(0, $dom[$key]['cols'], $table_min_column_width);
                    }
                    // table row
                    if ($dom[$key]['value'] == 'tr') {
                        // reset column counter
                        $colid = 0;
                    }
                    // table cell
                    if (($dom[$key]['value'] == 'td') or ($dom[$key]['value'] == 'th')) {
                        $trid     = $dom[$key]['parent'];
                        $table_el = $dom[$trid]['parent'];
                        if (!isset($dom[$table_el]['cols'])) {
                            $dom[$table_el]['cols'] = $dom[$trid]['cols'];
                        }
                        // store border info
                        $tdborder = 0;
                        if (isset($dom[$key]['border']) and !empty($dom[$key]['border'])) {
                            $tdborder = $dom[$key]['border'];
                        }
                        $colspan = intval($dom[$key]['attribute']['colspan']);
                        if ($colspan <= 0) {
                            $colspan = 1;
                        }
                        $oldPaddings = $this->cellPaddings;
                        if (isset($dom[($dom[$trid]['parent'])]['attribute']['cellpadding'])) {
                            $crclpd          = $this->fontSizeToUnits($dom[($dom[$trid]['parent'])]['attribute']['cellpadding']);
                            $currentPaddings = ['L' => $crclpd, 'T' => $crclpd, 'R' => $crclpd, 'B' => $crclpd];
                        }
                        elseif (isset($dom[($dom[$trid]['parent'])]['padding'])) {
                            $currentPaddings = $dom[($dom[$trid]['parent'])]['padding'];
                        }
                        else {
                            $currentPaddings = ['L' => 0, 'T' => 0, 'R' => 0, 'B' => 0];
                        }
                        $this->cellPaddings = $currentPaddings;
                        if (isset($dom[$key]['height'])) {
                            // minimum cell height
                            $cellh = $this->fontSizeToUnits($dom[$key]['height'], 0);
                        }
                        else {
                            $cellh = 0;
                        }
                        if (isset($dom[$key]['content'])) {
                            $cell_content = $dom[$key]['content'];
                        }
                        else {
                            $cell_content = '&nbsp;';
                        }
                        $tagtype  = $dom[$key]['value'];
                        $parentid = $key;
                        while (($key < $maxel) and (!(($dom[$key]['tag']) and (!$dom[$key]['opening']) and ($dom[$key]['value'] == $tagtype) and ($dom[$key]['parent'] == $parentid)))) {
                            // move $key index forward
                            ++$key;
                        }
                        if (!isset($dom[$trid]['startpage'])) {
                            $dom[$trid]['startpage'] = $this->page;
                        }
                        else {
                            $this->setPage($dom[$trid]['startpage']);
                        }
                        if (!isset($dom[$trid]['startcolumn'])) {
                            $dom[$trid]['startcolumn'] = $this->current_column;
                        }
                        elseif ($this->current_column != $dom[$trid]['startcolumn']) {
                            $tmpx = $this->x;
                            $this->selectColumn($dom[$trid]['startcolumn']);
                            $this->x = $tmpx;
                        }
                        if (!isset($dom[$trid]['starty'])) {
                            $dom[$trid]['starty'] = $this->y;
                        }
                        else {
                            $this->y = $dom[$trid]['starty'];
                        }
                        if (!isset($dom[$trid]['startx'])) {
                            $dom[$trid]['startx'] = $this->x;
                            $this->x              += $cellspacingx;
                        }
                        else {
                            $this->x += ($cellspacingx / 2);
                        }
                        if (isset($dom[$parentid]['attribute']['rowspan'])) {
                            $rowspan = intval($dom[$parentid]['attribute']['rowspan']);
                        }
                        else {
                            $rowspan = 1;
                        }
                        // skip row-spanned cells started on the previous rows
                        if (isset($dom[$table_el]['rowspans'])) {
                            $rsk    = 0;
                            $rskmax = count($dom[$table_el]['rowspans']);
                            while ($rsk < $rskmax) {
                                $trwsp    = $dom[$table_el]['rowspans'][$rsk];
                                $rsstartx = $trwsp['startx'];
                                $rsendx   = $trwsp['endx'];
                                // account for margin changes
                                if ($trwsp['startpage'] < $this->page) {
                                    if ($this->pageDimensions[$this->page]['olm'] != $this->pageDimensions[$trwsp['startpage']]['olm']) {
                                        $dl       = ($this->pageDimensions[$this->page]['olm'] - $this->pageDimensions[$trwsp['startpage']]['olm']);
                                        $rsstartx += $dl;
                                        $rsendx   += $dl;
                                    }
                                }
                                if (($trwsp['rowspan'] > 0)
                                    and ($rsstartx > ($this->x - $cellspacing['H'] - $currentPaddings['L'] - self::EPSILON))
                                    and ($rsstartx < ($this->x + $cellspacing['H'] + $currentPaddings['R'] + self::EPSILON))
                                    and (($trwsp['starty'] < ($this->y - self::EPSILON)) or ($trwsp['startpage'] < $this->page) or ($trwsp['startcolumn'] < $this->current_column))) {
                                    // set the starting X position of the current cell
                                    $this->x = $rsendx + $cellspacingx;
                                    // increment column indicator
                                    $colid += $trwsp['colspan'];
                                    if (($trwsp['rowspan'] == 1)
                                        and (isset($dom[$trid]['endy']))
                                        and (isset($dom[$trid]['endpage']))
                                        and (isset($dom[$trid]['endcolumn']))
                                        and ($trwsp['endpage'] == $dom[$trid]['endpage'])
                                        and ($trwsp['endcolumn'] == $dom[$trid]['endcolumn'])) {
                                        // set ending Y position for row
                                        $dom[$table_el]['rowspans'][$rsk]['endy'] = max($dom[$trid]['endy'], $trwsp['endy']);
                                        $dom[$trid]['endy']                       = $dom[$table_el]['rowspans'][$rsk]['endy'];
                                    }
                                    $rsk = 0;
                                }
                                else {
                                    ++$rsk;
                                }
                            }
                        }
                        if (isset($dom[$parentid]['width'])) {
                            // user specified width
                            $cellw = $this->fontSizeToUnits($dom[$parentid]['width'], $table_columns_width);
                            $tmpcw = ($cellw / $colspan);
                            for ($i = 0; $i < $colspan; ++$i) {
                                $table_colwidths[($colid + $i)] = $tmpcw;
                            }
                        }
                        else {
                            // inherit column width
                            $cellw = 0;
                            for ($i = 0; $i < $colspan; ++$i) {
                                $cellw += (isset($table_colwidths[($colid + $i)]) ? $table_colwidths[($colid + $i)] : 0);
                            }
                        }
                        $cellw += (($colspan - 1) * $cellspacing['H']);
                        // increment column indicator
                        $colid += $colspan;
                        // add rowspan information to table element
                        if ($rowspan > 1) {
                            $trsid = array_push($dom[$table_el]['rowspans'], ['trid' => $trid, 'rowspan' => $rowspan, 'mrowspan' => $rowspan, 'colspan' => $colspan, 'startpage' => $this->page, 'startcolumn' => $this->current_column, 'startx' => $this->x, 'starty' => $this->y]);
                        }
                        $cellid = array_push($dom[$trid]['cellpos'], ['startx' => $this->x]);
                        if ($rowspan > 1) {
                            $dom[$trid]['cellpos'][($cellid - 1)]['rowspanid'] = ($trsid - 1);
                        }
                        // push background colors
                        if (isset($dom[$parentid]['bgColor']) and ($dom[$parentid]['bgColor'] !== false)) {
                            $dom[$trid]['cellpos'][($cellid - 1)]['bgColor'] = $dom[$parentid]['bgColor'];
                        }
                        // store border info
                        if (!empty($tdborder)) {
                            $dom[$trid]['cellpos'][($cellid - 1)]['border'] = $tdborder;
                        }
                        $prevLastH = $this->lastPrintedCellHeight;
                        // store some info for multicolumn mode
                        $this->colxshift['x'] = $this->x - $this->leftMargin;
                        $this->colxshift['s'] = $cellspacing;
                        $this->colxshift['p'] = $currentPaddings;
                        // ****** write the cell content ******
                        $this->MultiCell($cellw, $cellh, $cell_content, false, $lalign, false, 2, '', '', true, 0, true, true, 0, 'T', false);
                        // restore some values
                        $this->colxshift                              = ['x' => 0, 's' => ['H' => 0, 'V' => 0], 'p' => ['L' => 0, 'T' => 0, 'R' => 0, 'B' => 0]];
                        $this->lastPrintedCellHeight                  = $prevLastH;
                        $this->cellPaddings                           = $oldPaddings;
                        $dom[$trid]['cellpos'][($cellid - 1)]['endx'] = $this->x;
                        // update the end of row position
                        if ($rowspan <= 1) {
                            if (isset($dom[$trid]['endy'])) {
                                if (($this->page == $dom[$trid]['endpage']) and ($this->current_column == $dom[$trid]['endcolumn'])) {
                                    $dom[$trid]['endy'] = max($this->y, $dom[$trid]['endy']);
                                }
                                elseif (($this->page > $dom[$trid]['endpage']) or ($this->current_column > $dom[$trid]['endcolumn'])) {
                                    $dom[$trid]['endy'] = $this->y;
                                }
                            }
                            else {
                                $dom[$trid]['endy'] = $this->y;
                            }
                            if (isset($dom[$trid]['endpage'])) {
                                $dom[$trid]['endpage'] = max($this->page, $dom[$trid]['endpage']);
                            }
                            else {
                                $dom[$trid]['endpage'] = $this->page;
                            }
                            if (isset($dom[$trid]['endcolumn'])) {
                                $dom[$trid]['endcolumn'] = max($this->current_column, $dom[$trid]['endcolumn']);
                            }
                            else {
                                $dom[$trid]['endcolumn'] = $this->current_column;
                            }
                        }
                        else {
                            // account for row-spanned cells
                            $dom[$table_el]['rowspans'][($trsid - 1)]['endx']      = $this->x;
                            $dom[$table_el]['rowspans'][($trsid - 1)]['endy']      = $this->y;
                            $dom[$table_el]['rowspans'][($trsid - 1)]['endpage']   = $this->page;
                            $dom[$table_el]['rowspans'][($trsid - 1)]['endcolumn'] = $this->current_column;
                        }
                        if (isset($dom[$table_el]['rowspans'])) {
                            // update endy and endpage on rowspanned cells
                            foreach ($dom[$table_el]['rowspans'] as $k => $trwsp) {
                                if ($trwsp['rowspan'] > 0) {
                                    if (isset($dom[$trid]['endpage'])) {
                                        if (($trwsp['endpage'] == $dom[$trid]['endpage']) and ($trwsp['endcolumn'] == $dom[$trid]['endcolumn'])) {
                                            $dom[$table_el]['rowspans'][$k]['endy'] = max($dom[$trid]['endy'], $trwsp['endy']);
                                        }
                                        elseif (($trwsp['endpage'] < $dom[$trid]['endpage']) or ($trwsp['endcolumn'] < $dom[$trid]['endcolumn'])) {
                                            $dom[$table_el]['rowspans'][$k]['endy']      = $dom[$trid]['endy'];
                                            $dom[$table_el]['rowspans'][$k]['endpage']   = $dom[$trid]['endpage'];
                                            $dom[$table_el]['rowspans'][$k]['endcolumn'] = $dom[$trid]['endcolumn'];
                                        }
                                        else {
                                            $dom[$trid]['endy'] = $this->pageDimensions[$dom[$trid]['endpage']]['hk'] - $this->pageDimensions[$dom[$trid]['endpage']]['bm'];
                                        }
                                    }
                                }
                            }
                        }
                        $this->x += ($cellspacingx / 2);
                    }
                    else {
                        // opening tag (or self-closing tag)
                        if (!isset($opentagpos)) {
                            if ($this->inXObject) {
                                // we are inside an XObject template
                                $opentagpos = strlen($this->xObjects[$this->xObjectID]['content']);
                            }
                            elseif (!$this->inFooter) {
                                if (isset($this->footerLengths[$this->page])) {
                                    $this->footerpos[$this->page] = $this->pageLengths[$this->page] - $this->footerLengths[$this->page];
                                }
                                else {
                                    $this->footerpos[$this->page] = $this->pageLengths[$this->page];
                                }
                                $opentagpos = $this->footerpos[$this->page];
                            }
                        }
                        $dom = $this->openHTMLTagHandler($dom, $key, $cell);
                    }
                }
                else { // closing tag
                    $prev_numpages = $this->numpages;
                    $old_bordermrk = $this->bordermrk[$this->page];
                    $dom           = $this->closeHTMLTagHandler($dom, $key, $cell, $maxbottomliney);
                    if ($this->bordermrk[$this->page] > $old_bordermrk) {
                        $startlinepos += ($this->bordermrk[$this->page] - $old_bordermrk);
                    }
                    if ($prev_numpages > $this->numpages) {
                        $startlinepage = $this->page;
                    }
                }
            }
            elseif (strlen($dom[$key]['value']) > 0) {
                // print list-item
                if (!StaticMethods::empty_string($this->lispacer) and ($this->lispacer != '^')) {
                    $this->font($pfontname, $pfontstyle, $pfontsize);
                    $this->resetLastH();
                    $minstartliney  = $this->y;
                    $maxbottomliney = ($startliney + $this->getCellHeight($this->fontSize));
                    if (is_numeric($pfontsize) and ($pfontsize > 0)) {
                        $this->putHtmlListBullet($this->listnum, $this->lispacer, $pfontsize);
                    }
                    $this->font($curfontname, $curfontstyle, $curfontsize);
                    $this->resetLastH();
                    if (is_numeric($pfontsize) and ($pfontsize > 0) and is_numeric($curfontsize) and ($curfontsize > 0) and ($pfontsize != $curfontsize)) {
                        $pfontascent    = $this->getFontAscent($pfontname, $pfontstyle, $pfontsize);
                        $pfontdescent   = $this->getFontDescent($pfontname, $pfontstyle, $pfontsize);
                        $this->y        += ($this->getCellHeight(($pfontsize - $curfontsize) / $this->factor) + $pfontascent - $curfontascent - $pfontdescent + $curfontdescent) / 2;
                        $minstartliney  = min($this->y, $minstartliney);
                        $maxbottomliney = max(($this->y + $this->getCellHeight($pfontsize / $this->factor)), $maxbottomliney);
                    }
                }
                // text
                $this->htmlvspace = 0;
                if ($newline) {
                    if (!$this->premode) {
                        $prelen = strlen($dom[$key]['value']);
                        // left trim except non-breaking space
                        $dom[$key]['value'] = $this->trimLeft($dom[$key]['value']);
                        $postlen            = strlen($dom[$key]['value']);
                        if (($postlen == 0) and ($prelen > 0)) {
                            $dom[$key]['trimmed_space'] = true;
                        }
                    }
                    $newline    = false;
                    $firstblock = true;
                }
                else {
                    $firstblock = false;
                    // replace empty multiple spaces string with a single space
                    $dom[$key]['value'] = preg_replace('/^' . $this->spaceRE['p'] . '+$/' . $this->spaceRE['m'], chr(32), $dom[$key]['value']);
                }
                $strrest = '';
                $this->x += $this->textindent;
                if (!isset($dom[$key]['trimmed_space']) or !$dom[$key]['trimmed_space']) {
                    $strlinelen = $this->GetStringWidth($dom[$key]['value']);
                    if (!empty($this->HREF) and (isset($this->HREF['url']))) {
                        // HTML <a> Link
                        $hrefcolor = '';
                        if (isset($dom[($dom[$key]['parent'])]['fgColor']) and ($dom[($dom[$key]['parent'])]['fgColor'] !== false)) {
                            $hrefcolor = $dom[($dom[$key]['parent'])]['fgColor'];
                        }
                        $hrefstyle = -1;
                        if (isset($dom[($dom[$key]['parent'])]['fontstyle']) and ($dom[($dom[$key]['parent'])]['fontstyle'] !== false)) {
                            $hrefstyle = $dom[($dom[$key]['parent'])]['fontstyle'];
                        }
                        $strrest = $this->addHtmlLink($this->HREF['url'], $dom[$key]['value'], $wfill, true, $hrefcolor, $hrefstyle, true);
                    }
                    else {
                        $wadj = 0; // space to leave for block continuity
                        $cwa  = ($this->width - $this->rightMargin - $this->x);
                        if (($strlinelen < $cwa) and (isset($dom[($key + 1)])) and ($dom[($key + 1)]['tag']) and (!$dom[($key + 1)]['block'])) {
                            // check the next text blocks for continuity
                            $nkey          = ($key + 1);
                            $write_block   = true;
                            $same_textdir  = true;
                            $tmp_fontname  = $this->fontFamily;
                            $tmp_fontstyle = $this->fontStyle;
                            $tmp_fontsize  = $this->fontSizePT;
                            while ($write_block and isset($dom[$nkey])) {
                                if ($dom[$nkey]['tag']) {
                                    if ($dom[$nkey]['block']) {
                                        // end of block
                                        $write_block = false;
                                    }
                                    $tmp_fontname  = isset($dom[$nkey]['fontname']) ? $dom[$nkey]['fontname'] : $this->fontFamily;
                                    $tmp_fontstyle = isset($dom[$nkey]['fontstyle']) ? $dom[$nkey]['fontstyle'] : $this->fontStyle;
                                    $tmp_fontsize  = isset($dom[$nkey]['fontsize']) ? $dom[$nkey]['fontsize'] : $this->fontSizePT;
                                    $same_textdir  = ($dom[$nkey]['dir'] == $dom[$key]['dir']);
                                }
                                else {
                                    $nextstr = preg_split('/' . $this->spaceRE['p'] . '+/' . $this->spaceRE['m'], $dom[$nkey]['value']);
                                    if (isset($nextstr[0]) and $same_textdir) {
                                        $wadj += $this->GetStringWidth($nextstr[0], $tmp_fontname, $tmp_fontstyle, $tmp_fontsize);
                                        if (isset($nextstr[1])) {
                                            $write_block = false;
                                        }
                                    }
                                }
                                ++$nkey;
                            }
                        }
                        if (($wadj > 0) and (($strlinelen + $wadj) >= $cwa)) {
                            $wadj    = 0;
                            $nextstr = preg_split('/' . $this->spaceRE['p'] . '/' . $this->spaceRE['m'], $dom[$key]['value']);
                            $numblks = count($nextstr);
                            if ($numblks > 1) {
                                // try to split on blank spaces
                                $wadj = ($cwa - $strlinelen + $this->GetStringWidth($nextstr[($numblks - 1)]));
                            }
                            else {
                                // set the entire block on new line
                                $wadj = $this->GetStringWidth($nextstr[0]);
                            }
                        }
                        // ****** write only until the end of the line and get the rest ******
                        $strrest = $this->Write($this->lastPrintedCellHeight, $dom[$key]['value'], '', $wfill, '', false, 0, true, $firstblock, 0, $wadj);
                        // restore default direction
                    }
                }
                $this->textindent = 0;
                if (strlen($strrest) > 0) {
                    // store the remaining string on the previous $key position
                    $this->newLine = true;
                    if ($strrest == $dom[$key]['value']) {
                        // used to avoid infinite loop
                        ++$loop;
                    }
                    else {
                        $loop = 0;
                    }
                    $dom[$key]['value'] = $strrest;
                    if ($cell) {
                        $this->x += $this->cellPaddings['L'];
                    }
                    if ($loop < 3) {
                        --$key;
                    }
                }
                else {
                    $loop = 0;
                    // add the positive font spacing of the last character (if any)
                    if ($this->fontSpacing > 0) {
                        $this->x += $this->fontSpacing;
                    }
                }
            }
            ++$key;
            if (isset($dom[$key]['tag']) and $dom[$key]['tag'] and (!isset($dom[$key]['opening']) or !$dom[$key]['opening']) and isset($dom[($dom[$key]['parent'])]['attribute']['nobr']) and ($dom[($dom[$key]['parent'])]['attribute']['nobr'] == 'true')) {
                // check if we are on a new page or on a new column
                if ((!$undo) and (($this->y < $this->start_transaction_y) or (($dom[$key]['value'] == 'tr') and ($dom[($dom[$key]['parent'])]['endy'] < $this->start_transaction_y)))) {
                    // we are on a new page or on a new column and the total object height is less than the available vertical space.
                    // restore previous object
                    $this->rollbackTransaction(true);
                    // restore previous values
                    foreach ($this_method_vars as $vkey => $vval) {
                        $$vkey = $vval;
                    }
                    if (!empty($dom[$key]['thead'])) {
                        $this->inthead = true;
                    }
                    // add a page (or trig AcceptPageBreak() for multicolumn mode)
                    $pre_y = $this->y;
                    if ((!$this->checkPageBreak($this->breakTrigger + 1)) and ($this->y < $pre_y)) {
                        $startliney = $this->y;
                    }
                    $undo = true; // avoid infinite loop
                }
                else {
                    $undo = false;
                }
            }
        } // end for each $key
        // align the last line
        if (isset($startlinex)) {
            $yshift = ($minstartliney - $startliney);
            if (($yshift > 0) or ($this->page > $startlinepage)) {
                $yshift = 0;
            }
            $t_x = 0;
            // the last line must be shifted to be aligned as requested
            $linew = abs($this->endlinex - $startlinex);
            if ($this->inXObject) {
                // we are inside an XObject template
                $pstart = substr($this->xObjects[$this->xObjectID]['content'], 0, $startlinepos);
                if (isset($opentagpos)) {
                    $midpos = $opentagpos;
                }
                else {
                    $midpos = 0;
                }
                if ($midpos > 0) {
                    $pmid = substr($this->xObjects[$this->xObjectID]['content'], $startlinepos, ($midpos - $startlinepos));
                    $pend = substr($this->xObjects[$this->xObjectID]['content'], $midpos);
                }
                else {
                    $pmid = substr($this->xObjects[$this->xObjectID]['content'], $startlinepos);
                    $pend = '';
                }
            }
            else {
                $pstart = substr($this->pageBuffer($startlinepage), 0, $startlinepos);
                if (isset($opentagpos) and isset($this->footerLengths[$startlinepage]) and (!$this->inFooter)) {
                    $this->footerpos[$startlinepage] = $this->pageLengths[$startlinepage] - $this->footerLengths[$startlinepage];
                    $midpos                          = min($opentagpos, $this->footerpos[$startlinepage]);
                }
                elseif (isset($opentagpos)) {
                    $midpos = $opentagpos;
                }
                elseif (isset($this->footerLengths[$startlinepage]) and (!$this->inFooter)) {
                    $this->footerpos[$startlinepage] = $this->pageLengths[$startlinepage] - $this->footerLengths[$startlinepage];
                    $midpos                          = $this->footerpos[$startlinepage];
                }
                else {
                    $midpos = 0;
                }
                if ($midpos > 0) {
                    $pmid = substr($this->pageBuffer($startlinepage), $startlinepos, ($midpos - $startlinepos));
                    $pend = substr($this->pageBuffer($startlinepage), $midpos);
                }
                else {
                    $pmid = substr($this->pageBuffer($startlinepage), $startlinepos);
                    $pend = '';
                }
            }
            if ($plalign === 'C' or $plalign === 'R') {
                // calculate shifting amount
                $tw = $w;
                if ($this->leftMargin != $prevlMargin) {
                    $tw += ($prevlMargin - $this->leftMargin);
                }
                if ($this->rightMargin != $prevrMargin) {
                    $tw += ($prevrMargin - $this->rightMargin);
                }
                $one_space_width = $this->GetStringWidth(chr(32));
                $no              = 0; // number of spaces on a line contained on a single block
                // remove right space if exist
                $pos1 = $this->lastStringPosition($pmid, ')]');
                if ($pos1 > 0) {
                    $pos1     = intval($pos1);
                    $pos2     = intval($this->lastStringPosition($pmid, chr(32) . ')]')) + 1;
                    $spacelen = 1;
                    if ($pos1 == $pos2) {
                        $pmid  = substr($pmid, 0, ($pos1 - $spacelen)) . substr($pmid, $pos1);
                        $linew -= $one_space_width;
                    }
                }
                $mdiff = ($tw - $linew);
                if ($plalign === 'C') {
                    $t_x = ($mdiff / 2);
                }
                else {
                    // right alignment on LTR document
                    $t_x = $mdiff;
                }
            } // end if startlinex
            if (($t_x != 0) or ($yshift < 0)) {
                // shift the line
                $trx        = sprintf('1 0 0 1 %F %F cm', ($t_x * $this->factor), ($yshift * $this->factor));
                $pstart     .= "\nq\n" . $trx . "\n" . $pmid . "\nQ\n";
                $endlinepos = strlen($pstart);
                if ($this->inXObject) {
                    // we are inside an XObject template
                    $this->xObjects[$this->xObjectID]['content'] = $pstart . $pend;
                    foreach ($this->xObjects[$this->xObjectID]['annotations'] as $pak => $pac) {
                        if ($pak >= $pask) {
                            $this->xObjects[$this->xObjectID]['annotations'][$pak]['x'] += $t_x;
                            $this->xObjects[$this->xObjectID]['annotations'][$pak]['y'] -= $yshift;
                        }
                    }
                }
                else {
                    $this->pageBuffer($startlinepage, $pstart . $pend);
                    // shift the annotations and links
                    if (isset($this->pageAnnotations[$this->page])) {
                        foreach ($this->pageAnnotations[$this->page] as $pak => $pac) {
                            if ($pak >= $pask) {
                                $this->pageAnnotations[$this->page][$pak]['x'] += $t_x;
                                $this->pageAnnotations[$this->page][$pak]['y'] -= $yshift;
                            }
                        }
                    }
                }
                $this->y -= $yshift;
                $yshift  = 0;
            }
        }
        // restore previous values
        $this->graphicsConfiguration($gvars);
        if ($this->num_columns > 1) {
            $this->selectColumn();
        }
        elseif ($this->page > $prevPage) {
            $this->leftMargin  = $this->pageDimensions[$this->page]['olm'];
            $this->rightMargin = $this->pageDimensions[$this->page]['orm'];
        }
        // restore previous list state
        $this->cellHeightRatio = $previousHeightRatio;
        $this->listnum         = $prev_listnum;
        $this->listordered     = $prev_listordered;
        $this->listcount       = $prev_listcount;
        $this->lispacer        = $prev_lispacer;
        if ($ln and (!($cell and ($dom[$key - 1]['value'] == 'table')))) {
            $this->Ln($this->lastPrintedCellHeight);
            if (($this->y < $maxbottomliney) and ($startlinepage == $this->page)) {
                $this->y = $maxbottomliney;
            }
        }
        unset($dom);
    }

    /**
     * Process opening tags.
     * @param array $dom  html dom array
     * @param int   $key  current element id
     * @param bool  $cell if true add the default left padding to each new line (default false).
     * @return array $dom
     */
    protected function openHTMLTagHandler($dom, $key, $cell)
    {
        $tag      = $dom[$key];
        $parent   = $dom[($dom[$key]['parent'])];
        $firsttag = ($key == 1);
        if ($tag['block']) {
            $hbz = 0; // distance from y to line bottom
            $hb  = 0; // vertical space between block tags
            // calculate vertical space for block tags
            if (isset($this->tagvspaces[$tag['value']][0]['h']) && !empty($this->tagvspaces[$tag['value']][0]['h']) && ($this->tagvspaces[$tag['value']][0]['h'] >= 0)) {
                $cur_h = $this->tagvspaces[$tag['value']][0]['h'];
            }
            elseif (isset($tag['fontsize'])) {
                $cur_h = $this->getCellHeight($tag['fontsize'] / $this->factor);
            }
            else {
                $cur_h = $this->getCellHeight($this->fontSize);
            }
            if (isset($this->tagvspaces[$tag['value']][0]['n'])) {
                $on = $this->tagvspaces[$tag['value']][0]['n'];
            }
            elseif (preg_match('/[h][0-9]/', $tag['value']) > 0) {
                $on = 0.6;
            }
            else {
                $on = 1;
            }
            if ((!isset($this->tagvspaces[$tag['value']])) and (in_array($tag['value'], ['div', 'dt', 'dd', 'li', 'br', 'hr']))) {
                $hb = 0;
            }
            else {
                $hb = ($on * $cur_h);
            }
            if (($this->htmlvspace <= 0) and ($on > 0)) {
                if (isset($parent['fontsize'])) {
                    $hbz = (($parent['fontsize'] / $this->factor) * $this->cellHeightRatio);
                }
                else {
                    $hbz = $this->getCellHeight($this->fontSize);
                }
            }
            if (isset($dom[($key - 1)]) and ($dom[($key - 1)]['value'] == 'table')) {
                // fix vertical space after table
                $hbz = 0;
            }
            // closing vertical space
            $hbc = 0;
            if (isset($this->tagvspaces[$tag['value']][1]['h']) && !empty($this->tagvspaces[$tag['value']][1]['h']) && ($this->tagvspaces[$tag['value']][1]['h'] >= 0)) {
                $pre_h = $this->tagvspaces[$tag['value']][1]['h'];
            }
            elseif (isset($parent['fontsize'])) {
                $pre_h = $this->getCellHeight($parent['fontsize'] / $this->factor);
            }
            else {
                $pre_h = $this->getCellHeight($this->fontSize);
            }
            if (isset($this->tagvspaces[$tag['value']][1]['n'])) {
                $cn = $this->tagvspaces[$tag['value']][1]['n'];
            }
            elseif (preg_match('/[h][0-9]/', $tag['value']) > 0) {
                $cn = 0.6;
            }
            else {
                $cn = 1;
            }
            if (isset($this->tagvspaces[$tag['value']][1])) {
                $hbc = ($cn * $pre_h);
            }
        }
        // Opening tag
        switch ($tag['value']) {
            case 'table':
            {
                $cp                    = 0;
                $cs                    = 0;
                $dom[$key]['rowspans'] = [];
                if (!isset($dom[$key]['attribute']['nested']) or ($dom[$key]['attribute']['nested'] != 'true')) {
                    $this->htmlvspace = 0;
                    // set table header
                    if (!empty($dom[$key]['thead'])) {
                        // set table header
                        $this->headerTemplate = $dom[$key]['thead'];
                        if (!isset($this->headerTemplateMargins) or (empty($this->headerTemplateMargins))) {
                            $this->headerTemplateMargins                 = [];
                            $this->headerTemplateMargins['cellPaddings'] = $this->cellPaddings;
                            $this->headerTemplateMargins['lmargin']      = $this->leftMargin;
                            $this->headerTemplateMargins['rmargin']      = $this->rightMargin;
                            $this->headerTemplateMargins['page']         = $this->page;
                            $this->headerTemplateMargins['cell']         = $cell;
                            $this->headerTemplateMargins['gvars']        = $this->graphicsConfiguration();
                        }
                    }
                }
                // store current margins and page
                $dom[$key]['oldPaddings'] = $this->cellPaddings;
                if (isset($tag['attribute']['cellpadding'])) {
                    $pad = $this->fontSizeToUnits($tag['attribute']['cellpadding']);
                    $this->cellPadding($pad);
                }
                elseif (isset($tag['padding'])) {
                    $this->cellPaddings = $tag['padding'];
                }
                if (isset($tag['attribute']['cellspacing'])) {
                    $cs = $this->fontSizeToUnits($tag['attribute']['cellspacing']);
                }
                elseif (isset($tag['border-spacing'])) {
                    $cs = $tag['border-spacing']['V'];
                }
                $prev_y = $this->y;
                if ($this->checkPageBreak(((2 * $cp) + (2 * $cs) + $this->lastPrintedCellHeight), '', false) or ($this->y < $prev_y)) {
                    $this->inthead = true;
                    // add a page (or trig AcceptPageBreak() for multicolumn mode)
                    $this->checkPageBreak($this->breakTrigger + 1);
                }
                break;
            }
            case 'tr':
            {
                // array of columns positions
                $dom[$key]['cellpos'] = [];
                break;
            }
            case 'hr':
            {
                if ((isset($tag['height'])) and ($tag['height'] != '')) {
                    $hrHeight = $this->fontSizeToUnits($tag['height']);
                }
                else {
                    $hrHeight = $this->lineWidth();
                }
                $this->addHTMLVertSpace($hbz, max($hb, ($hrHeight / 2)), $cell, $firsttag);
                $x    = $this->x();
                $y    = $this->y();
                $wtmp = $this->width - $this->leftMargin - $this->rightMargin;
                if ($cell) {
                    $wtmp -= ($this->cellPaddings['L'] + $this->cellPaddings['R']);
                }
                if ((isset($tag['width'])) and ($tag['width'] != '')) {
                    $hrWidth = $this->fontSizeToUnits($tag['width'], $wtmp);
                }
                else {
                    $hrWidth = $wtmp;
                }
                $prevlinewidth = $this->lineWidth();
                $this->lineWidth($hrHeight);

                $lineStyle = [];

                if (isset($tag['fgColor'])) {
                    $lineStyle['color'] = $tag['fgColor'];
                }

                if (isset($tag['style']['cap'])) {
                    $lineStyle['cap'] = $tag['style']['cap'];
                }

                if (isset($tag['style']['join'])) {
                    $lineStyle['join'] = $tag['style']['join'];
                }

                if (isset($tag['style']['dash'])) {
                    $lineStyle['dash'] = $tag['style']['dash'];
                }

                if (isset($tag['style']['phase'])) {
                    $lineStyle['phase'] = $tag['style']['phase'];
                }

                $lineStyle = array_filter($lineStyle);

                $this->line($x, $y, $x + $hrWidth, $y, $lineStyle);
                $this->lineWidth($prevlinewidth);
                $this->addHTMLVertSpace(max($hbc, ($hrHeight / 2)), 0, $cell, !isset($dom[($key + 1)]));
                break;
            }
            case 'a':
            {
                if (array_key_exists('href', $tag['attribute'])) {
                    $this->HREF['url'] = $tag['attribute']['href'];
                }
                break;
            }
            case 'img':
            {
                if (empty($tag['attribute']['src'])) {
                    break;
                }
                $imgsrc = $tag['attribute']['src'];
                if ($imgsrc[0] === '@') {
                    // data stream
                    $imgsrc = '@' . base64_decode(substr($imgsrc, 1));
                    $type   = '';
                }
                else if (preg_match('@^data:image/([^;]*);base64,(.*)@', $imgsrc, $reg)) {
                    $imgsrc = '@' . base64_decode($reg[2]);
                    $type   = $reg[1];
                }
                else {
                    if (($imgsrc[0] === '/') and !empty($_SERVER['DOCUMENT_ROOT']) and ($_SERVER['DOCUMENT_ROOT'] != '/')) {
                        // fix image path
                        $findroot = strpos($imgsrc, $_SERVER['DOCUMENT_ROOT']);
                        if (($findroot === false) or ($findroot > 1)) {
                            if (substr($_SERVER['DOCUMENT_ROOT'], -1) == '/') {
                                $imgsrc = substr($_SERVER['DOCUMENT_ROOT'], 0, -1) . $imgsrc;
                            }
                            else {
                                $imgsrc = $_SERVER['DOCUMENT_ROOT'] . $imgsrc;
                            }
                        }
                        $imgsrc      = urldecode($imgsrc);
                        $testscrtype = @parse_url($imgsrc);
                        if (empty($testscrtype['query'])) {
                            // convert URL to server path
                            $imgsrc = str_replace($this->libraryURL, self::LIBRARY_PATH, $imgsrc);
                        }
                        elseif (preg_match('|^https?://|', $imgsrc) !== 1) {
                            // convert URL to server path
                            $imgsrc = str_replace(self::LIBRARY_PATH, $this->libraryURL, $imgsrc);
                        }
                    }
                    // get image type
                    $type = $this->imageType($imgsrc);
                }
                if (!isset($tag['width'])) {
                    $tag['width'] = 0;
                }
                if (!isset($tag['height'])) {
                    $tag['height'] = 0;
                }
                //if (!isset($tag['attribute']['align'])) {
                // the only alignment supported is "bottom"
                // further development is required for other modes.
                $tag['attribute']['align'] = 'bottom';
                //}
                switch ($tag['attribute']['align']) {
                    case 'top':
                    {
                        $align = 'T';
                        break;
                    }
                    case 'middle':
                    {
                        $align = 'M';
                        break;
                    }
                    case 'bottom':
                    {
                        $align = 'B';
                        break;
                    }
                    default:
                    {
                        $align = 'B';
                        break;
                    }
                }
                $prevy   = $this->y;
                $xpos    = $this->x;
                $imglink = '';
                if (isset($this->HREF['url']) and !empty($this->HREF['url'])) {
                    $imglink = $this->HREF['url'];
                    if ($imglink[0] == '#') {
                        // convert url to internal link
                        $lnkdata = explode(',', $imglink);
                        if (isset($lnkdata[0])) {
                            $page = intval(substr($lnkdata[0], 1));
                            if (empty($page) or ($page <= 0)) {
                                $page = $this->page;
                            }
                            if (isset($lnkdata[1]) and (strlen($lnkdata[1]) > 0)) {
                                $lnky = floatval($lnkdata[1]);
                            }
                            else {
                                $lnky = 0;
                            }
                            $imglink = $this->AddLink();
                            $this->setLink($imglink, $lnky, $page);
                        }
                    }
                }
                $border = 0;
                if (isset($tag['border']) and !empty($tag['border'])) {
                    // currently only support 1 (frame) or a combination of 'LTRB'
                    $border = $tag['border'];
                }
                $iw = '';
                if (isset($tag['width'])) {
                    $iw = $this->fontSizeToUnits($tag['width'], ($tag['fontsize'] / $this->factor));
                }
                $ih = '';
                if (isset($tag['height'])) {
                    $ih = $this->fontSizeToUnits($tag['height'], ($tag['fontsize'] / $this->factor));
                }
                if (($type == 'eps') or ($type == 'ai')) {
                    $this->ImageEps($imgsrc, $xpos, $this->y, $iw, $ih, $imglink, true, $align, '', $border, true);
                }
                elseif ($type == 'svg') {
                    $this->ImageSVG($imgsrc, $xpos, $this->y, $iw, $ih, $imglink, $align, '', $border, true);
                }
                else {
                    $this->image($imgsrc, $xpos, $this->y, $iw, $ih, '', $imglink, $align, false, 300, '', false, false, $border, false, false, true);
                }
                switch ($align) {
                    case 'T':
                    {
                        $this->y = $prevy;
                        break;
                    }
                    case 'M':
                    {
                        $this->y = (($this->imageRBY + $prevy - ($this->getCellHeight($tag['fontsize'] / $this->factor))) / 2);
                        break;
                    }
                    case 'B':
                    {
                        $this->y = $this->imageRBY - ($this->getCellHeight($tag['fontsize'] / $this->factor) - ($this->getFontDescent($tag['fontname'], $tag['fontstyle'], $tag['fontsize']) * $this->cellHeightRatio));
                        break;
                    }
                }
                break;
            }
            case 'dl':
            {
                ++$this->listnum;
                if ($this->listnum == 1) {
                    $this->addHTMLVertSpace($hbz, $hb, $cell, $firsttag);
                }
                else {
                    $this->addHTMLVertSpace(0, 0, $cell, $firsttag);
                }
                break;
            }
            case 'dt':
            {
                $this->addHTMLVertSpace($hbz, $hb, $cell, $firsttag);
                break;
            }
            case 'dd':
            {
                $this->leftMargin += $this->listindent;
                ++$this->listindentlevel;
                $this->addHTMLVertSpace($hbz, $hb, $cell, $firsttag);
                break;
            }
            case 'ul':
            case 'ol':
            {
                ++$this->listnum;
                if ($tag['value'] == 'ol') {
                    $this->listordered[$this->listnum] = true;
                }
                else {
                    $this->listordered[$this->listnum] = false;
                }
                if (isset($tag['attribute']['start'])) {
                    $this->listcount[$this->listnum] = intval($tag['attribute']['start']) - 1;
                }
                else {
                    $this->listcount[$this->listnum] = 0;
                }
                $this->leftMargin += $this->listindent;
                $this->x          += $this->listindent;
                ++$this->listindentlevel;
                if ($this->listnum == 1) {
                    if ($key > 1) {
                        $this->addHTMLVertSpace($hbz, $hb, $cell, $firsttag);
                    }
                }
                else {
                    $this->addHTMLVertSpace(0, 0, $cell, $firsttag);
                }
                break;
            }
            case 'li':
            {
                if ($key > 2) {
                    $this->addHTMLVertSpace($hbz, $hb, $cell, $firsttag);
                }
                if ($this->listordered[$this->listnum]) {
                    // ordered item
                    if (isset($parent['attribute']['type']) and !empty($parent['attribute']['type'])) {
                        $this->lispacer = $parent['attribute']['type'];
                    }
                    elseif (isset($parent['listtype']) and !empty($parent['listtype'])) {
                        $this->lispacer = $parent['listtype'];
                    }
                    elseif (isset($this->lisymbol) and !empty($this->lisymbol)) {
                        $this->lispacer = $this->lisymbol;
                    }
                    else {
                        $this->lispacer = '#';
                    }
                    ++$this->listcount[$this->listnum];
                    if (isset($tag['attribute']['value'])) {
                        $this->listcount[$this->listnum] = intval($tag['attribute']['value']);
                    }
                }
                else {
                    // unordered item
                    if (isset($parent['attribute']['type']) and !empty($parent['attribute']['type'])) {
                        $this->lispacer = $parent['attribute']['type'];
                    }
                    elseif (isset($parent['listtype']) and !empty($parent['listtype'])) {
                        $this->lispacer = $parent['listtype'];
                    }
                    elseif (isset($this->lisymbol) and !empty($this->lisymbol)) {
                        $this->lispacer = $this->lisymbol;
                    }
                    else {
                        $this->lispacer = '!';
                    }
                }
                break;
            }
            case 'blockquote':
            {
                $this->leftMargin += $this->listindent;
                ++$this->listindentlevel;
                $this->addHTMLVertSpace($hbz, $hb, $cell, $firsttag);
                break;
            }
            case 'br':
            {
                $this->addHTMLVertSpace($hbz, $hb, $cell, $firsttag);
                break;
            }
            case 'div':
            {
                $this->addHTMLVertSpace($hbz, $hb, $cell, $firsttag);
                break;
            }
            case 'p':
            {
                $this->addHTMLVertSpace($hbz, $hb, $cell, $firsttag);
                break;
            }
            case 'pre':
            {
                $this->addHTMLVertSpace($hbz, $hb, $cell, $firsttag);
                $this->premode = true;
                break;
            }
            case 'sup':
            {
                $this->xy($this->x(), $this->y() - ((0.7 * $this->fontSizePT) / $this->factor));
                break;
            }
            case 'sub':
            {
                $this->xy($this->x(), $this->y() + ((0.3 * $this->fontSizePT) / $this->factor));
                break;
            }
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
            {
                $this->addHTMLVertSpace($hbz, $hb, $cell, $firsttag);
                break;
            }
            // Form fields (since 4.8.000 - 2009-09-07)
            case 'form':
            {
                if (isset($tag['attribute']['action'])) {
                    $this->form_action = $tag['attribute']['action'];
                }
                else {
                    $this->Error('Please explicitly set action attribute path!');
                }
                if (isset($tag['attribute']['enctype'])) {
                    $this->form_enctype = $tag['attribute']['enctype'];
                }
                else {
                    $this->form_enctype = 'application/x-www-form-urlencoded';
                }
                if (isset($tag['attribute']['method'])) {
                    $this->form_mode = $tag['attribute']['method'];
                }
                else {
                    $this->form_mode = 'post';
                }
                break;
            }
            case 'input':
            {
                if (isset($tag['attribute']['name']) and !empty($tag['attribute']['name'])) {
                    $name = $tag['attribute']['name'];
                }
                else {
                    break;
                }
                $prop = [];
                $opt  = [];
                if (isset($tag['attribute']['readonly']) and !empty($tag['attribute']['readonly'])) {
                    $prop['readonly'] = true;
                }
                if (isset($tag['attribute']['value']) and !empty($tag['attribute']['value'])) {
                    $value = $tag['attribute']['value'];
                }
                if (isset($tag['attribute']['maxlength']) and !empty($tag['attribute']['maxlength'])) {
                    $opt['maxlen'] = intval($tag['attribute']['maxlength']);
                }
                $h = $this->getCellHeight($this->fontSize);
                if (isset($tag['attribute']['size']) and !empty($tag['attribute']['size'])) {
                    $w = intval($tag['attribute']['size']) * $this->GetStringWidth(chr(32)) * 2;
                }
                else {
                    $w = $h;
                }
                if (isset($tag['attribute']['checked']) and (($tag['attribute']['checked'] == 'checked') or ($tag['attribute']['checked'] == 'true'))) {
                    $checked = true;
                }
                else {
                    $checked = false;
                }
                if (isset($tag['align'])) {
                    switch ($tag['align']) {
                        case 'C':
                        {
                            $opt['q'] = 1;
                            break;
                        }
                        case 'R':
                        {
                            $opt['q'] = 2;
                            break;
                        }
                        case 'L':
                        default:
                        {
                            break;
                        }
                    }
                }
                switch ($tag['attribute']['type']) {
                    case 'text':
                    {
                        if (isset($value)) {
                            $opt['v'] = $value;
                        }
                        $this->TextField($name, $w, $h, $prop, $opt, '', '', false);
                        break;
                    }
                    case 'password':
                    {
                        if (isset($value)) {
                            $opt['v'] = $value;
                        }
                        $prop['password'] = 'true';
                        $this->TextField($name, $w, $h, $prop, $opt, '', '', false);
                        break;
                    }
                    case 'checkbox':
                    {
                        if (!isset($value)) {
                            break;
                        }
                        $this->CheckBox($name, $w, $checked, $prop, $opt, $value, '', '', false);
                        break;
                    }
                    case 'radio':
                    {
                        if (!isset($value)) {
                            break;
                        }
                        $this->RadioButton($name, $w, $prop, $opt, $value, $checked, '', '', false);
                        break;
                    }
                    case 'submit':
                    {
                        if (!isset($value)) {
                            $value = 'submit';
                        }
                        $w           = $this->GetStringWidth($value) * 1.5;
                        $h           *= 1.6;
                        $prop        = ['lineWidth' => 1, 'borderStyle' => 'beveled', 'fillColor' => [196, 196, 196], 'strokeColor' => self::WHITE];
                        $action      = [];
                        $action['S'] = 'SubmitForm';
                        $action['F'] = $this->form_action;
                        if ($this->form_enctype != 'FDF') {
                            $action['Flags'] = ['ExportFormat'];
                        }
                        if ($this->form_mode == 'get') {
                            $action['Flags'] = ['GetMethod'];
                        }
                        $this->Button($name, $w, $h, $value, $action, $prop, $opt, '', '', false);
                        break;
                    }
                    case 'reset':
                    {
                        if (!isset($value)) {
                            $value = 'reset';
                        }
                        $w    = $this->GetStringWidth($value) * 1.5;
                        $h    *= 1.6;
                        $prop = ['lineWidth' => 1, 'borderStyle' => 'beveled', 'fillColor' => [196, 196, 196], 'strokeColor' => self::WHITE];
                        $this->Button($name, $w, $h, $value, ['S' => 'ResetForm'], $prop, $opt, '', '', false);
                        break;
                    }
                    case 'file':
                    {
                        $prop['fileSelect'] = 'true';
                        $this->TextField($name, $w, $h, $prop, $opt, '', '', false);
                        if (!isset($value)) {
                            $value = '*';
                        }
                        $w        = $this->GetStringWidth($value) * 2;
                        $h        *= 1.2;
                        $prop     = ['lineWidth' => 1, 'borderStyle' => 'beveled', 'fillColor' => [196, 196, 196], 'strokeColor' => self::WHITE];
                        $jsaction = 'var f=this.getField(\'' . $name . '\'); f.browseForFileToSubmit();';
                        $this->Button('FB_' . $name, $w, $h, $value, $jsaction, $prop, $opt, '', '', false);
                        break;
                    }
                    case 'hidden':
                    {
                        if (isset($value)) {
                            $opt['v'] = $value;
                        }
                        $opt['f'] = ['invisible', 'hidden'];
                        $this->TextField($name, 0, 0, $prop, $opt, '', '', false);
                        break;
                    }
                    case 'image':
                    {
                        // THIS TYPE MUST BE FIXED
                        if (isset($tag['attribute']['src']) and !empty($tag['attribute']['src'])) {
                            $img = $tag['attribute']['src'];
                        }
                        else {
                            break;
                        }
                        $value = 'img';
                        if (isset($tag['attribute']['onclick']) and !empty($tag['attribute']['onclick'])) {
                            $jsaction = $tag['attribute']['onclick'];
                        }
                        else {
                            $jsaction = '';
                        }
                        $this->Button($name, $w, $h, $value, $jsaction, $prop, $opt, '', '', false);
                        break;
                    }
                    case 'button':
                    {
                        if (!isset($value)) {
                            $value = ' ';
                        }
                        $w    = $this->GetStringWidth($value) * 1.5;
                        $h    *= 1.6;
                        $prop = ['lineWidth' => 1, 'borderStyle' => 'beveled', 'fillColor' => [196, 196, 196], 'strokeColor' => self::WHITE];
                        if (isset($tag['attribute']['onclick']) and !empty($tag['attribute']['onclick'])) {
                            $jsaction = $tag['attribute']['onclick'];
                        }
                        else {
                            $jsaction = '';
                        }
                        $this->Button($name, $w, $h, $value, $jsaction, $prop, $opt, '', '', false);
                        break;
                    }
                }
                break;
            }
            case 'textarea':
            {
                $prop = [];
                $opt  = [];
                if (isset($tag['attribute']['readonly']) and !empty($tag['attribute']['readonly'])) {
                    $prop['readonly'] = true;
                }
                if (isset($tag['attribute']['name']) and !empty($tag['attribute']['name'])) {
                    $name = $tag['attribute']['name'];
                }
                else {
                    break;
                }
                if (isset($tag['attribute']['value']) and !empty($tag['attribute']['value'])) {
                    $opt['v'] = $tag['attribute']['value'];
                }
                if (isset($tag['attribute']['cols']) and !empty($tag['attribute']['cols'])) {
                    $w = intval($tag['attribute']['cols']) * $this->GetStringWidth(chr(32)) * 2;
                }
                else {
                    $w = 40;
                }
                if (isset($tag['attribute']['rows']) and !empty($tag['attribute']['rows'])) {
                    $h = intval($tag['attribute']['rows']) * $this->getCellHeight($this->fontSize);
                }
                else {
                    $h = 10;
                }
                $prop['multiline'] = 'true';
                $this->TextField($name, $w, $h, $prop, $opt, '', '', false);
                break;
            }
            case 'select':
            {
                $h = $this->getCellHeight($this->fontSize);
                if (isset($tag['attribute']['size']) and !empty($tag['attribute']['size'])) {
                    $h *= ($tag['attribute']['size'] + 1);
                }
                $prop = [];
                $opt  = [];
                if (isset($tag['attribute']['name']) and !empty($tag['attribute']['name'])) {
                    $name = $tag['attribute']['name'];
                }
                else {
                    break;
                }
                $w = 0;
                if (isset($tag['attribute']['opt']) and !empty($tag['attribute']['opt'])) {
                    $options = explode('#!NwL!#', $tag['attribute']['opt']);
                    $values  = [];
                    foreach ($options as $val) {
                        if (strpos($val, '#!TaB!#') !== false) {
                            $opts     = explode('#!TaB!#', $val);
                            $values[] = $opts;
                            $w        = max($w, $this->GetStringWidth($opts[1]));
                        }
                        else {
                            $values[] = $val;
                            $w        = max($w, $this->GetStringWidth($val));
                        }
                    }
                }
                else {
                    break;
                }
                $w *= 2;
                if (isset($tag['attribute']['multiple']) and ($tag['attribute']['multiple'] = 'multiple')) {
                    $prop['multipleSelection'] = 'true';
                    $this->ListBox($name, $w, $h, $values, $prop, $opt, '', '', false);
                }
                else {
                    $this->ComboBox($name, $w, $h, $values, $prop, $opt, '', '', false);
                }
                break;
            }
            default:
            {
                break;
            }
        }
        // define tags that support borders and background colors
        $bordertags = ['blockquote', 'br', 'dd', 'dl', 'div', 'dt', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'li', 'ol', 'p', 'pre', 'ul', 'tcpdf', 'table'];
        if (in_array($tag['value'], $bordertags)) {
            // set border
            $dom[$key]['borderposition'] = $this->getBorderStartPosition();
        }
        if ($dom[$key]['self'] and isset($dom[$key]['attribute']['pagebreakafter'])) {
            $pba = $dom[$key]['attribute']['pagebreakafter'];
            // check for pagebreak
            if (($pba == 'true') or ($pba == 'left') or ($pba == 'right')) {
                // add a page (or trig AcceptPageBreak() for multicolumn mode)
                $this->checkPageBreak($this->breakTrigger + 1);
            }
            if (($pba === 'left' and $this->page % 2 == 0) or ($pba === 'right' and $this->page % 2 !== 0)) {
                // add a page (or trig AcceptPageBreak() for multicolumn mode)
                $this->checkPageBreak($this->breakTrigger + 1);
            }
        }
        return $dom;
    }

    /**
     * Process closing tags.
     * @param array $dom            html dom array
     * @param int   $key            current element id
     * @param bool  $cell           if true add the default left padding to each new line (default false).
     * @param int   $maxbottomliney maximum y value of current line
     * @return array $dom
     */
    protected function closeHTMLTagHandler($dom, $key, $cell, $maxbottomliney = 0)
    {
        $tag           = $dom[$key];
        $parent        = $dom[($dom[$key]['parent'])];
        $lasttag       = ((!isset($dom[($key + 1)])) or ((!isset($dom[($key + 2)])) and ($dom[($key + 1)]['value'] == 'marker')));
        $in_table_head = false;
        // maximum x position (used to draw borders)
        $xmax = 0;
        if ($tag['block']) {
            $hbz = 0; // distance from y to line bottom
            $hb  = 0; // vertical space between block tags
            // calculate vertical space for block tags
            if (isset($this->tagvspaces[$tag['value']][1]['h']) && !empty($this->tagvspaces[$tag['value']][1]['h']) && ($this->tagvspaces[$tag['value']][1]['h'] >= 0)) {
                $pre_h = $this->tagvspaces[$tag['value']][1]['h'];
            }
            elseif (isset($parent['fontsize'])) {
                $pre_h = $this->getCellHeight($parent['fontsize'] / $this->factor);
            }
            else {
                $pre_h = $this->getCellHeight($this->fontSize);
            }
            if (isset($this->tagvspaces[$tag['value']][1]['n'])) {
                $cn = $this->tagvspaces[$tag['value']][1]['n'];
            }
            elseif (preg_match('/[h][0-9]/', $tag['value']) > 0) {
                $cn = 0.6;
            }
            else {
                $cn = 1;
            }
            if ((!isset($this->tagvspaces[$tag['value']])) and ($tag['value'] == 'div')) {
                $hb = 0;
            }
            else {
                $hb = ($cn * $pre_h);
            }
            if ($maxbottomliney > $this->breakTrigger) {
                $hbz = $this->getCellHeight($this->fontSize);
            }
            elseif ($this->y < $maxbottomliney) {
                $hbz = ($maxbottomliney - $this->y);
            }
        }
        // Closing tag
        switch ($tag['value']) {
            case 'tr':
            {
                $table_el = $dom[($dom[$key]['parent'])]['parent'];
                if (!isset($parent['endy'])) {
                    $dom[($dom[$key]['parent'])]['endy'] = $this->y;
                    $parent['endy']                      = $this->y;
                }
                if (!isset($parent['endpage'])) {
                    $dom[($dom[$key]['parent'])]['endpage'] = $this->page;
                    $parent['endpage']                      = $this->page;
                }
                if (!isset($parent['endcolumn'])) {
                    $dom[($dom[$key]['parent'])]['endcolumn'] = $this->current_column;
                    $parent['endcolumn']                      = $this->current_column;
                }
                // update row-spanned cells
                if (isset($dom[$table_el]['rowspans'])) {
                    foreach ($dom[$table_el]['rowspans'] as $k => $trwsp) {
                        $dom[$table_el]['rowspans'][$k]['rowspan'] -= 1;
                        if ($dom[$table_el]['rowspans'][$k]['rowspan'] == 0) {
                            if (($dom[$table_el]['rowspans'][$k]['endpage'] == $parent['endpage']) and ($dom[$table_el]['rowspans'][$k]['endcolumn'] == $parent['endcolumn'])) {
                                $dom[($dom[$key]['parent'])]['endy'] = max($dom[$table_el]['rowspans'][$k]['endy'], $parent['endy']);
                            }
                            elseif (($dom[$table_el]['rowspans'][$k]['endpage'] > $parent['endpage']) or ($dom[$table_el]['rowspans'][$k]['endcolumn'] > $parent['endcolumn'])) {
                                $dom[($dom[$key]['parent'])]['endy']      = $dom[$table_el]['rowspans'][$k]['endy'];
                                $dom[($dom[$key]['parent'])]['endpage']   = $dom[$table_el]['rowspans'][$k]['endpage'];
                                $dom[($dom[$key]['parent'])]['endcolumn'] = $dom[$table_el]['rowspans'][$k]['endcolumn'];
                            }
                        }
                    }
                    // report new endy and endpage to the rowspanned cells
                    foreach ($dom[$table_el]['rowspans'] as $k => $trwsp) {
                        if ($dom[$table_el]['rowspans'][$k]['rowspan'] == 0) {
                            $dom[$table_el]['rowspans'][$k]['endpage']   = max($dom[$table_el]['rowspans'][$k]['endpage'], $dom[($dom[$key]['parent'])]['endpage']);
                            $dom[($dom[$key]['parent'])]['endpage']      = $dom[$table_el]['rowspans'][$k]['endpage'];
                            $dom[$table_el]['rowspans'][$k]['endcolumn'] = max($dom[$table_el]['rowspans'][$k]['endcolumn'], $dom[($dom[$key]['parent'])]['endcolumn']);
                            $dom[($dom[$key]['parent'])]['endcolumn']    = $dom[$table_el]['rowspans'][$k]['endcolumn'];
                            $dom[$table_el]['rowspans'][$k]['endy']      = max($dom[$table_el]['rowspans'][$k]['endy'], $dom[($dom[$key]['parent'])]['endy']);
                            $dom[($dom[$key]['parent'])]['endy']         = $dom[$table_el]['rowspans'][$k]['endy'];
                        }
                    }
                    // update remaining rowspanned cells
                    foreach ($dom[$table_el]['rowspans'] as $k => $trwsp) {
                        if ($dom[$table_el]['rowspans'][$k]['rowspan'] == 0) {
                            $dom[$table_el]['rowspans'][$k]['endpage']   = $dom[($dom[$key]['parent'])]['endpage'];
                            $dom[$table_el]['rowspans'][$k]['endcolumn'] = $dom[($dom[$key]['parent'])]['endcolumn'];
                            $dom[$table_el]['rowspans'][$k]['endy']      = $dom[($dom[$key]['parent'])]['endy'];
                        }
                    }
                }
                $prev_page = $this->page;
                $this->setPage($dom[($dom[$key]['parent'])]['endpage']);
                if ($this->num_columns > 1) {
                    if (($prev_page < $this->page)
                        and ((($this->current_column == 0) and ($dom[($dom[$key]['parent'])]['endcolumn'] == ($this->num_columns - 1)))
                            or ($this->current_column == $dom[($dom[$key]['parent'])]['endcolumn']))) {
                        // page jump
                        $this->selectColumn(0);
                        $dom[($dom[$key]['parent'])]['endcolumn'] = 0;
                        $dom[($dom[$key]['parent'])]['endy']      = $this->y;
                    }
                    else {
                        $this->selectColumn($dom[($dom[$key]['parent'])]['endcolumn']);
                        $this->y = $dom[($dom[$key]['parent'])]['endy'];
                    }
                }
                else {
                    $this->y = $dom[($dom[$key]['parent'])]['endy'];
                }
                if (isset($dom[$table_el]['attribute']['cellspacing'])) {
                    $this->y += $this->fontSizeToUnits($dom[$table_el]['attribute']['cellspacing']);
                }
                elseif (isset($dom[$table_el]['border-spacing'])) {
                    $this->y += $dom[$table_el]['border-spacing']['V'];
                }
                $this->Ln(0, $cell);
                if ($this->current_column == $parent['startcolumn']) {
                    $this->x = $parent['startx'];
                }
                // account for booklet mode
                if ($this->page > $parent['startpage']) {
                    if ($this->pageDimensions[$this->page]['olm'] !== $this->pageDimensions[$parent['startpage']]['olm']) {
                        $this->x += ($this->pageDimensions[$this->page]['olm'] - $this->pageDimensions[$parent['startpage']]['olm']);
                    }
                }
                break;
            }
            case 'tablehead':
                // closing tag used for the thead part
                $in_table_head = true;
                $this->inthead = false;
            case 'table':
            {
                $table_el = $parent;
                // set default border
                if (isset($table_el['attribute']['border']) and ($table_el['attribute']['border'] > 0)) {
                    // set default border
                    $border = array('LTRB' => array('width' => $this->getCSSBorderWidth($table_el['attribute']['border']), 'cap' => 'square', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK));
                }
                else {
                    $border = 0;
                }
                $default_border = $border;
                // fix bottom line alignment of last line before page break
                foreach ($dom[($dom[$key]['parent'])]['trids'] as $j => $trkey) {
                    // update row-spanned cells
                    if (isset($dom[($dom[$key]['parent'])]['rowspans'])) {
                        foreach ($dom[($dom[$key]['parent'])]['rowspans'] as $k => $trwsp) {
                            if (isset($prevtrkey) and ($trwsp['trid'] == $prevtrkey) and ($trwsp['mrowspan'] > 0)) {
                                $dom[($dom[$key]['parent'])]['rowspans'][$k]['trid'] = $trkey;
                            }
                            if ($dom[($dom[$key]['parent'])]['rowspans'][$k]['trid'] == $trkey) {
                                $dom[($dom[$key]['parent'])]['rowspans'][$k]['mrowspan'] -= 1;
                            }
                        }
                    }
                    if (isset($prevtrkey) and ($dom[$trkey]['startpage'] > $dom[$prevtrkey]['endpage'])) {
                        $pgendy                  = $this->pageDimensions[$dom[$prevtrkey]['endpage']]['hk'] - $this->pageDimensions[$dom[$prevtrkey]['endpage']]['bm'];
                        $dom[$prevtrkey]['endy'] = $pgendy;
                        // update row-spanned cells
                        if (isset($dom[($dom[$key]['parent'])]['rowspans'])) {
                            foreach ($dom[($dom[$key]['parent'])]['rowspans'] as $k => $trwsp) {
                                if (($trwsp['trid'] == $prevtrkey) and ($trwsp['mrowspan'] >= 0) and ($trwsp['endpage'] == $dom[$prevtrkey]['endpage'])) {
                                    $dom[($dom[$key]['parent'])]['rowspans'][$k]['endy']     = $pgendy;
                                    $dom[($dom[$key]['parent'])]['rowspans'][$k]['mrowspan'] = -1;
                                }
                            }
                        }
                    }
                    $prevtrkey = $trkey;
                    $table_el  = $dom[($dom[$key]['parent'])];
                }
                // for each row
                if (!empty($table_el['trids'])) {
                    unset($xmax);
                }
                foreach ($table_el['trids'] as $j => $trkey) {
                    $parent = $dom[$trkey];
                    if (!isset($xmax)) {
                        $xmax = $parent['cellpos'][(count($parent['cellpos']) - 1)]['endx'];
                    }
                    // for each cell on the row
                    foreach ($parent['cellpos'] as $k => $cellpos) {
                        if (isset($cellpos['rowspanid']) and ($cellpos['rowspanid'] >= 0)) {
                            $cellpos['startx'] = $table_el['rowspans'][($cellpos['rowspanid'])]['startx'];
                            $cellpos['endx']   = $table_el['rowspans'][($cellpos['rowspanid'])]['endx'];
                            $endy              = $table_el['rowspans'][($cellpos['rowspanid'])]['endy'];
                            $startpage         = $table_el['rowspans'][($cellpos['rowspanid'])]['startpage'];
                            $endpage           = $table_el['rowspans'][($cellpos['rowspanid'])]['endpage'];
                            $startcolumn       = $table_el['rowspans'][($cellpos['rowspanid'])]['startcolumn'];
                            $endcolumn         = $table_el['rowspans'][($cellpos['rowspanid'])]['endcolumn'];
                        }
                        else {
                            $endy        = $parent['endy'];
                            $startpage   = $parent['startpage'];
                            $endpage     = $parent['endpage'];
                            $startcolumn = $parent['startcolumn'];
                            $endcolumn   = $parent['endcolumn'];
                        }
                        if ($this->num_columns == 0) {
                            $this->num_columns = 1;
                        }
                        if (isset($cellpos['border'])) {
                            $border = $cellpos['border'];
                        }
                        if (isset($cellpos['bgColor']) and ($cellpos['bgColor']) !== false) {
                            $this->fillColorArray($cellpos['bgColor']);
                            $fill = true;
                        }
                        else {
                            $fill = false;
                        }
                        $x      = $cellpos['startx'];
                        $y      = $parent['starty'];
                        $starty = $y;
                        $w      = abs($cellpos['endx'] - $cellpos['startx']);
                        // get border modes
                        $border_start  = StaticMethods::getBorderMode($border, $position = 'start', $this->opencell);
                        $border_end    = StaticMethods::getBorderMode($border, $position = 'end', $this->opencell);
                        $border_middle = StaticMethods::getBorderMode($border, $position = 'middle', $this->opencell);
                        // design borders around HTML cells.
                        for ($page = $startpage; $page <= $endpage; ++$page) { // for each page
                            $ccode = '';
                            $this->setPage($page);
                            if ($this->num_columns < 2) {
                                // single-column mode
                                $this->x = $x;
                                $this->y = $this->topMargin;
                            }
                            // account for margin changes
                            if ($page > $startpage) {
                                if ($this->pageDimensions[$page]['olm'] !== $this->pageDimensions[$startpage]['olm']) {
                                    $this->x += ($this->pageDimensions[$page]['olm'] - $this->pageDimensions[$startpage]['olm']);
                                }
                            }
                            if ($startpage == $endpage) { // single page
                                $deltacol = 0;
                                $deltath  = 0;
                                for ($column = $startcolumn; $column <= $endcolumn; ++$column) { // for each column
                                    $this->selectColumn($column);
                                    if ($startcolumn == $endcolumn) { // single column
                                        $cborder = $border;
                                        $h       = $endy - $parent['starty'];
                                        $this->y = $y;
                                        $this->x = $x;
                                    }
                                    elseif ($column == $startcolumn) { // first column
                                        $cborder  = $border_start;
                                        $this->y  = $starty;
                                        $this->x  = $x;
                                        $h        = $this->height - $this->y - $this->bottomMargin;
                                        $deltacol = $this->x - $this->leftMargin;
                                    }
                                    elseif ($column == $endcolumn) { // end column
                                        $cborder = $border_end;
                                        if (isset($this->columns[$column]['th']['\'' . $page . '\''])) {
                                            $this->y = $this->columns[$column]['th']['\'' . $page . '\''];
                                        }
                                        $this->x += $deltacol;
                                        $h       = $endy - $this->y;
                                    }
                                    else { // middle column
                                        $cborder = $border_middle;
                                        if (isset($this->columns[$column]['th']['\'' . $page . '\''])) {
                                            $this->y = $this->columns[$column]['th']['\'' . $page . '\''];
                                        }
                                        $this->x += $deltacol;
                                        $h       = $this->height - $this->y - $this->bottomMargin;
                                    }
                                    $ccode .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true) . "\n";
                                } // end for each column
                            }
                            elseif ($page == $startpage) { // first page
                                $deltacol = 0;
                                $deltath  = 0;
                                for ($column = $startcolumn; $column < $this->num_columns; ++$column) { // for each column
                                    $this->selectColumn($column);
                                    if ($column == $startcolumn) { // first column
                                        $cborder  = $border_start;
                                        $this->y  = $starty;
                                        $this->x  = $x;
                                        $h        = $this->height - $this->y - $this->bottomMargin;
                                        $deltacol = $this->x - $this->leftMargin;
                                    }
                                    else { // middle column
                                        $cborder = $border_middle;
                                        if (isset($this->columns[$column]['th']['\'' . $page . '\''])) {
                                            $this->y = $this->columns[$column]['th']['\'' . $page . '\''];
                                        }
                                        $this->x += $deltacol;
                                        $h       = $this->height - $this->y - $this->bottomMargin;
                                    }
                                    $ccode .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true) . "\n";
                                } // end for each column
                            }
                            elseif ($page == $endpage) { // last page
                                $deltacol = 0;
                                $deltath  = 0;
                                for ($column = 0; $column <= $endcolumn; ++$column) { // for each column
                                    $this->selectColumn($column);
                                    if ($column == $endcolumn) { // end column
                                        $cborder = $border_end;
                                        if (isset($this->columns[$column]['th']['\'' . $page . '\''])) {
                                            $this->y = $this->columns[$column]['th']['\'' . $page . '\''];
                                        }
                                        $this->x += $deltacol;
                                        $h       = $endy - $this->y;
                                    }
                                    else { // middle column
                                        $cborder = $border_middle;
                                        if (isset($this->columns[$column]['th']['\'' . $page . '\''])) {
                                            $this->y = $this->columns[$column]['th']['\'' . $page . '\''];
                                        }
                                        $this->x += $deltacol;
                                        $h       = $this->height - $this->y - $this->bottomMargin;
                                    }
                                    $ccode .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true) . "\n";
                                } // end for each column
                            }
                            else { // middle page
                                $deltacol = 0;
                                $deltath  = 0;
                                for ($column = 0; $column < $this->num_columns; ++$column) { // for each column
                                    $this->selectColumn($column);
                                    $cborder = $border_middle;
                                    if (isset($this->columns[$column]['th']['\'' . $page . '\''])) {
                                        $this->y = $this->columns[$column]['th']['\'' . $page . '\''];
                                    }
                                    $this->x += $deltacol;
                                    $h       = $this->height - $this->y - $this->bottomMargin;
                                    $ccode   .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true) . "\n";
                                } // end for each column
                            }
                            if (!empty($cborder) or !empty($fill)) {
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
                                    // draw border and fill
                                    if (end($this->transpositions[$this->page]) !== false) {
                                        $pagemarkkey = key($this->transpositions[$this->page]);
                                        $pagemark    = $this->transpositions[$this->page][$pagemarkkey];
                                    }
                                    elseif ($this->inFooter) {
                                        $pagemark = $this->footerpos[$this->page];
                                    }
                                    else {
                                        $pagemark = $this->intmrk[$this->page];
                                    }
                                    $pagebuff = $this->pageBuffer($this->page);
                                    $pstart   = substr($pagebuff, 0, $pagemark);
                                    $pend     = substr($pagebuff, $pagemark);
                                    $this->pageBuffer($this->page, $pstart . $ccode . $pend);
                                }
                            }
                        } // end for each page
                        // restore default border
                        $border = $default_border;
                    } // end for each cell on the row
                    if (isset($table_el['attribute']['cellspacing'])) {
                        $this->y += $this->fontSizeToUnits($table_el['attribute']['cellspacing']);
                    }
                    elseif (isset($table_el['border-spacing'])) {
                        $this->y += $table_el['border-spacing']['V'];
                    }
                    $this->Ln(0, $cell);
                    $this->x = $parent['startx'];
                    if ($endpage > $startpage) {
                        if ($this->pageDimensions[$endpage]['olm'] !== $this->pageDimensions[$startpage]['olm']) {
                            $this->x += ($this->pageDimensions[$endpage]['olm'] - $this->pageDimensions[$startpage]['olm']);
                        }
                    }
                }
                if (!$in_table_head) { // we are not inside a thead section
                    $this->cellPaddings = isset($table_el['oldPaddings']) ? $table_el['oldPaddings'] : null;
                    // reset row height
                    $this->resetLastH();
                    if (($this->page == ($this->numpages - 1)) and ($this->pageopen[$this->numpages])) {
                        $plendiff = ($this->pageLengths[$this->numpages] - $this->emptypagemrk[$this->numpages]);
                        if (($plendiff > 0) and ($plendiff < 60)) {
                            $pagediff = substr($this->pageBuffer($this->numpages), $this->emptypagemrk[$this->numpages], $plendiff);
                            if (substr($pagediff, 0, 5) == 'BT /F') {
                                // the difference is only a font setting
                                $plendiff = 0;
                            }
                        }
                        if ($plendiff == 0) {
                            // remove last blank page
                            $this->deletePage($this->numpages);
                        }
                    }
                    if (isset($this->headerTemplateMargins['top'])) {
                        // restore top margin
                        $this->topMargin = $this->headerTemplateMargins['top'];
                    }
                    if (!isset($table_el['attribute']['nested']) or ($table_el['attribute']['nested'] != 'true')) {
                        // reset main table header
                        $this->headerTemplate                    = '';
                        $this->headerTemplateMargins             = [];
                        $this->pageDimensions[$this->page]['tm'] = $this->topMargin;
                    }
                }
                $parent = $table_el;
                break;
            }
            case 'a':
            {
                $this->HREF = [];
                break;
            }
            case 'sup':
            {
                $this->xy($this->x(), $this->y() + ((0.7 * $parent['fontsize']) / $this->factor));
                break;
            }
            case 'sub':
            {
                $this->xy($this->x(), $this->y() - ((0.3 * $parent['fontsize']) / $this->factor));
                break;
            }
            case 'div':
            {
                $this->addHTMLVertSpace($hbz, $hb, $cell, false, $lasttag);
                break;
            }
            case 'blockquote':
            {
                $this->leftMargin -= $this->listindent;
                --$this->listindentlevel;
                $this->addHTMLVertSpace($hbz, $hb, $cell, false, $lasttag);
                break;
            }
            case 'p':
            {
                $this->addHTMLVertSpace($hbz, $hb, $cell, false, $lasttag);
                break;
            }
            case 'pre':
            {
                $this->addHTMLVertSpace($hbz, $hb, $cell, false, $lasttag);
                $this->premode = false;
                break;
            }
            case 'dl':
            {
                --$this->listnum;
                if ($this->listnum <= 0) {
                    $this->listnum = 0;
                    $this->addHTMLVertSpace($hbz, $hb, $cell, false, $lasttag);
                }
                else {
                    $this->addHTMLVertSpace(0, 0, $cell, false, $lasttag);
                }
                $this->resetLastH();
                break;
            }
            case 'dt':
            {
                $this->lispacer = '';
                $this->addHTMLVertSpace(0, 0, $cell, false, $lasttag);
                break;
            }
            case 'dd':
            {
                $this->lispacer   = '';
                $this->leftMargin -= $this->listindent;
                --$this->listindentlevel;
                $this->addHTMLVertSpace(0, 0, $cell, false, $lasttag);
                break;
            }
            case 'ul':
            case 'ol':
            {
                --$this->listnum;
                $this->lispacer   = '';
                $this->leftMargin -= $this->listindent;
                --$this->listindentlevel;
                if ($this->listnum <= 0) {
                    $this->listnum = 0;
                    $this->addHTMLVertSpace($hbz, $hb, $cell, false, $lasttag);
                }
                else {
                    $this->addHTMLVertSpace(0, 0, $cell, false, $lasttag);
                }
                $this->resetLastH();
                break;
            }
            case 'li':
            {
                $this->lispacer = '';
                $this->addHTMLVertSpace(0, 0, $cell, false, $lasttag);
                break;
            }
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
            {
                $this->addHTMLVertSpace($hbz, $hb, $cell, false, $lasttag);
                break;
            }
            // Form fields (since 4.8.000 - 2009-09-07)
            case 'form':
            {
                $this->form_action  = '';
                $this->form_enctype = 'application/x-www-form-urlencoded';
                break;
            }
            default :
            {
                break;
            }
        }
        // draw border and background (if any)
        $this->drawHTMLTagBorder($parent, $xmax);
        if (isset($dom[($dom[$key]['parent'])]['attribute']['pagebreakafter'])) {
            $pba = $dom[($dom[$key]['parent'])]['attribute']['pagebreakafter'];
            // check for pagebreak
            if (($pba == 'true') or ($pba == 'left') or ($pba == 'right')) {
                // add a page (or trig AcceptPageBreak() for multicolumn mode)
                $this->checkPageBreak($this->breakTrigger + 1);
            }
            if (($pba === 'left' and $this->page % 2 === 0) or ($pba === 'right' and $this->page % 2 !== 0)) {
                // add a page (or trig AcceptPageBreak() for multicolumn mode)
                $this->checkPageBreak($this->breakTrigger + 1);
            }
        }
        return $dom;
    }

    /**
     * Returns the HTML DOM array.
     * @param string $html html code
     * @return array
     */
    protected function getHtmlDomArray($html)
    {
        // array of CSS styles ( selector => properties).
        $css = [];
        // get CSS array defined at previous call
        $matches = [];
        if (preg_match_all('/<cssarray>([^\<]*?)<\/cssarray>/is', $html, $matches) > 0) {
            if (isset($matches[1][0])) {
                $css = array_merge($css, json_decode($this->unhtmlentities($matches[1][0]), true));
            }
            $html = preg_replace('/<cssarray>(.*?)<\/cssarray>/is', '', $html);
        }
        // extract external CSS files
        $matches = [];
        if (preg_match_all('/<link([^\>]*?)>/is', $html, $matches) > 0) {
            foreach ($matches[1] as $key => $link) {
                $type = [];
                if (preg_match('/type[\s]*=[\s]*"text\/css"/', $link, $type)) {
                    $type = [];
                    preg_match('/media[\s]*=[\s]*"([^"]*)"/', $link, $type);
                    // get 'all' and 'print' media, other media types are discarded
                    // (all, braille, embossed, handheld, print, projection, screen, speech, tty, tv)
                    if (empty($type) or (isset($type[1]) and (($type[1] == 'all') or ($type[1] == 'print')))) {
                        $type = [];
                        if (preg_match('/href[\s]*=[\s]*"([^"]*)"/', $link, $type) > 0) {
                            // read CSS data file
                            $cssdata = $this->getCachedFileContents(trim($type[1]));
                            if (($cssdata !== false) and (strlen($cssdata) > 0)) {
                                $css = array_merge($css, StaticMethods::extractCSSproperties($cssdata));
                            }
                        }
                    }
                }
            }
        }
        // extract style tags
        $matches = [];
        if (preg_match_all('/<style([^\>]*?)>([^\<]*?)<\/style>/is', $html, $matches) > 0) {
            foreach ($matches[1] as $key => $media) {
                $type = [];
                preg_match('/media[\s]*=[\s]*"([^"]*)"/', $media, $type);
                // get 'all' and 'print' media, other media types are discarded
                // (all, braille, embossed, handheld, print, projection, screen, speech, tty, tv)
                if (empty($type) or (isset($type[1]) and (($type[1] == 'all') or ($type[1] == 'print')))) {
                    $cssdata = $matches[2][$key];
                    $css     = array_merge($css, StaticMethods::extractCSSproperties($cssdata));
                }
            }
        }
        // create a special tag to contain the CSS array (used for table content)
        $csstagarray = '<cssarray>' . htmlentities(json_encode($css)) . '</cssarray>';
        // remove head and style blocks
        $html = preg_replace('/<head([^\>]*?)>(.*?)<\/head>/is', '', $html);
        $html = preg_replace('/<style([^\>]*?)>([^\<]*?)<\/style>/is', '', $html);
        // define block tags
        $blocktags = ['blockquote', 'br', 'dd', 'dl', 'div', 'dt', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'li', 'ol', 'p', 'pre', 'ul', 'tcpdf', 'table', 'tr', 'td'];
        // define self-closing tags
        $selfclosingtags = ['area', 'base', 'basefont', 'br', 'hr', 'input', 'img', 'link', 'meta'];
        // remove all unsupported tags (the line below lists all supported tags)
        $html = strip_tags($html, '<marker/><a><b><blockquote><body><br><br/><dd><del><div><dl><dt><em><font><form><h1><h2><h3><h4><h5><h6><hr><hr/><i><img><input><label><li><ol><option><p><pre><s><select><small><span><strike><strong><sub><sup><table><tablehead><tcpdf><td><textarea><th><thead><tr><tt><u><ul>');
        //replace some blank characters
        $html     = preg_replace('/<pre/', '<xre', $html); // preserve pre tag
        $html     = preg_replace('/<(table|tr|td|th|tcpdf|blockquote|dd|div|dl|dt|form|h1|h2|h3|h4|h5|h6|br|hr|li|ol|ul|p)([^\>]*)>[\n\r\t]+/', '<\\1\\2>', $html);
        $html     = preg_replace('@(\r\n|\r)@', "\n", $html);
        $repTable = ["\t" => ' ', "\0" => ' ', "\x0B" => ' ', "\\" => "\\\\"];
        $html     = strtr($html, $repTable);
        $offset   = 0;
        while (($offset < strlen($html)) and ($pos = strpos($html, '</pre>', $offset)) !== false) {
            $html_a = substr($html, 0, $offset);
            $html_b = substr($html, $offset, ($pos - $offset + 6));
            while (preg_match("'<xre([^\>]*)>(.*?)\n(.*?)</pre>'si", $html_b)) {
                // preserve newlines on <pre> tag
                $html_b = preg_replace("'<xre([^\>]*)>(.*?)\n(.*?)</pre>'si", "<xre\\1>\\2<br />\\3</pre>", $html_b);
            }
            while (preg_match("'<xre([^\>]*)>(.*?)" . $this->spaceRE['p'] . "(.*?)</pre>'" . $this->spaceRE['m'], $html_b)) {
                // preserve spaces on <pre> tag
                $html_b = preg_replace("'<xre([^\>]*)>(.*?)" . $this->spaceRE['p'] . "(.*?)</pre>'" . $this->spaceRE['m'], "<xre\\1>\\2&nbsp;\\3</pre>", $html_b);
            }
            $html   = $html_a . $html_b . substr($html, $pos + 6);
            $offset = strlen($html_a . $html_b);
        }
        $offset = 0;
        while (($offset < strlen($html)) and ($pos = strpos($html, '</textarea>', $offset)) !== false) {
            $html_a = substr($html, 0, $offset);
            $html_b = substr($html, $offset, ($pos - $offset + 11));
            while (preg_match("'<textarea([^\>]*)>(.*?)\n(.*?)</textarea>'si", $html_b)) {
                // preserve newlines on <textarea> tag
                $html_b = preg_replace("'<textarea([^\>]*)>(.*?)\n(.*?)</textarea>'si", "<textarea\\1>\\2<TBR>\\3</textarea>", $html_b);
                $html_b = preg_replace("'<textarea([^\>]*)>(.*?)[\"](.*?)</textarea>'si", "<textarea\\1>\\2''\\3</textarea>", $html_b);
            }
            $html   = $html_a . $html_b . substr($html, $pos + 11);
            $offset = strlen($html_a . $html_b);
        }
        $html   = preg_replace('/([\s]*)<option/si', '<option', $html);
        $html   = preg_replace('/<\/option>([\s]*)/si', '</option>', $html);
        $offset = 0;
        while (($offset < strlen($html)) and ($pos = strpos($html, '</option>', $offset)) !== false) {
            $html_a = substr($html, 0, $offset);
            $html_b = substr($html, $offset, ($pos - $offset + 9));
            while (preg_match("'<option([^\>]*)>(.*?)</option>'si", $html_b)) {
                $html_b = preg_replace("'<option([\s]+)value=\"([^\"]*)\"([^\>]*)>(.*?)</option>'si", "\\2#!TaB!#\\4#!NwL!#", $html_b);
                $html_b = preg_replace("'<option([^\>]*)>(.*?)</option>'si", "\\2#!NwL!#", $html_b);
            }
            $html   = $html_a . $html_b . substr($html, $pos + 9);
            $offset = strlen($html_a . $html_b);
        }
        if (preg_match("'</select'si", $html)) {
            $html = preg_replace("'<select([^\>]*)>'si", "<select\\1 opt=\"", $html);
            $html = preg_replace("'#!NwL!#</select>'si", "\" />", $html);
        }
        $html = str_replace("\n", ' ', $html);
        // restore textarea newlines
        $html = str_replace('<TBR>', "\n", $html);
        // remove extra spaces from code
        $html = preg_replace('/[\s]+<\/(table|tr|ul|ol|dl)>/', '</\\1>', $html);
        $html = preg_replace('/' . $this->spaceRE['p'] . '+<\/(td|th|li|dt|dd)>/' . $this->spaceRE['m'], '</\\1>', $html);
        $html = preg_replace('/[\s]+<(tr|td|th|li|dt|dd)/', '<\\1', $html);
        $html = preg_replace('/' . $this->spaceRE['p'] . '+<(ul|ol|dl|br)/' . $this->spaceRE['m'], '<\\1', $html);
        $html = preg_replace('/<\/(table|tr|td|th|blockquote|dd|dt|dl|div|dt|h1|h2|h3|h4|h5|h6|hr|li|ol|ul|p)>[\s]+</', '</\\1><', $html);
        $html = preg_replace('/<\/(td|th)>/', '<marker style="font-size:0"/></\\1>', $html);
        $html = preg_replace('/<\/table>([\s]*)<marker style="font-size:0"\/>/', '</table>', $html);
        $html = preg_replace('/' . $this->spaceRE['p'] . '+<img/' . $this->spaceRE['m'], chr(32) . '<img', $html);
        $html = preg_replace('/<img([^\>]*)>[\s]+([^\<])/xi', '<img\\1>&nbsp;\\2', $html);
        $html = preg_replace('/<img([^\>]*)>/xi', '<img\\1><span><marker style="font-size:0"/></span>', $html);
        $html = preg_replace('/<xre/', '<pre', $html); // restore pre tag
        $html = preg_replace('/<textarea([^\>]*)>([^\<]*)<\/textarea>/xi', '<textarea\\1 value="\\2" />', $html);
        $html = preg_replace('/<li([^\>]*)><\/li>/', '<li\\1>&nbsp;</li>', $html);
        $html = preg_replace('/<li([^\>]*)>' . $this->spaceRE['p'] . '*<img/' . $this->spaceRE['m'], '<li\\1><font size="1">&nbsp;</font><img', $html);
        $html = preg_replace('/<([^\>\/]*)>[\s]/', '<\\1>&nbsp;', $html); // preserve some spaces
        $html = preg_replace('/[\s]<\/([^\>]*)>/', '&nbsp;</\\1>', $html); // preserve some spaces
        $html = preg_replace('/<su([bp])/', '<zws/><su\\1', $html); // fix sub/sup alignment
        $html = preg_replace('/<\/su([bp])>/', '</su\\1><zws/>', $html); // fix sub/sup alignment
        $html = preg_replace('/' . $this->spaceRE['p'] . '+/' . $this->spaceRE['m'], chr(32), $html); // replace multiple spaces with a single space
        // trim string
        $html = $this->trim($html);
        // fix br tag after li
        $html = preg_replace('/<li><br([^\>]*)>/', '<li> <br\\1>', $html);
        // fix first image tag alignment
        $html = preg_replace('/^<img/', '<span style="font-size:0"><br /></span> <img', $html, 1);
        // pattern for generic tag
        $tagpattern = '/(<[^>]+>)/';
        // explodes the string
        $a = preg_split($tagpattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        // count elements
        $maxel = count($a);
        $elkey = 0;
        $key   = 0;
        // create an array of elements
        $dom       = [];
        $dom[$key] = [];
        // set inheritable properties fot the first void element
        // possible inheritable properties are: azimuth, border-collapse, border-spacing, caption-side, color, cursor, direction, empty-cells, font, font-family, font-stretch, font-size, font-size-adjust, font-style, font-variant, font-weight, letter-spacing, line-height, list-style, list-style-image, list-style-position, list-style-type, orphans, page, page-break-inside, quotes, speak, speak-header, text-align, text-indent, text-transform, volume, white-space, widows, word-spacing
        $dom[$key]['align']          = '';
        $dom[$key]['bgColor']        = false;
        $dom[$key]['block']          = false;
        $dom[$key]['border']         = [];
        $dom[$key]['clip']           = ($this->textrendermode > 3);
        $dom[$key]['dir']            = 'ltr';
        $dom[$key]['fgColor']        = $this->fgColor;
        $dom[$key]['fill']           = (($this->textrendermode % 2) == 0);
        $dom[$key]['fontname']       = $this->fontFamily;
        $dom[$key]['fontsize']       = $this->fontSizePT;
        $dom[$key]['font-stretch']   = $this->fontStretching;
        $dom[$key]['fontstyle']      = $this->fontStyle;
        $dom[$key]['hide']           = false;
        $dom[$key]['letter-spacing'] = $this->fontSpacing;
        $dom[$key]['line-height']    = $this->cellHeightRatio;
        $dom[$key]['listtype']       = '';
        $dom[$key]['parent']         = 0;
        $dom[$key]['stroke']         = $this->textstrokewidth;
        $dom[$key]['strokecolor']    = $this->strokeColor;
        $dom[$key]['tag']            = false;
        $dom[$key]['text-indent']    = 0;
        $dom[$key]['text-transform'] = '';
        $dom[$key]['value']          = '';
        $thead                       = false; // true when we are inside the THEAD tag
        ++$key;
        $level = [];
        array_push($level, 0); // root
        while ($elkey < $maxel) {
            $dom[$key]          = [];
            $element            = $a[$elkey];
            $dom[$key]['elkey'] = $elkey;
            if (preg_match($tagpattern, $element)) {
                // html tag
                $element = substr($element, 1, -1);
                // get tag name
                preg_match('/[\/]?([a-zA-Z0-9]*)/', $element, $tag);
                $tagname = strtolower($tag[1]);
                // check if we are inside a table header
                if ($tagname == 'thead') {
                    if ($element[0] == '/') {
                        $thead = false;
                    }
                    else {
                        $thead = true;
                    }
                    ++$elkey;
                    continue;
                }
                $dom[$key]['tag']   = true;
                $dom[$key]['value'] = $tagname;
                if (in_array($dom[$key]['value'], $blocktags)) {
                    $dom[$key]['block'] = true;
                }
                else {
                    $dom[$key]['block'] = false;
                }
                if ($element[0] == '/') {
                    // *** closing html tag
                    $dom[$key]['opening'] = false;
                    $dom[$key]['parent']  = end($level);
                    array_pop($level);
                    $dom[$key]['align']          = $dom[($dom[($dom[$key]['parent'])]['parent'])]['align'];
                    $dom[$key]['bgColor']        = $dom[($dom[($dom[$key]['parent'])]['parent'])]['bgColor'];
                    $dom[$key]['clip']           = $dom[($dom[($dom[$key]['parent'])]['parent'])]['clip'];
                    $dom[$key]['dir']            = $dom[($dom[($dom[$key]['parent'])]['parent'])]['dir'];
                    $dom[$key]['fgColor']        = $dom[($dom[($dom[$key]['parent'])]['parent'])]['fgColor'];
                    $dom[$key]['fill']           = $dom[($dom[($dom[$key]['parent'])]['parent'])]['fill'];
                    $dom[$key]['fontname']       = $dom[($dom[($dom[$key]['parent'])]['parent'])]['fontname'];
                    $dom[$key]['fontsize']       = $dom[($dom[($dom[$key]['parent'])]['parent'])]['fontsize'];
                    $dom[$key]['font-stretch']   = $dom[($dom[($dom[$key]['parent'])]['parent'])]['font-stretch'];
                    $dom[$key]['fontstyle']      = $dom[($dom[($dom[$key]['parent'])]['parent'])]['fontstyle'];
                    $dom[$key]['hide']           = $dom[($dom[($dom[$key]['parent'])]['parent'])]['hide'];
                    $dom[$key]['letter-spacing'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['letter-spacing'];
                    $dom[$key]['line-height']    = $dom[($dom[($dom[$key]['parent'])]['parent'])]['line-height'];
                    $dom[$key]['stroke']         = $dom[($dom[($dom[$key]['parent'])]['parent'])]['stroke'];
                    $dom[$key]['strokecolor']    = $dom[($dom[($dom[$key]['parent'])]['parent'])]['strokecolor'];
                    $dom[$key]['text-transform'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['text-transform'];
                    if (isset($dom[($dom[($dom[$key]['parent'])]['parent'])]['listtype'])) {
                        $dom[$key]['listtype'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['listtype'];
                    }
                    // set the number of columns in table tag
                    if (($dom[$key]['value'] == 'tr') and (!isset($dom[($dom[($dom[$key]['parent'])]['parent'])]['cols']))) {
                        $dom[($dom[($dom[$key]['parent'])]['parent'])]['cols'] = $dom[($dom[$key]['parent'])]['cols'];
                    }
                    if (($dom[$key]['value'] == 'td') or ($dom[$key]['value'] == 'th')) {
                        $dom[($dom[$key]['parent'])]['content'] = $csstagarray;
                        for ($i = ($dom[$key]['parent'] + 1); $i < $key; ++$i) {
                            $dom[($dom[$key]['parent'])]['content'] .= stripslashes($a[$dom[$i]['elkey']]);
                        }
                        $key = $i;
                        // mark nested tables
                        $dom[($dom[$key]['parent'])]['content'] = str_replace('<table', '<table nested="true"', $dom[($dom[$key]['parent'])]['content']);
                        // remove thead sections from nested tables
                        $dom[($dom[$key]['parent'])]['content'] = str_replace('<thead>', '', $dom[($dom[$key]['parent'])]['content']);
                        $dom[($dom[$key]['parent'])]['content'] = str_replace('</thead>', '', $dom[($dom[$key]['parent'])]['content']);
                    }
                    // store header rows on a new table
                    if (
                        ($dom[$key]['value'] === 'tr')
                        && !empty($dom[($dom[$key]['parent'])]['thead'])
                        && ($dom[($dom[$key]['parent'])]['thead'] === true)
                    ) {
                        if (empty($dom[($dom[($dom[$key]['parent'])]['parent'])]['thead'])) {
                            $dom[($dom[($dom[$key]['parent'])]['parent'])]['thead'] = $csstagarray . $a[$dom[($dom[($dom[$key]['parent'])]['parent'])]['elkey']];
                        }
                        for ($i = $dom[$key]['parent']; $i <= $key; ++$i) {
                            $dom[($dom[($dom[$key]['parent'])]['parent'])]['thead'] .= $a[$dom[$i]['elkey']];
                        }
                        if (!isset($dom[($dom[$key]['parent'])]['attribute'])) {
                            $dom[($dom[$key]['parent'])]['attribute'] = [];
                        }
                        // header elements must be always contained in a single page
                        $dom[($dom[$key]['parent'])]['attribute']['nobr'] = 'true';
                    }
                    if (($dom[$key]['value'] == 'table') and (!empty($dom[($dom[$key]['parent'])]['thead']))) {
                        // remove the nobr attributes from the table header
                        $dom[($dom[$key]['parent'])]['thead'] = str_replace(' nobr="true"', '', $dom[($dom[$key]['parent'])]['thead']);
                        $dom[($dom[$key]['parent'])]['thead'] .= '</tablehead>';
                    }
                }
                else {
                    // *** opening or self-closing html tag
                    $dom[$key]['opening'] = true;
                    $dom[$key]['parent']  = end($level);
                    if ((substr($element, -1, 1) == '/') or (in_array($dom[$key]['value'], $selfclosingtags))) {
                        // self-closing tag
                        $dom[$key]['self'] = true;
                    }
                    else {
                        // opening tag
                        array_push($level, $key);
                        $dom[$key]['self'] = false;
                    }
                    // copy some values from parent
                    $parentkey = 0;
                    if ($key > 0) {
                        $parentkey                   = $dom[$key]['parent'];
                        $dom[$key]['hide']           = $dom[$parentkey]['hide'];
                        $dom[$key]['fontname']       = $dom[$parentkey]['fontname'];
                        $dom[$key]['fontstyle']      = $dom[$parentkey]['fontstyle'];
                        $dom[$key]['fontsize']       = $dom[$parentkey]['fontsize'];
                        $dom[$key]['font-stretch']   = $dom[$parentkey]['font-stretch'];
                        $dom[$key]['letter-spacing'] = $dom[$parentkey]['letter-spacing'];
                        $dom[$key]['stroke']         = $dom[$parentkey]['stroke'];
                        $dom[$key]['fill']           = $dom[$parentkey]['fill'];
                        $dom[$key]['clip']           = $dom[$parentkey]['clip'];
                        $dom[$key]['line-height']    = $dom[$parentkey]['line-height'];
                        $dom[$key]['bgColor']        = $dom[$parentkey]['bgColor'];
                        $dom[$key]['fgColor']        = $dom[$parentkey]['fgColor'];
                        $dom[$key]['strokecolor']    = $dom[$parentkey]['strokecolor'];
                        $dom[$key]['align']          = $dom[$parentkey]['align'];
                        $dom[$key]['listtype']       = $dom[$parentkey]['listtype'];
                        $dom[$key]['text-indent']    = $dom[$parentkey]['text-indent'];
                        $dom[$key]['text-transform'] = $dom[$parentkey]['text-transform'];
                        $dom[$key]['border']         = [];
                        $dom[$key]['dir']            = $dom[$parentkey]['dir'];
                    }
                    // get attributes
                    preg_match_all('/([^=\s]*)[\s]*=[\s]*"([^"]*)"/', $element, $attr_array, PREG_PATTERN_ORDER);
                    $dom[$key]['attribute'] = []; // reset attribute array
                    foreach ($attr_array[1] as $id => $name) {
                        $dom[$key]['attribute'][strtolower($name)] = $attr_array[2][$id];
                    }
                    if (!empty($css)) {
                        // merge CSS style to current style
                        list($dom[$key]['csssel'], $dom[$key]['cssdata']) = StaticMethods::getCSSdataArray($dom, $key, $css);
                        $dom[$key]['attribute']['style'] = StaticMethods::getTagStyleFromCSSarray($dom[$key]['cssdata']);
                    }
                    // split style attributes
                    if (isset($dom[$key]['attribute']['style']) and !empty($dom[$key]['attribute']['style'])) {
                        // get style attributes
                        preg_match_all('/([^;:\s]*):([^;]*)/', $dom[$key]['attribute']['style'], $style_array, PREG_PATTERN_ORDER);
                        $dom[$key]['style'] = []; // reset style attribute array
                        foreach ($style_array[1] as $id => $name) {
                            // in case of duplicate attribute the last replace the previous
                            $dom[$key]['style'][strtolower($name)] = trim($style_array[2][$id]);
                        }
                        // --- get some style attributes ---
                        // text direction
                        if (isset($dom[$key]['style']['direction'])) {
                            $dom[$key]['dir'] = $dom[$key]['style']['direction'];
                        }
                        // display
                        if (isset($dom[$key]['style']['display'])) {
                            $dom[$key]['hide'] = (trim(strtolower($dom[$key]['style']['display'])) == 'none');
                        }
                        // font family
                        if (isset($dom[$key]['style']['font-family'])) {
                            $dom[$key]['fontname'] = $this->fontFamily($dom[$key]['style']['font-family']);
                        }
                        // list-style-type
                        if (isset($dom[$key]['style']['list-style-type'])) {
                            $dom[$key]['listtype'] = trim(strtolower($dom[$key]['style']['list-style-type']));
                            if ($dom[$key]['listtype'] == 'inherit') {
                                $dom[$key]['listtype'] = $dom[$parentkey]['listtype'];
                            }
                        }
                        // text-indent
                        if (isset($dom[$key]['style']['text-indent'])) {
                            $dom[$key]['text-indent'] = $this->fontSizeToUnits($dom[$key]['style']['text-indent']);
                            if ($dom[$key]['text-indent'] == 'inherit') {
                                $dom[$key]['text-indent'] = $dom[$parentkey]['text-indent'];
                            }
                        }
                        // text-transform
                        if (isset($dom[$key]['style']['text-transform'])) {
                            $dom[$key]['text-transform'] = $dom[$key]['style']['text-transform'];
                        }
                        // font size
                        if (isset($dom[$key]['style']['font-size'])) {
                            $fsize                 = trim($dom[$key]['style']['font-size']);
                            $dom[$key]['fontsize'] = $this->fontSizeToUnits($fsize, $dom[$parentkey]['fontsize'], self::POINT, true);
                        }
                        // font-stretch
                        if (isset($dom[$key]['style']['font-stretch'])) {
                            $dom[$key]['font-stretch'] = $this->getCSSFontStretching($dom[$key]['style']['font-stretch'], $dom[$parentkey]['font-stretch']);
                        }
                        // letter-spacing
                        if (isset($dom[$key]['style']['letter-spacing'])) {
                            $dom[$key]['letter-spacing'] = $this->getCSSFontSpacing($dom[$key]['style']['letter-spacing'], $dom[$parentkey]['letter-spacing']);
                        }
                        // line-height (internally is the cell height ratio)
                        if (isset($dom[$key]['style']['line-height'])) {
                            $lineheight = trim($dom[$key]['style']['line-height']);
                            switch ($lineheight) {
                                // A normal line height. This is default
                                case 'normal':
                                {
                                    $dom[$key]['line-height'] = $dom[0]['line-height'];
                                    break;
                                }
                                case 'inherit':
                                {
                                    $dom[$key]['line-height'] = $dom[$parentkey]['line-height'];
                                }
                                default:
                                {
                                    if (is_numeric($lineheight)) {
                                        // convert to percentage of font height
                                        $lineheight = ($lineheight * 100) . '%';
                                    }
                                    $dom[$key]['line-height'] = $this->fontSizeToUnits($lineheight, 1, self::PERCENT, true);
                                    if (substr($lineheight, -1) !== '%') {
                                        if ($dom[$key]['fontsize'] <= 0) {
                                            $dom[$key]['line-height'] = 1;
                                        }
                                        else {
                                            $dom[$key]['line-height'] = (($dom[$key]['line-height'] - $this->cellPaddings['T'] - $this->cellPaddings['B']) / $dom[$key]['fontsize']);
                                        }
                                    }
                                }
                            }
                        }
                        // font style
                        if (isset($dom[$key]['style']['font-weight'])) {
                            if (strtolower($dom[$key]['style']['font-weight'][0]) == 'n') {
                                if (strpos($dom[$key]['fontstyle'], 'B') !== false) {
                                    $dom[$key]['fontstyle'] = str_replace('B', '', $dom[$key]['fontstyle']);
                                }
                            }
                            elseif (strtolower($dom[$key]['style']['font-weight'][0]) == 'b') {
                                $dom[$key]['fontstyle'] .= 'B';
                            }
                        }
                        if (isset($dom[$key]['style']['font-style']) and (strtolower($dom[$key]['style']['font-style'][0]) == 'i')) {
                            $dom[$key]['fontstyle'] .= 'I';
                        }
                        // font color
                        if (isset($dom[$key]['style']['color']) and (!empty($dom[$key]['style']['color']))) {
                            $dom[$key]['fgColor'] = $this->htmlToColor($dom[$key]['style']['color']);
                        }
                        elseif ($dom[$key]['value'] == 'a') {
                            $dom[$key]['fgColor'] = $this->linkColor;
                        }
                        // background color
                        if (isset($dom[$key]['style']['background-color']) and (!empty($dom[$key]['style']['background-color']))) {
                            $dom[$key]['bgColor'] = $this->htmlToColor($dom[$key]['style']['background-color']);
                        }
                        // text-decoration
                        if (isset($dom[$key]['style']['text-decoration'])) {
                            $decors = explode(' ', strtolower($dom[$key]['style']['text-decoration']));
                            foreach ($decors as $dec) {
                                $dec = trim($dec);
                                if (!empty($dec)) {
                                    if ($dec[0] == 'u') {
                                        // underline
                                        $dom[$key]['fontstyle'] .= 'U';
                                    }
                                    elseif ($dec[0] == 'l') {
                                        // line-through
                                        $dom[$key]['fontstyle'] .= 'D';
                                    }
                                    elseif ($dec[0] == 'o') {
                                        // overline
                                        $dom[$key]['fontstyle'] .= 'O';
                                    }
                                }
                            }
                        }
                        elseif ($dom[$key]['value'] == 'a') {
                            $dom[$key]['fontstyle'] = $this->linkStyle;
                        }
                        // check for width attribute
                        if (isset($dom[$key]['style']['width'])) {
                            $dom[$key]['width'] = $dom[$key]['style']['width'];
                        }
                        // check for height attribute
                        if (isset($dom[$key]['style']['height'])) {
                            $dom[$key]['height'] = $dom[$key]['style']['height'];
                        }
                        // check for text alignment
                        if (isset($dom[$key]['style']['text-align'])) {
                            $dom[$key]['align'] = strtoupper($dom[$key]['style']['text-align'][0]);
                        }
                        // check for CSS border properties
                        if (isset($dom[$key]['style']['border'])) {
                            $borderstyle = $this->getCSSBorderStyle($dom[$key]['style']['border']);
                            if (!empty($borderstyle)) {
                                $dom[$key]['border']['LTRB'] = $borderstyle;
                            }
                        }
                        if (isset($dom[$key]['style']['border-color'])) {
                            $brd_colors = preg_split('/[\s]+/', trim($dom[$key]['style']['border-color']));
                            if (isset($brd_colors[3])) {
                                $dom[$key]['border']['L']['color'] = $this->htmlToColor($brd_colors[3]);
                            }
                            if (isset($brd_colors[1])) {
                                $dom[$key]['border']['R']['color'] = $this->htmlToColor($brd_colors[1]);
                            }
                            if (isset($brd_colors[0])) {
                                $dom[$key]['border']['T']['color'] = $this->htmlToColor($brd_colors[0]);
                            }
                            if (isset($brd_colors[2])) {
                                $dom[$key]['border']['B']['color'] = $this->htmlToColor($brd_colors[2]);
                            }
                        }
                        if (isset($dom[$key]['style']['border-width'])) {
                            $brd_widths = preg_split('/[\s]+/', trim($dom[$key]['style']['border-width']));
                            if (isset($brd_widths[3])) {
                                $dom[$key]['border']['L']['width'] = $this->getCSSBorderWidth($brd_widths[3]);
                            }
                            if (isset($brd_widths[1])) {
                                $dom[$key]['border']['R']['width'] = $this->getCSSBorderWidth($brd_widths[1]);
                            }
                            if (isset($brd_widths[0])) {
                                $dom[$key]['border']['T']['width'] = $this->getCSSBorderWidth($brd_widths[0]);
                            }
                            if (isset($brd_widths[2])) {
                                $dom[$key]['border']['B']['width'] = $this->getCSSBorderWidth($brd_widths[2]);
                            }
                        }
                        if (isset($dom[$key]['style']['border-style'])) {
                            $brd_styles = preg_split('/[\s]+/', trim($dom[$key]['style']['border-style']));
                            if (isset($brd_styles[3]) and ($brd_styles[3] != 'none')) {
                                $dom[$key]['border']['L']['cap']  = 'square';
                                $dom[$key]['border']['L']['join'] = 'miter';
                                $dom[$key]['border']['L']['dash'] = $this->getCSSBorderDashStyle($brd_styles[3]);
                                if ($dom[$key]['border']['L']['dash'] < 0) {
                                    $dom[$key]['border']['L'] = [];
                                }
                            }
                            if (isset($brd_styles[1])) {
                                $dom[$key]['border']['R']['cap']  = 'square';
                                $dom[$key]['border']['R']['join'] = 'miter';
                                $dom[$key]['border']['R']['dash'] = $this->getCSSBorderDashStyle($brd_styles[1]);
                                if ($dom[$key]['border']['R']['dash'] < 0) {
                                    $dom[$key]['border']['R'] = [];
                                }
                            }
                            if (isset($brd_styles[0])) {
                                $dom[$key]['border']['T']['cap']  = 'square';
                                $dom[$key]['border']['T']['join'] = 'miter';
                                $dom[$key]['border']['T']['dash'] = $this->getCSSBorderDashStyle($brd_styles[0]);
                                if ($dom[$key]['border']['T']['dash'] < 0) {
                                    $dom[$key]['border']['T'] = [];
                                }
                            }
                            if (isset($brd_styles[2])) {
                                $dom[$key]['border']['B']['cap']  = 'square';
                                $dom[$key]['border']['B']['join'] = 'miter';
                                $dom[$key]['border']['B']['dash'] = $this->getCSSBorderDashStyle($brd_styles[2]);
                                if ($dom[$key]['border']['B']['dash'] < 0) {
                                    $dom[$key]['border']['B'] = [];
                                }
                            }
                        }
                        $cellside = ['L' => 'left', 'R' => 'right', 'T' => 'top', 'B' => 'bottom'];
                        foreach ($cellside as $bsk => $bsv) {
                            if (isset($dom[$key]['style']['border-' . $bsv])) {
                                $borderstyle = $this->getCSSBorderStyle($dom[$key]['style']['border-' . $bsv]);
                                if (!empty($borderstyle)) {
                                    $dom[$key]['border'][$bsk] = $borderstyle;
                                }
                            }
                            if (isset($dom[$key]['style']['border-' . $bsv . '-color'])) {
                                $dom[$key]['border'][$bsk]['color'] = $this->htmlToColor($dom[$key]['style']['border-' . $bsv . '-color']);
                            }
                            if (isset($dom[$key]['style']['border-' . $bsv . '-width'])) {
                                $dom[$key]['border'][$bsk]['width'] = $this->getCSSBorderWidth($dom[$key]['style']['border-' . $bsv . '-width']);
                            }
                            if (isset($dom[$key]['style']['border-' . $bsv . '-style'])) {
                                $dom[$key]['border'][$bsk]['dash'] = $this->getCSSBorderDashStyle($dom[$key]['style']['border-' . $bsv . '-style']);
                                if ($dom[$key]['border'][$bsk]['dash'] < 0) {
                                    $dom[$key]['border'][$bsk] = [];
                                }
                            }
                        }
                        // check for CSS padding properties
                        if (isset($dom[$key]['style']['padding'])) {
                            $dom[$key]['padding'] = $this->getCSSPadding($dom[$key]['style']['padding']);
                        }
                        else {
                            $dom[$key]['padding'] = $this->cellPaddings;
                        }
                        foreach ($cellside as $psk => $psv) {
                            if (isset($dom[$key]['style']['padding-' . $psv])) {
                                $dom[$key]['padding'][$psk] = $this->fontSizeToUnits($dom[$key]['style']['padding-' . $psv], 0);
                            }
                        }
                        // check for CSS margin properties
                        if (isset($dom[$key]['style']['margin'])) {
                            $dom[$key]['margin'] = $this->getCSSMargin($dom[$key]['style']['margin']);
                        }
                        else {
                            $dom[$key]['margin'] = $this->cellMargins;
                        }
                        foreach ($cellside as $psk => $psv) {
                            if (isset($dom[$key]['style']['margin-' . $psv])) {
                                $dom[$key]['margin'][$psk] = $this->fontSizeToUnits(str_replace('auto', '0', $dom[$key]['style']['margin-' . $psv]), 0);
                            }
                        }
                        // check for CSS border-spacing properties
                        if (isset($dom[$key]['style']['border-spacing'])) {
                            $dom[$key]['border-spacing'] = $this->getCSSBorderMargin($dom[$key]['style']['border-spacing']);
                        }
                        // page-break-inside
                        if (isset($dom[$key]['style']['page-break-inside']) and ($dom[$key]['style']['page-break-inside'] == 'avoid')) {
                            $dom[$key]['attribute']['nobr'] = 'true';
                        }
                        // page-break-before
                        if (isset($dom[$key]['style']['page-break-before'])) {
                            if ($dom[$key]['style']['page-break-before'] == 'always') {
                                $dom[$key]['attribute']['pagebreak'] = 'true';
                            }
                            elseif ($dom[$key]['style']['page-break-before'] == 'left') {
                                $dom[$key]['attribute']['pagebreak'] = 'left';
                            }
                            elseif ($dom[$key]['style']['page-break-before'] == 'right') {
                                $dom[$key]['attribute']['pagebreak'] = 'right';
                            }
                        }
                        // page-break-after
                        if (isset($dom[$key]['style']['page-break-after'])) {
                            if ($dom[$key]['style']['page-break-after'] == 'always') {
                                $dom[$key]['attribute']['pagebreakafter'] = 'true';
                            }
                            elseif ($dom[$key]['style']['page-break-after'] == 'left') {
                                $dom[$key]['attribute']['pagebreakafter'] = 'left';
                            }
                            elseif ($dom[$key]['style']['page-break-after'] == 'right') {
                                $dom[$key]['attribute']['pagebreakafter'] = 'right';
                            }
                        }
                    }
                    if (isset($dom[$key]['attribute']['display'])) {
                        $dom[$key]['hide'] = (trim(strtolower($dom[$key]['attribute']['display'])) == 'none');
                    }
                    if (isset($dom[$key]['attribute']['border']) and ($dom[$key]['attribute']['border'] != 0)) {
                        $borderstyle = $this->getCSSBorderStyle($dom[$key]['attribute']['border'] . ' solid black');
                        if (!empty($borderstyle)) {
                            $dom[$key]['border']['LTRB'] = $borderstyle;
                        }
                    }
                    // check for font tag
                    if ($dom[$key]['value'] == 'font') {
                        // font family
                        if (isset($dom[$key]['attribute']['face'])) {
                            $dom[$key]['fontname'] = $this->fontFamily($dom[$key]['attribute']['face']);
                        }
                        // font size
                        if (isset($dom[$key]['attribute']['size'])) {
                            if ($key > 0) {
                                if ($dom[$key]['attribute']['size'][0] == '+') {
                                    $dom[$key]['fontsize'] = $dom[($dom[$key]['parent'])]['fontsize'] + intval(substr($dom[$key]['attribute']['size'], 1));
                                }
                                elseif ($dom[$key]['attribute']['size'][0] == '-') {
                                    $dom[$key]['fontsize'] = $dom[($dom[$key]['parent'])]['fontsize'] - intval(substr($dom[$key]['attribute']['size'], 1));
                                }
                                else {
                                    $dom[$key]['fontsize'] = intval($dom[$key]['attribute']['size']);
                                }
                            }
                            else {
                                $dom[$key]['fontsize'] = intval($dom[$key]['attribute']['size']);
                            }
                        }
                    }
                    // force natural alignment for lists
                    if ((($dom[$key]['value'] == 'ul') or ($dom[$key]['value'] == 'ol') or ($dom[$key]['value'] == 'dl'))
                        and (!isset($dom[$key]['align']) or empty($dom[$key]['align']) or ($dom[$key]['align'] != 'J'))) {
                        $dom[$key]['align'] = 'L';
                    }
                    if (($dom[$key]['value'] == 'small') or ($dom[$key]['value'] == 'sup') or ($dom[$key]['value'] == 'sub')) {
                        if (!isset($dom[$key]['attribute']['size']) and !isset($dom[$key]['style']['font-size'])) {
                            $dom[$key]['fontsize'] = $dom[$key]['fontsize'] * self::SMALL_FONT_RATIO;
                        }
                    }
                    if (($dom[$key]['value'] == 'strong') or ($dom[$key]['value'] == 'b')) {
                        $dom[$key]['fontstyle'] .= 'B';
                    }
                    if (($dom[$key]['value'] == 'em') or ($dom[$key]['value'] == 'i')) {
                        $dom[$key]['fontstyle'] .= 'I';
                    }
                    if ($dom[$key]['value'] == 'u') {
                        $dom[$key]['fontstyle'] .= 'U';
                    }
                    if (($dom[$key]['value'] == 'del') or ($dom[$key]['value'] == 's') or ($dom[$key]['value'] == 'strike')) {
                        $dom[$key]['fontstyle'] .= 'D';
                    }
                    if (!isset($dom[$key]['style']['text-decoration']) and ($dom[$key]['value'] == 'a')) {
                        $dom[$key]['fontstyle'] = $this->linkStyle;
                    }
                    if (($dom[$key]['value'] == 'pre') or ($dom[$key]['value'] == 'tt')) {
                        $dom[$key]['fontname'] = $this->default_monospaced_font;
                    }
                    if (!empty($dom[$key]['value']) and ($dom[$key]['value'][0] == 'h') and (intval($dom[$key]['value'][1]) > 0) and (intval($dom[$key]['value'][1]) < 7)) {
                        // headings h1, h2, h3, h4, h5, h6
                        if (!isset($dom[$key]['attribute']['size']) and !isset($dom[$key]['style']['font-size'])) {
                            $headsize              = (4 - intval($dom[$key]['value'][1])) * 2;
                            $dom[$key]['fontsize'] = $dom[0]['fontsize'] + $headsize;
                        }
                        if (!isset($dom[$key]['style']['font-weight'])) {
                            $dom[$key]['fontstyle'] .= 'B';
                        }
                    }
                    if (($dom[$key]['value'] == 'table')) {
                        $dom[$key]['rows']  = 0; // number of rows
                        $dom[$key]['trids'] = []; // IDs of TR elements
                        $dom[$key]['thead'] = ''; // table header rows
                    }
                    if (($dom[$key]['value'] == 'tr')) {
                        $dom[$key]['cols'] = 0;
                        if ($thead) {
                            $dom[$key]['thead'] = true;
                            // rows on thead block are printed as a separate table
                        }
                        else {
                            $dom[$key]['thead'] = false;
                            $parent             = $dom[$key]['parent'];

                            if (!isset($dom[$parent]['rows'])) {
                                $dom[$parent]['rows'] = 0;
                            }
                            // store the number of rows on table element
                            ++$dom[$parent]['rows'];

                            if (!isset($dom[$parent]['trids'])) {
                                $dom[$parent]['trids'] = [];
                            }

                            // store the TR elements IDs on table element
                            array_push($dom[$parent]['trids'], $key);
                        }
                    }
                    if (($dom[$key]['value'] == 'th') or ($dom[$key]['value'] == 'td')) {
                        if (isset($dom[$key]['attribute']['colspan'])) {
                            $colspan = intval($dom[$key]['attribute']['colspan']);
                        }
                        else {
                            $colspan = 1;
                        }
                        $dom[$key]['attribute']['colspan']   = $colspan;
                        $dom[($dom[$key]['parent'])]['cols'] += $colspan;
                    }
                    // text direction
                    if (isset($dom[$key]['attribute']['dir'])) {
                        $dom[$key]['dir'] = $dom[$key]['attribute']['dir'];
                    }
                    // set foreground color attribute
                    if (isset($dom[$key]['attribute']['color']) and (!empty($dom[$key]['attribute']['color']))) {
                        $dom[$key]['fgColor'] = $this->htmlToColor($dom[$key]['attribute']['color']);
                    }
                    elseif (!isset($dom[$key]['style']['color']) and ($dom[$key]['value'] == 'a')) {
                        $dom[$key]['fgColor'] = $this->linkColor;
                    }
                    // set background color attribute
                    if (isset($dom[$key]['attribute']['bgColor']) and (!empty($dom[$key]['attribute']['bgColor']))) {
                        $dom[$key]['bgColor'] = $this->htmlToColor($dom[$key]['attribute']['bgColor']);
                    }
                    // set stroke color attribute
                    if (isset($dom[$key]['attribute']['strokecolor']) and (!empty($dom[$key]['attribute']['strokecolor']))) {
                        $dom[$key]['strokecolor'] = $this->htmlToColor($dom[$key]['attribute']['strokecolor']);
                    }
                    // check for width attribute
                    if (isset($dom[$key]['attribute']['width'])) {
                        $dom[$key]['width'] = $dom[$key]['attribute']['width'];
                    }
                    // check for height attribute
                    if (isset($dom[$key]['attribute']['height'])) {
                        $dom[$key]['height'] = $dom[$key]['attribute']['height'];
                    }
                    // check for text alignment
                    if (isset($dom[$key]['attribute']['align']) and (!empty($dom[$key]['attribute']['align'])) and ($dom[$key]['value'] !== 'img')) {
                        $dom[$key]['align'] = strtoupper($dom[$key]['attribute']['align'][0]);
                    }
                    // check for text rendering mode (the following attributes do not exist in HTML)
                    if (isset($dom[$key]['attribute']['stroke'])) {
                        // font stroke width
                        $dom[$key]['stroke'] = $this->fontSizeToUnits($dom[$key]['attribute']['stroke'], $dom[$key]['fontsize'], self::POINT, true);
                    }
                    if (isset($dom[$key]['attribute']['fill'])) {
                        // font fill
                        if ($dom[$key]['attribute']['fill'] == 'true') {
                            $dom[$key]['fill'] = true;
                        }
                        else {
                            $dom[$key]['fill'] = false;
                        }
                    }
                    if (isset($dom[$key]['attribute']['clip'])) {
                        // clipping mode
                        if ($dom[$key]['attribute']['clip'] == 'true') {
                            $dom[$key]['clip'] = true;
                        }
                        else {
                            $dom[$key]['clip'] = false;
                        }
                    }
                } // end opening tag
            }
            else {
                // text
                $dom[$key]['tag']    = false;
                $dom[$key]['block']  = false;
                $dom[$key]['parent'] = end($level);
                $dom[$key]['dir']    = $dom[$dom[$key]['parent']]['dir'];
                if (!empty($dom[$dom[$key]['parent']]['text-transform'])) {
                    // text-transform for unicode requires mb_convert_case (Multibyte String Functions)
                    if (function_exists('mb_convert_case')) {
                        $ttm = ['capitalize' => MB_CASE_TITLE, 'uppercase' => MB_CASE_UPPER, 'lowercase' => MB_CASE_LOWER];
                        if (isset($ttm[$dom[$dom[$key]['parent']]['text-transform']])) {
                            $element = mb_convert_case($element, $ttm[$dom[$dom[$key]['parent']]['text-transform']], 'UTF-8');
                        }
                    }
                    elseif (!$this->isUnicode) {
                        switch ($dom[$dom[$key]['parent']]['text-transform']) {
                            case 'capitalize':
                            {
                                $element = ucwords(strtolower($element));
                                break;
                            }
                            case 'uppercase':
                            {
                                $element = strtoupper($element);
                                break;
                            }
                            case 'lowercase':
                            {
                                $element = strtolower($element);
                                break;
                            }
                        }
                    }
                    $element = preg_replace("/&NBSP;/i", "&nbsp;", $element);
                }
                $dom[$key]['value'] = stripslashes($this->unhtmlentities($element));
            }
            ++$elkey;
            ++$key;
        }
        return $dom;
    }

    /**
     * Cleanup HTML code (requires HTML Tidy library).
     * @param string     $html         htmlcode to fix
     * @param string     $default_css  CSS commands to add
     * @param array|null $tagvs        parameters for setHtmlVSpace method
     * @param array|null $tidy_options options for tidy_parse_string function
     * @return string XHTML code cleaned up
     * @author Nicola Asuni
     * @see    setHtmlVSpace()
     */
    public function fixHTMLCode($html, $default_css = '', $tagvs = null, $tidy_options = null)
    {
        return StaticMethods::fixHTMLCode($html, $default_css, $tagvs, $tidy_options, $this->tagvspaces);
    }

    /**
     * Returns the border width from CSS property
     * @param string $width border width
     * @return int with in user units
     */
    protected function getCSSBorderWidth($width)
    {
        if ($width == 'thin') {
            $width = (2 / $this->factor);
        }
        elseif ($width == 'medium') {
            $width = (4 / $this->factor);
        }
        elseif ($width == 'thick') {
            $width = (6 / $this->factor);
        }
        else {
            $width = $this->fontSizeToUnits($width);
        }
        return $width;
    }

    /**
     * Returns the border dash style from CSS property
     * @param string $style border style to convert
     * @return int sash style (return -1 in case of none or hidden border)
     */
    protected function getCSSBorderDashStyle($style)
    {
        switch (strtolower($style)) {
            case 'none':
            case 'hidden':
            {
                $dash = -1;
                break;
            }
            case 'dotted':
            {
                $dash = 1;
                break;
            }
            case 'dashed':
            {
                $dash = 3;
                break;
            }
            case 'double':
            case 'groove':
            case 'ridge':
            case 'inset':
            case 'outset':
            case 'solid':
            default:
            {
                $dash = 0;
                break;
            }
        }
        return $dash;
    }

    /**
     * Returns the border style array from CSS border properties
     * @param string $cssborder border properties
     * @return array containing border properties
     */
    protected function getCSSBorderStyle($cssborder)
    {
        $bprop = preg_split('/[\s]+/', trim($cssborder));
        $count = count($bprop);
        if ($count > 0 && $bprop[$count - 1] === '!important') {
            unset($bprop[$count - 1]);
            --$count;
        }

        $border = []; // value to be returned
        switch ($count) {
            case 2:
            {
                $width = 'medium';
                $style = $bprop[0];
                $color = $bprop[1];
                break;
            }
            case 1:
            {
                $width = 'medium';
                $style = $bprop[0];
                $color = 'black';
                break;
            }
            case 0:
            {
                $width = 'medium';
                $style = 'solid';
                $color = 'black';
                break;
            }
            default:
            {
                $width = $bprop[0];
                $style = $bprop[1];
                $color = $bprop[2];
                break;
            }
        }
        if ($style == 'none') {
            return [];
        }
        $border['cap']  = 'square';
        $border['join'] = 'miter';
        $border['dash'] = $this->getCSSBorderDashStyle($style);
        if ($border['dash'] < 0) {
            return [];
        }
        $border['width'] = $this->getCSSBorderWidth($width);
        $border['color'] = $this->htmlToColor($color);
        return $border;
    }

    /**
     * Get the internal Cell padding from CSS attribute.
     * @param string $csspadding padding properties
     * @param float  $width      width of the containing element
     * @return array of cell paddings
     */
    public function getCSSPadding($csspadding, $width = 0)
    {
        $padding  = preg_split('/[\s]+/', trim($csspadding));
        $paddings = []; // value to be returned
        switch (count($padding)) {
            case 4:
            {
                $paddings['T'] = $padding[0];
                $paddings['R'] = $padding[1];
                $paddings['B'] = $padding[2];
                $paddings['L'] = $padding[3];
                break;
            }
            case 3:
            {
                $paddings['T'] = $padding[0];
                $paddings['R'] = $padding[1];
                $paddings['B'] = $padding[2];
                $paddings['L'] = $padding[1];
                break;
            }
            case 2:
            {
                $paddings['T'] = $padding[0];
                $paddings['R'] = $padding[1];
                $paddings['B'] = $padding[0];
                $paddings['L'] = $padding[1];
                break;
            }
            case 1:
            {
                $paddings['T'] = $padding[0];
                $paddings['R'] = $padding[0];
                $paddings['B'] = $padding[0];
                $paddings['L'] = $padding[0];
                break;
            }
            default:
            {
                return $this->cellPaddings;
            }
        }
        if ($width == 0) {
            $width = $this->width - $this->leftMargin - $this->rightMargin;
        }
        $paddings['T'] = $this->fontSizeToUnits($paddings['T'], $width);
        $paddings['R'] = $this->fontSizeToUnits($paddings['R'], $width);
        $paddings['B'] = $this->fontSizeToUnits($paddings['B'], $width);
        $paddings['L'] = $this->fontSizeToUnits($paddings['L'], $width);
        return $paddings;
    }

    /**
     * Get the internal Cell margin from CSS attribute.
     * @param string $cssmargin margin properties
     * @param float  $width     width of the containing element
     * @return array of cell margins
     */
    public function getCSSMargin($cssmargin, $width = 0)
    {
        $margin  = preg_split('/[\s]+/', trim($cssmargin));
        $margins = []; // value to be returned
        switch (count($margin)) {
            case 4:
            {
                $margins['T'] = $margin[0];
                $margins['R'] = $margin[1];
                $margins['B'] = $margin[2];
                $margins['L'] = $margin[3];
                break;
            }
            case 3:
            {
                $margins['T'] = $margin[0];
                $margins['R'] = $margin[1];
                $margins['B'] = $margin[2];
                $margins['L'] = $margin[1];
                break;
            }
            case 2:
            {
                $margins['T'] = $margin[0];
                $margins['R'] = $margin[1];
                $margins['B'] = $margin[0];
                $margins['L'] = $margin[1];
                break;
            }
            case 1:
            {
                $margins['T'] = $margin[0];
                $margins['R'] = $margin[0];
                $margins['B'] = $margin[0];
                $margins['L'] = $margin[0];
                break;
            }
            default:
            {
                return $this->cellMargins;
            }
        }
        if ($width == 0) {
            $width = $this->width - $this->leftMargin - $this->rightMargin;
        }
        $margins['T'] = $this->fontSizeToUnits(str_replace('auto', '0', $margins['T']), $width);
        $margins['R'] = $this->fontSizeToUnits(str_replace('auto', '0', $margins['R']), $width);
        $margins['B'] = $this->fontSizeToUnits(str_replace('auto', '0', $margins['B']), $width);
        $margins['L'] = $this->fontSizeToUnits(str_replace('auto', '0', $margins['L']), $width);
        return $margins;
    }

    /**
     * Get the border-spacing from CSS attribute.
     * @param string $cssbspace border-spacing CSS properties
     * @param float  $width     width of the containing element
     * @return array of border spacings
     */
    public function getCSSBorderMargin($cssbspace, $width = 0)
    {
        $space          = preg_split('/[\s]+/', trim($cssbspace));
        $border_spacing = []; // value to be returned
        switch (count($space)) {
            case 2:
            {
                $border_spacing['H'] = $space[0];
                $border_spacing['V'] = $space[1];
                break;
            }
            case 1:
            {
                $border_spacing['H'] = $space[0];
                $border_spacing['V'] = $space[0];
                break;
            }
            default:
            {
                return ['H' => 0, 'V' => 0];
            }
        }
        if ($width == 0) {
            $width = $this->width - $this->leftMargin - $this->rightMargin;
        }
        $border_spacing['H'] = $this->fontSizeToUnits($border_spacing['H'], $width);
        $border_spacing['V'] = $this->fontSizeToUnits($border_spacing['V'], $width);
        return $border_spacing;
    }

    /**
     * Returns the letter-spacing value from CSS value
     * @param string $spacing letter-spacing value
     * @param float  $parent  font spacing (tracking) value of the parent element
     * @return float quantity to increases or decreases the space between characters in a text.
     */
    protected function getCSSFontSpacing($spacing, $parent = 0)
    {
        $val     = 0; // value to be returned
        $spacing = trim($spacing);
        switch ($spacing) {
            case 'normal':
            {
                $val = 0;
                break;
            }
            case 'inherit':
            {
                if ($parent == 'normal') {
                    $val = 0;
                }
                else {
                    $val = $parent;
                }
                break;
            }
            default:
            {
                $val = $this->fontSizeToUnits($spacing, 0);
            }
        }
        return $val;
    }

    /**
     * Returns the percentage of font stretching from CSS value
     * @param string $stretch stretch mode
     * @param float  $parent  stretch value of the parent element
     * @return float font stretching percentage
     */
    protected function getCSSFontStretching($stretch, $parent = 100)
    {
        $val     = 100; // value to be returned
        $stretch = trim($stretch);
        switch ($stretch) {
            case 'ultra-condensed':
            {
                $val = 40;
                break;
            }
            case 'extra-condensed':
            {
                $val = 55;
                break;
            }
            case 'condensed':
            {
                $val = 70;
                break;
            }
            case 'semi-condensed':
            {
                $val = 85;
                break;
            }
            case 'normal':
            {
                $val = 100;
                break;
            }
            case 'semi-expanded':
            {
                $val = 115;
                break;
            }
            case 'expanded':
            {
                $val = 130;
                break;
            }
            case 'extra-expanded':
            {
                $val = 145;
                break;
            }
            case 'ultra-expanded':
            {
                $val = 160;
                break;
            }
            case 'wider':
            {
                $val = ($parent + 10);
                break;
            }
            case 'narrower':
            {
                $val = ($parent - 10);
                break;
            }
            case 'inherit':
            {
                if ($parent == 'normal') {
                    $val = 100;
                }
                else {
                    $val = $parent;
                }
                break;
            }
            default:
            {
                $val = $this->fontSizeToUnits($stretch, 100, self::PERCENT);
            }
        }
        return $val;
    }


    /**
     * Set the color and font style for HTML links.
     * @param array  $color     RGB array of colors
     * @param string $fontstyle additional font styles to add
     * @public
     * @since 4.4.003 (2008-12-09)
     */
    public function setHtmlLinksStyle($color = array(0, 0, 255), $fontstyle = 'U')
    {
        $this->htmlLinkColorArray = $color;
        $this->htmlLinkFontStyle  = $fontstyle;
    }

    // variables for html parser

    /**
     * HTML PARSER: array to store current link and rendering styles.
     */
    protected array $HREF = [];

    /**
     * HTML PARSER: array of bool values, true in case of ordered list (OL), false otherwise.
     */
    protected $listordered = [];

    /**
     * HTML PARSER: array count list items on nested lists.
     */
    protected $listcount = [];

    /**
     * HTML PARSER: current list nesting level.
     */
    protected $listnum = 0;

    /**
     * HTML PARSER: indent amount for lists.
     */
    protected $listindent = 0;

    /**
     * HTML PARSER: current list indententation level.
     */
    protected $listindentlevel = 0;

    /**
     * HTML PARSER: custom indent amount for lists. Negative value means disabled.
     */
    protected $customlistindent = -1;

    /**
     * Current form action (used during XHTML rendering).
     */
    protected $form_action = '';

    /**
     * Current form encryption type (used during XHTML rendering).
     */
    protected $form_enctype = 'application/x-www-form-urlencoded';

    /**
     * Add vertical spaces if needed.
     * @param string $hbz      Distance between current y and line bottom.
     * @param string $hb       The height of the break.
     * @param bool   $cell     if true add the default left padding to each new line (default false).
     * @param bool   $firsttag set to true when the tag is the first.
     * @param bool   $lasttag  set to true when the tag is the last.
     */
    protected function addHTMLVertSpace($hbz = 0, $hb = 0, $cell = false, $firsttag = false, $lasttag = false)
    {
        if ($firsttag) {
            $this->Ln(0, $cell);
            $this->htmlvspace = 0;
            return;
        }
        if ($lasttag) {
            $this->Ln($hbz, $cell);
            $this->htmlvspace = 0;
            return;
        }
        if ($hb < $this->htmlvspace) {
            $hd = 0;
        }
        else {
            $hd               = $hb - $this->htmlvspace;
            $this->htmlvspace = $hb;
        }
        $this->Ln(($hbz + $hd), $cell);
    }

    /**
     * Draw an HTML block border and fill
     * @param array $tag  array of tag properties.
     * @param int   $xmax end X coordinate for border.
     */
    protected function drawHTMLTagBorder($tag, $xmax)
    {
        if (!isset($tag['borderposition'])) {
            // nothing to draw
            return;
        }
        $prev_x                      = $this->x;
        $prev_y                      = $this->y;
        $prev_lasth                  = $this->lastPrintedCellHeight;
        $border                      = 0;
        $fill                        = false;
        $this->lastPrintedCellHeight = 0;
        if (isset($tag['border']) and !empty($tag['border'])) {
            // get border style
            $border = $tag['border'];
            if (!StaticMethods::empty_string($this->headerTemplate) and (!$this->inthead)) {
                // border for table header
                $border = StaticMethods::getBorderMode($border, $position = 'middle', $this->opencell);
            }
        }
        if (isset($tag['bgColor']) and ($tag['bgColor'] !== false)) {
            // get background color
            $oldBGColor = $this->bgColor;
            $this->fillColorArray($tag['bgColor']);
            $fill = true;
        }
        if (!$border and !$fill) {
            // nothing to draw
            return;
        }
        if (isset($tag['attribute']['cellspacing'])) {
            $clsp        = $this->fontSizeToUnits($tag['attribute']['cellspacing']);
            $cellspacing = ['H' => $clsp, 'V' => $clsp];
        }
        elseif (isset($tag['border-spacing'])) {
            $cellspacing = $tag['border-spacing'];
        }
        else {
            $cellspacing = ['H' => 0, 'V' => 0];
        }
        if (($tag['value'] != 'table') and (is_array($border)) and (!empty($border))) {
            // draw the border externally respect the sqare edge.
            $border['mode'] = 'ext';
        }
        if ($xmax <= $tag['borderposition']['x']) {
            $xmax = $tag['borderposition']['xmax'];
        }
        $w = ($xmax - $tag['borderposition']['x']);
        if ($w <= 0) {
            return;
        }
        $w           += $cellspacing['H'];
        $startpage   = $tag['borderposition']['page'];
        $startcolumn = $tag['borderposition']['column'];
        $x           = $tag['borderposition']['x'];
        $y           = $tag['borderposition']['y'];
        $endpage     = $this->page;
        $starty      = $tag['borderposition']['y'] - $cellspacing['V'];
        $currentY    = $this->y;
        $this->x     = $x;
        // get latest column
        $endcolumn = $this->current_column;
        if ($this->num_columns == 0) {
            $this->num_columns = 1;
        }
        // get border modes
        $border_start  = StaticMethods::getBorderMode($border, $position = 'start', $this->opencell);
        $border_end    = StaticMethods::getBorderMode($border, $position = 'end', $this->opencell);
        $border_middle = StaticMethods::getBorderMode($border, $position = 'middle', $this->opencell);
        // temporary disable page regions
        $temp_page_regions  = $this->page_regions;
        $this->page_regions = [];
        // design borders around HTML cells.
        for ($page = $startpage; $page <= $endpage; ++$page) { // for each page
            $ccode = '';
            $this->setPage($page);
            if ($this->num_columns < 2) {
                // single-column mode
                $this->x = $x;
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
                    $this->selectColumn($column);
                    if ($startcolumn == $endcolumn) { // single column
                        $cborder = $border;
                        $h       = ($currentY - $y) + $cellspacing['V'];
                        $this->y = $starty;
                    }
                    elseif ($column == $startcolumn) { // first column
                        $cborder = $border_start;
                        $this->y = $starty;
                        $h       = $this->height - $this->y - $this->bottomMargin;
                    }
                    elseif ($column == $endcolumn) { // end column
                        $cborder = $border_end;
                        $h       = $currentY - $this->y;
                    }
                    else { // middle column
                        $cborder = $border_middle;
                        $h       = $this->height - $this->y - $this->bottomMargin;
                    }
                    $ccode .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true) . "\n";
                } // end for each column
            }
            elseif ($page == $startpage) { // first page
                for ($column = $startcolumn; $column < $this->num_columns; ++$column) { // for each column
                    $this->selectColumn($column);
                    if ($column == $startcolumn) { // first column
                        $cborder = $border_start;
                        $this->y = $starty;
                        $h       = $this->height - $this->y - $this->bottomMargin;
                    }
                    else { // middle column
                        $cborder = $border_middle;
                        $h       = $this->height - $this->y - $this->bottomMargin;
                    }
                    $ccode .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true) . "\n";
                } // end for each column
            }
            elseif ($page == $endpage) { // last page
                for ($column = 0; $column <= $endcolumn; ++$column) { // for each column
                    $this->selectColumn($column);
                    if ($column == $endcolumn) {
                        // end column
                        $cborder = $border_end;
                        $h       = $currentY - $this->y;
                    }
                    else {
                        // middle column
                        $cborder = $border_middle;
                        $h       = $this->height - $this->y - $this->bottomMargin;
                    }
                    $ccode .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true) . "\n";
                } // end for each column
            }
            else { // middle page
                for ($column = 0; $column < $this->num_columns; ++$column) { // for each column
                    $this->selectColumn($column);
                    $cborder = $border_middle;
                    $h       = $this->height - $this->y - $this->bottomMargin;
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
                        $pagemarkkey = key($this->transpositions[$this->page]);
                        $pagemark    = $this->transpositions[$this->page][$pagemarkkey];
                    }
                    elseif ($this->inFooter) {
                        $pagemark = $this->footerpos[$this->page];
                    }
                    else {
                        $pagemark = $this->intmrk[$this->page];
                    }
                    $pagebuff = $this->pageBuffer($this->page);
                    $pstart   = substr($pagebuff, 0, $pagemark);
                    $pend     = substr($pagebuff, $pagemark);
                    $this->pageBuffer($this->page, $pstart . $ccode . $pend);
                    $this->bordermrk[$this->page] += $offsetlen;
                    $this->cntmrk[$this->page]    += $offsetlen;
                }
            }
        } // end for each page
        // restore page regions
        $this->page_regions = $temp_page_regions;
        if (isset($oldBGColor)) {
            // restore background color
            $this->fillColorArray($oldBGColor);
        }
        // restore pointer position
        $this->x                     = $prev_x;
        $this->y                     = $prev_y;
        $this->lastPrintedCellHeight = $prev_lasth;
    }
}