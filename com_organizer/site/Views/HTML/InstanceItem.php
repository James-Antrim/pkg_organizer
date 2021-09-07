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
use Organizer\Helpers\Instances as Helper;
use Organizer\Helpers\Languages;
use stdClass;

/**
 * Class loads information about a given instance.
 */
class InstanceItem extends ListView
{
	use ListsInstances;

	private $buttons = [];

	/**
	 * The relevant status date of the entire instance.
	 * @var string
	 */
	private $dateTime;

	/**
	 * @var object the data for the instance
	 */
	public $instance;

	protected $layout = 'instance-item';

	public $minibar = '';

	private $manages = false;

	private $messages = [];

	protected $rowStructure = [
		'checkbox' => '',
		'date'     => 'value',
		'time'     => 'value',
		'persons'  => 'value',
		'rooms'    => 'value'
	];

	/**
	 * The relevant status of the entire instance.
	 * @var string
	 */
	private $status;

	private $statusDate;

	private $userID;

	/**
	 * @inheritDoc
	 */
	protected function addSupplement()
	{
		$color    = 'blue';
		$instance = $this->instance;
		$text     = '';

		if ($instance->expired)
		{
			$color          = 'grey';
			$this->messages = [Languages::_('ORGANIZER_INSTANCE_EXPIRED')];
		}
		elseif (!$this->userID)
		{
			$this->messages[] = Languages::_('ORGANIZER_INSTANCE_LOG_IN_FIRST');
		}
		elseif ($instance->registered)
		{
			$color            = 'green';
			$this->messages[] = Languages::_('ORGANIZER_INSTANCE_REGISTERED');
		}
		elseif ($instance->scheduled)
		{
			$this->messages[] = Languages::_('ORGANIZER_INSTANCE_SCHEDULED');

			if ($instance->presence !== Helper::ONLINE)
			{
				$color = 'yellow';
			}
		}
		else
		{
			$this->messages[] = Languages::_('ORGANIZER_INSTANCE_NOT_SCHEDULED');
		}

		if ($this->messages)
		{
			$text = "<div class=\"tbox-$color\">" . implode('<br>', $this->messages) . '</div>';
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

		if ($this->userID and $this->buttons)
		{
			$buttons  = $this->buttons;
			$minibar  = [];
			$standard = new StandardButton();

			if ($buttons['schedule'])
			{
				$minibar[] = $standard->fetchButton(
					'Standard',
					'bookmark',
					Languages::_('ORGANIZER_ADD_INSTANCE'),
					'InstanceParticipants.scheduleThis',
					false
				);
			}
			elseif ($buttons['deschedule'])
			{
				$minibar[] = $standard->fetchButton(
					'Standard',
					'bookmark-2',
					Languages::_('ORGANIZER_DELETE_INSTANCE'),
					'InstanceParticipants.descheduleThis',
					false
				);
			}

			if ($buttons['scheduleBlock'])
			{
				$minibar[] = $standard->fetchButton(
					'Standard',
					'bookmark',
					Languages::_('ORGANIZER_ADD_BLOCK_INSTANCES'),
					'InstanceParticipants.scheduleBlock',
					false
				);
			}

			if ($buttons['descheduleBlock'])
			{
				$minibar[] = $standard->fetchButton(
					'Standard',
					'bookmark-2',
					Languages::_('ORGANIZER_DELETE_BLOCK_INSTANCES'),
					'InstanceParticipants.descheduleBlock',
					false
				);
			}

			if ($buttons['register'])
			{
				$minibar[] = $standard->fetchButton(
					'Standard',
					'signup',
					Languages::_('ORGANIZER_REGISTER'),
					'InstanceParticipants.registerThis',
					false
				);
			}
			elseif ($buttons['deregister'])
			{
				$minibar[] = $standard->fetchButton(
					'Standard',
					'exit',
					Languages::_('ORGANIZER_DEREGISTER'),
					'InstanceParticipants.deregisterThis',
					false
				);
			}

			if ($buttons['scheduleList'])
			{
				$toolbar->appendButton(
					'Standard',
					'bookmark',
					Languages::_('ORGANIZER_ADD_INSTANCES'),
					'InstanceParticipants.schedule',
					true
				);
			}

			if ($buttons['descheduleList'])
			{
				$toolbar->appendButton(
					'Standard',
					'bookmark-2',
					Languages::_('ORGANIZER_DELETE_INSTANCES'),
					'InstanceParticipants.deschedule',
					true
				);
			}

			if ($buttons['registerList'])
			{
				$toolbar->appendButton(
					'Standard',
					'signup',
					Languages::_('ORGANIZER_REGISTER'),
					'InstanceParticipants.register',
					true
				);
			}

			if ($buttons['deregisterList'])
			{
				$toolbar->appendButton(
					'Standard',
					'exit',
					Languages::_('ORGANIZER_DEREGISTER'),
					'InstanceParticipants.deregister',
					true
				);
			}

			if ($minibar)
			{
				$this->minibar = '<div class="btn-toolbar" role="toolbar" aria-label="Toolbar" id="minibar">';
				$this->minibar .= implode('', $minibar) . '</div>';
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

		if ($this->userID = Helpers\Users::getID())
		{
			$this->manages = Helpers\Can::manage('instance', $instanceID);

			return;
		}

		$this->manages = false;
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
		$instance = $this->instance;

		echo '<div class="attribute-item">';
		echo '<div class="attribute-label">' . Languages::_('ORGANIZER_PERSONS') . '</div>';
		echo '<div class="attribute-content"><ul>';

		foreach ($instance->persons as $persons)
		{
			if ($instance->showRoles)
			{
				$personIDs = array_keys($persons);
				$firstID   = reset($personIDs);
				echo '<u>' . $instance->resources[$firstID]['role'] . '</u><ul>';
			}

			foreach (array_keys($persons) as $personID)
			{
				$list   = ($instance->showRoles or count($persons) > 1);
				$person = $instance->resources[$personID];

				echo $list ? '<li>' : '';

				$this->renderResource($person['person'], $person['status'], $person['statusDate']);

				if ($instance->hideGroups or $instance->hideRooms)
				{
					echo '<ul>';
				}

				if ($instance->hideGroups and !empty($person['groups']))
				{
					echo '<li>' . Languages::_('ORGANIZER_GROUPS') . '<ul>';
					foreach ($person['groups'] as $group)
					{
						$list = count($person['groups']) > 1;
						echo $list ? '<li>' : '';
						$name = (strlen($group['fullName']) > 80 and $group['status']) ?
							$group['group'] : $group['fullName'];
						$this->renderResource($name, $group['status'], $group['statusDate']);
						echo $list ? '</li>' : '';
					}
					echo '</ul></li>';
				}

				if ($instance->hideRooms and !empty($person['rooms']))
				{
					echo '<li>' . Languages::_('ORGANIZER_ROOMS') . '<ul>';
					foreach ($person['rooms'] as $room)
					{
						$list = count($person['rooms']) > 1;
						echo $list ? '<li>' : '';
						$this->renderResource($room['room'], $room['status'], $room['statusDate']);
						echo $list ? '</li>' : '';
					}
					echo '</ul></li>';
				}

				if ($instance->hideGroups or $instance->hideRooms)
				{
					echo '</ul>';
				}

				echo $list ? '</li>' : '';
			}

			if ($instance->showRoles)
			{
				echo '</ul>';
			}
		}

		echo '</ul></div></div>';
	}

	/**
	 * Renders texts about the organization of the appointment in terms of presence...
	 *
	 * @return void
	 */
	public function renderOrganizational()
	{
		$instance = $this->instance;

		$registration = $instance->registration;

		if ($registration)
		{
			echo '<ul>';
		}

		$formText = '';

		switch ($instance->presence)
		{
			case Helper::HYBRID:
				$formText = Languages::_('ORGANIZER_HYBRID_TEXT');
				break;
			case Helper::ONLINE:
				$formText = Languages::_('ORGANIZER_ONLINE_TEXT');
				break;
			case Helper::PRESENCE:
				$formText = Languages::_('ORGANIZER_PRESENCE_TEXT');
				break;
		}

		echo $registration ? "<li>$formText</li>" : $formText;

		if ($instance->registration)
		{
			if ($instance->premature)
			{
				echo '<li>' . sprintf(Languages::_('ORGANIZER_REGISTRATION_OPENS_ON'),
						$instance->registrationStart) . '</li>';
			}
			elseif ($instance->running)
			{
				echo '<li>' . Languages::_('ORGANIZER_REGISTRATION_CLOSED') . '</li>';
			}
			else
			{
				echo '<li>' . Languages::_('ORGANIZER_REGISTRATION_OPEN') . '</li>';

				if ($instance->capacity)
				{
					if ($available = $instance->capacity - $instance->current)
					{
						echo '<li>' . sprintf(Languages::_('ORGANIZER_REGISTRATIONS_AVAILABLE_COUNT'),
								$available) . '</li>';
					}
					else
					{
						echo '<li>' . Languages::_('ORGANIZER_INSTANCE_FULL') . '</li>';
					}
				}
				// No capacity => no idea
				else
				{
					echo '<li>' . Languages::_('ORGANIZER_REGISTRATIONS_AVAILABLE') . '</li>';
				}
			}

			echo '</ul>';
		}
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
		$implied       = ($dateTime === $this->dateTime and $status === $this->status);
		$irrelevant    = $dateTime < $this->statusDate;
		$uninteresting = !$status;

		if ($implied or $irrelevant or $uninteresting)
		{
			echo $name;

			return;
		}

		$dateTime = Helpers\Dates::formatDateTime($dateTime);
		$delta    = $status === 'removed' ?
			sprintf(Languages::_('ORGANIZER_REMOVED_ON'), $dateTime) : sprintf(Languages::_('ORGANIZER_ADDED_ON'),
				$dateTime);

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
			$list = count($resources) > 1;
			echo $list ? '<li>' : '';
			$this->renderResource($name, $data['status'], $data['date']);
			echo $list ? '</li>' : '';
		}
		echo '</ul></div></div>';
	}

	/**
	 * @inheritdoc
	 */
	public function setHeaders()
	{
		$this->headers = [
			'checkbox' => $this->userID ? Helpers\HTML::_('grid.checkall') : '',
			'instance' => Languages::_('ORGANIZER_INSTANCE'),
			'status'   => Languages::_('ORGANIZER_STATUS'),
			'persons'  => Languages::_('ORGANIZER_PERSONS'),
			'groups'   => Languages::_('ORGANIZER_GROUPS'),
			'rooms'    => Languages::_('ORGANIZER_ROOMS')
		];
	}

	/**
	 * Processes the instance to aid in simplifying/supplementing the item display.
	 *
	 * @param   stdClass  $instance  the instance data
	 *
	 * @return void
	 */
	private function setInstance(stdClass $instance)
	{
		$this->setSingle($instance);

		$this->statusDate = date('Y-m-d 00:00:00', strtotime('-14 days'));
		$cutOff           = $this->statusDate;

		$bookends = ['new', 'removed'];
		$message  = '';
		$status   = '';

		$dateTime       = $instance->unitStatusDate;
		$this->dateTime = $dateTime;
		$dtRelevant     = ($instance->unitStatus and $dateTime >= $cutOff and in_array($instance->unitStatus,
				$bookends));
		$modified       = $instance->unitStatusDate;

		// Set unit baseline for process dating.
		if ($dtRelevant)
		{
			$this->dateTime = $instance->unitStatusDate;
			$constant       = $instance->unitStatus === 'removed' ? 'ORGANIZER_UNIT_REMOVED_ON' : 'ORGANIZER_UNIT_ADDED_ON';
			$status         = $instance->unitStatus;
			$this->status   = $instance->unitStatus;
			$statusDate     = Helpers\Dates::formatDateTime($instance->unitStatusDate);
			$message        = sprintf(Languages::_($constant), $statusDate);
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
					$this->dateTime = $instance->instanceStatusDate;
					$message        = sprintf($text, $statusDate);
				}
				// ...and the unit was not.
				elseif ($status !== 'removed' and $later)
				{
					$this->dateTime = $instance->instanceStatusDate;
					$this->status   = $instance->instanceStatus;
					$message        = sprintf($text, $statusDate);
				}
			}
			// Instance was recently added
			elseif ($status !== 'removed' and $instance->instanceStatus === 'new')
			{
				$this->dateTime = $instance->instanceStatusDate;
				$this->status   = $instance->instanceStatus;
				$message        = sprintf(Languages::_('ORGANIZER_INSTANCE_ADDED_ON'), $statusDate);
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
						$this->setResource($groups, $filteredGroups, $modified, $groupID, $name, $group);
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
						if ((!$dtRelevant and $room['status'] === 'removed') or $room['virtual'])
						{
							unset($instance->resources[$personID]['rooms'][$roomID]);
							continue;
						}

						$name = $room['room'];
						$this->setResource($rooms, $filteredRooms, $modified, $roomID, $name, $room);
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
		$instance->hideGroups = count(array_filter($uniqueGroups)) > 1;
		$instance->hideRooms  = count(array_filter($uniqueRooms)) > 1;
		$instance->persons    = $persons;
		$instance->rooms      = $rooms;
		$instance->showRoles  = count($instance->persons) > 1;

		if ($message)
		{
			$this->messages[] = $message;
		}

		if ($modified and $status !== 'new' and $status !== 'removed')
		{
			$modified         = Helpers\Dates::formatDateTime($modified);
			$this->messages[] = sprintf(Languages::_('ORGANIZER_LAST_UPDATED'), $modified);
		}

		$this->instance = $instance;
	}

	/**
	 * @param   array   &$collection  the aggregated collection for the resource
	 * @param   array   &$filtered    the resource filtered of attributes obfuscating resource uniqueness
	 * @param   string  &$modified    the date time string denoting the last modification date for the whole instance
	 * @param   int      $key         the resource's id in the database
	 * @param   string   $name        the name of the resource
	 * @param   array    $resource    the resource being iterated
	 *
	 * @return void
	 */
	private function setResource(
		array &$collection,
		array &$filtered,
		string &$modified,
		int $key,
		string $name,
		array $resource
	) {
		$dateTime = $resource['statusDate'];

		if (empty($collection[$name]) or $dateTime > $collection[$name]['date'])
		{
			$collection[$name] = [
				'date'   => $modified,
				'status' => $resource['status']
			];
		}

		$modified = $dateTime > $modified ? $dateTime : $modified;

		$copy = $resource;
		unset($copy['status'], $copy['statusDate']);
		$filtered[$key] = $copy;
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
		$this->setDerived($this->items);

		$buttons = [
			'deregister'      => false,
			'deregisterList'  => false,
			'deschedule'      => false,
			'descheduleBlock' => false,
			'descheduleList'  => false,
			'register'        => false,
			'registerList'    => false,
			'schedule'        => false,
			'scheduleBlock'   => false,
			'scheduleList'    => false
		];

		$instance = $this->instance;

		if (!$instance->expired and !$instance->running)
		{
			if ($instance->scheduled)
			{
				$buttons['deschedule'] = true;

			}
			else
			{
				$buttons['schedule'] = true;

			}

			if ($instance->registered)
			{
				$buttons['deregister'] = true;
			}
			elseif (!$instance->full and $instance->presence !== Helper::ONLINE and !$instance->premature)
			{
				$buttons['register'] = true;
			}
		}

		$index           = 0;
		$structuredItems = [];
		$thisDOW         = strtoupper(date('l', strtotime($instance->date)));

		foreach ($this->items as $item)
		{
			if (!$item->expired and !$item->running)
			{
				$sameDOW      = (strtoupper(date('l', strtotime($item->date))) === $thisDOW);
				$sameET       = $item->startTime === $instance->startTime;
				$sameST       = $item->startTime === $instance->startTime;
				$sameBlock    = ($sameDOW and $sameET and $sameST);
				$sameInstance = $item->instanceID === $instance->instanceID;

				if ($item->scheduled)
				{
					$buttons['descheduleList'] = true;

					if ($item->registered)
					{
						$buttons['deregisterList'] = true;
					}

					if ($sameBlock and !$sameInstance)
					{
						$buttons['descheduleBlock'] = true;
					}
				}
				else
				{
					$buttons['scheduleList'] = true;

					if (!$item->full and $item->presence !== Helper::ONLINE and !$item->premature)
					{
						$buttons['registerList'] = true;
					}

					if ($sameBlock and !$sameInstance)
					{
						$buttons['scheduleBlock'] = true;
					}
				}
			}

			$times = $item->method ? $item->method . '<br>' : '';
			$times .= '<span class="date">' . Helpers\Dates::formatDate($item->date) . '</span><br>';
			$times .= '<span class="times">' . $item->startTime . ' - ' . $item->endTime . '</span>';

			$checkbox   = $this->userID ? Helpers\HTML::_('grid.id', $index, $item->instanceID) : '';
			$statusIcon = $this->getStatusIcon($item);
			$checkbox   .= ($checkbox and $statusIcon) ? '<br>' . $statusIcon : $statusIcon;

			$structuredItems[$index]             = [];
			$structuredItems[$index]['checkbox'] = $checkbox;
			$structuredItems[$index]['instance'] = $times;
			$structuredItems[$index]['status']   = $this->getStatus($item);
			$this->addResources($structuredItems[$index], $item);

			$index++;
		}

		$this->buttons = $buttons;
		$this->items   = $structuredItems;
	}
}
