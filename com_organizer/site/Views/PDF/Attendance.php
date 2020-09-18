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
class Attendance extends BaseView
{
	use CourseDocumentation;

	private $headers;

	private $widths = [
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
		elseif (!Helpers\Users::getID())
		{
			Helpers\OrganizerHelper::error(401);
		}
		elseif (!Helpers\Can::manage('course', $this->courseID))
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
	 * Adds a new page to the document and creates the column headers for the table
	 *
	 * @return void
	 */
	private function addAttendancePage()
	{
		$this->AddPage();

		// create the column headers for the page
		$this->SetFillColor(210);
		$this->changeSize(10);
		$initial = true;
		foreach (array_keys($this->headers) as $column)
		{
			$border = [];
			if ($initial)
			{
				$border['BLRT'] = $this->border;
				$this->renderCell($this->widths[$column], 7, $this->headers[$column], self::CENTER, 'BLRT', 1);
				$initial = false;
				continue;
			}
			$border['BRT'] = $this->border;
			$this->renderCell($this->widths[$column], 7, $this->headers[$column], self::CENTER, 'BRT', 1);
		}
		$this->Ln();

		// reset styles
		$this->SetFillColor(255);
		$this->changeSize(8);
	}

	/**
	 * Method to get display
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return void
	 * @throws Exception => invalid request / unauthorized access
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function display($tpl = null)
	{
		$this->addAttendancePage();

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
						$value = Helpers\Programs::getOrganization($participant->programID, true);
						break;
					case 'program':
						$value = Helpers\Programs::getName($participant->programID);
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

			$this->Ln();

			if ($this->getY() > 275)
			{
				$this->addAttendancePage();
			}

			$itemNo++;
		}

		$this->Output($this->filename, 'I');
		ob_flush();
	}

	/**
	 * Set header items.
	 *
	 * @return void
	 */
	public function setHeader()
	{
		$dates     = ($this->endDate and $this->endDate != $this->startDate) ?
			"$this->startDate - $this->endDate" : $this->startDate;
		$subHeader = $this->campus ? "$this->campus $dates" : $dates;

		$this->setHeaderData('pdf_logo.png', '55', $this->course, $subHeader, self::BLACK, self::WHITE);
		$this->setFooterData(self::BLACK, self::WHITE);
		parent::setHeader();
	}
}
