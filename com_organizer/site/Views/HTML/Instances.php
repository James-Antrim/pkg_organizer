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

use Joomla\CMS\Uri\Uri;
use Organizer\Adapters\Toolbar;
use Organizer\Helpers;
use Organizer\Helpers\Languages;

/**
 * Class loads persistent information a filtered set of instances into the display context.
 */
class Instances extends ListView
{
	private $entryStatus = '';

	private $manages = false;

	private $statusDate;

	private $teaches = false;

	/**
	 * @inheritdoc
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);
		$this->statusDate = date('Y-m-d 00:00:00', strtotime('-14 days'));
	}

	/**
	 * @inheritdoc
	 */
	protected function addToolBar()
	{
		$title = $this->get('title');

		// Add menu title support, both direct and via selected filters
		Helpers\HTML::setTitle($title, 'list-2');
		$toolbar = Toolbar::getInstance();

		$state = $this->state;
		$my    = $state->get('filter.my');

//		if ($my and Helpers\Persons::getIDByUserID())
//		{
//			$toolbar->appendButton(
//				'Standard',
//				'info-calender',
//				Languages::_('ORGANIZER_NEW_INSTANCE'),
//				'Instances.appointment',
//				false
//			);
//		}

		$toolbar->appendButton(
			'NewTab',
			'file-xls',
			Languages::_('ORGANIZER_XLS_SPREADSHEET'),
			'Instances.xls',
			false
		);

		$resourceSelected = ($state->get('filter.groupID') or $state->get('filter.personID') or $state->get('filter.roomID'));

		if ($my or $resourceSelected)
		{
			$toolbar->appendButton(
				'NewTab',
				'file-pdf',
				Languages::_('ORGANIZER_PDF_GRID_A4'),
				'Instances.gridA4',
				false
			);
		}
		else
		{
			$toolbar->appendButton(
				'NewTab',
				'file-pdf',
				Languages::_('ORGANIZER_PDF_GRID_A3'),
				'Instances.gridA3',
				false
			);
		}

		if ($this->manages or $this->teaches)
		{
			$URL = Uri::base() . "index.php?Itemid=4908";
			$toolbar->appendButton('Link', 'help', Languages::_('ORGANIZER_HELP'), $URL, true);
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function authorize()
	{
		if ($this->adminContext)
		{
			if (!$this->manages = Helpers\Can::scheduleTheseOrganizations())
			{
				Helpers\OrganizerHelper::error(403);
			}

			return;
		}

		if (Helpers\Input::getBool('my') and !Helpers\Users::getID())
		{
			Helpers\OrganizerHelper::error(401);
		}

		$organizationID = Helpers\Input::getParams()->get('organizationID', 0);
		$this->manages  = $organizationID ?
			Helpers\Can::manage('organization', $organizationID) : (bool) Helpers\Can::manageTheseOrganizations();
		$this->teaches  = Helpers\Instances::teaches();
	}

	/**
	 * @inheritDoc
	 */
	public function display($tpl = null)
	{
		if (Helpers\Input::getInt('my'))
		{
			if (Helpers\Users::getID())
			{
				$this->empty = Helpers\Languages::_('ORGANIZER_EMPTY_PERSONAL_RESULT_SET');
			}
			else
			{
				$this->empty = Helpers\Languages::_('ORGANIZER_401');
			}
		}

		parent::display($tpl);
	}

	/**
	 * Lists the instance associated teachers.
	 *
	 * @param   object  $item  the instance being iterated
	 *
	 * @return string
	 */
	private function getPersons(object $item): string
	{
		$added   = Languages::_('ORGANIZER_PERSON_ADDED_ON');
		$removed = Languages::_('ORGANIZER_PERSON_REMOVED_ON');
		$roles   = [];

		foreach ($item->resources as $person)
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

		ksort($roles);

		foreach ($roles as $roleID => $persons)
		{
			$roleDisplay = '';

			if (!$roleTitle = Helpers\Roles::getLabel($roleID, count($persons)))
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
	 * @param   object  $item          the instance being iterated
	 * @param   string  $resourceName  the resource type's name
	 * @param   string  $rIndex        the individual resource's name index
	 *
	 * @return string
	 */
	private function getResource(object $item, string $resourceName, string $rIndex): string
	{
		$constant        = strtoupper($resourceName);
		$collectionIndex = $resourceName . 's';

		$added     = Languages::_("ORGANIZER_{$constant}_ADDED_ON");
		$resources = [];
		$removed   = Languages::_("ORGANIZER_{$constant}_REMOVED_ON");

		foreach ($item->resources as $person)
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
	private function getStatus(object $item)
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
	private function getTitle(object $item): array
	{
		$item->comment = $this->resolveLinks($item->comment);

		$name  = '<span class="event">' . $item->name . '</span>';
		$name  .= empty($item->method) ? '' : "<br><span class=\"method\">$item->method</span>";
		$name  .= empty($item->comment) ? '' : "<br><span class=\"comment\">$item->comment</span>";
		$value = $item->link ? Helpers\HTML::_('link', $item->link, $name) : $name;

		return ['attributes' => ['class' => 'title-column'], 'value' => $value];
	}

	/**
	 * Creates the instance tools for the user.
	 *
	 * @param   object  $item
	 *
	 * @return array|string
	 */
	private function getTools(object $item)
	{
		$link = '';

		if ($item->manageable)
		{
			$label = '';
			$icon  = '';
			$URL   = Uri::base() . '?option=com_organizer';

			if ($item->bookingID)
			{
				$label = Languages::_('ORGANIZER_MANAGE_BOOKING');
				$icon  = Helpers\HTML::icon('users', $label, true);
				$URL   = Helpers\Routing::getViewURL('booking', $item->bookingID);
			}
			elseif (!$item->expired and $item->current)
			{
				$label = Languages::_('ORGANIZER_START_BOOKING');
				$icon  = Helpers\HTML::icon('enter', $label, true);
				$URL   = Helpers\Routing::getTaskURL('bookings.add', $item->instanceID);
			}

			if ($label)
			{
				$attribs = ['aria-label' => $label, 'class' => 'btn'];

				$link = Helpers\HTML::link($URL, $icon, $attribs);
			}
		}

		return $link ? ['attributes' => ['class' => 'tools-column'], 'value' => $link] : '';
	}

	/**
	 * Determines whether the item is conducted virtually: every person is assigned rooms, all assigned rooms are virtual.
	 *
	 * @param   object  $item  the item being iterated
	 *
	 * @return bool true if every assigned room is virtual, otherwise false
	 */
	private function isVirtual(object $item): bool
	{
		$virtual = true;

		if (empty($item->resources))
		{
			return false;
		}

		foreach ($item->resources as $person)
		{

			if (empty($person['rooms']))
			{
				return false;
			}

			foreach ($person['rooms'] as $room)
			{
				$virtual = ($virtual and $room['virtual']);
			}
		}

		return $virtual;
	}

	/**
	 * @inheritdoc
	 */
	public function setHeaders()
	{
		$this->headers = [
			'tools'   => '',
			'status'  => '',
			'title'   => ['attributes' => ['class' => 'title-column'], 'value' => Languages::_('ORGANIZER_NAME')],
			'times'   => Languages::_('ORGANIZER_DATETIME'),
			'persons' => Languages::_('ORGANIZER_PERSONS'),
			'groups'  => Languages::_('ORGANIZER_GROUPS'),
			'rooms'   => Languages::_('ORGANIZER_ROOMS')
		];
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
		$moodleIcon     = '<span class="icon-moodle hasTooltip" title="Moodle Link"></span>';
		$moodleURL1     = 'https://moodle.thm.de/course/view.php?id=PID';
		$moodleURL2     = 'https://moodle.thm.de/course/index.php?categoryid=PID';
		$moodleTemplate = "<a href=\"MOODLEURL\" target=\"_blank\">$moodleIcon</a>";

		$template = str_replace('PID', '$4', str_replace('MOODLEURL', $moodleURL1, $moodleTemplate));
		$text     = preg_replace('/(((https?):\/\/)moodle.thm.de\/course\/view.php\?id=(\d+))/', $template, $text);
		$template = str_replace('PID', '$1', str_replace('MOODLEURL', $moodleURL1, $moodleTemplate));
		$text     = preg_replace('/moodle=(\d+)/', $template, $text);
		$template = str_replace('PID', '$4', str_replace('MOODLEURL', $moodleURL2, $moodleTemplate));
		$text     = preg_replace(
			'/(((https?):\/\/)moodle\.thm\.de\/course\/index\.php\\?categoryid=(\\d+))/',
			$template,
			$text
		);

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
		$pilosREGEX = '/(((https?):\/\/)(\d+|roxy).pilos-thm.de\/(b\/)?[\d\w]{3}-[\d\w]{3}-[\d\w]{3})/';
		$template   = "<a href=\"$1\" target=\"_blank\">$pilosIcon</a>";

		return preg_replace($pilosREGEX, $template, $text);
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
			$item->link = '';

			$times = '<span class="date">' . Helpers\Dates::formatDate($item->date) . '</span><br>';
			$times .= '<span class="times">' . $item->startTime . ' - ' . $item->endTime . '</span>';

			if ($item->manageable = Helpers\Can::manage('instance', $item->instanceID))
			{
				$today = date('Y-m-d');
				$then  = date('Y-m-d', strtotime('+2 days'));

				$item->current = ($item->date <= $then);
				$item->expired = ($item->date < $today or ($item->date === $today and $item->endTime < date('H:i:s')));

				// An associated booking exists and has participants

				if (!$item->expired and empty($item->eventID))
				{
					if (!$item->bookingID or !Helpers\Bookings::getParticipantCount($item->bookingID))
					{
						$item->link = Helpers\Routing::getViewURL('InstanceEdit', $item->instanceID);
					}
				}
			}

			$structuredItems[$index]            = [];
			$structuredItems[$index]['tools']   = $this->isVirtual($item) ? '' : $this->getTools($item);
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