<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @author      Florian Fenzl, <florian.fenzl@mni.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\PDF;

use Joomla\CMS\Table\Table;
use Organizer\Helpers;
use Organizer\Helpers\Languages;
use Organizer\Tables;

/**
 * Base PDF export class used for the generation of various course exports.
 */
trait CourseDocumentation
{
	/**
	 * The campus where the course takes place
	 * @var string
	 */
	protected $campus;

	/**
	 * The id of the associated course.
	 * @var int
	 */
	private $courseID;

	/**
	 * The dates as displayed in the generated document.
	 * @var string
	 */
	protected $dates;

	/**
	 * The course end date
	 * @var string
	 */
	protected $endDate;

	/**
	 * The fee required for participation in the course
	 * @var int
	 */
	protected $fee;

	/**
	 * The name of the course
	 * @var string
	 */
	protected $course;

	protected $rectangleStyle = [
		'width' => 0.1,
		'cap'   => 'butt',
		'join'  => 'miter',
		'dash'  => 0,
		'color' => [0, 0, 0]
	];

	/**
	 * The course start date
	 * @var string
	 */
	protected $startDate;

	/**
	 * Adds a badge position to the sheet
	 *
	 * @param   Table  $participant  the participant being iterated
	 * @param   int    $xOffset      the reference value for x
	 * @param   int    $yOffset      the reference value for y
	 *
	 * @return void modifies the pdf document
	 */
	protected function addBadge($participant, $xOffset, $yOffset)
	{
		$this->SetLineStyle($this->rectangleStyle);
		$this->Rect($xOffset, $yOffset + 10, 90, 80);

		$left = $xOffset + 4;
		$this->Image(K_PATH_IMAGES . 'logo.png', $left, $yOffset + 15, 30, 0);

		$this->changePosition($xOffset + 70, $yOffset + 15);
		$this->changeFont(self::REGULAR, 10);
		$this->renderCell(16, 5, $participant->id, self::CENTER, self::ALL);

		$this->changePosition($left, $yOffset + 29);
		$this->changeFont(self::BOLD, 12);
		$headerLine = $this->course;
		$this->renderMultiCell(80, 5, $headerLine, self::CENTER);

		$titleOffset = strlen($headerLine) > 35 ? 12 : 2;

		$this->changeFont(self::REGULAR, 10);
		$dates = $this->startDate == $this->endDate ? $this->startDate : "$this->startDate - $this->endDate";

		if ($this->campus)
		{
			$this->changePosition($left, $yOffset + $titleOffset + 33);
			$this->renderCell(80, 5, $this->campus, self::CENTER);
			$this->changePosition($left, $yOffset + $titleOffset + 38);
			$this->renderCell(80, 5, $dates, self::CENTER);
		}
		else
		{
			$this->changePosition($left, $yOffset + $titleOffset + 34);
			$this->renderCell(80, 5, $dates, self::CENTER);
		}

		$halfTitleOffset = $titleOffset / 2;
		$this->Ln();
		$this->changeFont(self::BOLD, 20);
		$this->changePosition($left, $yOffset + $halfTitleOffset + 45);
		$this->renderCell(80, 5, Languages::_('ORGANIZER_BADGE'), self::CENTER);

		$this->changePosition($left, $yOffset + 45);
		$this->changeFont(self::REGULAR, 10);

		$participantName = $participant->surname;
		$participantName .= empty($participant->forename) ? '' : ",  $participant->forename";

		$this->Ln();
		$this->changePosition($left, $yOffset + 63);
		$this->renderCell(20, 5, Languages::_('ORGANIZER_NAME') . ': ');
		$this->changeFont(self::BOLD);
		$this->renderCell(65, 5, $participantName);
		$this->changeFont(self::REGULAR);

		$this->Ln();
		$this->changePosition($left, $yOffset + 68);
		$this->renderCell(20, 5, Languages::_('ORGANIZER_ADDRESS') . ': ');
		$this->renderCell(65, 5, $participant->address);

		$this->Ln();
		$this->changePosition($left, $yOffset + 73);
		$this->renderCell(20, 5, Languages::_('ORGANIZER_RESIDENCE') . ': ');
		$this->renderCell(65, 5, "$participant->zipCode $participant->city");
	}

	/**
	 * Adds a badge reverse to the sheet reverse
	 *
	 * @param   int  $xOffset  the reference x offset for the box
	 * @param   int  $yOffset  the reference y offset for the box
	 *
	 * @return void modifies the pdf document
	 */
	protected function addBadgeBack($xOffset, $yOffset)
	{
		$this->SetLineStyle($this->rectangleStyle);
		$this->Rect($xOffset, 10 + $yOffset, 90, 80);

		$badgeCenter = $xOffset + 5;

		if ($this->fee)
		{
			$headerOffset    = 12 + $yOffset;
			$titleOffset     = 24 + $yOffset;
			$labelOffset     = 55 + $yOffset;
			$signatureOffset = 61 + $yOffset;
			$nameOffset      = 76 + $yOffset;
			$addressOffset   = 80 + $yOffset;
			$contactOffset   = 83 + $yOffset;
		}
		else
		{
			$headerOffset    = 17 + $yOffset;
			$titleOffset     = 29 + $yOffset;
			$labelOffset     = 42 + $yOffset;
			$signatureOffset = 47 + $yOffset;
			$nameOffset      = 62 + $yOffset;
			$addressOffset   = 73 + $yOffset;
			$contactOffset   = 76 + $yOffset;
		}

		$this->changeFont(self::BOLD, 20);
		$this->changePosition($badgeCenter, $headerOffset);
		$this->renderCell(80, 5, Languages::_('ORGANIZER_RECEIPT'), self::CENTER);

		$this->changeFont(self::BOLD, 12);
		$title       = $this->course;
		$longTitle   = strlen($title) > 50;
		$titleOffset = $longTitle ? $titleOffset - 3 : $titleOffset;
		$this->changePosition($badgeCenter, $titleOffset);
		$this->renderMultiCell(80, 5, $title, self::CENTER);

		$dates      = $this->startDate == $this->endDate ? $this->startDate : "$this->startDate - $this->endDate";
		$dateOffset = $longTitle ? $titleOffset + 10 : $titleOffset + 6;
		$this->changePosition($badgeCenter, $dateOffset);
		$this->changeFont(self::REGULAR, 10);
		$this->renderMultiCell(80, 5, $dates, self::CENTER);

		if ($this->fee)
		{
			$this->changePosition($badgeCenter, 37 + $yOffset);
			$this->changeFont(self::REGULAR, 11);
			$this->renderMultiCell(
				80,
				5,
				sprintf(Languages::_('ORGANIZER_BADGE_PAYMENT_TEXT'), $this->fee),
				self::CENTER
			);

			$this->changePosition($badgeCenter, 50 + $yOffset);
			$this->changeFont(self::ITALIC, 6);
			$this->renderMultiCell(80, 5, Languages::_('ORGANIZER_BADGE_TAX_TEXT'), self::CENTER);
		}

		$this->changeSize(8);
		$this->changePosition($badgeCenter, $labelOffset);
		$this->renderCell(80, 5, Languages::_('ORGANIZER_REPRESENTATIVE'), self::CENTER);

		$params = Helpers\Input::getParams();
		if (!empty($params->get('signatureFile')))
		{
			$signaturePath = K_PATH_IMAGES . $params->get('signatureFile');
			$this->Image($signaturePath, $xOffset + 35, $signatureOffset, 20, 0);
		}

		$this->changeSize(7);
		$this->changePosition($badgeCenter, $nameOffset);
		$this->renderCell(80, 5, $params->get('representativeName', ''), self::CENTER);

		$this->changeSize(6);
		$this->changePosition($badgeCenter, $addressOffset);
		$this->renderCell(80, 5, $params->get('address'), self::CENTER);

		$this->changePosition($badgeCenter, $contactOffset);
		$this->renderCell(80, 5, $params->get('contact'), self::CENTER);
	}

	/**
	 * Sets course related object properties.
	 *
	 * @return void
	 */
	protected function setCourseProperties()
	{
		$dates        = Helpers\Courses::getDates($this->courseID);
		$nameProperty = 'name_' . Helpers\Languages::getTag();

		$course = new Tables\Courses();
		$course->load($this->courseID);

		$this->campus    = Helpers\Campuses::getName($course->campusID);
		$this->course    = $course->$nameProperty;
		$this->endDate   = Helpers\Dates::formatDate($dates['endDate']);
		$this->fee       = $course->fee;
		$this->startDate = Helpers\Dates::formatDate($dates['startDate']);
	}
}
