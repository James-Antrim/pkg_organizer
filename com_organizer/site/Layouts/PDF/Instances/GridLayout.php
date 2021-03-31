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

use Organizer\Helpers;
use Organizer\Layouts\PDF\BaseLayout;
use Organizer\Tables\Roles;
use Organizer\Views\PDF\Instances;

/**
 * Base class for a Joomla View
 *
 * Class holding methods for displaying presentation data.
 */
abstract class GridLayout extends BaseLayout
{
	private const OVERPAD = .6;

	protected const PADDING = 2, TIME_HEIGHT = 15, TIME_WIDTH = 11;

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
	 * The text pertaining to the selected resources.
	 *
	 * @var string
	 */
	private $resourceHeader;

	/**
	 * The view document.
	 * @var Instances
	 */
	protected $view;

	/**
	 * @inheritDoc
	 */
	public function __construct(Instances $view)
	{
		parent::__construct($view);

		// 10 bottom m because of the footer
		$view->margins(5, 25, 5, 10);
		$view->setCellPaddings('', 1, '', 1);
		$view->setPageOrientation($view::LANDSCAPE);

		// This allows new header data per page.
		$view->setHeaderTemplateAutoreset(true);
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
		$endDate   = Helpers\Dates::formatDate($endDate);
		$startDate = Helpers\Dates::formatDate($startDate);
		$this->view->Cell('', '', sprintf(Helpers\Languages::_('ORGANIZER_NO_INSTANCES'), $startDate, $endDate));
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
		$subTitle = $this->resourceHeader ? $this->resourceHeader . "\n" : '';

		$fEndDate   = Helpers\Dates::formatDate($endDate);
		$fStartDate = Helpers\Dates::formatDate($startDate);
		$subTitle   .= "$fStartDate - $fEndDate";

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
		$overPad      = ($lines > 3) ? ($lines - 3) * self::OVERPAD : 0;
		$rowHeight    = $lines * $this::LINE_HEIGHT - $overPad;
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
		$view = $this->view;
		$this->setResourceHeader();
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
	 * Aggregates resource collections of the same type and creates an output text.
	 *
	 * @param   array   $resources  the resources to be aggregated
	 * @param   string  $key        the name key for the resource type
	 * @param   bool    $showCode   whether the resource code should be displayed in lieu of the name
	 *
	 * @return string
	 */
	private function getAggregatedResources(array $resources, string $key, bool $showCode = false): string
	{
		$aggregate = [];

		foreach ($resources as $set)
		{
			$aggregate = array_merge($aggregate, $set);
		}

		return $this->getResourceText($aggregate, $key, $showCode) . '<br>';
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

			if ($instance->endTime <= $startTime)
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
	 * Gets an accurate measurement of a cell's rendered height.
	 *
	 * @param   float   $width     the width of the column to be rendered
	 * @param   string  $contents  the contents to be rendered
	 *
	 * @return float
	 */
	private function getCellHeight(float $width, string $contents): float
	{
		$clone = clone $this->view;
		$clone->AddPage();
		$x     = $clone->GetX();
		$start = $clone->GetY();

		$clone->writeHTMLCell($width, 15, $x, $start, $contents, self::INSTANCE_BORDER, 1);

		return $clone->GetY() - $start;
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

				$contents = $this->getInstance($instance, $startTime, $endTime);

				$cells[$row][$date]     = $contents;
				$cells[$row]['lines'][] = $view->getNumLines($contents, $this::DATA_WIDTH);
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
	 * Gets the text to be displayed for the given block.
	 *
	 * @param   array  $block  the block being iterated
	 *
	 * @return string
	 */
	protected function getLabel(array $block): string
	{
		$label = 'label_' . Helpers\Languages::getTag();

		if ($block[$label])
		{
			$value = $block[$label];
		}
		else
		{
			$startTime = Helpers\Dates::formatTime($block['startTime']);
			$endTime   = date('H:i', strtotime('+1 minute', strtotime($block['endTime'])));
			$endTime   = Helpers\Dates::formatTime($endTime);
			$value     = $startTime . "<br>-<br>" . $endTime;
		}

		return '<div style="font-size: 8.5pt; text-align: center">' . $value . '</div>';
	}

	/**
	 * Creates a text for an individual person, inclusive their group and room assignments as requested.
	 *
	 * @param   array  $rolePersons  the persons of a single role in the form personID => name
	 * @param   array  $persons      the instance person resources hierarchy
	 * @param   bool   $showGroups   whether person specific groups should be displayed
	 * @param   bool   $showRooms    whether person specific rooms should be displayed
	 *
	 * @return string
	 */
	private function getIndividualTexts(array $rolePersons, array $persons, bool $showGroups, bool $showRooms): string
	{
		$html = '';

		if ($showGroups or $showRooms)
		{
			foreach ($rolePersons as $personID => $name)
			{
				$html .= $name;

				if ($showGroups and array_key_exists('groups', $persons[$personID]))
				{
					$glue = count($persons[$personID]['groups']) > 2 ? '<br>' : ' - ';
					$html .= $glue . $this->getResourceText($persons[$personID]['groups'], 'group');
				}

				if ($showRooms and array_key_exists('rooms', $persons[$personID]))
				{
					$glue = count($persons[$personID]['rooms']) > 2 ? '<br>' : ' - ';
					$html .= $glue . $this->getResourceText($persons[$personID]['rooms'], 'room');
				}

				$html .= '<br>';
			}
		}
		else
		{
			$html .= $this->implode($rolePersons) . '<br>';
		}

		return $html;
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
		$html = '<div style="font-size: 10px; text-align: center">';

		// Understood end time the exclusive minute at which the instance has ended
		$iEndTime   = $instance->endTime;
		$altEndTime = date('H:i', strtotime('+1 minute', strtotime($endTime)));

		// Instance time not coincidental with block
		if ($instance->startTime != $startTime or ($iEndTime != $endTime and $iEndTime != $altEndTime))
		{
			$formattedStart = Helpers\Dates::formatTime($instance->startTime);
			$formattedEnd   = Helpers\Dates::formatTime($iEndTime);

			$html .= "<span style=\"font-style: italic\">$formattedStart - $formattedEnd</span>";
		}

		$html .= '<br>';

		$name = $instance->name;

		if ($instance->subjectNo)
		{
			$name .= " ($instance->subjectNo)";
		}

		$html .= "<span style=\"font-size: 10pt;\">$name</span><br>";

		if ($instance->methodID)
		{
			$html .= "<span>$instance->method</span><br>";
		}

		if ($instance->comment)
		{
			$comment = $this->resolveLinks($instance->comment);
			$html    .= "<span style=\"font-style: italic\">$comment</span><br>";
		}

		$conditions = $this->view->conditions;

		// If there is no category context the group names may overlap.
		$showGroupCodes = empty($conditions['categoryIDs']);

		// If groups/rooms were restricted their output is redundant.
		$showGroups  = empty($conditions['groupIDs']);
		$showPersons = empty($conditions['personIDs']);
		$showRooms   = empty($conditions['roomIDs']);

		// Aggregation containers
		$groups      = [];
		$persons     = (array) $instance->resources;
		$personTexts = [];
		$rooms       = [];

		foreach ($persons as $personID => $person)
		{
			// No delta display in pdf
			if ($person['status'] === 'removed' or (!empty($conditions['personIDs']) and !in_array($personID, $conditions['personIDs'])))
			{
				unset($persons[$personID]);
				continue;
			}

			if (!$showPersons and !in_array($personID, $conditions['personIDs']))
			{
				unset($persons[$personID]);
				continue;
			}

			if (!array_key_exists($person['roleID'], $personTexts))
			{
				$personTexts[$person['roleID']] = [];
			}

			$personTexts[$person['roleID']][$personID] = $person['person'];

			if (array_key_exists('groups', $person))
			{
				// The group status creates false negatives using in_array
				$filteredGroups = [];
				foreach ($person['groups'] as $groupID => $group)
				{
					// No delta display in pdf
					if ($group['status'] === 'removed')
					{
						unset($persons[$personID]['groups'][$groupID]);
						continue;
					}

					unset($group['status'], $group['statusDate']);
					$filteredGroups[$groupID] = $group;
				}

				if (!in_array($filteredGroups, $groups))
				{
					$groups[] = $filteredGroups;
				}
			}

			if (array_key_exists('rooms', $person) and !in_array($person['rooms'], $rooms))
			{
				// The room status creates false negatives using in_array
				$filteredRooms = [];
				foreach ($person['rooms'] as $roomID => $room)
				{
					// No delta display in pdf
					if ($room['status'] === 'removed')
					{
						unset($persons[$personID]['rooms'][$roomID]);
						continue;
					}

					unset($room['status'], $room['statusDate']);
					$filteredRooms[$roomID] = $room;
				}

				if (!in_array($filteredRooms, $rooms))
				{
					$rooms[] = $filteredRooms;
				}
			}
		}

		// The role ids are in order of relative importance
		ksort($personTexts);

		$groupCount = count($groups);
		$roleCount  = count($personTexts);
		$roomCount  = count($rooms);

		// Status: share and share alike, all persons are assigned the same groups and rooms or none
		if ($groupCount < 2 and $roomCount < 2)
		{
			if ($showPersons)
			{
				if ($roleCount === 1)
				{
					$html .= $this->getResourceText($persons, 'person') . '<br>';
				}
				else
				{
					foreach ($personTexts as $roleID => $rolePersons)
					{
						asort($rolePersons);
						$html .= $this->getRoleText($roleID, $rolePersons);
						$html .= $this->getIndividualTexts($rolePersons, $persons, false, false);
					}
				}
			}

			if ($groups and $showGroups)
			{
				$html .= $this->getResourceText($groups[0], 'group', $showGroupCodes) . '<br>';
			}

			if ($rooms and $showRooms)
			{
				$html .= $this->getResourceText($rooms[0], 'room') . '<br>';
			}
		}
		// Status: laser focused, all persons are assigned the same groups or none, rooms may vary between persons
		elseif ($groupCount < 2)
		{
			// Assumption specific room assignments => small number per person => no need to further process rooms per line
			if ($groups and $showGroups)
			{
				$html .= $this->getResourceText($groups[0], 'group', $showGroupCodes) . '<br>';
			}

			if ($showPersons)
			{
				if ($roleCount === 1)
				{
					$rolePersons = array_shift($personTexts);
					asort($rolePersons);
					$html .= $this->getIndividualTexts($rolePersons, $persons, false, $showRooms);
				}
				else
				{
					foreach ($personTexts as $roleID => $rolePersons)
					{
						asort($rolePersons);
						$html .= $this->getRoleText($roleID, $rolePersons);
						$html .= $this->getIndividualTexts($rolePersons, $persons, false, $showRooms);
					}
				}
			}
			elseif ($rooms and $showRooms)
			{
				$html .= $this->getAggregatedResources($rooms, 'room');
			}
		}
		// Status: claustrophobic, all persons are assigned the same rooms or none, groups may vary between persons
		elseif ($roomCount < 2)
		{
			if ($showPersons)
			{
				if ($roleCount === 1)
				{
					$rolePersons = array_shift($personTexts);
					asort($rolePersons);
					$html .= $this->getIndividualTexts($rolePersons, $persons, $showGroups, false);
				}
				else
				{
					foreach ($personTexts as $roleID => $rolePersons)
					{
						asort($rolePersons);
						$html .= $this->getRoleText($roleID, $rolePersons);
						$html .= $this->getIndividualTexts($rolePersons, $persons, $showGroups, false);
					}
				}
			}
			elseif ($groups and $showGroups)
			{
				$html .= $this->getAggregatedResources($groups, 'group', $showGroupCodes);
			}

			if ($rooms and $showRooms)
			{
				$html .= $this->getResourceText($rooms[0], 'room') . '<br>';
			}
		}
		// Status: varying degrees of complexity, most easily handled by individual output,
		elseif ($showPersons)
		{
			// Suppress for less output where differentiation is not necessary.
			if ($roleCount === 1)
			{
				$personTexts = array_shift($personTexts);
				asort($personTexts);
				$html .= $this->getIndividualTexts($personTexts, $persons, $showGroups, $showRooms);
			}
			else
			{
				foreach ($personTexts as $roleID => $rolePersons)
				{
					asort($rolePersons);
					$html .= $this->getRoleText($roleID, $rolePersons);
					$html .= $this->getIndividualTexts($rolePersons, $persons, $showGroups, $showRooms);
				}
			}
		}
		else
		{
			if ($groups and $showGroups)
			{
				$html .= $this->getAggregatedResources($groups, 'group', $showGroupCodes);
			}

			if ($rooms and $showRooms)
			{
				$html .= $this->getAggregatedResources($rooms, 'room');
			}
		}

		// Check if return is the last character before shortening
		$length    = strlen($html);
		$lastBreak = strrpos($html, '<br>');

		if ($lastBreak + 1 === $length)
		{
			$html = substr($html, 0, strlen($html) - 1);
		}

		return $html . '</div>';
	}

	/**
	 * Generates the text for the given resources.
	 *
	 * @param   array   $resources  the rooms associated with the person or persons
	 * @param   string  $name       the name and index of the resource within the respective array
	 * @param   bool    $code       whether the resource code should be used for the text
	 *
	 * @return string
	 */
	private function getResourceText(array $resources, string $name, bool $code = false): string
	{
		$container = [];

		foreach ($resources as $resourceID => $resource)
		{
			$container[] = $code ? $resource['code'] : $resource[$name];
		}

		return '<span>' . $this->implode($container) . '</span>';
	}

	/**
	 * Creates the text for the role being currently iterated.
	 *
	 * @param   int    $roleID       the id of the role being iterated
	 * @param   array  $rolePersons  the persons associated with the role being iterated
	 *
	 * @return string
	 */
	private function getRoleText(int $roleID, array $rolePersons): string
	{
		$role = new Roles();
		$role->load($roleID);
		$tag    = Helpers\Languages::getTag();
		$column = count($rolePersons) > 1 ? "plural_$tag" : "name_$tag";

		return $role->$column . ":<br>";
	}

	/**
	 * Aggregates resources to string with a soft wrap at $this::BREAK characters.
	 *
	 * @param   array  $resources  the resources to be aggregated
	 *
	 * @return string
	 */
	private function implode(array $resources): string
	{
		asort($resources);
		$lastResource = array_pop($resources);

		if ($resources)
		{
			$index  = 0;
			$length = 0;
			$texts  = [];

			foreach ($resources as $resource)
			{
				if (empty($texts[$index]))
				{
					$texts[$index] = $resource;
				}
				else
				{
					$probe = $texts[$index] . ", $resource";

					if (strlen($probe) > $this::LINE_LENGTH)
					{
						$texts[$index] .= ",<br>";
						$index++;
						$texts[$index] = $resource;
					}
					else
					{
						$texts[$index] = $probe;
					}
				}

				$length = strlen($texts[$index]);
			}

			$text = implode('', $texts);
			$text .= ($length + strlen($lastResource)) > $this::LINE_LENGTH ? '<br>' : ' ';
			$text .= "& $lastResource";

			return $text;
		}

		return $lastResource;
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
		$view = $this->view;

		$x = $view->GetX();
		$y = $view->GetY();

		$this->addPageBreak(3, $startDate, $endDate);
		$view->writeHTMLCell(self::TIME_WIDTH, self::TIME_HEIGHT, $x, $y, $label, self::CORNER_BORDER);
		$x += $this::TIME_WIDTH;

		for ($currentDT = strtotime($startDate); $currentDT <= strtotime($endDate);)
		{
			$view->writeHTMLCell($this::DATA_WIDTH, self::TIME_HEIGHT, $x, $y, '', self::CORNER_BORDER);
			$x += $this::DATA_WIDTH;

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
	protected function renderGrid(string $startDate, string $endDate)
	{
		foreach ($this->grid as $block)
		{
			$cells = $this->getCells($startDate, $endDate, $block);
			$label = $this->getLabel($block);

			if ($cells)
			{
				$this->renderRow($label, $cells, $startDate, $endDate);
				continue;
			}

			$this->renderEmptyRow($label, $startDate, $endDate);
		}
	}

	/**
	 * Renders the grid headers.
	 *
	 * @param   string  $startDate  the page start date
	 * @param   string  $endDate    the page end date
	 *
	 * @return void  modifies the document
	 */
	protected function renderHeaders(string $startDate, string $endDate)
	{
		$view = $this->view;
		$view->SetFont('helvetica', '', 10);
		$view->SetLineStyle(['width' => 0.5, 'dash' => 0, 'color' => [74, 92, 102]]);
		$view->renderMultiCell(self::TIME_WIDTH, 0, Helpers\Languages::_('ORGANIZER_TIME'), $view::CENTER, $view::HORIZONTAL);

		for ($currentDT = strtotime($startDate); $currentDT <= strtotime($endDate);)
		{
			$view->renderMultiCell(
				$this::DATA_WIDTH,
				0,
				Helpers\Dates::formatDate(date('Y-m-d', $currentDT)),
				$view::CENTER,
				$view::HORIZONTAL
			);

			$currentDT = strtotime("+1 day", $currentDT);
		}

		$view->Ln();
		$view->SetFont('helvetica', '', $this::FONT_SIZE);
	}

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
		// Less one because of the 'lines' count index
		$lastIndex = count($cells) - 1;
		$rowNumber = 1;
		$view      = $this->view;

		foreach ($cells as $index => $row)
		{
			if ($index === 'lines')
			{
				continue;
			}

			$border = $rowNumber === $lastIndex ? self::CORNER_BORDER : self::TIME_BORDER;
			$x      = $view->GetX();
			$y      = $view->GetY();

			$lines   = $row['lines'];
			$overPad = ($lines > 3) ? ($lines - 3) * self::OVERPAD : 0;
			$height  = $lines * $this::LINE_HEIGHT - $overPad;

			if ($this->addPageBreak($lines, $startDate, $endDate))
			{
				$y = $view->GetY();
				$view->writeHTMLCell(self::TIME_WIDTH, $height, $x, $y, $label, $border);
			}
			elseif ($rowNumber === 1)
			{
				$view->writeHTMLCell(self::TIME_WIDTH, $height, $x, $y, $label, $border);
			}
			else
			{
				$view->writeHTMLCell(self::TIME_WIDTH, $height, $x, $y, '', $border);
			}

			$border = $rowNumber === $lastIndex ? self::CORNER_BORDER : self::INSTANCE_BORDER;
			$rowNumber++;
			$x += self::TIME_WIDTH;

			for ($currentDT = strtotime($startDate); $currentDT <= strtotime($endDate);)
			{
				$date  = date('Y-m-d', $currentDT);
				$value = empty($row[$date]) ? '' : $row[$date];
				$view->writeHTMLCell($this::DATA_WIDTH, $height, $x, $y, $value, $border);

				$x         += $this::DATA_WIDTH;
				$currentDT = strtotime("+1 day", $currentDT);
			}

			$view->Ln();
		}
	}

	/**
	 * Resolves any links/link parameters to links with icons.
	 *
	 * @param   string  $text  the text to search
	 *
	 * @return string
	 */
	private function resolveLinks(string $text): string
	{
		$course = Helpers\Languages::_('ORGANIZER_MOODLE_COURSE');

		$courseURL      = 'https://moodle.thm.de/course/view.php?id=CID';
		$courseTemplate = "<a href=\"MOODLEURL\" target=\"_blank\" style=\"text-decoration: underline\">$course: CID</a>";

		// Complete Course URL
		$template = str_replace('CID', '$4', str_replace('MOODLEURL', $courseURL, $courseTemplate));
		$text     = preg_replace('/(((https?):\/\/)moodle.thm.de\/course\/view.php\?id=(\d+))/', $template, $text);

		// Course ID
		$template = str_replace('CID', '$1', str_replace('MOODLEURL', $courseURL, $courseTemplate));
		$text     = preg_replace('/moodle=(\d+)/', $template, $text);

		// Category URL
		$category         = Helpers\Languages::_('ORGANIZER_MOODLE_COURSE');
		$categoryURL      = 'https://moodle.thm.de/course/index.php?categoryid=CID';
		$categoryTemplate =
			"<a href=\"MOODLEURL\" target=\"_blank\" style=\"text-decoration: underline\">$category: CID</a>";
		$template         = str_replace('CID', '$4', str_replace('MOODLEURL', $categoryURL, $categoryTemplate));
		$text             = preg_replace(
			'/(((https?):\/\/)moodle\.thm\.de\/course\/index\.php\\?categoryid=(\\d+))/',
			$template,
			$text
		);

		$template = "<a href=\"$1\" target=\"_blank\">NetAcad</a>";
		$text     = preg_replace('/(((https?):\/\/)\d+.netacad.com\/courses\/\d+)/', $template, $text);

		$panoptoURL      = 'https://panopto.thm.de/Panopto/Pages/Viewer.aspx?id=PID';
		$panoptoTemplate = "<a href=\"$panoptoURL\" target=\"_blank\">Panopto</a>";

		$template = str_replace('PID', '$4', $panoptoTemplate);
		$text     = preg_replace(
			'/(((https?):\/\/)panopto.thm.de\/Panopto\/Pages\/Viewer.aspx\?id=[\d\w\-]+)/',
			$template,
			$text
		);
		$template = str_replace('PID', '$1', $panoptoTemplate);
		$text     = preg_replace('/panopto=([\d\w\-]+)/', $template, $text);

		$pilosREGEX = '/(((https?):\/\/)(\d+|roxy).pilos-thm.de\/(b\/)?[\d\w]{3}-[\d\w]{3}-[\d\w]{3})/';
		$template   = "<a href=\"$1\" target=\"_blank\">Pilos</a>";
		$text       = preg_replace($pilosREGEX, $template, $text);

		return $text;
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
		$grid       = json_decode(Helpers\Grids::getGrid($gridID), true);
		$this->grid = $grid['periods'];
	}

	/**
	 * Sets the subtitle line dedicated to the selected resources.
	 *
	 * @return void
	 */
	private function setResourceHeader()
	{
		$conditions = $this->view->conditions;
		$resources  = [];

		if (array_key_exists('personIDs', $conditions)
			and count($conditions['personIDs']) === 1
			and $person = Helpers\Persons::getName($conditions['personIDs'][0]))
		{
			$resources['person'] = $person;
		}

		if (array_key_exists('categoryIDs', $conditions)
			and count($conditions['categoryIDs']) === 1
			and $category = Helpers\Categories::getName($conditions['categoryIDs'][0]))
		{
			$resources['group'] = $category;
		}

		if (array_key_exists('groupIDs', $conditions))
		{
			if (count($conditions['groupIDs']) === 1)
			{
				$resources['group'] = Helpers\Groups::getFullName($conditions['groupIDs'][0]);
			}
			else
			{
				$groups    = [];
				$lastID    = array_pop($conditions['groupIDs']);
				$lastGroup = Helpers\Groups::getName($lastID);

				// If there was no specific category filter, the groups are not necessarily of the same category and should be ignored.
				if (array_key_exists('group', $resources))
				{
					foreach ($conditions['groupIDs'] as $groupID)
					{
						$groups[] = Helpers\Groups::getName($groupID);
					}

					$resources['group'] .= implode(', ', $groups) . " & $lastGroup";
				}
			}
		}

		if (array_key_exists('roomIDs', $conditions))
		{
			if (count($conditions['roomIDs']) === 1)
			{
				$resources['room'] = Helpers\Rooms::getName($conditions['roomIDs'][0]);
			}
			else
			{
				$rooms    = [];
				$lastID   = array_pop($conditions['roomIDs']);
				$lastRoom = Helpers\Rooms::getName($lastID);

				foreach ($conditions['roomIDs'] as $roomID)
				{
					$rooms[] = Helpers\Rooms::getName($roomID);
				}

				$resources['room'] .= implode(', ', $rooms) . " & $lastRoom";
			}
		}

		$this->resourceHeader = implode(' - ', $resources);
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
