<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Layouts\XLS;

jimport('phpexcel.library.PHPExcel');

use Organizer\Helpers;
use Organizer\Helpers\Languages;

/**
 * Class generates an XLS file for the schedule where lessons are listed as aggregates.
 */
class ScheduleStack
{
	private $lessons;

	private $parameters;

	private $spreadSheet;

	/**
	 * OrganizerTemplateExport_XLS constructor.
	 *
	 * @param   array  $parameters  the parameters used for determining the export structure
	 * @param   array  $lessons     the lessons for the given time frame and chosen resources
	 */
	public function __construct($parameters, &$lessons)
	{
		$this->parameters = $parameters;
		$this->lessons    = $lessons;

		$spreadSheet = new \PHPExcel();

		$userName    = Helpers\Users::getUser()->name;
		$description = $this->getDescription();
		$spreadSheet->getProperties()->setCreator('THM Organizer')
			->setLastModifiedBy($userName)
			->setTitle($this->parameters['pageTitle'])
			->setDescription($description);

		$this->spreadSheet = $spreadSheet;

		$this->setColumnDisplay();

		$activeSheetNumber = 0;

		if (!array_key_exists('pastDate', $this->lessons) and !array_key_exists('futureDate', $this->lessons))
		{
			$today       = date('Y-m-d');
			$sheetNumber = 0;
			$startDate   = key($this->lessons);

			while (isset($this->lessons[$startDate]))
			{
				$breakDate = date('Y-m-d', strtotime('+7 day', strtotime($startDate)));

				$this->addSheet($sheetNumber, $startDate);
				$this->addData($startDate, $breakDate);

				// If the week being iterated is the actual week it should automatically be active on opening
				$thisWeek = ($today >= $startDate and $today < $breakDate);
				if ($thisWeek)
				{
					$activeSheetNumber = $sheetNumber;
				}

				// Set variables for the next iteration
				$startDate = $breakDate;
				$sheetNumber++;
			}
		}

		// Reset the active sheet to the first item
		$this->spreadSheet->setActiveSheetIndex($activeSheetNumber);
	}

	/**
	 * Iterates the dates / times and calls the function to add the event data
	 *
	 * @param   string  $startDate  the start date for the interval
	 * @param   string  $breakDate  the end date for the interval
	 *
	 * @return void
	 */
	private function addData($startDate, $breakDate)
	{
		$row = 3;
		for ($currentDate = $startDate; $currentDate < $breakDate;)
		{
			if (empty($this->lessons[$currentDate]))
			{
				continue;
			}

			$timesIndexes = $this->lessons[$currentDate];

			foreach ($timesIndexes as $lessonInstances)
			{
				foreach ($lessonInstances as $lessonInstance)
				{
					$this->addEvent($row, $currentDate, $lessonInstance);
					$row++;
				}
			}

			$currentDate = date('Y-m-d', strtotime('+1 day', strtotime($currentDate)));
		}
	}

	/**
	 * Adds lesson instances to the spreadsheet
	 *
	 * @param   int     $row             the row number for the event
	 * @param   string  $date            the date on which the lesson occurs
	 * @param   array   $lessonInstance  the lesson instance data
	 *
	 * @return void
	 */
	private function addEvent($row, $date, $lessonInstance)
	{
		$date = Helpers\Dates::formatDate($date);
		$this->spreadSheet->getActiveSheet()->setCellValue("A$row", $date);

		$startTime = Helpers\Dates::formatTime($lessonInstance['startTime']);
		$this->spreadSheet->getActiveSheet()->setCellValue("B$row", $startTime);

		$endTime = Helpers\Dates::formatTime($lessonInstance['endTime']);
		$this->spreadSheet->getActiveSheet()->setCellValue("C$row", $endTime);

		$name = implode(' / ', array_keys($lessonInstance['subjects']));
		$name .= empty($lessonInstance['method']) ? '' : " - {$lessonInstance['method']}";
		$this->spreadSheet->getActiveSheet()->setCellValue("D$row", $name);

		$pools   = [];
		$rooms   = [];
		$persons = [];

		foreach ($lessonInstance['subjects'] as $subjectConfig)
		{
			foreach ($subjectConfig['pools'] as $poolID => $poolData)
			{
				$pools[$poolID] = $poolData['fullName'];
			}

			$rooms   = $rooms + $subjectConfig['rooms'];
			$persons = $persons + $subjectConfig['persons'];
		}

		$letter = 'D';
		if ($this->parameters['showPersons'])
		{
			$column      = ++$letter;
			$cell        = "$column$row";
			$personsText = implode(' / ', $persons);
			$this->spreadSheet->getActiveSheet()->setCellValue($cell, $personsText);
		}

		if ($this->parameters['showRooms'])
		{
			$column    = ++$letter;
			$cell      = "$column$row";
			$roomsText = implode(' / ', $rooms);
			$this->spreadSheet->getActiveSheet()->setCellValue($cell, $roomsText);
		}

		if ($this->parameters['showPools'])
		{
			$column    = ++$letter;
			$cell      = "$column$row";
			$poolsText = implode(' / ', $pools);
			$this->spreadSheet->getActiveSheet()->setCellValue($cell, $poolsText);
		}
	}

	/**
	 * Adds column headers to the sheet
	 *
	 * @param   int     $sheetNumber   the sheet number to be added
	 * @param   string  $rawStartDate  the start date for the sheet
	 *
	 * @return void
	 */
	private function addSheet($sheetNumber, $rawStartDate)
	{
		if ($sheetNumber > 0)
		{
			$this->spreadSheet->createSheet();
		}

		$this->spreadSheet->setActiveSheetIndex($sheetNumber);

		$rawEndDate = date('Y-m-d', strtotime('+6 day', strtotime($rawStartDate)));
		$startDate  = Helpers\Dates::formatDate($rawStartDate);
		$endDate    = Helpers\Dates::formatDate($rawEndDate);
		$dates      = "$startDate - $endDate";

		$this->spreadSheet->getActiveSheet()->setTitle($dates);

		$this->spreadSheet->getActiveSheet()->setCellValue('A2', Languages::_('ORGANIZER_DATE'));
		$this->spreadSheet->getActiveSheet()->setCellValue('B2', Languages::_('ORGANIZER_START_TIME'));
		$this->spreadSheet->getActiveSheet()->setCellValue('C2', Languages::_('ORGANIZER_END_TIME'));
		$this->spreadSheet->getActiveSheet()->setCellValue('D2', Languages::_('ORGANIZER_SUBJECTS'));

		$letter = 'D';
		if ($this->parameters['showPersons'])
		{
			$column = ++$letter;
			$cell   = "{$column}2";
			$this->spreadSheet->getActiveSheet()->setCellValue($cell, Languages::_('ORGANIZER_TEACHERS'));
		}

		if ($this->parameters['showRooms'])
		{
			$column = ++$letter;
			$cell   = "{$column}2";
			$this->spreadSheet->getActiveSheet()->setCellValue($cell, Languages::_('ORGANIZER_ROOMS'));
		}

		if ($this->parameters['showPools'])
		{
			$column = ++$letter;
			$cell   = "{$column}2";
			$this->spreadSheet->getActiveSheet()->setCellValue($cell, Languages::_('ORGANIZER_POOLS'));
		}

		$this->spreadSheet->getActiveSheet()->mergeCells("A1:{$letter}1");
		$pageHeading = Languages::_('ORGANIZER_WEEK') . ": $dates";
		$this->spreadSheet->getActiveSheet()->setCellValue('A1', $pageHeading);

		foreach (range('A', $letter) as $columnID)
		{
			$this->spreadSheet->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
		}
	}

	/**
	 * Creates a description for the document
	 *
	 * @return string
	 */
	private function getDescription()
	{
		$lessonDates = array_keys($this->lessons);
		$startDate   = Helpers\Dates::formatDate(reset($lessonDates));
		$endDate     = Helpers\Dates::formatDate(end($lessonDates));

		return Languages::_('ORGANIZER_SCHEDULE') . " $startDate - $endDate " . $this->parameters['pageTitle'];
	}

	/**
	 * Outputs the generated Excel file. Execution is ended here to ensure that Joomla does not try to 'display' the
	 * output.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PMD.ExitExpression)
	 */
	public function render()
	{
		$objWriter = PHPExcel_IOFactory::createWriter($this->spreadSheet, 'Excel2007');
		ob_end_clean();
		header('Content-type: application/vnd.ms-excel');
		header("Content-Disposition: attachment;filename={$this->parameters['docTitle']}.xlsx");
		$objWriter->save('php://output');
		exit();
	}

	/**
	 * Determines whether individual resource columns will be displayed
	 * @return void
	 */
	private function setColumnDisplay()
	{
		$this->parameters['showPools'] = (
			(empty($this->parameters['poolIDs']) or count($this->parameters['poolIDs']) !== 1)
			or !empty($this->parameters['roomIDs'])
			or !empty($this->parameters['personIDs'])
		);

		$this->parameters['showRooms'] = (
			(empty($this->parameters['roomIDs']) or count($this->parameters['roomIDs']) !== 1)
			or !empty($this->parameters['poolIDs'])
			or !empty($this->parameters['personIDs'])
		);

		$this->parameters['showPersons'] = (
			(empty($this->parameters['personIDs']) or count($this->parameters['personIDs']) !== 1)
			or !empty($this->parameters['poolIDs'])
			or !empty($this->parameters['roomIDs'])
		);
	}
}
