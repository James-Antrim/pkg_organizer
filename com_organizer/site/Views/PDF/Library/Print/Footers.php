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

trait Footers
{
#region //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////DONE

#endregion
#region //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED

#endregion
#region ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Minimum distance between footer and bottom page margin.
     * @var float
     */
    protected $footer_margin;

    /**
     * bool flag to print/hide page footer.
     */
    protected $print_footer = true;

    /**
     * Array used to store footer positions of each page.
     */
    protected $footerpos = [];


    /**
     * Returns footer margin in user units.
     * @return float
     */
    public function getFooterMargin()
    {
        return $this->footer_margin;
    }

    /**
     * Set footer data.
     * @param int[] $tc RGB array color for text.
     * @param int[] $lc RGB array color for line.
     */
    public function setFooterData($tc = self::BLACK, $lc = self::BLACK)
    {
        $this->footerTextColor = $tc;
        $this->footerLineColor = $lc;
    }

    /**
     * Set footer margin.
     * (minimum distance between footer and bottom page margin)
     * @param float $fm distance in user units
     */
    public function setFooterMargin($fm = 10)
    {
        $this->footer_margin = $fm;
    }

    /**
     * Set a flag to print page footer.
     * @param bool $val set to true to print the page footer (default), false otherwise.
     */
    public function setPrintFooter($val = true)
    {
        $this->print_footer = $val ? true : false;
    }

    /**
     * This method is used to render the page footer.
     * It is automatically called by AddPage() and could be overwritten in your own inherited class.
     */
    public function Footer()
    {
        $cur_y = $this->y;
        $this->textColorArray($this->footerTextColor);
        //set style for cell border
        $line_width = (0.85 / $this->factor);
        $this->lineStyle(['width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $this->footerLineColor]);
        //print document barcode
        $barcode = $this->getBarcode();
        if (!empty($barcode)) {
            $this->lineBreak($line_width);
            $barcode_width = round(($this->width - $this->originalLeftMargin - $this->originalRightMargin) / 3);
            $style         = [
                'align'        => 'L',
                'bgColor'      => false,
                'border'       => false,
                'cellfitalign' => '',
                'fgColor'      => self::BLACK,
                'fitwidth'     => true,
                'padding'      => 0,
                'position'     => 'L',
                'stretch'      => false,
                'text'         => false
            ];
            $this->write1DBarcode($barcode, 'C128', '', $cur_y + $line_width, '', (($this->footer_margin / 3) - $line_width), 0.3, $style, '');
        }
        $w_page = '';
        if (empty($this->pageGroups)) {
            $pagenumtxt = $w_page . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();
        }
        else {
            $pagenumtxt = $w_page . $this->getPageNumGroupAlias() . ' / ' . $this->getPageGroupAlias();
        }
        $this->y($cur_y);
        //Print page number
        $this->x($this->originalLeftMargin);
        $this->Cell(0, 0, $this->getAliasRightShift() . $pagenumtxt, 'T', 0, 'R');
    }

    /**
     * This method is used to render the page footer.
     */
    protected function setFooter()
    {
        if ($this->state !== self::READY) {
            return;
        }
        $this->inFooter = true;
        // save current graphic settings
        $gvars = $this->graphicsConfiguration();
        // mark this point
        $this->footerpos[$this->page] = $this->pageLengths[$this->page];
        $this->append("\n");
        if ($this->print_footer) {
            $this->graphicsConfiguration($this->graphicsConfiguration);
            $this->currentColumn = 0;
            $this->columnCount   = 1;
            $temp_thead          = $this->headerTemplate;
            $temp_theadMargins   = $this->headerTemplateMargins;
            $lasth               = $this->lastPrintedCellHeight;
            $this->appendSaveGraphicsState();
            $this->rightMargin = $this->originalRightMargin;
            $this->leftMargin  = $this->originalLeftMargin;
            $this->cellPadding(0);
            //set current position
            $footer_y = $this->height - $this->footer_margin;
            $this->xy($this->originalLeftMargin, $footer_y);
            $this->font($this->footerFont[0], $this->footerFont[1], $this->footerFont[2]);
            $this->Footer();
            //restore position
            $this->xy($this->originalLeftMargin, $this->topMargin);
            $this->appendRestoreGraphicsState();
            $this->lastPrintedCellHeight = $lasth;
            $this->headerTemplate        = $temp_thead;
            $this->headerTemplateMargins = $temp_theadMargins;
        }
        // restore graphic settings
        $this->graphicsConfiguration($gvars);
        $this->currentColumn = $gvars['currentColumn'];
        $this->columnCount   = $gvars['columnCount'];
        // calculate footer length
        $this->footerLengths[$this->page] = $this->pageLengths[$this->page] - $this->footerpos[$this->page] + 1;
        $this->inFooter                   = false;
    }

#endregion

}