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

use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class loads persistent information about a course into the display context.
 */
class Attendance extends TableView
{
	use CourseDocumentation;

	protected $headers;

	protected $widths = [
		'index'        => 10,
		'name'         => 55,
		'organization' => 25,
		'program'      => 85,
		'room'         => 15
	];

	/**
	 * Constructor
	 */
	public function __construct()
	{
		if (!$this->courseID = Helpers\Input::getID())
		{
			Helpers\OrganizerHelper::error(400);
		}

		if (!Helpers\Can::manage('course', $this->courseID))
		{
			Helpers\OrganizerHelper::error(403);
		}

		parent::__construct();

		$this->setCourseProperties();

		$documentName = "$this->course - $this->campus - $this->startDate - " . Helpers\Languages::_('ORGANIZER_PARTICIPANTS');
		$this->setNames($documentName);
		$this->margins(10, 30, -1, 0, 8, 10);
		$this->showPrintOverhead(true);
		$this->setHeader();

		$this->headers = [
			'index'        => '#',
			'name'         => 'Name',
			'organization' => Helpers\Languages::_('ORGANIZER_ORGANIZATION'),
			'program'      => Helpers\Languages::_('ORGANIZER_PROGRAM'),
			'room'         => Helpers\Languages::_('ORGANIZER_ROOM')
		];

		// Adjust for more information
		if ($this->fee)
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
	public function display()
	{
		$this->addTablePage();

		$itemNo         = 1;
		$participantIDs = Helpers\Courses::getParticipantIDs($this->courseID);

		foreach ($participantIDs as $participantID)
		{
			$participant = new  Tables\Participants();
			if (!$participant->load($participantID))
			{
				continue;
			}

			// Get the starting coordinates for later use with borders
			$startX = $this->GetX();
			$startY = $this->GetY();

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

				$length = $this->renderMultiCell($this->widths[$columnName], 5, $value);
				if ($length > $maxLength)
				{
					$maxLength = $length;
				}
			}

			// Reset for borders
			$this->changePosition($startX, $startY);

			foreach ($this->widths as $index => $width)
			{
				$border = $index === 'index' ? ['BLR' => $this->border] : ['BR' => $this->border];
				$this->renderMultiCell($width, $maxLength * 5, '', self::LEFT, $border);
			}

			$this->addLine();

			$itemNo++;
		}

		parent::display();
	}
}
