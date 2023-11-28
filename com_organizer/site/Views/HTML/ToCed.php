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

        Document::style('sidebar');

        $viewName = strtolower(Application::getClass($this));

        Sidebar::addEntry(
            '<span class="icon-home"></span> Organizer',
            Routing::getViewURL('Organizer'),
            $viewName === 'organizer'
        );


        if (Can::scheduleTheseOrganizations()) {
            $spanText = Text::_('SCHEDULING');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            Sidebar::addEntry($spanText);

            $items = [];

            $items[Text::_('CATEGORIES')]      = [
                'url'    => Routing::getViewURL('Categories'),
                'active' => $viewName === 'categories'
            ];
            $items[Text::_('COURSES')]         = [
                'url'    => Routing::getViewURL('Courses'),
                'active' => $viewName === 'courses'
            ];
            $items[Text::_('COURSES_IMPORT')]  = [
                'url'    => Routing::getViewURL('CoursesImport'),
                'active' => $viewName === 'coursesimport'
            ];
            $items[Text::_('EVENT_TEMPLATES')] = [
                'url'    => Routing::getViewURL('Events'),
                'active' => $viewName === 'events'
            ];
            $items[Text::_('GROUPS')]          = [
                'url'    => Routing::getViewURL('Groups'),
                'active' => $viewName === 'groups'
            ];
            $items[Text::_('SCHEDULES')]       = [
                'url'    => Routing::getViewURL('Schedules'),
                'active' => $viewName === 'schedules'
            ];
            $items[Text::_('UNITS')]           = [
                'url'    => Routing::getViewURL('Units'),
                'active' => $viewName === 'units'
            ];

            ksort($items);

            // Uploading a schedule should always be the first menu item and will never be the active submenu item.
            $prepend = [
                Text::_('SCHEDULE_UPLOAD') . ' <span class="icon-upload"></span>' => [
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
            $spanText = Text::_('DOCUMENTATION');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            Sidebar::addEntry($spanText);

            $items = [];

            $items[Text::_('FIELD_COLORS')] = [
                'url'    => Routing::getViewURL('FieldColors'),
                'active' => $viewName === 'fieldcolors'
            ];
            $items[Text::_('POOLS')]        = [
                'url'    => Routing::getViewURL('Pools'),
                'active' => $viewName === 'pools'
            ];
            $items[Text::_('PROGRAMS')]     = [
                'url'    => Routing::getViewURL('Programs'),
                'active' => $viewName === 'programs'
            ];
            $items[Text::_('SUBJECTS')]     = [
                'url'    => Routing::getViewURL('Subjects'),
                'active' => $viewName === 'subjects'
            ];
            ksort($items);
            foreach ($items as $key => $value) {
                Text::unpack($key);
                Sidebar::addEntry($key, $value['url'], $value['active']);
            }
        }

        if (Can::manage('persons')) {
            $spanText = Text::_('HUMAN_RESOURCES');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            Sidebar::addEntry($spanText);
            Sidebar::addEntry(
                Text::_('PERSONS'),
                Routing::getViewURL('Persons'),
                $viewName === 'persons'
            );
        }

        if (Can::manage('facilities')) {
            $spanText = Text::_('FACILITY_MANAGEMENT');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            Sidebar::addEntry($spanText);

            $items = [];

            $items[Text::_('BUILDINGS')]      = [
                'url'    => Routing::getViewURL('Buildings'),
                'active' => $viewName === 'buildings'
            ];
            $items[Text::_('CAMPUSES')]       = [
                'url'    => Routing::getViewURL('Campuses'),
                'active' => $viewName === 'campuses'
            ];
            $items[Text::_('CLEANINGGROUPS')] = [
                'url'    => Routing::getViewURL('CleaningGroups'),
                'active' => $viewName === 'cleaninggroups'
            ];
            $items[Text::_('MONITORS')]       = [
                'url'    => Routing::getViewURL('Monitors'),
                'active' => $viewName === 'monitors'
            ];
            $items[Text::_('ROOMS')]          = [
                'url'    => Routing::getViewURL('Rooms'),
                'active' => $viewName === 'rooms'
            ];
            /*$items[Text::_('ROOMS_IMPORT')] = [
                'url'    => Routing::getViewURL('RoomsImport'),
                'active' => $viewName === 'rooms_import'
            ];*/
            $items[Text::_('ROOM_KEYS')]  = [
                'url'    => Routing::getViewURL('RoomKeys'),
                'active' => $viewName === 'roomkeys'
            ];
            $items[Text::_('ROOM_TYPES')] = [
                'url'    => Routing::getViewURL('RoomTypes'),
                'active' => $viewName === 'roomtypes'
            ];
            ksort($items);
            foreach ($items as $key => $value) {
                Text::unpack($key);
                Sidebar::addEntry($key, $value['url'], $value['active']);
            }
        }

        if (Can::administrate()) {
            $spanText = Text::_('ADMINISTRATION');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            Sidebar::addEntry($spanText);

            $items = [];

            $items[Text::_('COLORS')]        = [
                'url'    => Routing::getViewURL('Colors'),
                'active' => $viewName === 'colors'
            ];
            $items[Text::_('DEGREES')]       = [
                'url'    => Routing::getViewURL('Degrees'),
                'active' => $viewName === 'degrees'
            ];
            $items[Text::_('FIELDS')]        = [
                'url'    => Routing::getViewURL('Fields'),
                'active' => $viewName === 'fields'
            ];
            $items[Text::_('GRIDS')]         = [
                'url'    => Routing::getViewURL('Grids'),
                'active' => $viewName === 'grids'
            ];
            $items[Text::_('HOLIDAYS')]      = [
                'url'    => Routing::getViewURL('Holidays'),
                'active' => $viewName === 'holidays'
            ];
            $items[Text::_('METHODS')]       = [
                'url'    => Routing::getViewURL('Methods'),
                'active' => $viewName === 'methods'
            ];
            $items[Text::_('ORGANIZATIONS')] = [
                'url'    => Routing::getViewURL('Organizations'),
                'active' => $viewName === 'organizations'
            ];
            $items[Text::_('PARTICIPANTS')]  = [
                'url'    => Routing::getViewURL('Participants'),
                'active' => $viewName === 'participants'
            ];
            $items[Text::_('RUNS')]          = [
                'url'    => Routing::getViewURL('Runs'),
                'active' => $viewName === 'runs'
            ];
            $items[Text::_('TERMS')]         = [
                'url'    => Routing::getViewURL('Terms'),
                'active' => $viewName === 'terms'
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