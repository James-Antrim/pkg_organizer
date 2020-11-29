<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace Organizer\Views\PDF;


abstract class TableView extends BaseView
{
	protected $headers;

	protected $widths;

	/**
	 * Adds a new line, if the length exceeds page length a new page is added.
	 *
	 * @return void
	 */
	protected function addLine()
	{
		$this->Ln();

		if ($this->GetY() > 275)
		{
			$this->addTablePage();
		}
	}

	/**
	 * Adds a new page to the document and creates the column headers for the table
	 *
	 * @return void
	 */
	protected function addTablePage()
	{
		$this->AddPage();

		// create the column headers for the page
		$this->SetFillColor(210);
		$this->changeSize(10);
		$initial = true;
		foreach (array_keys($this->headers) as $column)
		{
			if ($initial)
			{
				$this->renderCell($this->widths[$column], 7, $this->headers[$column], self::CENTER, 'BLRT', 1);
				$initial = false;
				continue;
			}
			$this->renderCell($this->widths[$column], 7, $this->headers[$column], self::CENTER, 'BRT', 1);
		}
		$this->Ln();

		// reset styles
		$this->SetFillColor(255);
		$this->changeSize(8);
	}
}