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
use Organizer\Tables;

/**
 * Class loads persistent information a filtered set of instances into the display context.
 */
class Instances extends ListView
{
	const MONDAY = 1, TUESDAY = 2, WEDNESDAY = 3, THURSDAY = 4, FRIDAY = 5, SATURDAY = 6, SUNDAY = 7;

	private $entryStatus = '';

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
	 * @return void
	 */
	protected function authorize()
	{
		if (!$this->adminContext)
		{
			return;
		}

		if (!Helpers\Can::scheduleTheseOrganizations())
		{
			Helpers\OrganizerHelper::error(403);
		}
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
		$removed = Languages::_('ORGANIZER_PERSON_REMOVED_ON');
		$roles   = [];

		foreach ($item->resources as $personID => $person)
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
		$role         = new Tables\Roles();
		$tag          = Languages::getTag();

		$singularColumn = "name_$tag";
		$pluralColumn   = "plural_$tag";

		ksort($roles);

		foreach ($roles as $roleID => $persons)
		{
			$roleDisplay = '';
			if (!$role->load($roleID))
			{
				continue;
			}

			$roleTitle   = count($persons) > 1 ? $role->$pluralColumn : $role->$singularColumn;
			$roleDisplay .= "<span class=\"role-title\">$roleTitle:</span><br>";

			ksort($persons);
			$roleDisplay             .= implode('<br>', $persons);
			$displayRoles[$role->id] = $roleDisplay;
		}

		return implode('<br>', $displayRoles);
	}

	/**
	 * Lists the instance associated resources.
	 *
	 * @param   object  $item          the instance being iterated
	 * @param   string  $resourceName  the resource type's name
	 * @param   string  $rIndex        the individual resource's name index
	 *
	 * @return string
	 */
	private function getResource($item, $resourceName, $rIndex)
	{
		$constant        = strtoupper($resourceName);
		$collectionIndex = $resourceName . 's';

		$added     = Languages::_("ORGANIZER_{$constant}_ADDED_ON");
		$resources = [];
		$removed   = Languages::_("ORGANIZER_{$constant}_REMOVED_ON");

		foreach ($item->resources as $person)
		{
			if (empty($person[$collectionIndex]))
			{
				continue;
			}

			foreach ($person[$collectionIndex] as $resourceID => $resource)
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
				$date = Helpers\Dates::formatDate($resource['statusDate']);

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
	 * @param   object  $item  the instance item being iterated
	 *
	 * @return array|string
	 */
	private function getStatus($item)
	{
		$class = 'status-display hasToolTip';
		$title = '';

		// If removed are here at all, the status holds relevance irregardless of date
		if ($item->unitStatus === 'removed')
		{
			$date  = Helpers\Dates::formatDate($item->unitStatusDate);
			$class .= ' unit-removed';
			$title = sprintf(Languages::_('ORGANIZER_UNIT_REMOVED_ON'), $date);

			$this->entryStatus = 'removed';
		}
		elseif ($item->instanceStatus === 'removed')
		{
			$date  = Helpers\Dates::formatDate($item->instanceStatusDate);
			$class .= ' instance-removed';
			$title = sprintf(Languages::_('ORGANIZER_INSTANCE_REMOVED_ON'), $date);

			$this->entryStatus = 'removed';
		}
		elseif ($item->unitStatus === 'new' and $item->unitStatusDate >= $this->statusDate)
		{
			$date  = Helpers\Dates::formatDate($item->unitStatusDate);
			$class .= ' unit-new';
			$title = sprintf(Languages::_('ORGANIZER_UNIT_ADDED_ON'), $date);

			$this->entryStatus = 'new';
		}
		elseif ($item->instanceStatus === 'new' and $item->instanceStatusDate >= $this->statusDate)
		{
			$date  = Helpers\Dates::formatDate($item->instanceStatusDate);
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

	/**
	 * Creates the event title.
	 *
	 * @param   object  $item  the event item being iterated
	 *
	 * @return array the title column
	 */
	private function getTitle($item)
	{
		$item->comment = $this->resolveLinks($item->comment);

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
	 * Resolves any links/link parameters to iconed links.
	 *
	 * @param   string  $text  the text to search
	 *
	 * @return string
	 */
	private function resolveLinks(string $text)
	{
		$moodleIcon     = '<span class="icon-moodle hasTooltip" title="Moodle Link"></span>';
		$moodleURL      = 'https://moodle.thm.de/course/view.php?id=PID';
		$moodleTemplate = "<a href=\"$moodleURL\" target=\"_blank\">$moodleIcon</a>";

		$template = str_replace('PID', '$4', $moodleTemplate);
		$text     = preg_replace('/(((https?):\/\/)moodle.thm.de\/course\/view.php\?id=(\d+))/', $template, $text);
		$template = str_replace('PID', '$1', $moodleTemplate);
		$text     = preg_replace('/moodle=(\d+)/', $template, $text);

		$netACADIcon = '<span class="icon-cisco hasTooltip" title="Networking Academy Link"></span>';
		$template    = "<a href=\"$1\" target=\"_blank\">$netACADIcon</a>";
		$text        = preg_replace('/(((https?):\/\/)\d+.netacad.com\/courses\/\d+)/', $template, $text);

		$panoptoIcon     = '<span class="icon-panopto hasTooltip" title="Panopto Link"></span>';
		$panoptoURL      = 'https://panopto.thm.de/Panopto/Pages/Viewer.aspx?id=PID';
		$panoptoTemplate = "<a href=\"$panoptoURL\" target=\"_blank\">$panoptoIcon</a>";

		$template = str_replace('PID', '$4', $panoptoTemplate);
		$text     = preg_replace(
			'/(((https?):\/\/)panopto.thm.de\/Panopto\/Pages\/Viewer.aspx\?id=[\d\w\-]+)/',
			$template,
			$text
		);
		$template = str_replace('PID', '$1', $panoptoTemplate);
		$text     = preg_replace('/panopto=([\d\w\-]+)/', $template, $text);

		$pilosIcon  = '<span class="icon-pilos hasTooltip" title="Pilos Link"></span>';
		$pilosREGEX = '/(((https?):\/\/)(pilos\d+.ges.|\d+.pilos-)thm.de\/(b\/)?[\d\w]{3}-[\d\w]{3}-[\d\w]{3})/';
		$template   = "<a href=\"$1\" target=\"_blank\">$pilosIcon</a>";
		$text       = preg_replace($pilosREGEX, $template, $text);

		return $text;
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
			$structuredItems[$index]['groups']  = $this->getResource($item, 'group', 'fullName');
			$structuredItems[$index]['rooms']   = $this->getResource($item, 'room', 'room');

			$index++;
		}

		$this->items = $structuredItems;
	}
}