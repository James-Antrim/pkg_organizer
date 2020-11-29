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
use Joomla\CMS\Uri\Uri;
use Organizer\Adapters;
use Organizer\Helpers;
use Organizer\Helpers\Languages;
use Organizer\Views\BaseView;

/**
 * Base class for a Joomla View
 *
 * Class holding methods for displaying presentation data.
 */
abstract class BaseHTMLView extends BaseView
{
	public $disclaimer = '';

	public $refresh = 0;

	public $submenu = '';

	public $subtitle = '';

	public $supplement = '';

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
			'http://www.thm.de/amb/pruefungsordnungen',
			Languages::_('ORGANIZER_DISCLAIMER_AMB_TITLE'),
			$attributes
		);
		$poLink  = Helpers\HTML::link(
			'http://www.thm.de/site/studium/sie-studieren/pruefungsordnung.html',
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
			'index.php?option=com_organizer&amp;view=organizer',
			$viewName == 'organizer'
		);

		$admin = Helpers\Can::administrate();

		if (Helpers\Can::scheduleTheseOrganizations())
		{
			$spanText = '<span class="menu-spacer">' . Languages::_('ORGANIZER_SCHEDULING') . '</span>';
			JHtmlSidebar::addEntry($spanText, '', false);

			$items = [];

			$items[Languages::_('ORGANIZER_CATEGORIES')] = [
				'url'    => 'index.php?option=com_organizer&amp;view=categories',
				'active' => $viewName == 'categories'
			];
			$items[Languages::_('ORGANIZER_COURSES')]    = [
				'url'    => 'index.php?option=com_organizer&amp;view=courses',
				'active' => $viewName == 'courses'
			];
			$items[Languages::_('ORGANIZER_EVENTS')]     = [
				'url'    => 'index.php?option=com_organizer&amp;view=events',
				'active' => $viewName == 'events'
			];
			$items[Languages::_('ORGANIZER_GROUPS')]     = [
				'url'    => 'index.php?option=com_organizer&amp;view=groups',
				'active' => $viewName == 'groups'
			];
			$items[Languages::_('ORGANIZER_SCHEDULES')]  = [
				'url'    => 'index.php?option=com_organizer&amp;view=schedules',
				'active' => $viewName == 'schedules'
			];
			$items[Languages::_('ORGANIZER_UNITS')]      = [
				'url'    => 'index.php?option=com_organizer&amp;view=units',
				'active' => $viewName == 'units'
			];

			ksort($items);

			// Uploading a schedule should always be the first menu item and will never be the active submenu item.
			$prepend = [
				Languages::_('ORGANIZER_SCHEDULE_UPLOAD') . ' <span class="icon-upload"></span>' => [
					'url'    => 'index.php?option=com_organizer&amp;view=schedule_edit',
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
				'url'    => 'index.php?option=com_organizer&amp;view=field_colors',
				'active' => $viewName == 'field_colors'
			];
			$items[Languages::_('ORGANIZER_POOLS')]        = [
				'url'    => 'index.php?option=com_organizer&amp;view=pools',
				'active' => $viewName == 'pools'
			];
			$items[Languages::_('ORGANIZER_PROGRAMS')]     = [
				'url'    => 'index.php?option=com_organizer&amp;view=programs',
				'active' => $viewName == 'programs'
			];
			$items[Languages::_('ORGANIZER_SUBJECTS')]     = [
				'url'    => 'index.php?option=com_organizer&amp;view=subjects',
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
				'index.php?option=com_organizer&amp;view=persons',
				$viewName == 'persons'
			);
		}

		if (Helpers\Can::manage('facilities'))
		{
			$spanText = '<span class="menu-spacer">' . Languages::_('ORGANIZER_FACILITY_MANAGEMENT') . '</span>';
			JHtmlSidebar::addEntry($spanText, '', false);

			$items = [];

			$items[Languages::_('ORGANIZER_BUILDINGS')] = [
				'url'    => 'index.php?option=com_organizer&amp;view=buildings',
				'active' => $viewName == 'buildings'
			];
			$items[Languages::_('ORGANIZER_CAMPUSES')]  = [
				'url'    => 'index.php?option=com_organizer&amp;view=campuses',
				'active' => $viewName == 'campuses'
			];
			$items[Languages::_('ORGANIZER_MONITORS')]  = [
				'url'    => 'index.php?option=com_organizer&amp;view=monitors',
				'active' => $viewName == 'monitors'
			];
			$items[Languages::_('ORGANIZER_ROOMS')]     = [
				'url'    => 'index.php?option=com_organizer&amp;view=rooms',
				'active' => $viewName == 'rooms'
			];
			$items[Languages::_('ORGANIZER_ROOMTYPES')] = [
				'url'    => 'index.php?option=com_organizer&amp;view=roomtypes',
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

			$items[Languages::_('ORGANIZER_COLORS')]  = [
				'url'    => 'index.php?option=com_organizer&amp;view=colors',
				'active' => $viewName == 'colors'
			];
			$items[Languages::_('ORGANIZER_DEGREES')] = [
				'url'    => 'index.php?option=com_organizer&amp;view=degrees',
				'active' => $viewName == 'degrees'
			];
			$items[Languages::_('ORGANIZER_FIELDS')]  = [
				'url'    => 'index.php?option=com_organizer&amp;view=fields',
				'active' => $viewName == 'fields'
			];
			$items[Languages::_('ORGANIZER_GRIDS')]   = [
				'url'    => 'index.php?option=com_organizer&amp;view=grids',
				'active' => $viewName == 'grids'
			];
			/*$items[Languages::_('ORGANIZER_HOLIDAYS')]      = [
				'url'    => 'index.php?option=com_organizer&amp;view=holidays',
				'active' => $viewName == 'holidays'
			];*/
			$items[Languages::_('ORGANIZER_METHODS')]       = [
				'url'    => 'index.php?option=com_organizer&amp;view=methods',
				'active' => $viewName == 'methods'
			];
			$items[Languages::_('ORGANIZER_ORGANIZATIONS')] = [
				'url'    => 'index.php?option=com_organizer&amp;view=organizations',
				'active' => $viewName == 'organizations'
			];
			$items[Languages::_('ORGANIZER_PARTICIPANTS')]  = [
				'url'    => 'index.php?option=com_organizer&amp;view=participants',
				'active' => $viewName == 'participants'
			];
			/*$items[Languages::_('ORGANIZER_RUNS')]          = [
				'url'    => 'index.php?option=com_organizer&amp;view=runs',
				'active' => $viewName == 'runs'
			];*/
			ksort($items);
			foreach ($items as $key => $value)
			{
				JHtmlSidebar::addEntry($key, $value['url'], $value['active']);
			}
		}

		$this->submenu = JHtmlSidebar::render();
	}

	/**
	 * Modifies document variables and adds links to external files
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
}
