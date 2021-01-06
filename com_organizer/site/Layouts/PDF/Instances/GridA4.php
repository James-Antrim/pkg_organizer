<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Layouts\PDF\Instances;

use Organizer\Helpers\Dates;
use Organizer\Helpers\Languages;

/**
 * Class generates a PDF file in A4 format.
 */
class GridA4 extends GridLayout
{
	/**
	 * @inheritDoc
	 */
	protected function renderGrid(string $startDate, string $endDate)
	{
		foreach ($this->grid as $block)
		{
			$label = $this->getLabel($block);
			$cells = $this->getCells($startDate, $endDate, $block);

			if ($cells)
			{
				$this->renderRow($label, $cells, $startDate, $endDate);
				continue;
			}

			$this->renderEmptyRow($label, $startDate, $endDate);
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function renderHeaders(string $startDate, string $endDate)
	{
		$view = $this->view;
		$view->SetFont('helvetica', '', 10);
		$view->SetLineStyle(['width' => 0.5, 'dash' => 0, 'color' => [0, 0, 0]]);
		$view->renderMultiCell(self::TIME_WIDTH, 0, Languages::_('ORGANIZER_TIME'), $view::CENTER, $view::HORIZONTAL);

		for ($currentDT = strtotime($startDate); $currentDT <= strtotime($endDate);)
		{
			$view->renderMultiCell(
				self::DATA_WIDTH + 1,
				0,
				Dates::formatDate(date('Y-m-d', $currentDT)),
				$view::CENTER,
				$view::HORIZONTAL
			);

			$currentDT = strtotime("+1 day", $currentDT);
		}

		$view->Ln();
		$view->SetFont('helvetica', '', 8);
	}
}
