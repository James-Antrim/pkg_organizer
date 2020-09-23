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

use Exception;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class loads persistent information about a course into the display context.
 */
class Badge extends BaseView
{
	use CourseDocumentation;

	private $participantID;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		if (!$this->courseID = Helpers\Input::getID())
		{
			Helpers\OrganizerHelper::error(400);
		}

		if (!$this->participantID = Helpers\Users::getID())
		{
			Helpers\OrganizerHelper::error(401);
		}

		$courseParticipant = new Tables\CourseParticipants();
		$cpKeys            = ['courseID' => $this->courseID, 'participantID' => $this->participantID];
		if (!$courseParticipant->load($cpKeys))
		{
			Helpers\OrganizerHelper::error(403);
		}

		parent::__construct(self::LANDSCAPE);

		$this->setCourseProperties();

		$documentName = "$this->course - $this->campus - $this->startDate - " . Helpers\Languages::_('ORGANIZER_BADGE');
		$this->setNames($documentName);
		$this->margins();
		$this->showPrintOverhead(false);
	}

	/**
	 * Method to generate output.
	 *
	 * @return void
	 */
	public function display()
	{
		$participant = new Tables\Participants();
		$participant->load($this->participantID);

		$yOffset = 0;

		$this->AddPage();
		$xOffset = 10;
		$this->addBadge($participant, $xOffset, $yOffset);
		$xOffset += 92;
		$this->addBadgeBack($xOffset, $yOffset);

		$this->Output($this->filename, 'I');
		ob_flush();
	}
}
