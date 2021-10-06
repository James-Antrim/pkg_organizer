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
	 * Whether the registration is allowed for any instance.
	 * @var bool
	 */
	private $registration = false;

	private $statusDate;

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
		$toolbar  = Toolbar::getInstance();
		$newTab   = new Buttons\NewTab();
		$script   = new Buttons\Script();
		$standard = new StandardButton();

		$icsButton = $script->fetchButton(
			'Script',
			'info-calender',
			Languages::_('ORGANIZER_ICS_CALENDAR'),
			'onclick',
			'makeLink()'
		);

		if ($this->mobile)
		{
			$toolbar->appendButton('Script', 'info-calender', Languages::_('ORGANIZER_ICS_CALENDAR'), 'onclick',
				'makeLink()');
		}
		else
		{
			if (Helpers\Users::getID())
			{
				if (!$this->expired and !$this->teachesALL)
				{
					$add    = $standard->fetchButton(
						'Standard',
						'bookmark',
						Languages::_('ORGANIZER_ADD_MY_INSTANCES'),
						'InstanceParticipants.schedule',
						true
					);
					$remove = $standard->fetchButton(
						'Standard',
						'bookmark-2',
						Languages::_('ORGANIZER_DELETE_MY_INSTANCES'),
						'InstanceParticipants.deschedule',
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

				if ($this->registration and !$this->teachesALL)
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

				if ($this->manages or $this->teaches)
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
		$title .= Helpers\HTML::_('link', $item->link, $name, ['target' => '_blank']);
		$title .= empty($item->method) ? '' : "<br><span class=\"method\">$item->method</span>";

		return $this->liGetTitle($item, $title);
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

		$params = Helpers\Input::getParams();

		if ($params->get('my') and Helpers\Users::getID())
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
			$this->headers['tools'] = Helpers\HTML::_('grid.checkall');
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
	 * @inheritdoc
	 */
	protected function structureItems()
	{
		$this->setDerived($this->items);

		$index           = 0;
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			if (!$item->expired)
			{
				$this->expired = false;
			}

			if ($item->registration === true)
			{
				$this->registration = true;
			}

			$structuredItems[$index]           = [];
			$structuredItems[$index]['tools']  = $this->getToolsColumn($item, $index);
			$structuredItems[$index]['title']  = $this->getTitle($item);
			$structuredItems[$index]['status'] = $this->getStatus($item);
			$this->addResources($structuredItems[$index], $item);

			$index++;
		}

		$this->items = $structuredItems;
	}
}
