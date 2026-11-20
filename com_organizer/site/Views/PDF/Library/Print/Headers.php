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
     * String to print on page header after title.
     */

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
    public function headerConfig(string $logo = '', float $logoWidth = 0, string $title = '', string $subtitle = '', array $textColor = self::BLACK, array $bgColor = self::WHITE): array
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
            'logo'       => $this->headerLogo,
            'logoWidth'  => $this->headerLogoWidth,
            'title'      => $this->headerTitle,
            'subtitle'   => $this->headerSubtitle,
            'text_color' => $this->headerTextColor,
            'line_color' => $this->headerLineColor,
        ];
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

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////TODO

    /**
     * This method is used to render the page header.
     */
    protected function header(): void
    {
        if (!$this->printHeader or $this->state !== self::READY) {
            return;
        }

        $this->inHeader = true;
        $this->graphicsConfiguration($this->graphicsConfiguration);

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

        if (!$this->headerXObjectID) {
            // start a new XObject Template
            $this->headerXObjectID = $this->startTemplate($this->width, $this->topMargin);

            $font          = $this->headerFont();
            $configuration = $this->headerConfig();

            $this->y = $this->headerMargin;
            $this->x = $this->originalLeftMargin;

            if (empty($configuration['logo'])) {
                $imageY = $this->y;
            }
            else {
                $imageType = $this->imageType(self::IMAGE_PATH . $configuration['logo']);

                if ($imageType === 'eps' or $imageType === 'ai') {
                    $this->ImageEps(self::IMAGE_PATH . $configuration['logo'], null, null, $configuration['logoWidth']);
                }
                elseif ($imageType == 'svg') {
                    $this->ImageSVG(self::IMAGE_PATH . $configuration['logo'], null, null, $configuration['logoWidth']);
                }
                else {
                    $this->image(self::IMAGE_PATH . $configuration['logo'], null, null, $configuration['logoWidth']);
                }

                $imageY = $this->imageRBY;
            }

            $cellHeight = $this->getCellHeight($font[2] / $this->factor);

            // Set starting margin for text data cell
            $header_x = $this->originalLeftMargin + ($configuration['logoWidth'] * 1.1);
            $cw       = $this->width - $this->originalLeftMargin - $this->originalRightMargin - ($configuration['logoWidth'] * 1.1);
            $this->textColorArray($this->headerTextColor);

            // Title
            $this->font($font[0], 'B', $font[2] + 1);
            $this->x($header_x);
            $this->Cell($cw, $cellHeight, $configuration['title'], 0, 1, '', 0);

            // Subtitle
            $this->font($font[0], $font[1], $font[2]);
            $this->x($header_x);
            $this->MultiCell($cw, $cellHeight, $configuration['subtitle'], 0, '', 0, 1, '', '');

            // Print an ending header line
            $this->lineStyle(['width' => 0.85 / $this->factor, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $configuration['line_color']]);
            $this->y((2.835 / $this->factor) + max($imageY, $this->y));
            $this->x($this->originalLeftMargin);
            $this->Cell(($this->width - $this->originalLeftMargin - $this->originalRightMargin), 0, '', 'T', 0, 'C');
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

        $this->xy($this->originalLeftMargin, $this->topMargin);
        $this->appendRestoreGraphicsState();
        $this->lastPrintedCellHeight = $lastHeight;
        $this->headerTemplate        = $template;
        $this->headerTemplateMargins = $margins;
        $this->newLine               = $newLine;
        $this->inHeader              = false;
    }
}