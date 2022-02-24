<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Layouts\PDF\CourseParticipants;

use Organizer\Helpers;
use Organizer\Layouts\PDF\ListLayout;
use Organizer\Views\PDF\ListView;

class Participation extends ListLayout
{
	protected $widths = [
		'grouping'     => 130,
		'participants' => 60
	];

	/**
	 * @inheritDoc
	 */
	public function __construct(ListView $view)
	{
		parent::__construct($view);
		$view->margins(10, 30, -1, 0, 8);

		$groupingHeader = Helpers\Languages::_('ORGANIZER_ORGANIZATION') . ' / ';
		$groupingHeader .= Helpers\Languages::_('ORGANIZER_PROGRAM');

		$this->headers = [
			'grouping'     => $groupingHeader,
			'participants' => Helpers\Languages::_('ORGANIZER_PARTICIPANTS')
		];
	}

	/**
	 * @inheritdoc
	 */
	public function fill(array $data)
	{
		$view                 = $this->view;
		$groupedParticipation = Helpers\Courses::getGroupedParticipation($view->courseID);

		$this->addListPage();

		foreach ($groupedParticipation as $organization => $programs)
		{
			$maxLength = 0;
			$startX    = $view->GetX();
			$startY    = $view->GetY();

			$view->SetFillColor(225);
			foreach (array_keys($this->headers) as $columnName)
			{
				$value  = $columnName === 'grouping' ? $organization : $programs['participants'];
				$length = $view->renderMultiCell($this->widths[$columnName], 5, $value, $view::LEFT, $view::NONE, true);

				if ($length > $maxLength)
				{
					$maxLength = $length;
				}
			}
			$view->SetFillColor(255);

			$view->changePosition($startX, $startY);

			foreach ($this->widths as $oIndex => $width)
			{
				$border = $oIndex === 'grouping' ? ['BLRT' => $view->border] : ['BRT' => $view->border];
				$view->renderMultiCell($width, $maxLength * 5, '', $view::LEFT, $border);
			}

			$this->addLine();

			foreach ($programs as $key => $program)
			{
				if ($key === 'participants')
				{
					continue;
				}

				$maxLength = 0;
				$startX    = $view->GetX();
				$startY    = $view->GetY();

				foreach (array_keys($this->headers) as $columnName)
				{
					$value  = $columnName === 'grouping' ?
						" - {$program['program']} ({$program['degree']}, {$program['year']})" : $program['participants'];
					$length = $view->renderMultiCell($this->widths[$columnName], 5, $value);
					if ($length > $maxLength)
					{
						$maxLength = $length;
					}
				}

				$view->changePosition($startX, $startY);

				foreach ($this->widths as $iIndex => $width)
				{
					$border = $iIndex === 'grouping' ? ['BLR' => $view->border] : ['BR' => $view->border];
					$view->renderMultiCell($width, $maxLength * 5, '', $view::LEFT, $border);
				}

				$this->addLine();
			}
		}
	}

	/**
	 * Generates the title and sets name related properties.
	 */
	public function setTitle()
	{
		$view         = $this->view;
		$documentName = "$view->course - $view->campus - $view->startDate - " . Helpers\Languages::_('ORGANIZER_ATTENDANCE');
		$view->setNames($documentName);
	}
}
