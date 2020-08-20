<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Toolbar\Toolbar;
use Organizer\Helpers;
use Organizer\Helpers\Languages;

/**
 * Class loads persistent information a filtered set of instances into the display context.
 */
class Instances extends ListView
{
	const MONDAY = 1, TUESDAY = 2, WEDNESDAY = 3, THURSDAY = 4, FRIDAY = 5, SATURDAY = 6, SUNDAY = 7;

	private $statusDate;

	/**
	 * Constructor
	 *
	 * @param   array  $config  A named configuration array for object construction.
	 *
	 * @since   3.0
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->statusDate = date('Y-m-d H:i:s', strtotime('-14 days'));
	}

	/**
	 * Sets Joomla view title and action buttons
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		$params = Helpers\Input::getParams();

		if (!$params->get('show_page_heading') or !$title = $params->get('page_title'))
		{
			$title  = Helpers\Languages::_("ORGANIZER_INSTANCES");
			$suffix = '';

			if ($dow = $params->get('dow'))
			{
				switch ($dow)
				{
					case self::MONDAY:
						$title = Helpers\Languages::_("ORGANIZER_MONDAY_INSTANCES");
						break;
					case self::TUESDAY:
						$title = Helpers\Languages::_("ORGANIZER_TUESDAY_INSTANCES");
						break;
					case self::WEDNESDAY:
						$title = Helpers\Languages::_("ORGANIZER_WEDNESDAY_INSTANCES");
						break;
					case self::THURSDAY:
						$title = Helpers\Languages::_("ORGANIZER_THURSDAY_INSTANCES");
						break;
					case self::FRIDAY:
						$title = Helpers\Languages::_("ORGANIZER_FRIDAY_INSTANCES");
						break;
					case self::SATURDAY:
						$title = Helpers\Languages::_("ORGANIZER_SATURDAY_INSTANCES");
						break;
					case self::SUNDAY:
						$title = Helpers\Languages::_("ORGANIZER_SUNDAY_INSTANCES");
						break;
				}
			}
			elseif ($methodID = $params->get('methodID'))
			{
				$title = Helpers\Methods::getPlural($methodID);
			}

			if ($organizationID = $params->get('organizationID'))
			{
				$suffix .= ': ' . Helpers\Organizations::getFullName($organizationID);
			}
			elseif ($campusID = $params->get('campusID'))
			{
				$suffix .= ': ' . Helpers\Languages::_("ORGANIZER_CAMPUS") . ' ' . Helpers\Campuses::getName($campusID);
			}

			$title .= $suffix;
		}

		// Add menu title support, both direct and via selected filters
		Helpers\HTML::setTitle($title, 'list-2');

		$toolbar = Toolbar::getInstance();
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return bool true if the use may access the view, otherwise false
	 */
	protected function allowAccess()
	{
		return true;
	}

	/**
	 * Lists the instance associated groups.
	 *
	 * @param   object  $item  the instance being iterated
	 *
	 * @return string
	 */
	private function getGroups($item)
	{
		$added   = Languages::_('ORGANIZER_GROUP_ADDED_ON');
		$groups  = [];
		$removed = Languages::_('ORGANIZER_GROUP_REMOVED_ON');

		foreach ($item->resources as $person)
		{
			if (empty($person['groups']))
			{
				continue;
			}

			foreach ($person['groups'] as $group)
			{
				$fullName = $group['fullName'];

				if (empty($groups[$fullName]))
				{
					$groups[$fullName] = $group;
					continue;
				}

				$groups[$fullName]['statusDate'] = max($groups[$fullName]['statusDate'], $group['statusDate']);

				if ($groups[$fullName]['status'] !== $group['status'])
				{
					$groups[$fullName]['status'] = '';
				}
			}
		}

		ksort($groups);

		foreach ($groups as $fullName => $group)
		{
			$class = '';
			$title = '';

			if (strlen($fullName) > 45)
			{
				$class = 'hasToolTip';
				$name  = $group['code'];
				$title = $fullName;
			}
			else
			{
				$name = $fullName;
			}

			if ($group['status'] and $group['statusDate'] >= $this->statusDate)
			{
				$date = Helpers\Dates::formatDate($group['statusDate']);

				if ($group['status'] === 'new')
				{
					if ($class)
					{
						$class .= ' status-new';
						$title .= ': ' . sprintf($added, $date);
					}
					else
					{
						$class = 'status-new';
						$title = sprintf($added, $date);
					}
				}
				elseif ($group['status'] === 'removed')
				{
					if ($class)
					{
						$class .= ' status-removed';
						$title .= ': ' . sprintf($removed, $date);
					}
					else
					{
						$class = 'status-removed';
						$title = sprintf($removed, $date);
					}
				}
			}

			$class = !empty($class) ? 'class="' . $class . '"' : '';
			$title = !empty($title) ? 'title="' . $title . '"' : '';

			$groups[$fullName] = "<span $class $title>$name</span>";
		}

		return implode('<br>', $groups);
	}

	/**
	 * Lists the instance associated teachers.
	 *
	 * @param   object  $item  the instance being iterated
	 *
	 * @return string
	 */
	private function getPersons($item)
	{
		$added   = Languages::_('ORGANIZER_PERSON_ADDED_ON');
		$persons = [];
		$removed = Languages::_('ORGANIZER_PERSON_REMOVED_ON');

		foreach ($item->resources as $personID => $person)
		{
			$name  = $person['person'];
			$title = '';

			if ($person['status'] and $person['statusDate'] >= $this->statusDate)
			{
				$date = Helpers\Dates::formatDate($person['statusDate']);

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

			$persons[$name] = "<span $class $title>$name</span>";
		}

		ksort($persons);

		return implode('<br>', $persons);
	}

	/**
	 * Lists the instance associated groups.
	 *
	 * @param   object  $item  the instance being iterated
	 *
	 * @return string
	 */
	private function getRooms($item)
	{
		$added   = Languages::_('ORGANIZER_ROOM_ADDED_ON');
		$rooms   = [];
		$removed = Languages::_('ORGANIZER_ROOM_REMOVED_ON');

		foreach ($item->resources as $person)
		{
			if (empty($person['rooms']))
			{
				continue;
			}

			foreach ($person['rooms'] as $roomID => $room)
			{
				$name = $room['room'];

				if (empty($rooms[$name]))
				{
					$rooms[$name] = $room;
					continue;
				}

				$rooms[$name]['statusDate'] = max($rooms[$name]['statusDate'], $room['statusDate']);

				if ($rooms[$name]['status'] !== $room['status'])
				{
					$rooms[$name]['status'] = '';
				}
			}
		}

		ksort($rooms);

		foreach ($rooms as $name => $room)
		{
			$class = '';
			$title = '';

			if ($room['status'])
			{
				$date = Helpers\Dates::formatDate($room['statusDate']);

				if ($room['status'] === 'new')
				{
					$class .= 'class="status-new"';
					$title = 'title="' . sprintf($added, $date) . '"';
				}
				elseif ($room['status'] === 'removed')
				{
					$class .= 'class="status-removed"';
					$title = 'title="' . sprintf($removed, $date) . '"';
				}
			}

			$rooms[$name] = "<span $class $title>$name</span>";
		}

		return implode('<br>', $rooms);
	}

	/**
	 * Created a structure for displaying status information as necessary.
	 *
	 * @param   object  $item  the instance item being iterated
	 *
	 * @return array|string
	 */
	private function getStatus($item)
	{
		$class = 'status-display hasToolTip';
		$title = '';

		if ($item->unitStatus and $item->unitStatusDate >= $this->statusDate)
		{
			$date = Helpers\Dates::formatDate($item->unitStatusDate);

			if ($item->unitStatus === 'new')
			{
				$class .= ' unit-new';
				$title = sprintf(Languages::_('ORGANIZER_UNIT_ADDED_ON'), $date);
			}
			elseif ($item->unitStatus === 'removed')
			{
				$class .= ' unit-removed';
				$title = sprintf(Languages::_('ORGANIZER_UNIT_REMOVED_ON'), $date);
			}
		}
		elseif ($item->instanceStatus and $item->instanceStatusDate >= $this->statusDate)
		{
			$date = Helpers\Dates::formatDate($item->instanceStatusDate);

			if ($item->instanceStatus === 'new')
			{
				$class .= ' instance-new';
				$title = sprintf(Languages::_('ORGANIZER_INSTANCE_ADDED_ON'), $date);
			}
			elseif ($item->instanceStatus === 'removed')
			{
				$class .= ' instance-removed';
				$title = sprintf(Languages::_('ORGANIZER_INSTANCE_REMOVED_ON'), $date);
			}
		}

		return $title ? ['attributes' => ['class' => $class, 'title' => $title], 'value' => ''] : '';
	}

	/**
	 * Creates the event title.
	 *
	 * @param   object  $item  the event item being iterated
	 *
	 * @return array the title column
	 */
	private function getTitle($item)
	{
		$name = '<span class="event">' . $item->name . '</span>';
		$name .= empty($item->method) ? '' : "<br><span class=\"method\">$item->method</span>";
		$name .= empty($item->comment) ? '' : "<br><span class=\"comment\">$item->comment</span>";

		return ['attributes' => ['class' => 'title-column'], 'value' => $name];
	}

	/**
	 * Function to set the object's headers property
	 *
	 * @return void sets the object headers property
	 */
	public function setHeaders()
	{
		$this->headers = [
			//'checkbox'     => Helpers\HTML::_('grid.checkall'),
			'status'  => '',
			'title'   => ['attributes' => ['class' => 'title-column'], 'value' => Languages::_('ORGANIZER_NAME')],
			'times'   => Languages::_('ORGANIZER_DATETIME'),
			'persons' => Languages::_('ORGANIZER_PERSONS'),
			'groups'  => Languages::_('ORGANIZER_GROUPS'),
			'rooms'   => Languages::_('ORGANIZER_ROOMS')
		];
	}

	/**
	 * Processes the items in a manner specific to the view, so that a generalized  output in the layout can occur.
	 *
	 * @return void processes the class items property
	 */
	protected function structureItems()
	{
		$index = 0;
		//$link            = 'index.php?option=com_organizer&view=instance_edit&id=';
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$times = '<span class="date">' . Helpers\Dates::formatDate($item->date) . '</span><br>';
			$times .= '<span class="times">' . Helpers\Dates::formatTime($item->startTime) . ' - ';
			$times .= Helpers\Dates::formatTime($item->endTime) . '</span>';

			$structuredItems[$index] = [];
			//$structuredItems[$index]['checkbox']     = Helpers\HTML::_('grid.id', $index, $item->instanceID);
			$structuredItems[$index]['status']  = $this->getStatus($item);
			$structuredItems[$index]['title']   = $this->getTitle($item);
			$structuredItems[$index]['times']   = $times;
			$structuredItems[$index]['persons'] = $this->getPersons($item);
			$structuredItems[$index]['groups']  = $this->getGroups($item);
			$structuredItems[$index]['rooms']   = $this->getRooms($item);

			$index++;
		}

		$this->items = $structuredItems;
	}
}