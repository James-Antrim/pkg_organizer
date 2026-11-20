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

use THM\Organizer\Views\PDF\Library\Constants;

trait TOCs
{
#region //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////DONE

    use Constants;

#endregion
#region //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED

#endregion
#region ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * bool flag true when we are on TOC (Table Of Content) page.
     */
    protected $tocpage = false;

    /**
     * Output a Table of Content Index (TOC).
     * This method must be called after all Bookmarks were set.
     * Before calling this method you have to open the page using the addTOCPage() method.
     * After calling this method you have to call endTOCPage() to close the TOC page.
     * You can override this method to achieve different styles.
     * @param int|null $page        page number where this TOC should be inserted (leave empty for current page).
     * @param string   $numbersfont set the font for page numbers (please use monospaced font for better alignment).
     * @param string   $filler      string used to fill the space between text and page number.
     * @param string   $toc_name    name to use for TOC bookmark.
     * @param string   $style       Font style for title: B = Bold, I = Italic, BI = Bold + Italic.
     * @param array    $color       RGB color array for bookmark title (values from 0 to 255).
     * @author Nicola Asuni
     * @see    addTOCPage(), endTOCPage(), addHTMLTOC()
     */
    public function addTOC($page = null, $numbersfont = '', $filler = '.', $toc_name = 'TOC', $style = '', $color = self::BLACK)
    {
        $fontsize        = $this->fontSizePT;
        $fontfamily      = $this->fontFamily;
        $fontstyle       = $this->fontStyle;
        $w               = $this->width - $this->leftMargin - $this->rightMargin;
        $spacer          = $this->GetStringWidth(chr(32)) * 4;
        $lmargin         = $this->leftMargin;
        $rmargin         = $this->rightMargin;
        $x_start         = $this->x();
        $page_first      = $this->page;
        $current_page    = $this->page;
        $page_fill_start = false;
        $page_fill_end   = false;
        $currentColumn   = $this->currentColumn;
        if (StaticMethods::empty_string($numbersfont)) {
            $numbersfont = $this->default_monospaced_font;
        }
        if (StaticMethods::empty_string($filler)) {
            $filler = ' ';
        }
        if (StaticMethods::empty_string($page)) {
            $gap = ' ';
        }
        else {
            $gap = '';
            if ($page < 1) {
                $page = 1;
            }
        }
        $this->font($numbersfont, $fontstyle, $fontsize);
        $numwidth = $this->GetStringWidth('00000');
        $maxpage  = 0; //used for pages on attached documents
        foreach ($this->outlines as $key => $outline) {
            // check for extra pages (used for attachments)
            if (($this->page > $page_first) and ($outline['p'] >= $this->pageCount)) {
                $outline['p'] += ($this->page - $page_first);
            }
            $aligntext = 'L';
            $alignnum  = 'R';
            if ($outline['l'] == 0) {
                $this->font($fontfamily, $outline['s'] . 'B', $fontsize);
            }
            else {
                $this->font($fontfamily, $outline['s'], $fontsize - $outline['l']);
            }
            $this->textColorArray($outline['c']);
            // check for page break
            $this->checkPageBreak(2 * $this->getCellHeight($this->fontSize));
            // set margins and X position
            if (($this->page == $current_page) and ($this->currentColumn == $currentColumn)) {
                $this->leftMargin  = $lmargin;
                $this->rightMargin = $rmargin;
            }
            else {
                if ($this->currentColumn != $currentColumn) {
                    $x_start = $this->columns[$this->currentColumn]['x'];
                }
                $lmargin       = $this->leftMargin;
                $rmargin       = $this->rightMargin;
                $current_page  = $this->page;
                $currentColumn = $this->currentColumn;
            }
            $this->x($x_start);
            $indent           = ($spacer * $outline['l']);
            $this->x          += $indent;
            $this->leftMargin = $this->x;
            $link             = $this->AddLink();
            $this->setLink($link, $outline['y'], $outline['p']);
            // write the text
            $txt = $outline['t'] . ' ';
            $this->Write(0, $txt, $link, false, $aligntext, false, 0, false, false, 0, $numwidth, '');
            $tw = $this->width - $this->rightMargin - $this->x;
            $this->font($numbersfont, $fontstyle, $fontsize);
            if (StaticMethods::empty_string($page)) {
                $pagenum = $outline['p'];
            }
            else {
                // placemark to be replaced with the correct number
                $pagenum = '{#' . ($outline['p']) . '}';
                $maxpage = max($maxpage, $outline['p']);
            }
            $fw      = ($tw - $this->GetStringWidth($pagenum . $filler));
            $wfiller = $this->GetStringWidth($filler);
            if ($wfiller > 0) {
                $numfills = floor($fw / $wfiller);
            }
            else {
                $numfills = 0;
            }
            if ($numfills > 0) {
                $rowfill = str_repeat($filler, $numfills);
            }
            else {
                $rowfill = '';
            }
            $pagenum = $rowfill . $gap . $pagenum;
            // write the number
            $this->Cell($tw, 0, $pagenum, 0, 1, $alignnum, 0, $link, 0);
        }
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
                    if (($n >= $page) and ($n <= $this->pageCount)) {
                        $np = $n + $numpages;
                    }
                    else {
                        $np = $n;
                    }
                    $na = StaticMethods::formatTOCPageNumber(($this->starting_page_number + $np - 1));
                    $nu = $this->utf8ToUTF16BE($na);
                    // replace aliases with numbers
                    foreach ($pnalias['u'] as $u) {
                        $sfill    = str_repeat($filler, max(0, (strlen($u) - strlen($nu . ' '))));
                        $nr       = $this->utf8ToUTF16BE($sfill . ' ') . $nu;
                        $temppage = str_replace($u, $nr, $temppage);
                    }
                    foreach ($pnalias['a'] as $a) {
                        $sfill    = str_repeat($filler, max(0, (strlen($a) - strlen($na . ' '))));
                        $nr       = $sfill . ' ' . $na;
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
     * Adds a new TOC (Table Of Content) page to the document.
     * @param string $orientation page orientation.
     * @param mixed  $format      The format used for pages. It can be either: one of the string values specified at getPageSizeFromFormat() or an array of parameters specified at setPageFormat().
     * @param bool   $keepmargins if true overwrites the default page margins with the current margins
     * @see   AddPage(), startPage(), endPage(), endTOCPage()
     */
    public function addTOCPage($orientation = '', $format = '', $keepmargins = false)
    {
        $this->addPage($orientation, $format, $keepmargins, true);
    }

    /**
     * Terminate the current TOC (Table Of Content) page
     * @see   AddPage(), startPage(), endPage(), addTOCPage()
     */
    public function endTOCPage()
    {
        $this->closePage(true);
    }

#endregion

}