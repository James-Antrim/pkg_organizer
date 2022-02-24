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
use Organizer\Layouts\PDF\BadgeLayout;

/**
 * Class loads persistent information about a course into the display context.
 */
class Badges extends BadgeLayout
{
	/**
	 * Adds the reverse to a badge sheet
	 *
	 * @return void modifies the pdf document
	 */
	private function addSheetBack()
	{
		$view = $this->view;
		$view->AddPage('L', '', false, false);

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
	 * @inheritdoc
	 */
	public function fill(array $data)
	{
		$view             = $this->view;
		$sheetCount       = intval(count($data) / 6);
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

		$view->AddPage();

		for ($index = 0; $index < $badgeCount; $index++)
		{
			$participant = empty($data[$index]) ? $emptyParticipant : $data[$index];
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
					$view->AddPage($view::LANDSCAPE);
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
	}

	/**
	 * Generates the title and sets name related properties.
	 */
	public function setTitle()
	{
		$view         = $this->view;
		$documentName = "$view->course - $view->campus - $view->startDate - " . Helpers\Languages::_('ORGANIZER_BADGES');
		$view->setNames($documentName);
	}
}
