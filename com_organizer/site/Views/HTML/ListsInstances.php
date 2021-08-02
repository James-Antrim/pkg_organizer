<?php
/**
 * @package     Organizer\Views\HTML
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Organizer\Helpers\Dates;
use Organizer\Helpers\Languages;
use Organizer\Helpers\Roles;

/**
 * Trait HasResources provides functiouns used by classes with formatted instance resource output.
 */
trait ListsInstances
{
	/**
	 * Lists the instance associated teachers.
	 *
	 * @param   object  $instance  the instance being iterated
	 *
	 * @return string
	 */
	private function getPersons(object $instance): string
	{
		$added   = Languages::_('ORGANIZER_PERSON_ADDED_ON');
		$removed = Languages::_('ORGANIZER_PERSON_REMOVED_ON');
		$roles   = [];

		foreach ($instance->resources as $person)
		{
			$name  = $person['person'];
			$title = '';

			if ($this->entryStatus === 'new' and $person['status'] === 'removed'
				or $this->entryStatus === 'removed' and $person['status'] === 'new')
			{
				continue;
			}

			if (!$this->entryStatus and $person['status'] and $person['statusDate'] >= $this->statusDate)
			{
				$date = Dates::formatDate($person['statusDate']);

				if ($person['status'] === 'new')
				{
					$class = 'status-new';
					$title = 'title="' . sprintf($added, $date) . '"';
				}
				elseif ($person['status'] === 'removed')
				{
					$class = 'status-removed';
					$title = 'title="' . sprintf($removed, $date) . '"';
				}
			}

			$class = !empty($class) ? 'class="' . $class . '"' : '';

			if (empty($roles[$person['roleID']]))
			{
				$roles[$person['roleID']] = [];
			}

			$roles[$person['roleID']][$name] = "<span $class $title>$name</span>";
		}

		if (count($roles) === 1)
		{
			$persons = array_shift($roles);
			ksort($persons);

			return implode('<br>', $persons);
		}

		$displayRoles = [];

		ksort($roles);

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

		return implode('<br>', $displayRoles);
	}

	/**
	 * Lists the instance associated resources.
	 *
	 * @param   object  $instance      the instance being iterated
	 * @param   string  $resourceName  the resource type's name
	 * @param   string  $rIndex        the individual resource's name index
	 *
	 * @return string
	 */
	private function getResource(object $instance, string $resourceName, string $rIndex): string
	{
		$constant        = strtoupper($resourceName);
		$collectionIndex = $resourceName . 's';

		$added     = Languages::_("ORGANIZER_{$constant}_ADDED_ON");
		$resources = [];
		$removed   = Languages::_("ORGANIZER_{$constant}_REMOVED_ON");

		foreach ($instance->resources as $person)
		{
			if (empty($person[$collectionIndex]) or $person['status'] === 'removed')
			{
				continue;
			}

			if (empty($person[$collectionIndex]))
			{
				continue;
			}

			foreach ($person[$collectionIndex] as $resource)
			{
				if ($this->entryStatus === 'new' and $resource['status'] === 'removed'
					or $this->entryStatus === 'removed' and $resource['status'] === 'new')
				{
					continue;
				}

				$name = $resource[$rIndex];

				if (empty($resources[$name]))
				{
					$resources[$name] = $resource;
					continue;
				}

				$resources[$name]['statusDate'] =
					max($resources[$name]['statusDate'], $resource['statusDate']);

				if ($resources[$name]['status'] !== $resource['status'])
				{
					$resources[$name]['status'] = '';
				}
			}
		}

		ksort($resources);

		foreach ($resources as $name => $resource)
		{
			$class = '';
			$title = '';

			if (strlen($name) > 45)
			{
				$class         .= 'hasToolTip';
				$title         .= $name;
				$displayedName = $resource['code'];
			}
			else
			{
				$displayedName = $name;
			}

			if (!$this->entryStatus and $resource['status'] and $resource['statusDate'] >= $this->statusDate)
			{
				$date = Dates::formatDate($resource['statusDate']);

				if ($resource['status'] === 'new')
				{
					$class .= ' status-new';
					$title .= ' ' . sprintf($added, $date);
				}
				elseif ($resource['status'] === 'removed')
				{
					$class .= ' status-removed';
					$title .= ' ' . sprintf($removed, $date);
				}
			}

			if ($class = trim($class))
			{
				$class = "class=\"$class\"";
			}

			if ($title = trim($title))
			{
				$title = "title=\"$title\"";
			}

			$resources[$name] = "<span $class $title>$displayedName</span>";
		}

		return implode('<br>', $resources);
	}

	/**
	 * Created a structure for displaying status information as necessary.
	 *
	 * @param   object  $instance  the instance item being iterated
	 *
	 * @return array|string
	 */
	private function getStatus(object $instance)
	{
		$class = 'status-display hasToolTip';
		$title = '';

		// If removed are here at all, the status holds relevance irregardless of date
		if ($instance->unitStatus === 'removed')
		{
			$date  = Dates::formatDate($instance->unitStatusDate);
			$class .= ' unit-removed';
			$title = sprintf(Languages::_('ORGANIZER_UNIT_REMOVED_ON'), $date);

			$this->entryStatus = 'removed';
		}
		elseif ($instance->instanceStatus === 'removed')
		{
			$date  = Dates::formatDate($instance->instanceStatusDate);
			$class .= ' instance-removed';
			$title = sprintf(Languages::_('ORGANIZER_INSTANCE_REMOVED_ON'), $date);

			$this->entryStatus = 'removed';
		}
		elseif ($instance->unitStatus === 'new' and $instance->unitStatusDate >= $this->statusDate)
		{
			$date  = Dates::formatDate($instance->unitStatusDate);
			$class .= ' unit-new';
			$title = sprintf(Languages::_('ORGANIZER_UNIT_ADDED_ON'), $date);

			$this->entryStatus = 'new';
		}
		elseif ($instance->instanceStatus === 'new' and $instance->instanceStatusDate >= $this->statusDate)
		{
			$date  = Dates::formatDate($instance->instanceStatusDate);
			$class .= ' instance-new';
			$title = sprintf(Languages::_('ORGANIZER_INSTANCE_ADDED_ON'), $date);

			$this->entryStatus = 'new';
		}
		else
		{
			$this->entryStatus = '';
		}

		return $title ? ['attributes' => ['class' => $class, 'title' => $title], 'value' => ''] : '';
	}

}