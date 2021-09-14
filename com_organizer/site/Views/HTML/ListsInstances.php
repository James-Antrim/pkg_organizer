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

use Organizer\Helpers\Can;
use Organizer\Helpers\Dates;
use Organizer\Helpers\HTML;
use Organizer\Helpers\Instances as Helper;
use Organizer\Helpers\Languages;
use Organizer\Helpers\Roles;
use Organizer\Helpers\Routing;
use Organizer\Helpers\Users;
use stdClass;

trait ListsInstances
{
	private $manages = false;

	private $teaches = false;

	private $teachesALL = true;

	/**
	 * Adds previously set resources to the structured item.
	 *
	 * @param   array     $index
	 * @param   stdClass  $instance
	 *
	 * @return void
	 */
	private function addResources(array &$index, stdClass $instance)
	{
		$index['persons'] = $instance->persons;
		$index['groups']  = $instance->groups;
		$index['rooms']   = $instance->rooms;
	}

	/**
	 * Created a structure for displaying status information as necessary.
	 *
	 * @param   stdClass  $instance  the instance item being iterated
	 *
	 * @return string
	 */
	private function getStatus(stdClass $instance): string
	{
		$userID = Users::getID();

		if ($instance->instanceStatus !== 'removed' and $instance->unitStatus !== 'removed')
		{
			if ($instance->expired)
			{
				$value = Languages::_('ORGANIZER_EXPIRED');
			}
			elseif ($instance->presence === Helper::ONLINE)
			{
				$value = Languages::_('ORGANIZER_ONLINE');

				if ($userID)
				{
					if ($instance->scheduled)
					{
						$value .= ' ' . HTML::icon('bookmark', Languages::_('ORGANIZER_SUBSCRIBED'));
					}

					if ($instance->manageable)
					{
						$value .= '<br>' . $instance->interested . ' ';
						$value .= HTML::icon('bookmark', Languages::_('ORGANIZER_SUBSCRIBERS'));
					}

				}
			}
			else
			{
				$interested = $instance->interested - $instance->current;
				$value      = $instance->presence === Helper::HYBRID ? Languages::_('ORGANIZER_HYBRID') : Languages::_('ORGANIZER_PRESENCE');

				if ($userID)
				{
					if ($instance->scheduled)
					{
						$value .= ' ' . HTML::icon('bookmark', Languages::_('ORGANIZER_SUBSCRIBED'));

						if ($instance->registered)
						{
							$value .= ' ' . HTML::icon('signup', Languages::_('ORGANIZER_REGISTERED'));
						}
					}

					if ($instance->manageable)
					{
						if ($interested)
						{
							$value .= "<br>$interested ";
							$value .= HTML::icon('bookmark', Languages::_('ORGANIZER_SUBSCRIBERS'));
						}
					}
				}

				if ($instance->presence !== Helper::ONLINE)
				{
					$value .= '<br>';

					if ($instance->premature)
					{
						$value .= HTML::icon('unlock', Languages::_('ORGANIZER_REGISTRATION_PREMATURE'));
						$value .= ' ' . $instance->registrationStart;
					}
					elseif ($instance->running)
					{
						$value .= HTML::icon('stop', Languages::_('ORGANIZER_REGISTRATION_CLOSED'));
					}
					else
					{
						if ($instance->full)
						{
							$value .= HTML::icon('pause', Languages::_('ORGANIZER_INSTANCE_FULL')) . ' ';
						}
						else
						{
							$value .= HTML::icon('play', Languages::_('ORGANIZER_REGISTRATION_OPEN'));
						}

						// Forced output
						$value .= $instance->capacity ? "$instance->current/$instance->capacity " : "$instance->current ";
						$value .= HTML::icon('users', Languages::_('ORGANIZER_PARTICIPANTS'));
					}
				}
			}
		}
		else
		{
			$value = Languages::_('ORGANIZER_REMOVED');
		}

		return $value;
	}

	/**
	 * Gets an icon displaying the instance's (unit's) status as relevant.
	 *
	 * @param   stdClass  $instance  the object modeling the instance
	 *
	 * @return array|string an icon representing the status of the instance, empty if the status is irrelevant
	 */
	private function getToolsColumn(stdClass $instance, int $index)
	{
		$class      = 'status-display hasToolTip';
		$instanceID = $instance->instanceID;
		$title      = '';
		$userID     = Users::getID();
		$value      = '';

		// If removed are here at all, the status holds relevance regardless of date
		if ($instance->unitStatus === 'removed')
		{
			$date  = Dates::formatDate($instance->unitStatusDate);
			$class .= ' unit-removed';
			$title = sprintf(Languages::_('ORGANIZER_UNIT_REMOVED_ON'), $date);
		}
		elseif ($instance->instanceStatus === 'removed')
		{
			$date  = Dates::formatDate($instance->instanceStatusDate);
			$class .= ' instance-removed';
			$title = sprintf(Languages::_('ORGANIZER_INSTANCE_REMOVED_ON'), $date);
		}
		elseif ($instance->unitStatus === 'new' and $instance->unitStatusDate >= $this->statusDate)
		{
			$date  = Dates::formatDate($instance->instanceStatusDate);
			$class .= ' unit-new';
			$title = sprintf(Languages::_('ORGANIZER_INSTANCE_ADDED_ON'), $date);
		}
		elseif ($instance->instanceStatus === 'new' and $instance->instanceStatusDate >= $this->statusDate)
		{
			$date  = Dates::formatDate($instance->instanceStatusDate);
			$class .= ' instance-new';
			$title = sprintf(Languages::_('ORGANIZER_INSTANCE_ADDED_ON'), $date);
		}

		if ($userID)
		{
			if ($this->mobile)
			{
				$buttons = [];

				if ($instance->manageable)
				{
					$label   = Languages::_('ORGANIZER_MANAGE_BOOKING');
					$icon    = HTML::icon('users', $label, true);
					$attribs = ['aria-label' => $label, 'class' => 'btn btn-checkbox'];

					// Always allow management of existing
					if ($instance->bookingID)
					{
						$url       = Routing::getViewURL('booking', $instance->bookingID);
						$buttons[] = HTML::link($url, $icon, $attribs);
					}
					// Never allow creation of bookings for past instances
					elseif ($instance->registration)
					{
						$url       = Routing::getTaskURL('bookings.manage', $instanceID);
						$buttons[] = HTML::link($url, $icon, $attribs);
					}
				}
				// Virtual and full appointments can still be added to the personal calendar
				elseif (!$instance->expired and !$instance->running)
				{
					if ($instance->interested)
					{
						$label = Languages::_('ORGANIZER_REMOVE');
						$icon  = HTML::icon('bookmark-2', $label, true);
						$url   = Routing::getTaskURL('InstanceParticipants.deschedule', $instanceID);
					}
					else
					{
						$label = Languages::_('ORGANIZER_ADD');
						$icon  = HTML::icon('bookmark', $label, true);
						$url   = Routing::getTaskURL('InstanceParticipants.schedule', $instanceID);
					}

					$attribs   = ['aria-label' => $label, 'class' => 'btn'];
					$buttons[] = HTML::link($url, $icon, $attribs);

					// Not virtual and not full
					if ($instance->registration)
					{
						if ($instance->registered)
						{
							$label = Languages::_('ORGANIZER_DEREGISTER');
							$icon  = HTML::icon('exit', $label, true);
							$url   = Routing::getTaskURL('InstanceParticipants.deregister', $instanceID);
						}
						else
						{
							$label = Languages::_('ORGANIZER_REGISTER');
							$icon  = HTML::icon('signup', $label, true);
							$url   = Routing::getTaskURL('InstanceParticipants.register', $instanceID);
						}

						$attribs   = ['aria-label' => $label, 'class' => 'btn btn-checkbox'];
						$buttons[] = HTML::link($url, $icon, $attribs);
					}
				}

				$value .= implode('', $buttons);
			}
			elseif (!$instance->expired or ($instance->manageable and $instance->bookingID))
			{
				$value = HTML::_('grid.id', $index, $instanceID);
			}
		}

		return $title ? ['attributes' => ['class' => $class, 'title' => $title], 'value' => $value] : $value;
	}

	/**
	 * Adds derived attributes/resource output for the instances.
	 *
	 * @param   array  $instances
	 *
	 * @return void
	 */
	private function setDerived(array $instances)
	{
		foreach ($instances as $instance)
		{
			$this->setSingle($instance);
		}
	}

	/**
	 * Determines whether the item is conducted virtually: every person is assigned rooms, all assigned rooms are virtual.
	 *
	 * @param   stdClass  $instance  the item being iterated
	 *
	 * @return void
	 */
	private function setResources(stdClass $instance)
	{
		$instance->groups   = '';
		$instance->persons  = '';
		$instance->presence = Helper::ONLINE;
		$instance->rooms    = '';
		$removed            = ($instance->instanceStatus === 'removed' or $instance->unitStatus === 'removed');

		if (empty($instance->resources))
		{
			return;
		}

		$groups   = [];
		$presence = false;
		$roles    = [];
		$rooms    = [];
		$virtual  = false;

		foreach ($instance->resources as $person)
		{
			if (($removed and $person['status'] === 'new') or $person['status'] === 'removed')
			{
				continue;
			}

			$name = $person['person'];

			if (empty($roles[$person['roleID']]))
			{
				$roles[$person['roleID']] = [];
			}

			$roles[$person['roleID']][$name] = $name;

			if (!empty($person['groups']))
			{
				foreach ($person['groups'] as $group)
				{
					if (($removed and $group['status'] === 'new') or $group['status'] === 'removed')
					{
						continue;
					}

					$name = $group['code'];

					if (empty($groups[$name]))
					{
						$groups[$name] = $group;
					}
				}
			}

			if (!empty($person['rooms']))
			{
				foreach ($person['rooms'] as $room)
				{
					if (($removed and $room['status'] === 'new') or $room['status'] === 'removed')
					{
						continue;
					}

					if ($room['virtual'])
					{
						$virtual = true;
						continue;
					}

					$name     = $room['room'];
					$presence = true;

					if (empty($rooms[$name]))
					{
						$rooms[$name] = $name;
					}
				}
			}
		}

		ksort($groups);

		foreach ($groups as $code => $group)
		{
			$title = "title=\"{$group['fullName']}\"";

			$groups[$code] = "<span class=\"hasToolTip\" $title>{$group['code']}</span>";
		}

		$instance->groups = implode('<br>', $groups);

		if (count($roles) === 1)
		{
			$persons = array_shift($roles);
			ksort($persons);

			$instance->persons = implode('<br>', $persons);
		}
		else
		{
			$displayRoles = [];
			foreach ($roles as $roleID => $persons)
			{
				$roleDisplay = '';

				if (!$roleTitle = Roles::getLabel($roleID, count($persons)))
				{
					continue;
				}

				$roleDisplay .= "<span class=\"role-title\">$roleTitle:</span><br>";

				ksort($persons);
				$roleDisplay           .= implode('<br>', $persons);
				$displayRoles[$roleID] = $roleDisplay;
			}

			ksort($roles);
			$instance->persons = implode('<br>', $displayRoles);
		}

		if ($presence and $virtual)
		{
			$instance->presence = Helper::HYBRID;
		}
		elseif ($presence)
		{
			$instance->presence = Helper::PRESENCE;
		}

		if ($instance->presence === Helper::ONLINE)
		{
			$instance->rooms = Languages::_('ORGANIZER_ONLINE');

			return;
		}

		ksort($rooms);

		if ($instance->presence === Helper::HYBRID)
		{
			array_unshift($rooms, Languages::_('ORGANIZER_ONLINE'));
		}

		$instance->rooms = implode('<br>', $rooms);
	}

	private function setSingle(stdClass $instance)
	{
		$link   = 'index.php?option=com_organizer&view=instance_item&id=';
		$now    = date('H:i');
		$today  = date('Y-m-d');
		$userID = Users::getID();

		$this->setResources($instance);

		$instanceID = $instance->instanceID;
		$isToday    = $instance->date === $today;
		$then       = date('Y-m-d', strtotime('-2 days', strtotime($instance->date)));

		$instance->expired = ($instance->date < $today or ($isToday and $instance->endTime < $now));
		$instance->full    = (!empty($instance->capacity) and $instance->current >= $instance->capacity);
		$instance->link    = $link . $instanceID;

		if ($userID and Can::manage('instance', $instanceID))
		{
			$instance->manageable = true;
			$this->teaches        = true;
		}
		else
		{
			$instance->manageable = false;
			$this->teachesALL     = false;
		}

		$instance->premature         = $today < $then;
		$instance->registration      = false;
		$instance->registrationStart = Dates::formatDate($then);
		$instance->running           = (!$instance->expired and $instance->date === $today and $instance->startTime < $now);

		$validTiming = (!$instance->expired and !$instance->running);

		if ($validTiming and $instance->presence !== Helper::ONLINE and !$instance->full)
		{
			$instance->registration = true;
		}
	}
}