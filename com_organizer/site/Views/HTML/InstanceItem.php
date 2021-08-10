<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Toolbar\Button\StandardButton;
use Joomla\CMS\Uri\Uri;
use Organizer\Adapters\Document;
use Organizer\Adapters\Toolbar;
use Organizer\Helpers;
use Organizer\Helpers\Languages;

/**
 * Class loads information about a given instance.
 */
class InstanceItem extends ListView
{
	use ListsInstances;

	/**
	 * @var object the data for the instance
	 */
	public $instance;

	protected $layout = 'instance-item';

	private $manages = false;

	private $messages = [];

	protected $rowStructure = [
		'checkbox' => '',
		'date'     => 'value',
		'time'     => 'value',
		'persons'  => 'value',
		'rooms'    => 'value'
	];

	private $statusDate;

	private $teaches = false;

	private $userID;

	/**
	 * @inheritDoc
	 */
	protected function addSupplement()
	{
		$text = '';

		if ($this->messages)
		{
			$text = '<div class="tbox-blue">' . implode('<br>', $this->messages) . '</div>';
		}

		$this->supplement = $text;
	}

	/**
	 * @inheritdoc
	 */
	protected function addToolBar()
	{
		$instance = $this->instance;
		$method   = $instance->method ? " - $instance->method" : '';
		Helpers\HTML::setTitle($instance->name . $method, 'square');
		$this->setSubtitle();

		$toolbar = Toolbar::getInstance();

		if ($this->userID and $this->items)
		{
			$day           = Languages::_(strtoupper(date('D', strtotime($instance->date)))) . '.';
			$deRegThis     = false;
			$deRegBlock    = false;
			$deRegSelected = false;
			$deRegAll      = false;
			$regThis       = false;
			$regBlock      = false;
			$regSelected   = false;
			$regAll        = false;
			$thisDOW       = strtoupper(date('l', strtotime($instance->date)));

			foreach ($this->getModel()->getItems() as $item)
			{
				if ($item->participates)
				{
					$deRegAll      = true;
					$deRegSelected = true;
				}
				else
				{
					$regAll      = true;
					$regSelected = true;
				}

				$sameDOW = (strtoupper(date('l', strtotime($item->date))) === $thisDOW);
				$sameET  = $item->startTime === $instance->startTime;
				$sameST  = $item->startTime === $instance->startTime;

				$sameBlock = ($sameDOW and $sameET and $sameST);
				if ($sameBlock)
				{
					$identity = $item->instanceID === $instance->instanceID;

					if ($item->participates)
					{
						$deRegBlock = true;

						if ($identity)
						{
							$deRegThis = true;
						}
					}
					else
					{
						$regBlock = true;

						if ($identity)
						{
							$regThis = true;
						}
					}
				}
			}

			$registrations = [];

			if ($regThis)
			{
				$regThis         = new StandardButton();
				$registrations[] = $regThis->fetchButton(
					'Standard',
					'square',
					Languages::_('ORGANIZER_THIS_INSTANCE'),
					'InstanceParticipants.register',
					false
				);
			}

			if ($regBlock)
			{
				$regBlock        = new StandardButton();
				$registrations[] = $regBlock->fetchButton(
					'Standard',
					'menu',
					sprintf(Languages::_('ORGANIZER_BLOCK_INSTANCES'), $day, $instance->startTime, $instance->endTime),
					'InstanceParticipants.registerBlock',
					false
				);
			}

			if ($regSelected)
			{
				$regSelected     = new StandardButton();
				$registrations[] = $regSelected->fetchButton(
					'Standard',
					'checkbox',
					Languages::_('ORGANIZER_SELECTED_INSTANCES'),
					'InstanceParticipants.registerSelected',
					true
				);
			}

			if ($regAll)
			{
				$regAll          = new StandardButton();
				$registrations[] = $regAll->fetchButton(
					'Standard',
					'grid-2',
					Languages::_('ORGANIZER_ALL_INSTANCES'),
					'InstanceParticipants.registerAll',
					false
				);
			}

			if ($registrations)
			{
				$toolbar->appendButton('Buttons', 'buttons', Languages::_('ORGANIZER_REGISTER'), $registrations, 'enter');
			}

			$deregistrations = [];

			if ($deRegThis)
			{
				$deRegThis         = new StandardButton();
				$deregistrations[] = $deRegThis->fetchButton(
					'Standard',
					'square',
					Languages::_('ORGANIZER_THIS_INSTANCE'),
					'InstanceParticipants.deregister',
					false
				);
			}

			if ($deRegBlock)
			{
				$deRegBlock        = new StandardButton();
				$deregistrations[] = $deRegBlock->fetchButton(
					'Standard',
					'menu',
					sprintf(Languages::_('ORGANIZER_BLOCK_INSTANCES'), $day, $instance->startTime, $instance->endTime),
					'InstanceParticipants.deregisterBlock',
					false
				);
			}

			if ($deRegSelected)
			{
				$deRegSelected     = new StandardButton();
				$deregistrations[] = $deRegSelected->fetchButton(
					'Standard',
					'checkbox',
					Languages::_('ORGANIZER_SELECTED_INSTANCES'),
					'InstanceParticipants.deregisterSelected',
					true
				);
			}

			if ($deRegAll)
			{
				$deRegAll          = new StandardButton();
				$deregistrations[] = $deRegAll->fetchButton(
					'Standard',
					'grid-2',
					Languages::_('ORGANIZER_ALL_INSTANCES'),
					'InstanceParticipants.deregisterAll',
					false
				);
			}

			if ($deregistrations)
			{
				$toolbar->appendButton('Buttons', 'buttons', Languages::_('ORGANIZER_DEREGISTER'), $deregistrations, 'exit');
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function authorize()
	{
		if (!$instanceID = Helpers\Input::getID())
		{
			Helpers\OrganizerHelper::error(400);
		}

		$iOrganizationIDs = Helpers\Instances::getOrganizationIDs($instanceID);
		$mOrganizationIDs = Helpers\Can::manageTheseOrganizations();
		$this->manages    = (bool) array_intersect($iOrganizationIDs, $mOrganizationIDs);
		$this->teaches    = Helpers\Instances::teaches();
		$this->userID     = Helpers\Users::getID();
	}

	/**
	 * @inheritDoc
	 */
	public function display($tpl = null)
	{
		$this->setInstance($this->getModel()->instance);

		parent::display($tpl);
	}

	/**
	 * @inheritDoc
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();
		Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/item.css');
	}

	/**
	 * Renders the persons section of the item.
	 *
	 * @return void
	 */
	public function renderPersons()
	{
		$instance   = $this->instance;
		$showGroups = $instance->showGroups;
		$showRoles  = $instance->showRoles;
		$showRooms  = $instance->showRooms;

		echo '<div class="attribute-item">';
		echo '<div class="attribute-label">' . Languages::_('ORGANIZER_PERSONS') . '</div>';
		echo '<div class="attribute-content"><ul>';

		foreach ($instance->persons as $persons)
		{
			if ($showRoles)
			{
				$personIDs = array_keys($persons);
				$firstID   = reset($personIDs);
				echo '<u>' . $instance->resources[$firstID]['role'] . '</u><ul>';
			}

			foreach (array_keys($persons) as $personID)
			{
				$person = $instance->resources[$personID];
				echo '<li>';
				$this->renderResource($person['person'], $person['status'], $person['statusDate']);

				if ($instance->showGroups or $instance->showRooms)
				{
					echo '<ul>';
				}

				if ($instance->showGroups and !empty($person['groups']))
				{
					echo '<li>' . Languages::_('ORGANIZER_GROUPS') . '<ul>';
					foreach ($person['groups'] as $group)
					{
						echo '<li>';
						$name = (strlen($group['fullName']) > 80 and $group['status']) ?
							$group['group'] : $group['fullName'];
						$this->renderResource($name, $group['status'], $group['statusDate']);
						echo '</li>';
					}
					echo '</ul></li>';
				}

				if ($instance->showRooms and !empty($person['rooms']))
				{
					echo '<li>' . Languages::_('ORGANIZER_ROOMS') . '<ul>';
					foreach ($person['rooms'] as $room)
					{
						echo '<li>';
						$this->renderResource($room['room'], $room['status'], $room['statusDate']);
						echo '</li>';
					}
					echo '</ul></li>';
				}

				if ($instance->showGroups or $instance->showRooms)
				{
					echo '</ul>';
				}

				echo '</li>';
			}

			if ($showRoles)
			{
				echo '</ul>';
			}
		}

		echo '</ul></div></div>';
	}

	/**
	 * Renders the individual resource output.
	 *
	 * @param   string  $name      the resource name
	 * @param   string  $status    the resource's status
	 * @param   string  $dateTime  the date time of the resource's last status update
	 *
	 * @return void
	 */
	private function renderResource(string $name, string $status, string $dateTime)
	{
		if (!$status or $dateTime < $this->statusDate)
		{
			echo $name;

			return;
		}

		$dateTime = Helpers\Dates::formatDateTime($dateTime);
		$delta    = $status === 'removed' ?
			sprintf(Languages::_('ORGANIZER_REMOVED_ON'), $dateTime) : sprintf(Languages::_('ORGANIZER_ADDED_ON'), $dateTime);

		echo "<span class=\"$status\">$name</span> $delta";
	}

	/**
	 * Renders the persons section of the item.
	 *
	 * @return void
	 */
	public function renderResources(string $label, array $resources)
	{
		echo '<div class="attribute-item">';
		echo "<div class=\"attribute-label\">$label</div>";
		echo '<div class="attribute-content"><ul>';

		foreach ($resources as $name => $data)
		{
			echo '<li>';
			$this->renderResource($name, $data['status'], $data['date']);
			echo '</li>';
		}

		echo '</ul></div></div>';
	}

	/**
	 * @inheritdoc
	 */
	public function setHeaders()
	{
		$this->headers = [
			'checkbox' => Helpers\HTML::_('grid.checkall'),
			'status'   => '',
			'times'    => Languages::_('ORGANIZER_DATETIME'),
			'persons'  => Languages::_('ORGANIZER_PERSONS'),
			'groups'   => Languages::_('ORGANIZER_GROUPS'),
			'rooms'    => Languages::_('ORGANIZER_ROOMS')
		];

		if (!$this->userID)
		{
			unset($this->headers['checkbox']);
		}
	}

	/**
	 * Processes the instance to aid in simplifying/supplementing the item display.
	 *
	 * @param   object  $instance  the instance data
	 *
	 * @return void
	 */
	private function setInstance(object $instance)
	{
		$this->statusDate = date('Y-m-d 00:00:00', strtotime('-14 days'));
		$cutOff           = $this->statusDate;

		$message = '';
		$status  = '';

		$dateTime   = $instance->unitStatusDate;
		$dtRelevant = ($instance->unitStatus and $dateTime >= $cutOff);
		$modified   = $instance->unitStatusDate;

		// Set unit baseline for process dating.
		if ($dtRelevant)
		{
			$constant   = $instance->unitStatus === 'removed' ? 'ORGANIZER_UNIT_REMOVED_ON' : 'ORGANIZER_UNIT_ADDED_ON';
			$status     = $instance->unitStatus;
			$statusDate = Helpers\Dates::formatDateTime($instance->unitStatusDate);
			$message    = sprintf(Languages::_($constant), $statusDate);
		}

		$dateTime = $instance->instanceStatusDate;

		if ($instance->instanceStatus and $dateTime >= $cutOff)
		{
			$earlier    = $instance->instanceStatusDate < $instance->unitStatusDate;
			$later      = $instance->instanceStatusDate > $instance->unitStatusDate;
			$modified   = $dateTime > $modified ? $dateTime : $modified;
			$statusDate = Helpers\Dates::formatDateTime($instance->instanceStatusDate);

			// Instance was removed...
			if ($instance->instanceStatus === 'removed')
			{
				$text = Languages::_('ORGANIZER_INSTANCE_REMOVED_ON');

				// ...before the unit was removed.
				if ($status === 'removed' and $earlier)
				{
					$message = sprintf($text, $statusDate);
				}
				// ...and the unit was not.
				elseif ($status !== 'removed' and $later)
				{
					$message = sprintf($text, $statusDate);
				}
			}
			// Instance was recently added
			elseif ($status !== 'removed' and $instance->instanceStatus === 'new')
			{
				$message = sprintf(Languages::_('ORGANIZER_INSTANCE_ADDED_ON'), $statusDate);
			}
		}

		$persons = [];

		// Aggregate resource containers.
		$groups = [];
		$rooms  = [];

		// Containers for unique resource configurations.
		$uniqueGroups = [];
		$uniqueRooms  = [];

		if ($instance->resources)
		{
			foreach ($instance->resources as $personID => $person)
			{
				$dateTime   = $person['statusDate'];
				$dtRelevant = ($person['status'] and $dateTime >= $cutOff);

				// Removed before cut off
				if (!$dtRelevant and $person['status'] === 'removed')
				{
					unset($instance->resources[$personID]);
					continue;
				}

				$filteredGroups = [];
				$filteredRooms  = [];
				$modified       = $dateTime > $modified ? $dateTime : $modified;

				if (empty($persons[$person['roleID']]))
				{
					$persons[$person['roleID']] = [];
				}

				$persons[$person['roleID']][$personID] = $person['person'];

				if (!empty($person['groups']))
				{
					foreach ($person['groups'] as $groupID => $group)
					{
						$dateTime   = $group['statusDate'];
						$dtRelevant = ($group['status'] and $dateTime >= $cutOff);

						// Removed before cut off
						if (!$dtRelevant and $group['status'] === 'removed')
						{
							unset($instance->resources[$personID]['groups'][$groupID]);
							continue;
						}

						$name = $group['fullName'];
						if (empty($groups[$name]) or $dateTime > $groups[$name]['date'])
						{
							$groups[$name] = [
								'date'   => $modified,
								'status' => $group['status']
							];
						}

						$modified = $dateTime > $modified ? $dateTime : $modified;

						$copy = $group;
						unset($copy['status'], $copy['statusDate']);
						$filteredGroups[$groupID] = $copy;
					}
				}

				if (!in_array($filteredGroups, $uniqueGroups))
				{
					$uniqueGroups[] = $filteredGroups;
				}

				if (!empty($person['rooms']))
				{
					foreach ($person['rooms'] as $roomID => $room)
					{
						$dateTime   = $room['statusDate'];
						$dtRelevant = ($room['status'] and $dateTime >= $cutOff);

						// Removed before cut off
						if (!$dtRelevant and $room['status'] === 'removed')
						{
							unset($instance->resources[$personID]['rooms'][$roomID]);
							continue;
						}

						$name = $room['room'];

						if (empty($rooms[$name]) or $dateTime > $rooms[$name]['date'])
						{
							$rooms[$name] = [
								'date'   => $modified,
								'status' => $room['status']
							];
						}

						$modified = $dateTime > $modified ? $dateTime : $modified;

						$copy = $room;
						unset($copy['status'], $copy['statusDate']);
						$filteredRooms[$roomID] = $copy;
					}
				}

				if (!in_array($filteredRooms, $uniqueRooms))
				{
					$uniqueRooms[] = $filteredRooms;
				}
			}
		}

		// Alphabetize in role.
		foreach ($persons as $roleID => $entries)
		{
			asort($entries);
			$persons[$roleID] = $entries;
		}

		asort($groups);
		asort($rooms);

		$instance->groups     = $groups;
		$instance->persons    = $persons;
		$instance->rooms      = $rooms;
		$instance->showGroups = count(array_filter($uniqueGroups)) > 1;
		$instance->showRoles  = count($instance->persons) > 1;
		$instance->showRooms  = count(array_filter($uniqueRooms)) > 1;

		if ($message)
		{
			$this->messages[] = $message;
		}

		if ($modified)
		{
			$modified         = Helpers\Dates::formatDateTime($modified);
			$this->messages[] = sprintf(Languages::_('ORGANIZER_LAST_UPDATED'), $modified);
		}

		$this->instance = $instance;
	}

	/**
	 * @inheritdoc
	 */
	protected function setSubtitle()
	{
		$instance       = $this->instance;
		$date           = Helpers\Dates::formatDate($instance->date);
		$this->subtitle = "<h4>$date $instance->startTime - $instance->endTime</h4>";
	}

	/**
	 * @inheritdoc
	 */
	protected function structureItems()
	{
		$index           = 0;
		$structuredItems = [];

		foreach ($this->items as $item)
		{

			$times = '<span class="date">' . Helpers\Dates::formatDate($item->date) . '</span><br>';
			$times .= '<span class="times">' . $item->startTime . ' - ' . $item->endTime . '</span>';

			$structuredItems[$index] = [];

			if ($this->userID)
			{
				$structuredItems[$index]['checkbox'] = Helpers\HTML::_('grid.id', $index, $item->instanceID);
			}

			$structuredItems[$index]['status']  = $this->getStatus($item);
			$structuredItems[$index]['times']   = $times;
			$structuredItems[$index]['persons'] = $this->getPersons($item);
			$structuredItems[$index]['groups']  = $this->getResource($item, 'group', 'fullName');
			$structuredItems[$index]['rooms']   = $this->getResource($item, 'room', 'room');

			$index++;
		}

		$this->items = $structuredItems;
	}
}
