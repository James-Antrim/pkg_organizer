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
use Organizer\Tables\Participants;

/**
 * Class loads persistent information about a course into the display context.
 */
class Badges extends BaseView
{
	use CourseDocumentation;

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

		parent::__construct(self::LANDSCAPE);

		$this->setCourseProperties();

		$documentName = "$this->course - $this->campus - $this->startDate - " . Helpers\Languages::_('ORGANIZER_BADGES');
		$this->setNames($documentName);
		$this->margins();
		$this->showPrintOverhead(false);
	}

	/**
	 * Adds the reverse to a badge sheet
	 *
	 * @return void modifies the pdf document
	 */
	private function addSheetBack()
	{
		$this->AddPage('L', '', false, false);

		$xOffset = 14;

		for ($boxNo = 0; $boxNo < 3; $boxNo++)
		{
			for ($level = 0; $level < 2; $level++)
			{
				// The next item should be 82 to the right
				$yOffset = $level * 82;

				$this->addBadgeBack($xOffset, $yOffset);
			}

			// The next row should be 92 lower
			$xOffset += 92;
		}
	}

	/**
	 * Method to generate output.
	 *
	 * @return void
	 */
	public function display()
	{
		$participantIDs = Helpers\Courses::getParticipantIDs($this->courseID);

		$sheetCount       = intval(count($participantIDs) / 6);
		$badgeCount       = $sheetCount * 6;
		$emptyParticipant = new class {
			public $address = '';
			public $city = '';
			public $forename = '';
			public $id = '';
			public $surname = '';
			public $zipCode = '';
		};
		$xOffset          = 10;
		$yOffset          = 0;

		$this->AddPage();

		for ($index = 0; $index < $badgeCount; $index++)
		{
			$participant = new Participants();
			if (!$participant->load($participantIDs[$index]))
			{
				$participant = $emptyParticipant;
			}
			$badgeNumber = $index + 1;
			$this->addBadge($participant, $xOffset, $yOffset);

			// End of the sheet
			if ($badgeNumber % 6 == 0)
			{
				$xOffset = 10;
				$yOffset = 0;
				$this->addSheetBack();

				if ($badgeNumber < $badgeCount)
				{
					$this->AddPage(self::LANDSCAPE);
				}
			} // End of the first row on a sheet
			elseif ($badgeNumber % 3 == 0)
			{
				$xOffset = 10;
				$yOffset = 82;
			} // Next item
			else
			{
				$xOffset += 92;
			}
		}

		parent::display();
	}
}
