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
use Organizer\Layouts\PDF\TableLayout;
use Organizer\Views\PDF\ListView;

/**
 * Class loads persistent information about a course into the display context.
 */
class Attendance extends TableLayout
{
	protected $headers;

	protected $widths = [
		'index'        => 10,
		'name'         => 55,
		'organization' => 25,
		'program'      => 85,
		'room'         => 15
	];

	/**
	 * @inheritDoc
	 */
	public function __construct(ListView $view)
	{
		parent::__construct($view);
		$view->margins(10, 30, -1, 0, 8);
		$view->showPrintOverhead(true);
		$view->setOverhead();

		$this->headers = [
			'index'        => '#',
			'name'         => 'Name',
			'organization' => Helpers\Languages::_('ORGANIZER_ORGANIZATION'),
			'program'      => Helpers\Languages::_('ORGANIZER_PROGRAM'),
			'room'         => Helpers\Languages::_('ORGANIZER_ROOM')
		];

		// Adjust for more information
		if ($view->fee)
		{
			$this->headers['paid'] = Helpers\Languages::_('ORGANIZER_PAID');
			$this->widths['name']  = 42;
			$this->widths['paid']  = 14;
			$this->widths['room']  = 14;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function fill(array $data)
	{
		$itemNo = 1;
		$view   = $this->view;
		$this->addTablePage();

		foreach ($data as $participant)
		{
			// Get the starting coordinates for later use with borders
			$startX = $view->GetX();
			$startY = $view->GetY();

			$maxLength = 0;

			foreach (array_keys($this->headers) as $columnName)
			{
				switch ($columnName)
				{
					case 'index':
						$value = $itemNo;
						break;
					case 'name':
						$value = empty($participant->forename) ?
							$participant->surname : "$participant->surname,  $participant->forename";
						break;
					case 'organization':
						// The participant may not be associated with a program => cast to int to prevent null
						$value = Helpers\Programs::getOrganization((int) $participant->programID, true);
						break;
					case 'program':
						// The participant may not be associated with a program => cast to int to prevent null
						$value = Helpers\Programs::getName((int) $participant->programID);
						break;
					default:
						$value = '';
						break;
				}

				$length = $view->renderMultiCell($this->widths[$columnName], 5, $value);
				if ($length > $maxLength)
				{
					$maxLength = $length;
				}
			}

			// Reset for borders
			$view->changePosition($startX, $startY);

			foreach ($this->widths as $index => $width)
			{
				$border = $index === 'index' ? ['BLR' => $view->border] : ['BR' => $view->border];
				$view->renderMultiCell($width, $maxLength * 5, '', $view::LEFT, $border);
			}

			$this->addLine();

			$itemNo++;
		}
	}

	/**
	 * Generates the title and sets name related properties.
	 */
	public function setTitle()
	{
		$view         = $this->view;
		$documentName = "$view->course - $view->campus - $view->startDate - " . Helpers\Languages::_('ORGANIZER_PARTICIPANTS');
		$view->setNames($documentName);
	}
}
