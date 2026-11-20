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

trait Headers
{
    /** @var int[] RGB */
    protected array $headerLineColor = self::BLACK;
    protected string $headerLogo = '';
    protected float $headerLogoWidth = 30;
    protected float $headerMargin = 5;
    protected string $headerSubtitle = '';
    protected string $headerTemplate = '';
    protected array $headerTemplateMargins = [];
    /** @var int[] RGB */
    protected array $headerTextColor = self::BLACK;
    protected string $headerTitle = '';
    protected string $headerXObjectID = '';

    /**
     * If true reset the Header Xobject template at each page
     */
    protected bool $headerXObjectAutoreset = false;
    protected bool $inHeader = false;
    protected bool $printHeader = true;

    /**
     * Provides standard context handling before and after header rendering.
     * It is automatically called by AddPage() and could be overwritten in your own inherited class.
     */
    protected function header(): void
    {
        if (!$this->printHeader or $this->state !== self::READY) {
            return;
        }

        $this->inHeader = true;
        $this->graphicsSettings($this->graphicsSettings);

        $lastHeight = $this->lastPrintedCellHeight;
        $margins    = $this->headerTemplateMargins;
        $newLine    = $this->newLine;
        $template   = $this->headerTemplate;

        $this->appendSaveGraphicsState();

        $this->rightMargin = $this->originalRightMargin;
        $this->leftMargin  = $this->originalLeftMargin;

        $this->cellPadding(0);

        $this->xy($this->originalLeftMargin, $this->headerMargin);
        $this->font($this->headerFont[0], $this->headerFont[1], $this->headerFont[2]);

        $this->renderHeader();

        $this->xy($this->originalLeftMargin, $this->topMargin);
        $this->appendRestoreGraphicsState();
        $this->lastPrintedCellHeight = $lastHeight;
        $this->headerTemplate        = $template;
        $this->headerTemplateMargins = $margins;
        $this->newLine               = $newLine;
        $this->inHeader              = false;
    }

    /**
     * Set header margin: minimum distance between header and top page margin)
     * @param float $margin distance in user units
     * @return float
     */
    public function headerMargin(float $margin): float
    {
        if ($margin) {
            $this->headerMargin = $margin;
        }

        return $this->headerMargin;
    }

    /**
     * Set header data.
     * @param string $logo      header image logo
     * @param float  $logoWidth header image logo width in mm
     * @param string $title     string to print as title on document header
     * @param string $subtitle  string to print on document header
     * @param int[]  $textColor RGB array color for text.
     * @param int[]  $bgColor   RGB array color for line.
     * @return array
     */
    public function headerSettings(string $logo = '', float $logoWidth = 0, string $title = '', string $subtitle = '', array $textColor = self::BLACK, array $bgColor = self::WHITE): array
    {
        if ($logo or $title or $subtitle) {
            $this->headerLogo      = $logo;
            $this->headerLogoWidth = $logoWidth;
            $this->headerTitle     = $title;
            $this->headerSubtitle  = $subtitle;
            $this->headerTextColor = $textColor;
            $this->headerLineColor = $bgColor;
        }

        return [
            'lineColor' => $this->headerLineColor,
            'logo'      => $this->headerLogo,
            'logoWidth' => $this->headerLogoWidth,
            'subtitle'  => $this->headerSubtitle,
            'title'     => $this->headerTitle,
            'textColor' => $this->headerTextColor,
        ];
    }

    /**
     * Sets a flag to automatically reset the xObject template used by the header() method at each page.
     * @param bool $autoreset
     */
    public function headerTemplateAutoreset(bool $autoreset = true): void
    {
        $this->headerXObjectAutoreset = $autoreset;
    }

    /**
     * Flag whether to print the page header
     * @param bool $print
     */
    public function printHeader(bool $print = true): void
    {
        $this->printHeader = $print;
    }

    /** This method is used to render the page header. */
    protected function renderHeader(): void
    {
        if (!$this->headerXObjectID) {
            // start a new XObject Template
            $this->headerXObjectID = $this->startTemplate($this->width, $this->topMargin);

            $font     = $this->headerFont();
            $settings = $this->headerSettings();

            $this->y = $this->headerMargin;
            $this->x = $this->originalLeftMargin;

            if (empty($settings['logo'])) {
                $imageY = $this->y;
            }
            else {
                $imageType = $this->imageType(self::IMAGE_PATH . $settings['logo']);

                if ($imageType === 'svg') {
                    $this->imageSVG(self::IMAGE_PATH . $settings['logo'], null, null, $settings['logoWidth']);
                }
                else {
                    $this->image(self::IMAGE_PATH . $settings['logo'], null, null, $settings['logoWidth']);
                }

                $imageY = $this->imageRBY;
            }

            $cellHeight = $this->cellHeight($font[2] / $this->factor);

            // Set starting margin for text data cell
            $headerX   = $this->originalLeftMargin + ($settings['logoWidth'] * 1.1);
            $cellWidth = $this->width - $this->originalLeftMargin - $this->originalRightMargin - ($settings['logoWidth'] * 1.1);
            $this->textColorArray($this->headerTextColor);

            // Title
            $this->font($font[0], self::BOLD, $font[2] + 1);
            $this->x($headerX);
            $this->cell($cellWidth, $cellHeight, $settings['title'], 0, 1, '', 0);

            // Subtitle
            $this->font($font[0], $font[1], $font[2]);
            $this->x($headerX);
            $this->multiCell($cellWidth, $cellHeight, $settings['subtitle'], 0, '', 0, 1, '', '');

            // Print an ending header line
            $this->lineStyle(['width' => 0.85 / $this->factor, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $settings['lineColor']]);
            $this->y((2.835 / $this->factor) + max($imageY, $this->y));
            $this->x($this->originalLeftMargin);
            $this->cell(($this->width - $this->originalLeftMargin - $this->originalRightMargin), 0, '', 'T', 0, 'C');
            $this->endTemplate();
        }

        // Adjust margins for booklet mode
        $dx = 0;
        if (!$this->headerXObjectAutoreset and $this->booklet and $this->page % 2 === 0) {
            $dx = ($this->originalLeftMargin - $this->originalRightMargin);
        }

        $this->printTemplate($this->headerXObjectID, $dx, 0);

        if ($this->headerXObjectAutoreset) {
            $this->headerXObjectID = '';
        }
    }
}