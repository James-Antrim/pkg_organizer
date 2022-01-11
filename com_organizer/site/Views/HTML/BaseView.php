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

use JHtmlSidebar;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Uri\Uri;
use Organizer\Adapters;
use Organizer\Helpers;
use Organizer\Helpers\Languages;
use Organizer\Helpers\Routing;
use Organizer\Views\Named;

/**
 * Base class for a Joomla View
 *
 * Class holding methods for displaying presentation data.
 */
abstract class BaseView extends HtmlView
{
	use Named;

	public $adminContext;

	public $disclaimer = '';

	public $form;

	/**
	 * The name of the layout to use during rendering.
	 *
	 * @var string
	 */
	protected $layout = 'default';

	public $mobile = false;

	/**
	 * Inheritance stems from BaseDatabaseModel, not BaseModel. BaseDatabaseModel is higher in the Joomla internal
	 * hierarchy used for Joomla Admin, Form, List, ... models which in turn are the parents for the Organizer abstract
	 * classes of similar names.
	 *
	 * @var BaseDatabaseModel
	 */
	protected $model;

	public $refresh = 0;

	public $submenu = '';

	public $subtitle = '';

	public $supplement = '';

	public $title = '';

	/**
	 * @inheritdoc
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);
		$this->adminContext = Helpers\OrganizerHelper::getApplication()->isClient('administrator');
		$this->mobile       = Helpers\OrganizerHelper::isSmartphone();
	}

	/**
	 * Adds a legal disclaimer to the view.
	 *
	 * @return void modifies the class property disclaimer
	 */
	protected function addDisclaimer()
	{
		if ($this->adminContext)
		{
			return;
		}

		$thisClass = Helpers\OrganizerHelper::getClass($this);
		if (!in_array($thisClass, ['Curriculum', 'SubjectItem', 'Subjects']))
		{
			return;
		}

		Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/disclaimer.css');

		$attributes = ['target' => '_blank'];

		$lsfLink = Helpers\HTML::link(
			'https://studien-sb-service.th-mittelhessen.de/docu/online.html',
			Languages::_('ORGANIZER_DISCLAIMER_LSF_TITLE'),
			$attributes
		);
		$ambLink = Helpers\HTML::link(
			'https://www.thm.de/amb/pruefungsordnungen',
			Languages::_('ORGANIZER_DISCLAIMER_AMB_TITLE'),
			$attributes
		);
		$poLink  = Helpers\HTML::link(
			'https://www.thm.de/site/studium/sie-studieren/pruefungsordnung.html',
			Languages::_('ORGANIZER_DISCLAIMER_PO_TITLE'),
			$attributes
		);

		$disclaimer = '<div class="disclaimer">';
		$disclaimer .= '<h4>' . Languages::_('ORGANIZER_DISCLAIMER_LEGAL') . '</h4>';
		$disclaimer .= '<ul>';
		$disclaimer .= '<li>' . sprintf(Languages::_('ORGANIZER_DISCLAIMER_LSF_TEXT'), $lsfLink) . '</li>';
		$disclaimer .= '<li>' . sprintf(Languages::_('ORGANIZER_DISCLAIMER_AMB_TEXT'), $ambLink) . '</li>';
		$disclaimer .= '<li>' . sprintf(Languages::_('ORGANIZER_DISCLAIMER_PO_TEXT'), $poLink) . '</li>';
		$disclaimer .= '</ul>';
		$disclaimer .= '</div>';

		$this->disclaimer = $disclaimer;
	}

	/**
	 * Adds the component menu to the view.
	 *
	 * @return void
	 */
	protected function addMenu()
	{
		if (!$this->adminContext)
		{
			return;
		}

		Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/sidebar.css');

		$viewName = strtolower($this->get('name'));

		JHtmlSidebar::addEntry(
			'<span class="icon-home"></span>' . Languages::_('ORGANIZER'),
			Routing::getViewURL('Organizer'),
			$viewName == 'organizer'
		);

		$admin = Helpers\Can::administrate();

		if (Helpers\Can::scheduleTheseOrganizations())
		{
			$spanText = '<span class="menu-spacer">' . Languages::_('ORGANIZER_SCHEDULING') . '</span>';
			JHtmlSidebar::addEntry($spanText, '', false);

			$items = [];

			$items[Languages::_('ORGANIZER_CATEGORIES')]     = [
				'url'    => Routing::getViewURL('Categories'),
				'active' => $viewName == 'categories'
			];
			$items[Languages::_('ORGANIZER_COURSES')]        = [
				'url'    => Routing::getViewURL('Courses'),
				'active' => $viewName == 'courses'
			];
			$items[Languages::_('ORGANIZER_COURSES_IMPORT')] = [
				'url'    => Routing::getViewURL('CoursesImport'),
				'active' => $viewName == 'courses_import'
			];
			$items[Languages::_('ORGANIZER_EVENTS')]         = [
				'url'    => Routing::getViewURL('Events'),
				'active' => $viewName == 'events'
			];
			$items[Languages::_('ORGANIZER_GROUPS')]         = [
				'url'    => Routing::getViewURL('Groups'),
				'active' => $viewName == 'groups'
			];
			$items[Languages::_('ORGANIZER_RUNS')]           = [
				'url'    => Routing::getViewURL('Runs'),
				'active' => $viewName == 'runs'
			];
			$items[Languages::_('ORGANIZER_SCHEDULES')]      = [
				'url'    => Routing::getViewURL('Schedules'),
				'active' => $viewName == 'schedules'
			];
			$items[Languages::_('ORGANIZER_UNITS')]          = [
				'url'    => Routing::getViewURL('Units'),
				'active' => $viewName == 'units'
			];

			ksort($items);

			// Uploading a schedule should always be the first menu item and will never be the active submenu item.
			$prepend = [
				Languages::_('ORGANIZER_SCHEDULE_UPLOAD') . ' <span class="icon-upload"></span>' => [
					'url'    => Routing::getViewURL('ScheduleEdit'),
					'active' => false
				]
			];

			$items = $prepend + $items;

			foreach ($items as $key => $value)
			{
				JHtmlSidebar::addEntry($key, $value['url'], $value['active']);
			}
		}

		if (Helpers\Can::documentTheseOrganizations())
		{
			$spanText = '<span class="menu-spacer">' . Languages::_('ORGANIZER_DOCUMENTATION') . '</span>';
			JHtmlSidebar::addEntry($spanText, '', false);

			$items = [];

			$items[Languages::_('ORGANIZER_FIELD_COLORS')] = [
				'url'    => Routing::getViewURL('FieldColors'),
				'active' => $viewName == 'field_colors'
			];
			$items[Languages::_('ORGANIZER_POOLS')]        = [
				'url'    => Routing::getViewURL('Pools'),
				'active' => $viewName == 'pools'
			];
			$items[Languages::_('ORGANIZER_PROGRAMS')]     = [
				'url'    => Routing::getViewURL('Programs'),
				'active' => $viewName == 'programs'
			];
			$items[Languages::_('ORGANIZER_SUBJECTS')]     = [
				'url'    => Routing::getViewURL('Subjects'),
				'active' => $viewName == 'subjects'
			];
			ksort($items);
			foreach ($items as $key => $value)
			{
				JHtmlSidebar::addEntry($key, $value['url'], $value['active']);
			}
		}

		if (Helpers\Can::manage('persons'))
		{
			$spanText = '<span class="menu-spacer">' . Languages::_('ORGANIZER_HUMAN_RESOURCES') . '</span>';
			JHtmlSidebar::addEntry($spanText, '', false);
			JHtmlSidebar::addEntry(
				Languages::_('ORGANIZER_PERSONS'),
				Routing::getViewURL('Persons'),
				$viewName == 'persons'
			);
		}

		if (Helpers\Can::manage('facilities'))
		{
			$spanText = '<span class="menu-spacer">' . Languages::_('ORGANIZER_FACILITY_MANAGEMENT') . '</span>';
			JHtmlSidebar::addEntry($spanText, '', false);

			$items = [];

			$items[Languages::_('ORGANIZER_BUILDINGS')]    = [
				'url'    => Routing::getViewURL('Buildings'),
				'active' => $viewName == 'buildings'
			];
			$items[Languages::_('ORGANIZER_CAMPUSES')]     = [
				'url'    => Routing::getViewURL('Campuses'),
				'active' => $viewName == 'campuses'
			];
			$items[Languages::_('ORGANIZER_MONITORS')]     = [
				'url'    => Routing::getViewURL('Monitors'),
				'active' => $viewName == 'monitors'
			];
			$items[Languages::_('ORGANIZER_SURFACES')]     = [
				'url'    => Routing::getViewURL('Surfaces'),
				'active' => $viewName == 'surfaces'
			];
			$items[Languages::_('ORGANIZER_ROOMS')]        = [
				'url'    => Routing::getViewURL('Rooms'),
				'active' => $viewName == 'rooms'
			];
			$items[Languages::_('ORGANIZER_ROOMS_IMPORT')] = [
				'url'    => Routing::getViewURL('RoomsImport'),
				'active' => $viewName == 'rooms_import'
			];
			$items[Languages::_('ORGANIZER_ROOMTYPES')]    = [
				'url'    => Routing::getViewURL('Roomtypes'),
				'active' => $viewName == 'roomtypes'
			];
			ksort($items);
			foreach ($items as $key => $value)
			{
				JHtmlSidebar::addEntry($key, $value['url'], $value['active']);
			}
		}

		if ($admin)
		{
			$spanText = '<span class="menu-spacer">' . Languages::_('ORGANIZER_ADMINISTRATION') . '</span>';
			JHtmlSidebar::addEntry($spanText, '', false);

			$items = [];

			$items[Languages::_('ORGANIZER_COLORS')]        = [
				'url'    => Routing::getViewURL('Colors'),
				'active' => $viewName == 'colors'
			];
			$items[Languages::_('ORGANIZER_DEGREES')]       = [
				'url'    => Routing::getViewURL('Degrees'),
				'active' => $viewName == 'degrees'
			];
			$items[Languages::_('ORGANIZER_FIELDS')]        = [
				'url'    => Routing::getViewURL('Fields'),
				'active' => $viewName == 'fields'
			];
			$items[Languages::_('ORGANIZER_GRIDS')]         = [
				'url'    => Routing::getViewURL('Grids'),
				'active' => $viewName == 'grids'
			];
			$items[Languages::_('ORGANIZER_HOLIDAYS')]      = [
				'url'    => Routing::getViewURL('Holidays'),
				'active' => $viewName == 'holidays'
			];
			$items[Languages::_('ORGANIZER_METHODS')]       = [
				'url'    => Routing::getViewURL('Methods'),
				'active' => $viewName == 'methods'
			];
			$items[Languages::_('ORGANIZER_ORGANIZATIONS')] = [
				'url'    => Routing::getViewURL('Organizations'),
				'active' => $viewName == 'organizations'
			];
			$items[Languages::_('ORGANIZER_PARTICIPANTS')]  = [
				'url'    => Routing::getViewURL('Participants'),
				'active' => $viewName == 'participants'
			];
			$items[Languages::_('ORGANIZER_TERMS')]         = [
				'url'    => Routing::getViewURL('Terms'),
				'active' => $viewName == 'terms'
			];
			ksort($items);
			foreach ($items as $key => $value)
			{
				JHtmlSidebar::addEntry($key, $value['url'], $value['active']);
			}
		}

		$this->submenu = JHtmlSidebar::render();
	}

	/**
	 * Get the layout. Overwrites the use of the prefixed parent property.
	 *
	 * @return  string  The layout name
	 */
	public function getLayout(): string
	{
		return $this->layout;
	}

	/**
	 * Modifies document and adds scripts and styles.
	 *
	 * @return void
	 */
	protected function modifyDocument()
	{
		Adapters\Document::setCharset();
		Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/global.css');
		Adapters\Document::addStyleSheet(Uri::root() . 'media/jui/css/bootstrap-extended.css');

		Helpers\HTML::_('bootstrap.tooltip', '.hasTooltip', ['placement' => 'right']);
	}

	/**
	 * @inheritDoc
	 */
	public function setLayout($layout): string
	{
		// Default is not an option anymore.
		if ($layout === 'default' and $this->layout === 'default')
		{
			$exists     = false;
			$layoutName = strtolower($this->getName());

			foreach ($this->_path['template'] as $path)
			{
				$exists = file_exists("$path$layoutName.php");
				if ($exists)
				{
					break;
				}
			}

			if (!$exists)
			{
				Helpers\OrganizerHelper::error(501);
			}

			$this->layout = strtolower($this->getName());
		}
		elseif ($layout !== 'default')
		{
			$this->layout = $layout;
		}

		return $this->layout;
	}

	/**
	 * @inheritDoc
	 */
	public function setModel($model, $default = false): BaseDatabaseModel
	{
		$this->model = parent::setModel($model, $default);

		return $this->model;
	}

	/**
	 * Prepares the title for standard HTML output.
	 *
	 * @param   string  $standard     the title to display
	 * @param   string  $conditional  the conditional title to display
	 *
	 * @return void
	 */
	protected function setTitle(string $standard, string $conditional = '')
	{
		$app    = Helpers\OrganizerHelper::getApplication();
		$params = Helpers\Input::getParams();

		if ($params->get('show_page_heading') and $params->get('page_title'))
		{
			$title = $params->get('page_title');
		}
		else
		{
			$title = empty($conditional) ? Languages::_($standard) : $conditional;
		}

		$layout = new FileLayout('joomla.toolbar.title');
		$title  = $layout->render(['title' => $title]);

		// Backend => Joomla standard title/toolbar output property declared dynamically by Joomla
		/** @noinspection PhpUndefinedFieldInspection */
		$app->JComponentTitle = $title;

		// Frontend => self developed title/toolbar output
		$this->title = $title;

		Adapters\Document::setTitle(strip_tags($title) . ' - ' . $app->get('sitename'));
	}
}
