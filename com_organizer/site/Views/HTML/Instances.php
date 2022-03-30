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

use Joomla\CMS\Toolbar\Button\StandardButton;
use Joomla\CMS\Uri\Uri;
use Organizer\Adapters\Document;
use Organizer\Adapters\Toolbar;
use Organizer\Buttons;
use Organizer\Helpers;
use Organizer\Helpers\Dates;
use Organizer\Helpers\HTML;
use Organizer\Helpers\Instances as Helper;
use Organizer\Helpers\Languages;
use stdClass;

/**
 * Class loads persistent information a filtered set of instances into the display context.
 */
class Instances extends ListView
{
	use ListsInstances;

	/**
	 * Will later determine whether an edit button will be displayed
	 * @var bool
	 */
	private $allowEdit = false;

	private $courses = [];

	private $expired = true;

	/**
	 * @var \Organizer\Models\Instances
	 */
	protected $model;

	public $noInstances = true;

	private $premature = true;

	/**
	 * Whether the registration is allowed for any instance.
	 * @var bool
	 */
	private $registration = false;

	private $statusDate;

	protected $structureEmpty = true;

	/**
	 * @inheritdoc
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);
		$this->statusDate = date('Y-m-d 00:00:00', strtotime('-14 days'));
	}

	/**
	 * Adds supplemental information to the display output.
	 *
	 * @return void modifies the object property supplement
	 */
	protected function addSupplement()
	{
		if ($this->noInstances)
		{
			$supplement = '<div class="tbox-yellow">';

			if (!$this->model->noDate and $dates = Helper::getJumpDates($this->model->conditions))
			{
				$supplement .= Languages::_('ORGANIZER_NO_INSTANCES_IN_INTERVAL');
				$supplement .= '<ul><li>';

				foreach ($dates as $key => $date)
				{
					$constant      = $key === 'futureDate' ? 'ORGANIZER_NEXT_INSTANCE' : 'ORGANIZER_PREVIOUS_INSTANCE';
					$formattedDate = Dates::formatDate($date);
					$text          = Languages::_($constant);

					$template    = "TEXT: <a onclick=\"jump('DATE')\">FORMATTEDDATE</a>";
					$output      = str_replace('FORMATTEDDATE', $formattedDate, $template);
					$output      = str_replace('DATE', $date, $output);
					$dates[$key] = str_replace('TEXT', $text, $output);
				}

				$supplement .= implode('</li><li>', $dates) . '</li></ul>';
			}
			elseif (Helpers\Input::getInt('my'))
			{
				if (Helpers\Users::getID())
				{
					$supplement .= Languages::_('ORGANIZER_EMPTY_PERSONAL_RESULT_SET');
				}
				else
				{
					$supplement .= Languages::_('ORGANIZER_401');
				}
			}
			else
			{
				$supplement .= Languages::_('ORGANIZER_NO_INSTANCES_IN_INTERVAL');
			}

			$supplement .= '</div>';

			$this->supplement = $supplement;
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function addToolBar(bool $delete = true)
	{
		$this->setTitle($this->get('title'));
		$toolbar  = Toolbar::getInstance();
		$newTab   = new Buttons\NewTab();
		$script   = new Buttons\Script();
		$standard = new StandardButton();

		if ($this->mobile)
		{
			$toolbar->appendButton('Script', 'info-calender', Languages::_('ORGANIZER_ICS_CALENDAR'), 'onclick',
				'makeLink()');
		}
		else
		{
			if (Helpers\Users::getID() and $this->model->layout === Helper::LIST)
			{
				if (!$this->expired and !$this->teachesALL)
				{
					$add    = $standard->fetchButton(
						'Standard',
						'bookmark',
						Languages::_('ORGANIZER_ADD_MY_INSTANCES'),
						'InstanceParticipants.bookmark',
						true
					);
					$remove = $standard->fetchButton(
						'Standard',
						'bookmark-2',
						Languages::_('ORGANIZER_DELETE_MY_INSTANCES'),
						'InstanceParticipants.removeBookmark',
						true
					);
					$toolbar->appendButton(
						'Buttons',
						'buttons',
						Languages::_('ORGANIZER_INSTANCES'),
						[$add, $remove],
						'bookmark'
					);
				}

				if ($this->registration and !$this->premature and !$this->teachesALL)
				{
					$register   = $standard->fetchButton(
						'Standard',
						'signup',
						Languages::_('ORGANIZER_REGISTER'),
						'InstanceParticipants.register',
						true
					);
					$deregister = $standard->fetchButton(
						'Standard',
						'exit',
						Languages::_('ORGANIZER_DEREGISTER'),
						'InstanceParticipants.deregister',
						true
					);
					$toolbar->appendButton(
						'Buttons',
						'buttons',
						Languages::_('ORGANIZER_PRESENCE_PARTICIPATION'),
						[$register, $deregister],
						'signup'
					);
				}

				if (($this->manages or $this->teaches) and !$this->premature)
				{
					$toolbar->appendButton(
						'Highlander',
						'users',
						Languages::_('ORGANIZER_MANAGE_BOOKING'),
						'bookings.manage',
						true
					);
				}
			}

			$gridA3Button = $newTab->fetchButton(
				'NewTab',
				'file-pdf',
				Languages::_('ORGANIZER_PDF_GRID_A3'),
				'Instances.gridA3',
				false
			);

			$gridA4Button = $newTab->fetchButton(
				'NewTab',
				'file-pdf',
				Languages::_('ORGANIZER_PDF_GRID_A4'),
				'Instances.gridA4',
				false
			);

			$icsButton = $script->fetchButton(
				'Script',
				'info-calender',
				Languages::_('ORGANIZER_ICS_CALENDAR'),
				'onclick',
				'makeLink()'
			);

			$xlsButton = $newTab->fetchButton(
				'NewTab',
				'file-xls',
				Languages::_('ORGANIZER_XLS_LIST'),
				'Instances.xls',
				false
			);

			$exportButtons = [
				Languages::_('ORGANIZER_ICS_CALENDAR')    => $icsButton,
				Languages::_('ORGANIZER_PDF_GRID_A3')     => $gridA3Button,
				Languages::_('ORGANIZER_PDF_GRID_A4')     => $gridA4Button,
				Languages::_('ORGANIZER_XLS_SPREADSHEET') => $xlsButton
			];

			ksort($exportButtons);
			$toolbar->appendButton('Buttons', 'buttons', Languages::_('ORGANIZER_EXPORT'), $exportButtons, 'download');
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
	}

	/**
	 * @inheritDoc
	 */
	public function display($tpl = null)
	{
		$this->empty = '';

		parent::display($tpl);
	}

	/**
	 * Creates the blocks used to display a grid schedule from the raw grid periods.
	 *
	 * @param   array  $periods  the raw periods data
	 * @param   bool   $allDay   whether the grid consists of a single block for the whole day
	 *
	 * @return array
	 */
	private function getBlocks(array $periods, bool &$allDay): array
	{
		$blocks = [];
		$tag    = Languages::getTag();

		foreach ($periods as $period)
		{
			$block              = [];
			$block['key']       = "{$period['startTime']}-{$period['endTime']}";
			$block['endTime']   = Dates::formatEndTime($period['endTime']);
			$block['startTime'] = Dates::formatTime($period['startTime']);
			$block['type']      = $period['type'];

			if (!empty($period["label_$tag"]))
			{
				$block['label'] = $period["label_$tag"];
			}
			else
			{
				$allDay         = ($block['endTime'] === '00:00' and $block['startTime'] === '00:00');
				$block['label'] = $allDay ? '' : "{$block['startTime']}<br>-<br>{$block['endTime']}";
			}

			$blocks[] = $block;
		}

		return $blocks;
	}

	/**
	 * Generates a grid structure based upon the frame parameters, which can then later be filled with appointments.
	 *
	 * @param   array   $blocks   the daily structure of the grid
	 * @param   array  &$headers  the column headers
	 * @param   bool    $allDay   whether the grid consists of a single block lasting the whole day
	 *
	 * @return array the grid structure to fill with appointments
	 */
	private function getGrid(array $blocks, array &$headers, bool $allDay): array
	{
		$conditions = $this->model->conditions;
		$rawGrid    = $this->model->grid;

		$endDate   = $conditions['endDate'];
		$endDoW    = empty($rawGrid['endDay']) ? 6 : $rawGrid['endDay'];
		$startDate = $conditions['startDate'];
		$startDoW  = empty($rawGrid['startDay']) ? 1 : $rawGrid['startDay'];
		$grid      = [];
		for ($current = $startDate; $current <= $endDate;)
		{
			$currentDT = strtotime($current);
			$day       = date('w', $currentDT);

			if ($day >= $startDoW and $day <= $endDoW)
			{
				$day               = date('l', $currentDT);
				$headers[$current] = Languages::_($day) . '<br>' . Dates::formatDate($current);

				foreach ($blocks as $block)
				{
					$key = $block['key'];

					if (!$allDay)
					{
						$grid[$key]['times'] = $block['label'];
						$grid[$key]['type']  = $block['type'];
					}

					$busy = Helpers\InstanceParticipants::isBusy($current, $block['startTime'], $block['endTime']);

					$grid[$key]['endTime']   = $block['endTime'] === '00:00' ? '23:59' : $block['endTime'];
					$grid[$key]['startTime'] = $block['startTime'];

					// Create a container for instances to appear
					$grid[$key][$current] = ['busy' => $busy, 'instances' => []];
				}
			}

			$current = date('Y-m-d', strtotime('+1 Day', $currentDT));
		}

		return $grid;
	}

	/**
	 * Creates the event title.
	 *
	 * @param   stdClass  $item  the event item being iterated
	 *
	 * @return array the title column
	 */
	private function getTitle(stdClass $item): array
	{
		$name = '<span class="event">' . $item->name . '</span>';

		$title = '<span class="date">' . Helpers\Dates::formatDate($item->date) . '</span> ';
		$title .= '<span class="times">' . $item->startTime . ' - ' . $item->endTime . '</span><br>';
		$title .= HTML::_('link', $item->link, $name, ['target' => '_blank']);
		$title .= empty($item->method) ? '' : "<br><span class=\"method\">$item->method</span>";

		return $this->liGetTitle($item, $title);
	}

	/**
	 * Creates output for individual instances and assigns them to the day/block coordinates in which they will be
	 * displayed.
	 *
	 * @param   array  $grid  the grid used to structure the instances for display
	 *
	 * @return void
	 */
	private function fillGrid(array &$grid)
	{
		foreach ($this->items as $item)
		{
			$cClass = 'grid-item';
			$iClass = 'warning-2';
			$notice = '';

			// If removed are here at all, the status holds relevance regardless of date
			if ($item->unitStatus === 'removed')
			{
				$cClass  .= ' removed';
				$iClass  .= ' unit-removed';
				$date    = Dates::formatDate($item->unitStatusDate);
				$message = sprintf(Languages::_('ORGANIZER_UNIT_REMOVED_ON'), $date);
				$notice  = HTML::icon($iClass, $message);
			}
			elseif ($item->instanceStatus === 'removed')
			{
				$cClass  .= ' removed';
				$iClass  .= ' instance-removed';
				$date    = Dates::formatDate($item->instanceStatusDate);
				$message = sprintf(Languages::_('ORGANIZER_INSTANCE_REMOVED_ON'), $date);
				$notice  = HTML::icon($iClass, $message);
			}
			elseif ($item->unitStatus === 'new' and $item->unitStatusDate >= $this->statusDate)
			{
				$cClass  .= ' new';
				$iClass  .= ' unit-new';
				$date    = Dates::formatDate($item->instanceStatusDate);
				$message = sprintf(Languages::_('ORGANIZER_UNIT_ADDED_ON'), $date);
				$notice  = HTML::icon($iClass, $message);
			}
			elseif ($item->instanceStatus === 'new' and $item->instanceStatusDate >= $this->statusDate)
			{
				$cClass  .= ' new';
				$iClass  .= ' instance-new';
				$date    = Dates::formatDate($item->instanceStatusDate);
				$message = sprintf(Languages::_('ORGANIZER_INSTANCE_ADDED_ON'), $date);
				$notice  = HTML::icon($iClass, $message);
			}

			$times = '<span class="times">' . $item->startTime . ' - ' . $item->endTime . '</span><br>';
			$title = $item->name;
			$key   = $title;
			Languages::unpack($title);
			$title = '<span class="event">' . $title . '</span>';
			$title = HTML::_('link', $item->link, $title);

			if (empty($item->method))
			{
				$method = '';
			}
			else
			{
				$method = "<br><span class=\"method\">$item->method</span>";
				$key    .= $item->method;
			}

			$key .= "$item->instanceID";

			$persons = '';

			if (empty($this->state->get('filter.personID')) and $item->persons)
			{
				$persons = '<br>' . $item->persons;
			}

			$groups = '';

			if (empty($this->state->get('filter.groupID')) and $item->groups)
			{
				$groups = '<br>' . $item->groups;
			}

			$rooms = '';

			if (empty($this->state->get('filter.roomID')) and $item->rooms)
			{
				$rooms = '<br>' . $item->rooms;
			}

			$chain = '';

			if ($item->courseID)
			{
				$chain = '<br>' . HTML::icon('link hasToolTip',
						Languages::_('ORGANIZER_REGISTRATION_LINKED')) . ' ';
				$chain .= Languages::_('ORGANIZER_INSTANCE_SERIES') . ": $item->courseID";
			}

			$tools = [];

			if (Helpers\Users::getID())
			{
				$instanceID = $item->instanceID;

				if ($item->manageable)
				{
					if ($item->presence !== Helper::ONLINE and !$item->premature)
					{
						$label   = Languages::_('ORGANIZER_MANAGE_BOOKING');
						$attribs = ['aria-label' => $label];
						$icon    = HTML::icon('users', $label, true);
						$url     = '';

						if ($item->bookingID)
						{
							$url = Helpers\Routing::getViewURL('booking', $item->bookingID);
						}
						elseif ($item->registration and !$item->expired)
						{
							$url = Helpers\Routing::getTaskURL('bookings.manage', $instanceID);
						}

						if ($url)
						{
							$tools[] = HTML::link($url, $icon, $attribs);
						}
					}
				}
				// Virtual and full appointments can still be added to the personal calendar
				elseif (!$item->expired)
				{
					if (!$item->running)
					{
						if ($item->bookmarked)
						{
							$label = Languages::_('ORGANIZER_REMOVE_BOOKMARK');
							$icon  = HTML::icon('bookmark', $label, true);
							$url   = Helpers\Routing::getTaskURL('InstanceParticipants.removeBookmarkBlock',
								$instanceID);
						}
						else
						{
							$label = Languages::_('ORGANIZER_BOOKMARK');
							$icon  = HTML::icon('bookmark-2', $label, true);
							$url   = Helpers\Routing::getTaskURL('InstanceParticipants.bookmarkBlock', $instanceID);
						}

						$tools[] = HTML::link($url, $icon, ['aria-label' => $label]);
					}

					if ($item->presence !== Helper::ONLINE)
					{
						if ($item->running)
						{
							$tools[] = HTML::icon('stop', Languages::_('ORGANIZER_REGISTRATION_CLOSED'));
						}
						elseif (Helper::getMethodCode($item->instanceID) === Helpers\Methods::FINALCODE)
						{
							$tip     = Languages::_('ORGANIZER_REGISTRATION_EXTERNAL_TIP');
							$icon    = HTML::icon('out', $tip);
							$url     = "https://ecampus.thm.de";
							$tools[] = HTML::link($url, $icon, ['aria-label' => $tip]);
						}
						elseif ($item->premature)
						{
							$text    = Languages::_('ORGANIZER_REGISTRATION_BEGINS_ON');
							$tip     = sprintf($text, $item->registrationStart);
							$tools[] = HTML::icon('unlock', $tip);
						}
						elseif ($item->full)
						{
							$tip     = Languages::_('ORGANIZER_INSTANCE_FULL');
							$tools[] = HTML::icon('pause', $tip);
						}
						elseif ($item->registered)
						{
							$tip     = Languages::_('ORGANIZER_REGISTERED_DEREGISTER');
							$icon    = HTML::icon('signup', $tip);
							$url     = Helpers\Routing::getTaskURL('InstanceParticipants.deregister', $instanceID);
							$tools[] = HTML::link($url, $icon, ['aria-label' => $tip]);
						}
						else
						{
							$tip     = Languages::_('ORGANIZER_REGISTER');
							$icon    = HTML::icon('play', $tip);
							$url     = Helpers\Routing::getTaskURL('InstanceParticipants.register', $instanceID);
							$tools[] = HTML::link($url, $icon, ['aria-label' => $tip]);
						}
					}
				}
			}

			if ($item->subjectID)
			{
				$sIcon   = HTML::icon('book hasToolTip', Languages::_('ORGANIZER_READ_SUBJECT_DOCUMENTATION'));
				$sURL    = Helpers\Routing::getViewURL('SubjectItem', $item->subjectID);
				$tools[] = HTML::link($sURL, $sIcon);
			}

			$itemIcon = HTML::icon('info-circle hasToolTip', Languages::_('ORGANIZER_ITEM_VIEW'));
			$tools[]  = HTML::_('link', $item->link, $itemIcon);

			$comment = $this->resolveLinks($item->comment, $tools);
			$comment = empty(trim($comment)) ? '' : "<br><span class=\"comment\">$comment</span>";

			foreach ($grid as &$items)
			{
				// Date is assumed to exist
				if ($items['startTime'] >= $item->endTime or $items['endTime'] <= $item->startTime)
				{
					continue;
				}

				$blockKey = str_replace(':', '', "$item->startTime$item->endTime");
				$iKey     = $blockKey . $key;

				$entry = $cClass ? "<div class=\"$cClass\"><div class=\"notice\">$notice</div>" : '<div>';
				$entry .= ($items['startTime'] !== $item->startTime or $item->endTime !== $items['endTime']) ? $times : '';
				$entry .= $title . $method . $persons . $groups . $rooms . $comment . $chain;
				$entry .= "<div class=\"grid-tools\">" . implode(' ', $tools) . '</div>';
				$entry .= '</div>';

				$items[$item->date]['instances'][$iKey] = $entry;
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		$variables = [
			'ICS_URL' => Uri::base() . '?option=com_organizer&view=instances&format=ics'
		];

		if ($this->mobile)
		{
			$date    = $this->model->conditions['date'];
			$stamp   = strtotime($date);
			$tOffset = ((int) date('w', $stamp) === 6) ? '+2 days' : '+1 day';
			$yOffset = ((int) date('w', $stamp) === 1) ? '-2 days' : '-1 day';

			$variables['tomorrow']  = date('Y-m-d', strtotime($tOffset, $stamp));
			$variables['yesterday'] = date('Y-m-d', strtotime($yOffset, $stamp));
		}

		$list   = Helpers\Input::getListItems();
		$params = Helpers\Input::getParams();

		if (($params->get('my') or $list->get('my')) and Helpers\Users::getID())
		{
			$variables['auth']     = Helpers\Users::getAuth();
			$variables['my']       = 1;
			$variables['username'] = Helpers\Users::getUserName();
		}
		else
		{
			if ($campusID = $params->get('campusID'))
			{
				$variables['campusID'] = $campusID;
			}

			if ($methodIDs = $params->get('methodIDs'))
			{
				$variables['methodID'] = implode(',', $methodIDs);
			}

			if ($organizationID = $params->get('organizationID'))
			{
				$variables['organizationID'] = $organizationID;
			}
		}

		Languages::script('ORGANIZER_GENERATE_LINK');
		Document::addScriptOptions('variables', $variables);
		Document::addScript(Uri::root() . 'components/com_organizer/js/ics.js');
		Document::addScript(Uri::root() . 'components/com_organizer/js/jump.js');

		if ($this->model->layout === Helper::GRID)
		{
			Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/grid.css');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function setHeaders()
	{
		$this->headers = [
			'tools'   => '',
			'title'   => ['attributes' => ['class' => 'title-column'], 'value' => Languages::_('ORGANIZER_INSTANCE')],
			'status'  => Languages::_('ORGANIZER_STATUS'),
			'persons' => Languages::_('ORGANIZER_PERSONS'),
			'groups'  => Languages::_('ORGANIZER_GROUPS'),
			'rooms'   => Languages::_('ORGANIZER_ROOMS')
		];

		if (Helpers\Users::getID() and !$this->mobile)
		{
			$this->headers['tools'] = HTML::_('grid.checkall');
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function setSubtitle()
	{
		if ($interval = $this->state->get('list.interval') and $interval === 'quarter')
		{
			$date           = $this->state->get('list.date');
			$interval       = Helpers\Dates::getQuarter($date);
			$interval       = Helpers\Dates::getDisplay($interval['startDate'], $interval['endDate']);
			$this->subtitle = "<h6 class=\"sub-title\">$interval</h6>";
		}
	}

	/**
	 * Structures the instances to be presented in a weekly/daily plan (grid).
	 *
	 * @return void
	 */
	private function structureGrid()
	{
		$this->filterForm->setValue('gridID', 'list', $this->model->gridID);

		$allDay  = false;
		$rawGrid = $this->model->grid;
		$blocks  = $this->getBlocks($rawGrid['periods'], $allDay);

		$headers = $allDay ? [] : ['times' => Languages::_('ORGANIZER_TIMES')];
		$grid    = $this->getGrid($blocks, $headers, $allDay);

		$this->fillGrid($grid);

		foreach ($grid as $key => $dates)
		{
			foreach ($dates as $date => $instances)
			{
				if (in_array($date, ['endTime', 'startTime', 'times', 'type']))
				{
					continue;
				}

				ksort($instances['instances']);
				$grid[$key][$date]['instances'] = implode('', $instances['instances']);
			}
		}

		$this->headers = $headers;
		$this->items   = $grid;
	}

	/**
	 * @inheritdoc
	 */
	protected function structureItems()
	{
		if (!empty($this->items))
		{
			$this->noInstances = false;
			$this->setDerived($this->items);
		}

		if ($this->model->layout === Helper::GRID)
		{

			// Prevent setting the grid id without having the context from items at least once
			if (empty($this->items))
			{
				$this->filterForm->removeField('gridID', 'list');
			}

			$this->layout = 'grid';
			$this->structureGrid();

			return;
		}

		$this->layout = 'list';
		$this->structureList();
	}

	/**
	 * Structures the instances to be presented in a list/html table.
	 *
	 * @return void
	 */
	private function structureList()
	{
		$index     = 0;
		$listItems = [];

		foreach ($this->items as $item)
		{
			if (!$item->expired)
			{
				$this->expired = false;
			}

			if (!$item->premature)
			{
				$this->premature = false;
			}

			if (Helper::getMethodCode($item->instanceID) !== Helpers\Methods::FINALCODE and $item->registration === true)
			{
				$this->registration = true;
			}

			$listItems[$index]           = [];
			$listItems[$index]['tools']  = $this->getToolsColumn($item, $index);
			$listItems[$index]['title']  = $this->getTitle($item);
			$listItems[$index]['status'] = $this->getStatus($item);
			$this->addResources($listItems[$index], $item);

			$index++;
		}

		$this->items = $listItems;
	}
}
