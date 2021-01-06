<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Layouts\PDF\Instances;

use Organizer\Helpers\Dates;
use Organizer\Helpers\Grids;
use Organizer\Helpers\Languages;
use Organizer\Views\PDF\BaseView;
use Organizer\Layouts\PDF\BaseLayout;
use Organizer\Views\PDF\Instances;

/**
 * Base class for a Joomla View
 *
 * Class holding methods for displaying presentation data.
 */
abstract class GridLayout extends BaseLayout
{
	protected const DATA_WIDTH = 45, LINE_HEIGHT = 4.3, PADDING = 1, TIME_WIDTH = 11;

	protected const CORNER_BORDER = [
		'R' => ['width' => '.5', 'color' => [74, 92, 102]],
		'B' => ['width' => '.5', 'color' => [74, 92, 102]]
	];

	protected const INSTANCE_BORDER = [
		'R' => ['width' => '.5', 'color' => [74, 92, 102]],
		'B' => ['width' => '.5', 'color' => [210, 210, 210]]
	];

	protected const TIME_BORDER = [
		'R' => ['width' => '.5', 'color' => [74, 92, 102]],
		'B' => ['width' => '.5', 'color' => [255, 255, 255]]
	];

	/**
	 * The time grid.
	 *
	 * @var array
	 */
	protected $grid;

	/**
	 * The planned instances.
	 *
	 * @var array
	 */
	protected $instances;

	/**
	 * The view document.
	 * @var Instances
	 */
	protected $view;

	/**
	 * @inheritDoc
	 */
	public function __construct(BaseView $view)
	{
		parent::__construct($view);

		// 7bottom m because of the footer
		$view->margins(5, 25, 5, 10);
		$view->setCellPaddings('', 1, '', 1);
		$view->setPageOrientation($view::LANDSCAPE);
	}

	/**
	 * Adds a page to the document.
	 *
	 * @param   string  $startDate  the page start date
	 * @param   string  $endDate    the page end date
	 *
	 * @return void modifies the document
	 */
	protected function addGridPage(string $startDate, string $endDate)
	{
		$this->addLayoutPage($startDate, $endDate);
		$this->renderHeaders($startDate, $endDate);
	}

	/**
	 * Adds an empty page to the document.
	 *
	 * @param   string  $startDate  the page start date
	 * @param   string  $endDate    the page end date
	 *
	 * @return void modifies the document
	 */
	protected function addEmptyPage(string $startDate, string $endDate)
	{
		$this->addLayoutPage($startDate, $endDate);
		$endDate   = Dates::formatDate($endDate);
		$startDate = Dates::formatDate($startDate);
		$this->view->Cell('', '', sprintf(Languages::_('ORGANIZER_NO_INSTANCES'), $startDate, $endDate));
	}

	/**
	 * Adds a page to the document.
	 *
	 * @param   string  $startDate  the page start date
	 * @param   string  $endDate    the page end date
	 *
	 * @return void modifies the document
	 */
	private function addLayoutPage(string $startDate, string $endDate)
	{
		$fEndDate   = Dates::formatDate($endDate);
		$fStartDate = Dates::formatDate($startDate);
		$subTitle   = "$fStartDate - $fEndDate";

		$view = $this->view;
		$view->setHeaderData('pdf_logo.png', '55', $view->title, $subTitle, $view::BLACK, $view::WHITE);
		$view->AddPage();
	}

	/**
	 * Adds a page break as necessary.
	 *
	 * @param   int     $lines      the number of lines which will be added in the next row
	 * @param   string  $startDate  the page start date
	 * @param   string  $endDate    the page end date
	 *
	 * @return bool  true if a page was added, otherwise false
	 */
	protected function addPageBreak(int $lines, string $startDate, string $endDate): bool
	{
		$view       = $this->view;
		$dimensions = $view->getPageDimensions();

		$bottomMargin = $dimensions['bm'];
		$pageHeight   = $dimensions['hk'];
		$rowHeight    = $lines * self::LINE_HEIGHT;
		$yPos         = $view->GetY();

		if (($yPos + $rowHeight + $bottomMargin) > $pageHeight)
		{
			$view->Ln();
			$this->addGridPage($startDate, $endDate);

			return true;
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function fill($data)
	{
		$view      = $this->view;
		$endDate   = $view->conditions['endDate'];
		$startDate = $view->conditions['startDate'];

		if (!$data)
		{
			$this->addEmptyPage($startDate, $endDate);

			return;
		}

		$this->instances = $data;
		$monDate         = $startDate;

		while ($monDate < $endDate)
		{
			$saturDate = date('Y-m-d', strtotime('saturday this week', strtotime($monDate)));
			$this->setGrid($monDate, $saturDate);

			if ($this->grid)
			{
				$this->addGridPage($monDate, $saturDate);
				$this->renderGrid($monDate, $saturDate);
			}

			$monDate = date('Y-m-d', strtotime('next monday', strtotime($monDate)));
		}
	}

	/**
	 * Gets the text to be displayed in the row cells
	 *
	 * @param   string  $startDate  the page start date
	 * @param   string  $endDate    the page end date
	 * @param   array   $block      the block being iterated
	 *
	 * @return array
	 */
	protected function getCells(string $startDate, string $endDate, array $block): array
	{
		$cells = [];
		$view  = $this->view;

		for ($currentDT = strtotime($startDate); $currentDT <= strtotime($endDate);)
		{
			$date      = date('Y-m-d', $currentDT);
			$endTime   = date('H:i', strtotime($block['endTime']));
			$row       = 0;
			$startTime = date('H:i', strtotime($block['startTime']));

			foreach ($this->getBlockInstances($date, $startTime, $endTime) as $instance)
			{
				if (empty($cells[$row]))
				{
					$cells[$row] = ['lines' => []];
				}

				$cells[$row][$date]     = $this->getInstance($instance, $startTime, $endTime);
				$cells[$row]['lines'][] = $view->getNumLines($cells[$row][$date], self::DATA_WIDTH);
				$row++;
			}

			$currentDT = strtotime("+1 day", $currentDT);
		}

		foreach ($cells as $row => $instances)
		{
			$subRowLines          = max($cells[$row]['lines']);
			$cells[$row]['lines'] = $subRowLines;
			$cells['lines']       = array_key_exists('lines', $cells) ? $cells['lines'] + $subRowLines : $subRowLines;
		}

		return $cells;
	}

	/**
	 * Filters the instances to those planned in the block being iterated.
	 *
	 * @param   string  $date       the block date
	 * @param   string  $startTime  the block start time
	 * @param   string  $endTime    the block end time
	 *
	 * @return array the relevant instances
	 */
	protected function getBlockInstances(string $date, string $startTime, string $endTime): array
	{
		$instances = [];

		foreach ($this->instances as $instance)
		{
			if ($instance->date !== $date)
			{
				continue;
			}

			if ($instance->endTime < $startTime)
			{
				continue;
			}

			if ($instance->startTime > $endTime)
			{
				continue;
			}

			$name              = $instance->name;
			$method            = $instance->methodCode ? $instance->methodCode : 'ZZZ';
			$instanceID        = $instance->instanceID;
			$index             = "$name-$method-$instanceID";
			$instances[$index] = $instance;
		}

		ksort($instances);

		return $instances;
	}

	/**
	 * Gets the text to be displayed for the given block.
	 *
	 * @param   array  $block  the block being iterated
	 *
	 * @return string
	 */
	protected function getLabel(array $block): string
	{
		$label = 'label_' . Languages::getTag();
		if ($block[$label])
		{
			return $block[$label];
		}

		$startTime = Dates::formatTime($block['startTime']);
		$endTime   = date('H:i', strtotime('+1 minute', strtotime($block['endTime'])));
		$endTime   = Dates::formatTime($endTime);

		return $startTime . "\n-\n" . $endTime;
	}

	/**
	 * Creates the text to be output for the lesson instance
	 *
	 * @param   object  $instance   the instance information
	 * @param   string  $startTime  the block start time
	 * @param   string  $endTime    the block end time
	 *
	 * @return string the html for the instance text
	 */
	protected function getInstance(object $instance, string $startTime, string $endTime): string
	{
		$text = '';

		// Understood end time the exclusive minute at which the instance has ended
		$endTime = date('H:i', strtotime('+1 minute', strtotime($endTime)));

		// Instance time not coincidental with block
		if ($instance->startTime != $startTime or $instance->endTime != $endTime)
		{
			$formattedStart = Dates::formatTime($startTime);
			$formattedEnd   = Dates::formatTime($endTime);
			$text           .= "$formattedStart - $formattedEnd\n";
		}

		$pools   = [];
		$persons = [];
		$rooms   = [];

		/*foreach ($instance['subjects'] as $subject)
		{
			// Only if no specific pool was requested individually
			if (empty($this->parameters['poolIDs']) or count($this->parameters['poolIDs']) > 1)
			{
				foreach ($subject['pools'] as $poolID => $pool)
				{
					$pools[$poolID] = $pool['code'];
				}
			}

			// Only if no specific person was requested individually
			if (empty($this->parameters['personIDs']) or count($this->parameters['personIDs']) > 1)
			{
				foreach ($subject['persons'] as $personID => $personName)
				{
					$persons[$personID] = $personName;
				}
			}

			// Only if no specific room was requested individually
			if (empty($this->parameters['roomIDs']) or count($this->parameters['roomIDs']) > 1)
			{
				foreach ($subject['rooms'] as $roomID => $roomName)
				{
					$rooms[$roomID] = $roomName;
				}
			}
		}*/

		$name = $instance->name;

		if ($instance->methodCode)
		{
			$name .= " - $instance->methodCode";
		}

		if ($instance->subjectNo)
		{
			$name .= " ($instance->subjectNo)";
		}

		$text .= "$name\n";

		$output = [];

		/*if (!empty($pools))
		{
			$output[] = implode('/', $pools);
		}

		if (!empty($persons))
		{
			$output[] = implode('/', $persons);
		}

		if (!empty($rooms))
		{
			$output[] = implode('/', $rooms);
		}*/

		if ($instance->comment)
		{
			$output[] = $instance->comment;
		}

		$text .= implode(' ', $output);

		return $text;
	}

	/**
	 * Renders a row in which no instances are planned.
	 *
	 * @param   string  $label      the label for the row
	 * @param   string  $startDate  the page start date
	 * @param   string  $endDate    the page end date
	 *
	 * @return void  modifies the document
	 */
	protected function renderEmptyRow(string $label, string $startDate, string $endDate)
	{
		$view  = $this->view;
		$lines = $view->getNumLines($label, self::DATA_WIDTH);
		$this->addPageBreak($lines, $startDate, $endDate);
		$height = $lines * self::LINE_HEIGHT;
		$this->renderTimeCell($label, $height, $view::GINSBERG);

		for ($currentDT = strtotime($startDate); $currentDT <= strtotime($endDate);)
		{
			$view->renderMultiCell(self::DATA_WIDTH, $height, '', $view::CENTER, $view::GINSBERG);

			$currentDT = strtotime("+1 day", $currentDT);
		}

		$view->Ln();
	}

	/**
	 * Renders the grid body.
	 *
	 * @param   string  $startDate  the page start date
	 * @param   string  $endDate    the page end date
	 *
	 * @return void  modifies the document
	 */
	abstract protected function renderGrid(string $startDate, string $endDate);

	/**
	 * Renders the grid headers.
	 *
	 * @param   string  $startDate  the page start date
	 * @param   string  $endDate    the page end date
	 *
	 * @return void  modifies the document
	 */
	abstract protected function renderHeaders(string $startDate, string $endDate);

	/**
	 * @param   string  $label      the block label
	 * @param   array   $cells      the block instance data
	 * @param   string  $startDate  the page start date
	 * @param   string  $endDate    the page end date
	 *
	 * @return void  modifies the document
	 */
	protected function renderRow(string $label, array $cells, string $startDate, string $endDate)
	{
		$view = $this->view;

		// Less one because of the 'lines' count index
		$lastIndex = count($cells) - 1;
		$lLines    = $view->getNumLines($label, self::DATA_WIDTH);
		$rowNumber = 1;

		foreach ($cells as $index => $row)
		{
			if ($index === 'lines')
			{
				continue;
			}

			$lines  = $rowNumber === 1 ? max($lLines, $row['lines']) : $row['lines'];
			$border = $rowNumber === $lastIndex ? self::CORNER_BORDER : self::TIME_BORDER;

			// Time lines have the time output as the minimum height.
			if ($this->addPageBreak($lines, $startDate, $endDate) or $rowNumber === 1)
			{
				$lines  = max($lLines, $row['lines']);
				$height = $lines * self::LINE_HEIGHT;
				$this->renderTimeCell($label, $height, $border);
			}
			else
			{
				$lines  = $row['lines'];
				$height = $lines * self::LINE_HEIGHT;
				$view->renderCell(self::TIME_WIDTH, $height, '', $view::LEFT, $border);
			}

			$border = $rowNumber === $lastIndex ? self::CORNER_BORDER : self::INSTANCE_BORDER;
			$rowNumber++;

			for ($currentDT = strtotime($startDate); $currentDT <= strtotime($endDate);)
			{
				$date  = date('Y-m-d', $currentDT);
				$value = empty($row[$date]) ? '' : $row[$date];
				$view->renderMultiCell(self::DATA_WIDTH, $height, $value, $view::CENTER, $border);

				$currentDT = strtotime("+1 day", $currentDT);
			}

			$view->Ln();
		}
	}

	/**
	 * Writes the time cell to the document
	 *
	 * @param   string            $label   the block label typically the start and end times, but can also be a label
	 * @param   float             $height  the cell height
	 * @param   array|int|string  $border  the TCPDF border value
	 *
	 * @return void  modifies the document
	 * @see \TCPDF::MultiCell()
	 */
	protected function renderTimeCell(string $label, float $height, $border = 0)
	{
		$view = $this->view;
		$view->renderMultiCell(self::TIME_WIDTH, $height, $label, $view::CENTER, $border);
	}

	/**
	 * Sets the grid to be used for the current page
	 *
	 * @param   string  $startDate  the page start date
	 * @param   string  $endDate    the page end date
	 *
	 * @return void  modifies the document
	 */
	private function setGrid(string $startDate, string $endDate)
	{
		$gridIDs = [];

		foreach ($this->instances as $instance)
		{
			if ($instance->date < $startDate)
			{
				continue;
			}

			if ($instance->date > $endDate)
			{
				continue;
			}

			$gridIDs[$instance->gridID] = empty($gridIDs[$instance->gridID]) ? 1 : $gridIDs[$instance->gridID] + 1;
		}

		if (empty($gridIDs))
		{
			$this->grid = [];

			return;
		}

		$gridID     = array_search(max($gridIDs), $gridIDs);
		$grid       = json_decode(Grids::getGrid($gridID), true);
		$this->grid = $grid['periods'];
	}

	/**
	 * Generates the title and sets name related properties.
	 */
	public function setTitle()
	{
		$title = $this->view->get('title');
		$this->view->setNames($title);
	}
}
