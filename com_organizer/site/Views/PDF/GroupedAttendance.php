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

class GroupedAttendance extends TableView
{
	use CourseDocumentation;

	protected $headers;

	protected $widths = [
		'grouping'     => 130,
		'participants' => 60
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

		$documentName = "$this->course - $this->campus - $this->startDate - " . Helpers\Languages::_('ORGANIZER_ATTENDANCE');
		$this->setNames($documentName);
		$this->margins(10, 30, -1, 0, 8, 10);
		$this->showPrintOverhead(true);
		$this->setHeader();

		$groupingHeader = Helpers\Languages::_('ORGANIZER_ORGANIZATION') . ' / ';
		$groupingHeader .= Helpers\Languages::_('ORGANIZER_PROGRAM');

		$this->headers = [
			'grouping'     => $groupingHeader,
			'participants' => Helpers\Languages::_('ORGANIZER_PARTICIPANTS')
		];
	}

	/**
	 * Method to generate output.
	 *
	 * @return void
	 */
	public function display()
	{
		$index     = 0;
		$groupings = [];
		$pps       = Helpers\Courses::getParticipantPrograms($this->courseID);

		foreach ($pps as $pp)
		{
			$organization = $pp['organization'];

			if (empty($groupings[$organization]))
			{
				$groupings[$organization] = ['participants' => 0];
			}

			$groupings[$organization]['participants'] = $groupings[$organization]['participants'] + $pp['participants'];

			$groupings[$organization][$index]                 = [];
			$groupings[$organization][$index]['participants'] = $pp['participants'];
			$groupings[$organization][$index]['program']      = "{$pp['program']} ({$pp['degree']}, {$pp['year']})";
			$index++;
		}

		unset($pps);

		$this->addTablePage();

		foreach ($groupings as $organization => $programs)
		{
			$maxLength = 0;
			$startX    = $this->GetX();
			$startY    = $this->GetY();

			$this->SetFillColor(225);
			foreach (array_keys($this->headers) as $columnName)
			{
				$value  = $columnName === 'grouping' ? $organization : $programs['participants'];
				$length = $this->renderMultiCell($this->widths[$columnName], 5, $value, self::LEFT, self::NONE, true);

				if ($length > $maxLength)
				{
					$maxLength = $length;
				}
			}
			$this->SetFillColor(255);

			$this->changePosition($startX, $startY);

			foreach ($this->widths as $oIndex => $width)
			{
				$border = $oIndex === 'grouping' ? ['BLRT' => $this->border] : ['BRT' => $this->border];
				$this->renderMultiCell($width, $maxLength * 5, '', self::LEFT, $border);
			}

			$this->addLine();

			foreach ($programs as $key => $program)
			{
				if ($key === 'participants')
				{
					continue;
				}

				$maxLength = 0;
				$startX    = $this->GetX();
				$startY    = $this->GetY();

				foreach (array_keys($this->headers) as $columnName)
				{
					$value  = $columnName === 'grouping' ? ' - ' . $program['program'] : $program['participants'];
					$length = $this->renderMultiCell($this->widths[$columnName], 5, $value);
					if ($length > $maxLength)
					{
						$maxLength = $length;
					}
				}

				$this->changePosition($startX, $startY);

				foreach ($this->widths as $iIndex => $width)
				{
					$border = $iIndex === 'grouping' ? ['BLR' => $this->border] : ['BR' => $this->border];
					$this->renderMultiCell($width, $maxLength * 5, '', self::LEFT, $border);
				}

				$this->addLine();
			}
		}

		parent::display();
	}
}
