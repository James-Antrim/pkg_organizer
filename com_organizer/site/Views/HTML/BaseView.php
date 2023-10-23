<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use Exception;
use JHtmlSidebar;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Application, Document, Input, Text, Toolbar};
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Routing;
use THM\Organizer\Views\Named;

/**
 * Base class for a Joomla View
 * Class holding methods for displaying presentation data.
 */
abstract class BaseView extends HtmlView
{
    use Named;

    public string $disclaimer = '';

    public $form;

    /**
     * The name of the layout to use during rendering.
     * @var string
     */
    protected string $layout = 'default';

    /**
     * Inheritance stems from BaseDatabaseModel, not BaseModel. BaseDatabaseModel is higher in the Joomla internal
     * hierarchy used for Joomla Admin, Form, List, ... models which in turn are the parents for the Organizer abstract
     * classes of similar names.
     * @var BaseDatabaseModel
     */
    protected BaseDatabaseModel $model;

    public int $refresh = 0;

    public string $submenu = '';

    public string $subtitle = '';

    public string $supplement = '';

    public string $title = '';

    /**
     * Adds a legal disclaimer to the view.
     * @return void modifies the class property disclaimer
     */
    protected function addDisclaimer(): void
    {
        if (Application::backend()) {
            return;
        }

        $thisClass = Helpers\OrganizerHelper::getClass($this);
        if (!in_array($thisClass, ['Curriculum', 'SubjectItem', 'Subjects'])) {
            return;
        }

        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/disclaimer.css');

        $attributes = ['target' => '_blank'];

        $lsfLink = Helpers\HTML::link(
            'https://studien-sb-service.th-mittelhessen.de/docu/online.html',
            Text::_('ORGANIZER_DISCLAIMER_LSF_TITLE'),
            $attributes
        );
        $ambLink = Helpers\HTML::link(
            'https://www.thm.de/amb/pruefungsordnungen',
            Text::_('ORGANIZER_DISCLAIMER_AMB_TITLE'),
            $attributes
        );
        $poLink  = Helpers\HTML::link(
            'https://www.thm.de/site/studium/sie-studieren/pruefungsordnung.html',
            Text::_('ORGANIZER_DISCLAIMER_PO_TITLE'),
            $attributes
        );

        $disclaimer = '<div class="disclaimer">';
        $disclaimer .= '<h4>' . Text::_('ORGANIZER_DISCLAIMER_LEGAL') . '</h4>';
        $disclaimer .= '<ul>';
        $disclaimer .= '<li>' . Text::sprintf('ORGANIZER_DISCLAIMER_LSF_TEXT', $lsfLink) . '</li>';
        $disclaimer .= '<li>' . Text::sprintf('ORGANIZER_DISCLAIMER_AMB_TEXT', $ambLink) . '</li>';
        $disclaimer .= '<li>' . Text::sprintf('ORGANIZER_DISCLAIMER_PO_TEXT', $poLink) . '</li>';
        $disclaimer .= '</ul>';
        $disclaimer .= '</div>';

        $this->disclaimer = $disclaimer;
    }

    /**
     * Adds the component menu to the view.
     * @return void
     */
    protected function addMenu(): void
    {
        if (!Application::backend()) {
            return;
        }

        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/sidebar.css');

        $viewName = strtolower($this->get('name'));

        JHtmlSidebar::addEntry(
            '<span class="icon-home"></span>' . Text::_('ORGANIZER'),
            Routing::getViewURL('Organizer'),
            $viewName == 'organizer'
        );

        $admin = Helpers\Can::administrate();

        if (Helpers\Can::scheduleTheseOrganizations()) {
            $spanText = Text::_('ORGANIZER_SCHEDULING');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            JHtmlSidebar::addEntry($spanText);

            $items = [];

            $items[Text::_('ORGANIZER_CATEGORIES')]      = [
                'url' => Routing::getViewURL('Categories'),
                'active' => $viewName == 'categories'
            ];
            $items[Text::_('ORGANIZER_COURSES')]         = [
                'url' => Routing::getViewURL('Courses'),
                'active' => $viewName == 'courses'
            ];
            $items[Text::_('ORGANIZER_COURSES_IMPORT')]  = [
                'url' => Routing::getViewURL('CoursesImport'),
                'active' => $viewName == 'courses_import'
            ];
            $items[Text::_('ORGANIZER_EVENT_TEMPLATES')] = [
                'url' => Routing::getViewURL('Events'),
                'active' => $viewName == 'events'
            ];
            $items[Text::_('ORGANIZER_GROUPS')]          = [
                'url' => Routing::getViewURL('Groups'),
                'active' => $viewName == 'groups'
            ];
            $items[Text::_('ORGANIZER_SCHEDULES')]       = [
                'url' => Routing::getViewURL('Schedules'),
                'active' => $viewName == 'schedules'
            ];
            $items[Text::_('ORGANIZER_UNITS')]           = [
                'url' => Routing::getViewURL('Units'),
                'active' => $viewName == 'units'
            ];

            ksort($items);

            // Uploading a schedule should always be the first menu item and will never be the active submenu item.
            $prepend = [
                Text::_('ORGANIZER_SCHEDULE_UPLOAD') . ' <span class="icon-upload"></span>' => [
                    'url' => Routing::getViewURL('ScheduleEdit'),
                    'active' => false
                ]
            ];

            $items = $prepend + $items;

            foreach ($items as $key => $value) {
                Text::unpack($key);
                JHtmlSidebar::addEntry($key, $value['url'], $value['active']);
            }
        }

        if (Helpers\Can::documentTheseOrganizations()) {
            $spanText = Text::_('ORGANIZER_DOCUMENTATION');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            JHtmlSidebar::addEntry($spanText);

            $items = [];

            $items[Text::_('ORGANIZER_FIELD_COLORS')] = [
                'url' => Routing::getViewURL('FieldColors'),
                'active' => $viewName == 'field_colors'
            ];
            $items[Text::_('ORGANIZER_POOLS')]        = [
                'url' => Routing::getViewURL('Pools'),
                'active' => $viewName == 'pools'
            ];
            $items[Text::_('ORGANIZER_PROGRAMS')]     = [
                'url' => Routing::getViewURL('Programs'),
                'active' => $viewName == 'programs'
            ];
            $items[Text::_('ORGANIZER_SUBJECTS')]     = [
                'url' => Routing::getViewURL('Subjects'),
                'active' => $viewName == 'subjects'
            ];
            ksort($items);
            foreach ($items as $key => $value) {
                Text::unpack($key);
                JHtmlSidebar::addEntry($key, $value['url'], $value['active']);
            }
        }

        if (Helpers\Can::manage('persons')) {
            $spanText = Text::_('ORGANIZER_HUMAN_RESOURCES');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            JHtmlSidebar::addEntry($spanText);
            JHtmlSidebar::addEntry(
                Text::_('ORGANIZER_PERSONS'),
                Routing::getViewURL('Persons'),
                $viewName == 'persons'
            );
        }

        if (Helpers\Can::manage('facilities')) {
            $spanText = Text::_('ORGANIZER_FACILITY_MANAGEMENT');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            JHtmlSidebar::addEntry($spanText);

            $items = [];

            $items[Text::_('ORGANIZER_BUILDINGS')]       = [
                'url' => Routing::getViewURL('Buildings'),
                'active' => $viewName == 'buildings'
            ];
            $items[Text::_('ORGANIZER_CAMPUSES')]        = [
                'url' => Routing::getViewURL('Campuses'),
                'active' => $viewName == 'campuses'
            ];
            $items[Text::_('ORGANIZER_CLEANING_GROUPS')] = [
                'url' => Routing::getViewURL('CleaningGroups'),
                'active' => $viewName == 'cleaning_groups'
            ];
            $items[Text::_('ORGANIZER_MONITORS')]        = [
                'url' => Routing::getViewURL('Monitors'),
                'active' => $viewName == 'monitors'
            ];
            $items[Text::_('ORGANIZER_ROOMS')]           = [
                'url' => Routing::getViewURL('Rooms'),
                'active' => $viewName == 'rooms'
            ];
            /*$items[Text::_('ORGANIZER_ROOMS_IMPORT')] = [
                'url'    => Routing::getViewURL('RoomsImport'),
                'active' => $viewName == 'rooms_import'
            ];*/
            $items[Text::_('ORGANIZER_ROOMKEYS')]  = [
                'url' => Routing::getViewURL('Roomkeys'),
                'active' => $viewName == 'roomkeys'
            ];
            $items[Text::_('ORGANIZER_ROOMTYPES')] = [
                'url' => Routing::getViewURL('Roomtypes'),
                'active' => $viewName == 'roomtypes'
            ];
            ksort($items);
            foreach ($items as $key => $value) {
                Text::unpack($key);
                JHtmlSidebar::addEntry($key, $value['url'], $value['active']);
            }
        }

        if ($admin) {
            $spanText = Text::_('ORGANIZER_ADMINISTRATION');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            JHtmlSidebar::addEntry($spanText);

            $items = [];

            $items[Text::_('ORGANIZER_COLORS')]        = [
                'url' => Routing::getViewURL('Colors'),
                'active' => $viewName == 'colors'
            ];
            $items[Text::_('ORGANIZER_DEGREES')]       = [
                'url' => Routing::getViewURL('Degrees'),
                'active' => $viewName == 'degrees'
            ];
            $items[Text::_('ORGANIZER_FIELDS')]        = [
                'url' => Routing::getViewURL('Fields'),
                'active' => $viewName == 'fields'
            ];
            $items[Text::_('ORGANIZER_GRIDS')]         = [
                'url' => Routing::getViewURL('Grids'),
                'active' => $viewName == 'grids'
            ];
            $items[Text::_('ORGANIZER_HOLIDAYS')]      = [
                'url' => Routing::getViewURL('Holidays'),
                'active' => $viewName == 'holidays'
            ];
            $items[Text::_('ORGANIZER_METHODS')]       = [
                'url' => Routing::getViewURL('Methods'),
                'active' => $viewName == 'methods'
            ];
            $items[Text::_('ORGANIZER_ORGANIZATIONS')] = [
                'url' => Routing::getViewURL('Organizations'),
                'active' => $viewName == 'organizations'
            ];
            $items[Text::_('ORGANIZER_PARTICIPANTS')]  = [
                'url' => Routing::getViewURL('Participants'),
                'active' => $viewName == 'participants'
            ];
            $items[Text::_('ORGANIZER_RUNS')]          = [
                'url' => Routing::getViewURL('Runs'),
                'active' => $viewName == 'runs'
            ];
            $items[Text::_('ORGANIZER_TERMS')]         = [
                'url' => Routing::getViewURL('Terms'),
                'active' => $viewName == 'terms'
            ];
            ksort($items);
            foreach ($items as $key => $value) {
                Text::unpack($key);
                JHtmlSidebar::addEntry($key, $value['url'], $value['active']);
            }
        }

        $this->submenu = JHtmlSidebar::render();
    }

    /**
     * Get the layout. Overwrites the use of the prefixed parent property.
     * @return  string  The layout name
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * Modifies document and adds scripts and styles.
     * @return void
     */
    protected function modifyDocument(): void
    {
        Document::setCharset();
        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/global.css');
        Document::addStyleSheet(Uri::root() . 'media/jui/css/bootstrap-extended.css');

        Helpers\HTML::_('bootstrap.tooltip', '.hasTooltip', ['placement' => 'right']);
    }

    /**
     * @inheritDoc
     */
    public function setLayout($layout): string
    {
        // Default is not an option anymore.
        if ($layout === 'default' and $this->layout === 'default') {
            $exists     = false;
            $layoutName = strtolower($this->getName());

            foreach ($this->_path['template'] as $path) {
                $exists = file_exists("$path$layoutName.php");
                if ($exists) {
                    break;
                }
            }

            if (!$exists) {
                Application::error(501);
            }

            $this->layout = strtolower($this->getName());
        } elseif ($layout !== 'default') {
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
     * @param string $standard    the title to display
     * @param string $conditional the conditional title to display
     *
     * @return void
     */
    protected function setTitle(string $standard, string $conditional = ''): void
    {
        $params = Input::getParams();

        if ($params->get('show_page_heading') and $params->get('page_title')) {
            $title = $params->get('page_title');
        } else {
            $title = empty($conditional) ? Text::_($standard) : $conditional;
        }

        // Backend => Joomla standard title/toolbar output property declared dynamically by Joomla
        Toolbar::setTitle($title);

        // Frontend => self developed title/toolbar output
        $this->title = $title;

        Document::setTitle(strip_tags($title) . ' - ' . Application::getApplication()->get('sitename'));
    }
}
