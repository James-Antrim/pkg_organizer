<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Helpers\Input;

/**
 * Class retrieves information for a filtered set of participants.
 */
class Workload extends FormModel
{
	private const CURRENT_ITEMS = 1;

	private $bachelors = 0;

	private $doctors = 0;

	private $conditions;

	/**
	 * @var array
	 */
	public $methods;

	private $organizationID;

	private $masters = 0;

	private $personID;

	/**
	 * @var array
	 */
	public $programs;

	private $termID;

	/**
	 * Aggregates by concurrent blocks.
	 *
	 * @param   array  $units
	 *
	 * @return array
	 */
	private function aggregateByBlock(array $units): array
	{
		$count = count($units);

		for ($index = 0; $index < $count; $index++)
		{
			// Removed in a previous iteration
			if (empty($units[$index]))
			{
				continue;
			}

			$current =& $units[$index];
			$keys    = array_keys($current['blocks']);
			$method  = $current['method'];

			for ($nIndex = $index + 1; $nIndex < $count; $nIndex++)
			{
				// Removed in a previous iteration or inconsistent methods
				if (empty($units[$nIndex]) or $units[$nIndex]['method'] !== $method)
				{
					continue;
				}

				$next  = $units[$nIndex];
				$nKeys = array_keys($next['blocks']);

				// The blocks are a true subset in at least one direction
				if (empty(array_diff($keys, $nKeys)) or empty(array_diff($nKeys, $keys)))
				{
					$current['events'] = $current['events'] + $next['events'];
					unset($units[$nIndex]);
				}
			}
		}

		// Re-key after potential removal.
		return array_values($units);
	}

	/**
	 * Aggregates by event and method identity.
	 *
	 * @param   array  $items
	 *
	 * @return void
	 */
	private function aggregateByEvent(array &$items)
	{
		$count = count($items);

		for ($index = 0; $index < $count; $index++)
		{
			// Removed in a previous iteration
			if (empty($items[$index]))
			{
				continue;
			}

			$current =& $items[$index];
			$keys    = array_keys($current['events']);
			$method  = $current['method'];

			for ($nIndex = $index + 1; $nIndex < $count; $nIndex++)
			{
				// Removed in a previous iteration or inconsistent methods
				if (empty($items[$nIndex]) or $items[$nIndex]['method'] !== $method)
				{
					continue;
				}

				$next  = $items[$nIndex];
				$nKeys = array_keys($next['events']);

				// The
				if (!array_diff($keys, $nKeys) and !array_diff($nKeys, $keys))
				{
					$current['blocks'] = $current['blocks'] + $next['blocks'];
					unset($items[$nIndex]);
				}
			}
		}

		// Re-key after potential removal.
		$items = array_values($items);
	}

	private function aggregateByUnit(array $results): array
	{
		$units = [];

		// Aggregate units in first iteration
		foreach ($results as $unitID => $instance)
		{
			if ($instance['code'] === 'KOL.B')
			{
				$this->bachelors = $this->bachelors + 1;
				unset($results[$unitID]);
				continue;
			}

			if ($instance['code'] === 'KOL.M')
			{
				$this->masters = $this->masters + 1;
				unset($results[$unitID]);
				continue;
			}

			if (empty($units[$instance['unitID']]))
			{
				$units[$instance['unitID']] = [
					'blocks' => [],
					'events' => [],
					'method' => $instance['method']
				];
			}

			$unit =& $units[$instance['unitID']];

			if (empty($unit['blocks'][$instance['blockID']]))
			{
				$unit['blocks'][$instance['blockID']] = [
					'date'      => $instance['date'],
					'dow'       => $instance['dow'],
					'endTime'   => date('H:i:s', strtotime('+1 minute', strtotime($instance['endTime']))),
					'startTime' => $instance['startTime']
				];
			}

			if (empty($unit['events'][$instance['eventID']]))
			{
				$unit['events'][$instance['eventID']] = [
					'code'      => $instance['code'],
					'name'      => $instance['event'],
					'subjectNo' => $instance['subjectNo']
				];
			}
		}

		// Replace the actual unit ids which complicate iteration.
		return array_values($units);
	}

	/**
	 * @inheritDoc
	 */
	protected function authorize()
	{
		if (!Helpers\Users::getID())
		{
			Helpers\OrganizerHelper::error(401);
		}

		if (!$organizationIDs = Helpers\Can::manageTheseOrganizations())
		{
			Helpers\OrganizerHelper::error(403);
		}

		$this->organizationID = Input::getInt('organizationID', $organizationIDs[0]);
		$this->personID       = Input::getInt('personID');
		$this->termID         = Input::getInt('termID', Helpers\Terms::getCurrentID());

		$incomplete = (!$this->organizationID or !$this->personID or !$this->termID);

		if ($format = Input::getCMD('format') and $format === 'xls' and $incomplete)
		{
			Helpers\OrganizerHelper::error(400);
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = '')
	{
		$options['load_data'] = true;

		return parent::loadForm($name, $source, $options, $clear, $xpath);
	}

	/**
	 * @inheritDoc
	 */
	protected function loadFormData(): array
	{
		return ['organizationID' => $this->organizationID, 'personID' => $this->personID, 'termID' => $this->termID];
	}

	/**
	 * Turns aggregates into itemized events.
	 *
	 * @param   array  $aggregates
	 *
	 * @return array
	 */
	private function itemize(array $aggregates): array
	{
		$items = [];

		foreach ($aggregates as $aggregate)
		{
			$names      = [];
			$subjectNos = [];

			foreach ($aggregate['events'] as $event)
			{
				$names[$event['name']]      = $event['name'];
				$subjectNos[$event['name']] = $event['subjectNo'];
			}

			ksort($names);
			ksort($subjectNos);

			$eIndex = implode('-', $names) . "-{$aggregate['method']}";

			$items[$eIndex] = [
				'blocks'     => [],
				'method'     => $aggregate['method'],
				'names'      => $names,
				'subjectNos' => $subjectNos
			];

			$blocks =& $items[$eIndex]['blocks'];

			foreach ($aggregate['blocks'] as $block)
			{
				$bIndex = "{$block['dow']}-{$block['startTime']}-{$block['endTime']}";

				if (empty($blocks[$bIndex]))
				{
					$blocks[$bIndex] = [
						'dates'     => [$block['date'] => $block['date']],
						'dow'       => $block['dow'],
						'endTime'   => $block['endTime'],
						'startTime' => $block['startTime']
					];
				}
				else
				{
					$blocks[$bIndex]['dates'][$block['date']] = $block['date'];
				}
			}
		}

		return $items;
	}

	/**
	 * Builds the array of parameters used for instance retrieval.
	 *
	 * @return void
	 */
	private function setConditions()
	{
		$conditions               = [];
		$conditions['date']       = Helpers\Terms::getStartDate($this->termID);
		$conditions['delta']      = false;
		$conditions['endDate']    = Helpers\Terms::getEndDate($this->termID);
		$conditions['interval']   = 'term';
		$conditions['my']         = false;
		$conditions['mySchedule'] = false;
		$conditions['personIDs']  = [$this->personID];
		$conditions['startDate']  = $conditions['date'];
		$conditions['status']     = self::CURRENT_ITEMS;

		$this->conditions = $conditions;
	}

	/**
	 * Sets program data.
	 *
	 * @return void
	 */
	private function setMethods()
	{
		$tag   = Helpers\Languages::getTag();
		$query = Database::getQuery();
		$query->select("code, name_$tag AS method")->from('#__organizer_methods')->where('relevant = 1');
		Database::setQuery($query);

		$methods = [];

		foreach (Database::loadAssocList() as $method)
		{
			$methods[$method['code']] = $method['method'];
		}

		ksort($methods);

		$this->methods = $methods;
	}

	private function calculate()
	{
		$conditions = $this->conditions;
		$tag        = Helpers\Languages::getTag();
		$query      = Helpers\Instances::getInstanceQuery($this->conditions);
		$query->select('DISTINCT i.id AS instanceID, u.id AS unitID')
			->select('b.id AS blockID, b.date, b.dow, b.startTime, b.endTime')
			->select("e.id AS eventID, e.code, e.name_$tag AS event, e.subjectNo")
			->select('m.code AS method')
			->innerJoin('#__organizer_events AS e ON e.id = i.eventID')
			->innerJoin('#__organizer_methods AS m ON m.id = i.methodID')
			->where("b.date BETWEEN '{$conditions['startDate']}' AND '{$conditions['endDate']}'")
			->order('b.date, b.startTime, b.endTime')
			->where('ipe.roleID = 1')
			->where('m.relevant = 1');
		Database::setQuery($query);
		$instances = Database::loadAssocList();

		/*$query = Helpers\Instances::getInstanceQuery($this->conditions);
		$query->select('i.id AS instanceID')
			->select(group stuff)
			->where("b.date BETWEEN '{$conditions['startDate']}' AND '{$conditions['endDate']}'")
			->order('b.date, b.startTime, b.endTime')
			->where('ipe.roleID = 1')
			->where('m.relevant = 1');

		echo "<pre>" . print_r((string) $query, true) . "</pre><br>";
		die;*/
		$units      = $this->aggregateByUnit($instances);
		$aggregates = $this->aggregateByBlock($units);
		$this->aggregateByEvent($aggregates);
		$items = $this->itemize($aggregates);
		$this->structureOutliers($items);
		$this->structureRepeaters($items);
	}

	/**
	 * Set dynamic data.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->authorize();
		$this->setPrograms();
		$this->setMethods();
		$this->setConditions();
		$this->calculate();
	}

	/**
	 * Sets program data.
	 *
	 * @return void
	 */
	private function setPrograms()
	{
		$tag   = Helpers\Languages::getTag();
		$query = Database::getQuery();
		$query->select("p.id, categoryID, p.degreeID, p.name_$tag AS program, fee, frequencyID, nc, special")
			->select('d.abbreviation AS degree')
			->from('#__organizer_programs AS p')
			->innerJoin('#__organizer_degrees AS d ON d.id = p.degreeID')
			->where('active = 1');
		Database::setQuery($query);

		$results = Database::loadAssocList();

		$programs = [];

		foreach ($results as &$program)
		{
			$organizationIDs = Helpers\Programs::getOrganizationIDs($program['id']);

			foreach (array_keys($organizations = array_flip($organizationIDs)) as $organizationID)
			{
				$organizations[$organizationID] = Helpers\Organizations::getShortName($organizationID);
			}

			asort($organizations);
			$program['organizations'] = $organizations;
			$index                    = "{$program['program']} ({$program['degree']})";
			$programs[$index]         = $program;
		}

		ksort($programs);

		$this->programs = $programs;
	}

	/**
	 * Turns outlying event items into block event items.
	 *
	 * @param   array  $items
	 *
	 * @return void
	 */
	private function structureOutliers(array &$items)
	{
		foreach ($items as $eIndex => $item)
		{
			$dates                     = [];
			$items[$eIndex]['items']   = [];
			$items[$eIndex]['minutes'] = 0;

			foreach ($item['blocks'] as $block)
			{
				// Arbitrary cutoff for number of repetitions for a limited event
				if (count($block['dates']) < 3)
				{
					foreach ($block['dates'] as $date)
					{
						$dates[$date] = ['endDate' => $date, 'minutes' => 0, 'startDate' => $date];
					}
				}
			}

			foreach (array_keys($dates) as $date)
			{
				foreach ($item['blocks'] as $bIndex => $block)
				{
					if (empty($block['dates'][$date]))
					{
						continue;
					}

					$dates[$date]['minutes'] += ceil((strtotime($block['endTime']) - strtotime($block['startTime'])) / 60);
					unset($items[$eIndex]['blocks'][$bIndex]['dates'][$date]);

					if (empty($items[$eIndex]['blocks'][$bIndex]['dates']))
					{
						unset($items[$eIndex]['blocks'][$bIndex]);
					}
				}
			}

			if ($dates)
			{
				foreach ($dates as $date => $data)
				{
					if (empty($dates[$date]))
					{
						continue;
					}

					$endDate  = $data['endDate'];
					$tomorrow = date('Y-m-d', strtotime('+1 Day', strtotime($endDate)));

					while (!empty($dates[$tomorrow]))
					{
						$data['endDate'] = $tomorrow;
						$data['minutes'] += $dates[$tomorrow]['minutes'];
						unset($dates[$tomorrow]);
						$tomorrow = date('Y-m-d', strtotime('+1 Day', strtotime($tomorrow)));
					}
				}

				foreach ($dates as $date => $data)
				{
					$endDate                   = Helpers\Dates::formatDate($data['endDate']);
					$startDate                 = Helpers\Dates::formatDate($data['startDate']);
					$items[$eIndex]['minutes'] += $data['minutes'];
					$dateDisplay               = $startDate !== $endDate ? "$startDate - $endDate" : $startDate;
					$hoursDisplay              = ceil($data['minutes'] / 45) . ' ' . Helpers\Languages::_('ORGANIZER_HOURS_ABBR');
					$items[$eIndex]['items'][] = "$dateDisplay $hoursDisplay";
				}
			}
		}
	}

	/**
	 * Turns outlying event items into block event items.
	 *
	 * @param   array  $items
	 *
	 * @return void
	 */
	private function structureRepeaters(array &$items)
	{
		foreach ($items as $eIndex => $item)
		{
			foreach ($item['blocks'] as $bIndex => $block)
			{
				$length      = ceil((strtotime($block['endTime']) - strtotime($block['startTime'])) / 60);
				$repetitions = count($block['dates']);
				$minutes     = $length * $repetitions;

				$items[$eIndex]['minutes'] += $minutes;

				$suffix       = strtoupper(date('l', strtotime("Sunday +{$block['dow']} days")));
				$day          = Helpers\Languages::_("ORGANIZER_$suffix");
				$hoursDisplay = ceil($minutes / 45) . ' ' . Helpers\Languages::_('ORGANIZER_HOURS_ABBR');
				$endTime      = Helpers\Dates::formatTime($block['endTime']);
				$startTime    = Helpers\Dates::formatTime($block['startTime']);

				$items[$eIndex]['items'][] = "$day $startTime-$endTime $hoursDisplay";
			}

			unset($items[$eIndex]['blocks']);
		}
	}
}
