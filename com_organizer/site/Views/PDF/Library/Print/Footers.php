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
    protected bool $inFooter = false;
    protected string $footerBarcode = '';
    protected array $footerLengths = [];
    protected array $footerLineColor = self::WHITE;
    /** Minimum distance between footer and page bottom margin. */
    protected float $footerMargin = 0;
    protected array $footerTextColor = self::BLACK;
    protected array $footerPositions = [];
    protected bool $printFooter = true;

    /** Provides standard context handling before and after footer rendering */
    protected function footer(): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        $this->inFooter = true;
        // Create a copy of the current graphic settings to restore to later
        $settings = $this->graphicsSettings();
        // mark this point
        $this->footerPositions[$this->page] = $this->pageLengths[$this->page];
        $this->append("\n");

        if ($this->printFooter) {
            $this->graphicsSettings($this->graphicsSettings);
            $this->currentColumn   = 0;
            $this->columnCount     = 1;
            $headerTemplate        = $this->headerTemplate;
            $headerTemplateMargins = $this->headerTemplateMargins;
            $lastHeight            = $this->lastPrintedCellHeight;
            $this->appendSaveGraphicsState();
            $this->rightMargin = $this->originalRightMargin;
            $this->leftMargin  = $this->originalLeftMargin;
            $this->cellPadding(0);
            //set current position
            $footerY = $this->height - $this->footerMargin;
            $this->xy($this->originalLeftMargin, $footerY);
            $this->font($this->footerFont[0], $this->footerFont[1], $this->footerFont[2]);
            $this->renderFooter();
            //restore position
            $this->xy($this->originalLeftMargin, $this->topMargin);
            $this->appendRestoreGraphicsState();
            $this->lastPrintedCellHeight = $lastHeight;
            $this->headerTemplate        = $headerTemplate;
            $this->headerTemplateMargins = $headerTemplateMargins;
        }

        // Restore graphic settings
        $this->graphicsSettings($settings);
        $this->currentColumn = $settings['currentColumn'];
        $this->columnCount   = $settings['columnCount'];

        $this->footerLengths[$this->page] = $this->pageLengths[$this->page] - $this->footerPositions[$this->page] + 1;

        $this->inFooter = false;
    }

    /**
     * Getter / setter for footer barcode.
     * @param string $code barcode
     */
    public function footerBarcode(string $code = ''): string
    {
        $this->footerBarcode = $code ?: $this->footerBarcode;
        return $this->footerBarcode;
    }

    /**
     * Set footer data.
     * @param int[] $text RGB array color for text.
     * @param int[] $line RGB array color for line.
     */
    public function footerColors(array $text = self::BLACK, array $line = self::WHITE): void
    {
        $this->footerTextColor = $text;
        $this->footerLineColor = $line;
    }

    /**
     * Set footer margin.
     * (minimum distance between footer and bottom page margin)
     * @param float|null $margin distance in user units
     */
    public function footerMargin(float|null $margin = 10): float
    {
        if (is_float($margin)) {
            $this->footerMargin = $margin;
        }

        return $this->footerMargin;
    }

    /**
     * Flag whether to print the page footer
     * @param bool $print
     */
    public function printFooter(bool $print = true): void
    {
        $this->printFooter = $print;
    }

    /**
     * This method is used to render the page footer.
     * It is automatically called by AddPage() and could be overwritten in your own inherited class.
     */
    public function renderFooter(): void
    {
        $currentY = $this->y;
        $this->textColorArray($this->footerTextColor);
        //set style for cell border
        $lineWidth = 0.85 / $this->factor;
        $this->lineStyle(['width' => $lineWidth, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $this->footerLineColor]);

        //Print document barcode
        if ($barcode = $this->footerBarcode()) {
            $this->lineBreak($lineWidth);
            $style = [
                'align'       => 'L',
                'bgColor'     => false,
                'border'      => false,
                'fgColor'     => self::BLACK,
                'hAlign'      => '',
                'padding'     => 0,
                'position'    => 'L',
                'reduceWidth' => true,
                'stretch'     => false,
                'text'        => false
            ];
            $this->write1DBarcode($barcode, 'C128', '', $currentY + $lineWidth, '', ($this->footerMargin / 3) - $lineWidth, 0.3, $style);
        }

        $this->y($currentY);
        $this->x($this->originalLeftMargin);

        $pageNumberText = empty($this->pageGroups) ? self::PAGE_NUMBER_ALIAS . ' / ' . self::TOTAL_PAGES_ALIAS : self::PAGE_GROUP_NUMBER_ALIAS . ' / ' . self::PAGES_IN_GROUP_ALIAS;
        $this->cell(0, 0, $this->getAliasRightShift() . $pageNumberText, self::TOP, 0, self::RIGHT);
    }
}