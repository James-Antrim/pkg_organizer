<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Views\HTML;

use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\Application;
use THM\Organizer\Adapters\Document;
use THM\Organizer\Adapters\Text;
use THM\Organizer\Helpers\{Can, Routing};

/**
 * Class adds an administrative menu component menu to the given view.
 */
trait ToCed
{
    public string $ToC = '';

    public function addToC(): void
    {
        if (!Application::backend()) {
            return;
        }

        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/sidebar.css');

        $viewName = Application::getClass($this);

        Sidebar::addEntry(
            '<span class="icon-home"></span>Organizer',
            Routing::getViewURL('Organizer'),
            $viewName === 'Organizer'
        );


        if (Can::scheduleTheseOrganizations()) {
            $spanText = Text::_('ORGANIZER_SCHEDULING');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            Sidebar::addEntry($spanText);

            $items = [];

            $items[Text::_('ORGANIZER_CATEGORIES')]      = [
                'url'    => Routing::getViewURL('Categories'),
                'active' => $viewName == 'categories'
            ];
            $items[Text::_('ORGANIZER_COURSES')]         = [
                'url'    => Routing::getViewURL('Courses'),
                'active' => $viewName == 'courses'
            ];
            $items[Text::_('ORGANIZER_COURSES_IMPORT')]  = [
                'url'    => Routing::getViewURL('CoursesImport'),
                'active' => $viewName == 'courses_import'
            ];
            $items[Text::_('ORGANIZER_EVENT_TEMPLATES')] = [
                'url'    => Routing::getViewURL('Events'),
                'active' => $viewName == 'events'
            ];
            $items[Text::_('ORGANIZER_GROUPS')]          = [
                'url'    => Routing::getViewURL('Groups'),
                'active' => $viewName == 'groups'
            ];
            $items[Text::_('ORGANIZER_SCHEDULES')]       = [
                'url'    => Routing::getViewURL('Schedules'),
                'active' => $viewName == 'schedules'
            ];
            $items[Text::_('ORGANIZER_UNITS')]           = [
                'url'    => Routing::getViewURL('Units'),
                'active' => $viewName == 'units'
            ];

            ksort($items);

            // Uploading a schedule should always be the first menu item and will never be the active submenu item.
            $prepend = [
                Text::_('ORGANIZER_SCHEDULE_UPLOAD') . ' <span class="icon-upload"></span>' => [
                    'url'    => Routing::getViewURL('ScheduleEdit'),
                    'active' => false
                ]
            ];

            $items = $prepend + $items;

            foreach ($items as $key => $value) {
                Text::unpack($key);
                Sidebar::addEntry($key, $value['url'], $value['active']);
            }
        }

        if (Can::documentTheseOrganizations()) {
            $spanText = Text::_('ORGANIZER_DOCUMENTATION');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            Sidebar::addEntry($spanText);

            $items = [];

            $items[Text::_('ORGANIZER_FIELD_COLORS')] = [
                'url'    => Routing::getViewURL('FieldColors'),
                'active' => $viewName == 'field_colors'
            ];
            $items[Text::_('ORGANIZER_POOLS')]        = [
                'url'    => Routing::getViewURL('Pools'),
                'active' => $viewName == 'pools'
            ];
            $items[Text::_('ORGANIZER_PROGRAMS')]     = [
                'url'    => Routing::getViewURL('Programs'),
                'active' => $viewName == 'programs'
            ];
            $items[Text::_('ORGANIZER_SUBJECTS')]     = [
                'url'    => Routing::getViewURL('Subjects'),
                'active' => $viewName == 'subjects'
            ];
            ksort($items);
            foreach ($items as $key => $value) {
                Text::unpack($key);
                Sidebar::addEntry($key, $value['url'], $value['active']);
            }
        }

        if (Can::manage('persons')) {
            $spanText = Text::_('ORGANIZER_HUMAN_RESOURCES');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            Sidebar::addEntry($spanText);
            Sidebar::addEntry(
                Text::_('ORGANIZER_PERSONS'),
                Routing::getViewURL('Persons'),
                $viewName == 'persons'
            );
        }

        if (Can::manage('facilities')) {
            $spanText = Text::_('ORGANIZER_FACILITY_MANAGEMENT');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            Sidebar::addEntry($spanText);

            $items = [];

            $items[Text::_('ORGANIZER_BUILDINGS')]       = [
                'url'    => Routing::getViewURL('Buildings'),
                'active' => $viewName == 'buildings'
            ];
            $items[Text::_('ORGANIZER_CAMPUSES')]        = [
                'url'    => Routing::getViewURL('Campuses'),
                'active' => $viewName == 'campuses'
            ];
            $items[Text::_('ORGANIZER_CLEANING_GROUPS')] = [
                'url'    => Routing::getViewURL('CleaningGroups'),
                'active' => $viewName == 'cleaning_groups'
            ];
            $items[Text::_('ORGANIZER_MONITORS')]        = [
                'url'    => Routing::getViewURL('Monitors'),
                'active' => $viewName == 'monitors'
            ];
            $items[Text::_('ORGANIZER_ROOMS')]           = [
                'url'    => Routing::getViewURL('Rooms'),
                'active' => $viewName == 'rooms'
            ];
            /*$items[Text::_('ORGANIZER_ROOMS_IMPORT')] = [
                'url'    => Routing::getViewURL('RoomsImport'),
                'active' => $viewName == 'rooms_import'
            ];*/
            $items[Text::_('ORGANIZER_ROOMKEYS')]  = [
                'url'    => Routing::getViewURL('Roomkeys'),
                'active' => $viewName == 'roomkeys'
            ];
            $items[Text::_('ORGANIZER_ROOMTYPES')] = [
                'url'    => Routing::getViewURL('Roomtypes'),
                'active' => $viewName == 'roomtypes'
            ];
            ksort($items);
            foreach ($items as $key => $value) {
                Text::unpack($key);
                Sidebar::addEntry($key, $value['url'], $value['active']);
            }
        }

        if (Can::administrate()) {
            $spanText = Text::_('ORGANIZER_ADMINISTRATION');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            Sidebar::addEntry($spanText);

            $items = [];

            $items[Text::_('ORGANIZER_COLORS')]        = [
                'url'    => Routing::getViewURL('Colors'),
                'active' => $viewName == 'colors'
            ];
            $items[Text::_('ORGANIZER_DEGREES')]       = [
                'url'    => Routing::getViewURL('Degrees'),
                'active' => $viewName == 'degrees'
            ];
            $items[Text::_('ORGANIZER_FIELDS')]        = [
                'url'    => Routing::getViewURL('Fields'),
                'active' => $viewName == 'fields'
            ];
            $items[Text::_('ORGANIZER_GRIDS')]         = [
                'url'    => Routing::getViewURL('Grids'),
                'active' => $viewName == 'grids'
            ];
            $items[Text::_('ORGANIZER_HOLIDAYS')]      = [
                'url'    => Routing::getViewURL('Holidays'),
                'active' => $viewName == 'holidays'
            ];
            $items[Text::_('ORGANIZER_METHODS')]       = [
                'url'    => Routing::getViewURL('Methods'),
                'active' => $viewName == 'methods'
            ];
            $items[Text::_('ORGANIZER_ORGANIZATIONS')] = [
                'url'    => Routing::getViewURL('Organizations'),
                'active' => $viewName == 'organizations'
            ];
            $items[Text::_('ORGANIZER_PARTICIPANTS')]  = [
                'url'    => Routing::getViewURL('Participants'),
                'active' => $viewName == 'participants'
            ];
            $items[Text::_('ORGANIZER_RUNS')]          = [
                'url'    => Routing::getViewURL('Runs'),
                'active' => $viewName == 'runs'
            ];
            $items[Text::_('ORGANIZER_TERMS')]         = [
                'url'    => Routing::getViewURL('Terms'),
                'active' => $viewName == 'terms'
            ];
            ksort($items);
            foreach ($items as $key => $value) {
                Text::unpack($key);
                Sidebar::addEntry($key, $value['url'], $value['active']);
            }
        }

        $this->sidebar = Sidebar::render();
    }
}