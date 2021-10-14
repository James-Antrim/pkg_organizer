<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace Organizer\Layouts\PDF;

use Organizer\Views\PDF\ListView;

abstract class ListLayout extends BaseLayout
{
	protected $headers;

	/**
	 * @var ListView
	 */
	protected $view;

	protected $widths;

	/**
	 * @inheritDoc
	 */
	public function __construct(ListView $view)
	{
		parent::__construct($view);
		$view->showPrintOverhead(true);
	}

	/**
	 * Adds a new line, if the length exceeds page length a new page is added.
	 *
	 * @return void
	 */
	protected function addLine()
	{
		$view = $this->view;
		$view->Ln();

		if ($view->GetY() > 275)
		{
			$this->addListPage();
		}
	}

	/**
	 * Adds a new page to the document and creates the column headers for the table
	 *
	 * @return void
	 */
	protected function addListPage()
	{
		$view = $this->view;
		$view->AddPage();

		// create the column headers for the page
		$view->SetFillColor(210);
		$view->changeSize(10);
		$initial = true;
		foreach (array_keys($this->headers) as $column)
		{
			if ($initial)
			{
				$view->renderCell($this->widths[$column], 7, $this->headers[$column], $view::CENTER, 'BLRT', 1);
				$initial = false;
				continue;
			}

			$view->renderCell($this->widths[$column], 7, $this->headers[$column], $view::CENTER, 'BRT', 1);
		}
		$view->Ln();

		// reset styles
		$view->SetFillColor(255);
		$view->changeSize(8);
	}

	/**
	 * Formats the line with the set borders.
	 *
	 * @param   int  $startX     the horizontal start of the line
	 * @param   int  $startY     the vertical start of the line
	 * @param   int  $maxLength  the maximum number of rows of information to be presented on the iterated line
	 *
	 * @return void
	 */
	protected function addLineBorders(int $startX, int $startY, int $maxLength)
	{
		$view = $this->view;

		$view->changePosition($startX, $startY);

		foreach ($this->widths as $index => $width)
		{
			$border = $index === 'index' ? ['BLR' => $view->border] : ['BR' => $view->border];
			$view->renderMultiCell($width, $maxLength * 5, '', $view::LEFT, $border);
		}
	}
}