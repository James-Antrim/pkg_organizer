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
use Organizer\Views\HTML\Statistics as View;
use ParagonIE\Sodium\Core\Curve25519\H;

/**
 * Class calculates lesson statistics and loads them into the view context.
 */
class Statistics extends FormModel
{
	/**
	 * Authorizes the user.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		//Public access
	}

	/**
	 * Populates the grid with method use data.
	 *
	 * @param   array  &$grid       the structure of the data as it will be presented
	 * @param   array   $instances  the raw data for the appointments
	 *
	 * @return void
	 */
	private function fillMethodUse(array &$grid, array $instances)
	{
		$state          = $this->state;
		$categoryID     = $state->get('conditions.categoryID');
		$columnKeys     = array_keys($grid['headers']);
		$organizationID = $state->get('conditions.organizationID');
		$rowKeys        = array_keys($grid);
		$usedMethods    = [];
		$usedResources  = [];

		foreach ($instances as $instance)
		{
			$instanceID = $instance['instanceID'];
			$methodID   = $instance['methodID'];

			if (!in_array($methodID, $rowKeys))
			{
				continue;
			}

			if ($categoryID)
			{
				$resourceIDs = Helpers\Instances::getGroupIDs($instanceID);
			}
			elseif ($organizationID)
			{
				$resourceIDs = Helpers\Instances::getCategoryIDs($instanceID);
			}
			else
			{
				$resourceIDs = Helpers\Instances::getOrganizationIDs($instanceID);
			}

			if ($columnIDs = array_intersect($columnKeys, $resourceIDs))
			{
				$grid['sum']['sum']     = $grid['sum']['sum'] + 1;
				$grid[$methodID]['sum'] = $grid[$methodID]['sum'] + 1;
				$usedMethods[$methodID] = $methodID;

				foreach ($columnIDs as $columnID)
				{
					$grid['sum'][$columnID]     = $grid['sum'][$columnID] + 1;
					$grid[$methodID][$columnID] = $grid[$methodID][$columnID] + 1;
					$usedResources[$columnID]   = $columnID;
				}
			}
		}

		// Unused Rows
		foreach (array_diff($rowKeys, $usedMethods) as $unusedRowKey)
		{
			if (is_numeric($unusedRowKey))
			{
				unset($grid[$unusedRowKey]);
			}
		}

		// Unused columns
		foreach ($unusedColumnKeys = array_diff($columnKeys, $usedResources) as $key => $value)
		{
			if (!is_numeric($value))
			{
				unset($unusedColumnKeys[$key]);
			}
		}

		foreach (array_keys($grid) as $rowKey)
		{
			foreach ($unusedColumnKeys as $unusedColumnKey)
			{
				unset($grid[$rowKey][$unusedColumnKey]);
			}
		}
	}

	/**
	 * Populates the grid with in-person participation data.
	 *
	 * @param   array  &$grid       the structure of the data as it will be presented
	 * @param   array   $instances  the raw data for the appointments
	 *
	 * @return void
	 */
	private function fillPresenceUse(array &$grid, array $instances)
	{
		$state          = $this->state;
		$categoryID     = $state->get('conditions.categoryID');
		$columnKeys     = array_keys($grid['headers']);
		$organizationID = $state->get('conditions.organizationID');
		$usedKeys       = [];
		$usedMondays    = [];
		$usedResources  = [];

		foreach ($instances as $instance)
		{
			$instanceID = $instance['instanceID'];
			$presence   = Helpers\Instances::getPresence($instanceID);

			if ($presence === Helpers\Instances::ONLINE)
			{
				continue;
			}

			$attended  = Helpers\Instances::getAttended($instanceID);
			$capacity  = Helpers\Instances::getCapacity($instanceID);
			$monday    = date('Y-m-d', strtotime('monday this week', strtotime($instance['date'])));
			$uniqueKey = "{$instance['unitID']}-{$instance['blockID']}";
			$upTCap    = false;

			if (empty($usedKeys[$uniqueKey]))
			{
				$upTCap               = true;
				$usedKeys[$uniqueKey] = 1;

				if ($attended)
				{
					$usedMondays[$monday] = $monday;
				}
			}

			if ($categoryID)
			{
				$resourceIDs = Helpers\Instances::getGroupIDs($instanceID);
			}
			elseif ($organizationID)
			{
				$resourceIDs = Helpers\Instances::getCategoryIDs($instanceID);
			}
			else
			{
				$resourceIDs = Helpers\Instances::getOrganizationIDs($instanceID);
			}

			if ($columnIDs = array_intersect($columnKeys, $resourceIDs))
			{
				if ($capacity)
				{

					$grid['sum']['sum']['attended']   = $grid['sum']['sum']['attended'] + $attended;
					$grid[$monday]['sum']['attended'] = $grid[$monday]['sum']['attended'] + $attended;

					if ($upTCap)
					{
						$grid['sum']['sum']['capacity']   = $grid['sum']['sum']['capacity'] + $capacity;
						$grid[$monday]['sum']['capacity'] = $grid[$monday]['sum']['capacity'] + $capacity;
					}
				}

				$grid['sum']['sum']['total']   = $grid['sum']['sum']['total'] + $attended;
				$grid[$monday]['sum']['total'] = $grid[$monday]['sum']['total'] + $attended;

				foreach ($columnIDs as $columnID)
				{
					if ($capacity)
					{
						$usedResources[$columnID] = $columnID;

						$grid['sum'][$columnID]['attended']   = $grid['sum'][$columnID]['attended'] + $attended;
						$grid[$monday][$columnID]['attended'] = $grid[$monday][$columnID]['attended'] + $attended;

						if ($upTCap)
						{
							$grid['sum'][$columnID]['capacity']   = $grid['sum'][$columnID]['capacity'] + $capacity;
							$grid[$monday][$columnID]['capacity'] = $grid[$monday][$columnID]['capacity'] + $capacity;
						}
					}

					$grid['sum'][$columnID]['total']   = $grid['sum'][$columnID]['total'] + $attended;
					$grid[$monday][$columnID]['total'] = $grid[$monday][$columnID]['total'] + $attended;

				}
			}
		}

		// Unused Rows
		foreach (array_diff(array_keys($grid), $usedMondays) as $unusedMonday)
		{
			if (in_array($unusedMonday, ['headers', 'sum']))
			{
				continue;
			}

			unset($grid[$unusedMonday]);
		}

		// Unused columns
		foreach ($unusedResources = array_diff($columnKeys, $usedResources) as $key => $value)
		{
			if (in_array($value, ['week', 'sum']))
			{
				unset($unusedResources[$key]);
			}
		}

		foreach (array_keys($grid) as $monday)
		{
			foreach ($unusedResources as $unusedResource)
			{
				unset($grid[$monday][$unusedResource]);
			}
		}
	}

	/**
	 * Populates the grid with presence type use data.
	 *
	 * @param   array  &$grid
	 * @param   array   $instances
	 *
	 * @return void
	 */
	private function fillTypeUse(array &$grid, array $instances)
	{
		$state          = $this->state;
		$categoryID     = $state->get('conditions.categoryID');
		$columnKeys     = array_keys($grid['headers']);
		$organizationID = $state->get('conditions.organizationID');
		$usedMondays    = [];
		$usedResources  = [];

		foreach ($instances as $instance)
		{
			$instanceID = $instance['instanceID'];
			$presence   = Helpers\Instances::getPresence($instanceID);
			$monday     = date('Y-m-d', strtotime('monday this week', strtotime($instance['date'])));

			if ($categoryID)
			{
				$resourceIDs = Helpers\Instances::getGroupIDs($instanceID);
			}
			elseif ($organizationID)
			{
				$resourceIDs = Helpers\Instances::getCategoryIDs($instanceID);
			}
			else
			{
				$resourceIDs = Helpers\Instances::getOrganizationIDs($instanceID);
			}

			if ($columnIDs = array_intersect($columnKeys, $resourceIDs))
			{
				$grid['sum']['sum'][$presence]   = $grid['sum']['sum'][$presence] + 1;
				$grid['sum']['sum']['total']     = $grid['sum']['sum']['total'] + 1;
				$grid[$monday]['sum'][$presence] = $grid[$monday]['sum'][$presence] + 1;
				$grid[$monday]['sum']['total']   = $grid[$monday]['sum']['total'] + 1;

				$usedMondays[$monday] = $monday;

				foreach ($columnIDs as $columnID)
				{
					$grid['sum'][$columnID][$presence]   = $grid['sum'][$columnID][$presence] + 1;
					$grid['sum'][$columnID]['total']     = $grid['sum'][$columnID]['total'] + 1;
					$grid[$monday][$columnID][$presence] = $grid[$monday][$columnID][$presence] + 1;
					$grid[$monday][$columnID]['total']   = $grid[$monday][$columnID]['total'] + 1;

					$usedResources[$columnID] = $columnID;
				}
			}
		}

		// Unused Rows
		foreach (array_diff(array_keys($grid), $usedMondays) as $unusedMonday)
		{
			if (in_array($unusedMonday, ['headers', 'sum']))
			{
				continue;
			}

			unset($grid[$unusedMonday]);
		}

		// Unused columns
		foreach ($unusedResources = array_diff($columnKeys, $usedResources) as $key => $value)
		{
			if (in_array($value, ['week', 'sum']))
			{
				unset($unusedResources[$key]);
			}
		}

		foreach (array_keys($grid) as $monday)
		{
			foreach ($unusedResources as $unusedResource)
			{
				unset($grid[$monday][$unusedResource]);
			}
		}
	}

	/**
	 * Retrieves the raw data from the database.
	 *
	 * @return array
	 */
	public function getGrid(): array
	{
		if (!$statistic = $this->state->get('conditions.statistic'))
		{
			return [];
		}

		$grid      = $this->getStructure();
		$instances = $this->getInstances();

		switch ($statistic)
		{
			case View::METHOD_USE:
				$this->fillMethodUse($grid, $instances);
				break;
			case View::PLANNED_PRESENCE_TYPE:
				$this->fillTypeUse($grid, $instances);
				break;
			case View::PRESENCE_USE:
				$this->fillPresenceUse($grid, $instances);
				break;
			default:
				break;
		}

		return $grid;
	}

	/**
	 * Gets relevant instances from the database.
	 *
	 * @return array
	 */
	public function getInstances(): array
	{
		$state  = $this->state;
		$termID = $state->get('conditions.termID');
		$query  = Database::getQuery();
		$query->from('#__organizer_instances AS i')
			->select('DISTINCT i.id AS instanceID, i.*')
			->where("i.delta != 'removed'")
			->where("i.methodID IS NOT NULL")
			->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
			->select('b.date')
			->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
			->where("u.delta != 'removed'")
			->where("u.termID = $termID");

		$categoryID     = $state->get('conditions.categoryID');
		$organizationID = $state->get('conditions.organizationID');

		if ($categoryID or $organizationID)
		{
			$query->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
				->where("ip.delta != 'removed'")
				->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ip.id')
				->where("ig.delta != 'removed'");

			if ($categoryID)
			{
				$query->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID')
					->where("g.categoryID = $categoryID");
			}

			if ($organizationID)
			{
				$query->innerJoin('#__organizer_associations AS a ON a.groupID = ig.groupID')
					->where("a.organizationID = $organizationID");
			}
		}

		$statistic = $state->get('conditions.statistic');

		if ($statistic === View::PRESENCE_USE)
		{
			$today = date('Y-m-d');
			$now   = date('H:i:s');
			$query->where("(b.date < '$today' OR (b.date = '$today' and b.endTime < '$now'))");
		}

		Database::setQuery($query);

		return Database::loadAssocList();
	}

	/**
	 * Generates a grid to organize the instance data.
	 *
	 * @return array
	 */
	private function getStructure(): array
	{
		$columns = [];
		$state   = $this->state;

		if ($categoryID = $state->get('conditions.categoryID'))
		{
			foreach (Helpers\Categories::getGroups($categoryID) as $group)
			{
				$columns[$group['id']] = $group['name'];
			}
		}
		elseif ($organizationID = $state->get('conditions.organizationID'))
		{
			foreach (Helpers\Organizations::getCategories($organizationID) as $category)
			{
				$columns[$category['id']] = $category['name'];
			}
		}
		else
		{
			foreach (Helpers\Organizations::getResources() as $organization)
			{
				if (!$organization['active'])
				{
					continue;
				}

				$columns[$organization['id']] = $organization['shortName'];
			}
		}

		asort($columns);

		$grid      = [];
		$statistic = (int) $state->get('conditions.statistic');

		if ($statistic === View::METHOD_USE)
		{
			$grid['headers'] = [
				'method' => Helpers\Languages::_('ORGANIZER_METHOD'),
				'sum'    => Helpers\Languages::_('ORGANIZER_SUM')
			];
			foreach ($columns as $columnID => $columnName)
			{
				$grid['headers'][$columnID] = $columnName;
			}

			$columnIDs   = array_keys($grid['headers']);
			$grid['sum'] = [];

			foreach ($columnIDs as $columnID)
			{
				if ($columnID === 'method')
				{
					$grid['sum'][$columnID] = Helpers\Languages::_('ORGANIZER_SUM');
					continue;
				}

				$grid['sum'][$columnID] = 0;
			}

			foreach (Helpers\Methods::getResources() as $method)
			{
				$methodID        = $method['id'];
				$grid[$methodID] = [];

				foreach ($columnIDs as $columnID)
				{
					if ($columnID === 'method')
					{
						$grid[$methodID][$columnID] = $method['name'];
						continue;
					}

					$grid[$methodID][$columnID] = 0;
				}
			}
		}
		else
		{
			$grid['headers'] = [
				'week' => Helpers\Languages::_('ORGANIZER_WEEK'),
				'sum'  => $statistic === View::PRESENCE_USE ?
					Helpers\Languages::_('ORGANIZER_AVERAGE') : Helpers\Languages::_('ORGANIZER_SUM')
			];
			foreach ($columns as $columnID => $columnName)
			{
				$grid['headers'][$columnID] = $columnName;
			}

			$template = [];

			if ($statistic === View::PLANNED_PRESENCE_TYPE)
			{
				$template = [
					Helpers\Instances::HYBRID   => 0,
					Helpers\Instances::ONLINE   => 0,
					Helpers\Instances::PRESENCE => 0,
					'total'                     => 0
				];
			}
			elseif ($statistic === View::PRESENCE_USE)
			{
				$template = [
					'attended' => 0,
					'capacity' => 0,
					'total'    => 0
				];
			}

			$columnIDs = array_keys($grid['headers']);

			foreach ($columnIDs as $columnID)
			{
				if ($columnID === 'week')
				{
					$grid['sum'][$columnID] = $statistic === View::PRESENCE_USE ?
						Helpers\Languages::_('ORGANIZER_AVERAGE') : Helpers\Languages::_('ORGANIZER_SUM');
					continue;
				}

				$grid['sum'][$columnID] = $template;
			}

			$termID    = $state->get('conditions.termID');
			$startDate = Helpers\Terms::getStartDate($termID);
			$endDate   = Helpers\Terms::getEndDate($termID);

			for ($current = $startDate; $current < $endDate;)
			{
				$weekEndDate    = date('Y-m-d', strtotime('+7 days', strtotime($current)));
				$grid[$current] = [];

				foreach ($columnIDs as $columnID)
				{
					if ($columnID === 'week')
					{
						$grid[$current][$columnID] = Helpers\Dates::formatDate($current);
						continue;
					}

					$grid[$current][$columnID] = $template;
				}

				$current = $weekEndDate;
			}
		}

		return $grid;
	}

	/**
	 * @inheritDoc
	 */
	public function getForm($data = [], $loadData = false)
	{
		$this->authorize();

		$name = $this->get('name');
		$form = $this->loadForm($this->context, $name, ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form))
		{
			return false;
		}

		$state = $this->state;

		$form->setValue('termID', null, $state->get('conditions.termID'));

		if ($statistic = $state->get('conditions.statistic'))
		{
			$form->setValue('statistic', null, $statistic);

			$organizationID = $state->get('conditions.organizationID');
			$categoryID     = $state->get('conditions.categoryID');

			if ($categoryID)
			{
				$organizationIDs = Helpers\Categories::getOrganizationIDs($categoryID);

				if ($organizationID)
				{
					$form->setValue('organizationID', null, $organizationID);

					if (in_array($organizationID, $organizationIDs))
					{
						$form->setValue('categoryID', null, $categoryID);
					}
					else
					{
						$state->set('conditions.categoryID', 0);
					}
				}
				else
				{
					$form->setValue('categoryID', null, $categoryID);

					if (count($organizationIDs) === 1)
					{
						$organizationID = reset($organizationIDs);
						$form->setValue('organizationID', null, $organizationID);
					}
				}
			}
			elseif ($organizationID)
			{
				$state->set('conditions.categoryID', 0);
				$form->setValue('organizationID', null, $organizationID);
			}
		}
		else
		{
			$state->set('conditions.categoryID', 0);
			$state->set('conditions.organizationID', 0);
		}

		return $form;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @return void
	 */
	protected function populateState()
	{
		$app        = Helpers\OrganizerHelper::getApplication();
		$conditions = $app->getUserStateFromRequest($this->context . '.conditions', 'jform', [], 'array');
		foreach ($conditions as $input => $value)
		{
			$this->setState("conditions.$input", $value);
		}

		if (!$this->state->get('conditions.termID'))
		{
			$termID = Helpers\Terms::getCurrentID();
			$this->setState("conditions.termID", $termID);
		}
	}
}
