<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Layouts\PDF;

use THM\Organizer\Views\PDF\ListView;
use TCPDF_FONTS;

abstract class ListLayout extends BaseLayout
{
    protected array $headers;

    protected array $widths = [];

    /** @inheritDoc */
    public function __construct(ListView $view)
    {
        parent::__construct($view);
        $view->showHeaderFooter(true);
    }

    /**
     * Formats the list item borders.
     *
     * @param   int  $startX     the horizontal start of the line
     * @param   int  $startY     the vertical start of the line
     * @param   int  $maxLength  the maximum number of rows of information to be presented on the iterated line
     *
     * @return void
     */
    protected function borders(int $startX, int $startY, int $maxLength): void
    {
        $view = $this->view;

        $view->reposition($startX, $startY);

        $columns = array_keys($this->widths);
        $initial = reset($columns);

        foreach ($this->widths as $index => $width) {
            $border = $index === $initial ? ['BLR' => $view->border] : ['BR' => $view->border];
            $view->renderMultiCell($width, $maxLength * 5, '', $view::LEFT, $border);
        }
    }

    /**
     * Adds a line, if the length exceeds page length a page is added.
     * @return void
     */
    protected function line(): void
    {
        $view = $this->view;
        $view->Ln();

        if ($view->GetY() > 275) {
            $this->page();
        }
    }

    /**
     * Adds a list page to the document.
     * @return void
     */
    protected function page(): void
    {
        $view = $this->view;
        $view->AddPage();

        // create the column headers for the page
        $view->SetFillColor(210);
        $view->resize(10);
        $columns = array_keys($this->headers);
        $initial = reset($columns);

        foreach ($columns as $column) {
            $border = $column === $initial ? 'BLRT' : 'BRT';
            $header = $this->headers[$column];

            if ($header >= '0' and $header < '256') {
                $font   = $view->getFontFamily();
                $header = (int) $header;
                $view->SetFont('zapfdingbats');
                $view->renderCell($this->widths[$column], 7, TCPDF_FONTS::unichr($header), $view::CENTER, $border, 1);
                $view->SetFont($font);
            }
            else {
                $view->renderCell($this->widths[$column], 7, $header, $view::CENTER, $border, 1);
            }
        }

        $view->Ln();

        // reset styles
        $view->SetFillColor(255);
        $view->resize(8);
    }
}