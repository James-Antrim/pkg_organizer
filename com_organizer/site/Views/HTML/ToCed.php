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
use THM\Organizer\Adapters\{Application, Document, Text};
use THM\Organizer\Helpers\{Can, Organizations, Routing};

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
            Routing::getViewURL('organizer'),
            $viewName === 'organizer'
        );


        if (Organizations::schedulableIDs()) {
            $spanText = Text::_('SCHEDULING');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            Sidebar::addEntry($spanText);

            $items = [];

            $items[Text::_('CATEGORIES')]      = [
                'url'    => Routing::getViewURL('categories'),
                'active' => $viewName === 'categories'
            ];
            $items[Text::_('COURSES')]         = [
                'url'    => Routing::getViewURL('courses'),
                'active' => $viewName === 'courses'
            ];
            $items[Text::_('COURSES_IMPORT')]  = [
                'url'    => Routing::getViewURL('importcourses'),
                'active' => $viewName === 'importcourses'
            ];
            $items[Text::_('EVENT_TEMPLATES')] = [
                'url'    => Routing::getViewURL('events'),
                'active' => $viewName === 'events'
            ];
            $items[Text::_('GROUPS')]          = [
                'url'    => Routing::getViewURL('groups'),
                'active' => $viewName === 'groups'
            ];
            $items[Text::_('SCHEDULES')]       = [
                'url'    => Routing::getViewURL('schedules'),
                'active' => $viewName === 'schedules'
            ];
            $items[Text::_('UNITS')]           = [
                'url'    => Routing::getViewURL('units'),
                'active' => $viewName === 'units'
            ];

            ksort($items);

            // Uploading a schedule should always be the first menu item and will never be the active submenu item.
            $prepend = [
                Text::_('SCHEDULE_UPLOAD') . ' <span class="icon-upload"></span>' => [
                    'url'    => Routing::getViewURL('importschedule'),
                    'active' => false
                ]
            ];

            $items = $prepend + $items;

            foreach ($items as $key => $value) {
                Text::unpack($key);
                Sidebar::addEntry($key, $value['url'], $value['active']);
            }
        }

        if (Organizations::documentableIDs()) {
            $spanText = Text::_('DOCUMENTATION');
            Text::unpack($spanText);
            $spanText = '<span class="menu-spacer">' . $spanText . '</span>';
            Sidebar::addEntry($spanText);

            $items = [];

            $items[Text::_('FIELD_COLORS')] = [
                'url'    => Routing::getViewURL('fieldcolors'),
                'active' => $viewName === 'fieldcolors'
            ];
            $items[Text::_('POOLS')]        = [
                'url'    => Routing::getViewURL('pools'),
                'active' => $viewName === 'pools'
            ];
            $items[Text::_('PROGRAMS')]     = [
                'url'    => Routing::getViewURL('programs'),
                'active' => $viewName === 'programs'
            ];
            $items[Text::_('SUBJECTS')]     = [
                'url'    => Routing::getViewURL('subjects'),
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
                Routing::getViewURL('persons'),
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
                'url'    => Routing::getViewURL('buildings'),
                'active' => $viewName === 'buildings'
            ];
            $items[Text::_('CAMPUSES')]       = [
                'url'    => Routing::getViewURL('campuses'),
                'active' => $viewName === 'campuses'
            ];
            $items[Text::_('CLEANINGGROUPS')] = [
                'url'    => Routing::getViewURL('cleaninggroups'),
                'active' => $viewName === 'cleaninggroups'
            ];
            $items[Text::_('MONITORS')]       = [
                'url'    => Routing::getViewURL('monitors'),
                'active' => $viewName === 'monitors'
            ];
            $items[Text::_('ROOMS')]          = [
                'url'    => Routing::getViewURL('rooms'),
                'active' => $viewName === 'rooms'
            ];
            /*$items[Text::_('ROOMS_IMPORT')] = [
                'url'    => Routing::getViewURL('importrooms'),
                'active' => $viewName === 'importrooms'
            ];*/
            $items[Text::_('ROOM_KEYS')]  = [
                'url'    => Routing::getViewURL('roomkeys'),
                'active' => $viewName === 'roomkeys'
            ];
            $items[Text::_('ROOM_TYPES')] = [
                'url'    => Routing::getViewURL('roomtypes'),
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
                'url'    => Routing::getViewURL('colors'),
                'active' => $viewName === 'colors'
            ];
            $items[Text::_('DEGREES')]       = [
                'url'    => Routing::getViewURL('degrees'),
                'active' => $viewName === 'degrees'
            ];
            $items[Text::_('FIELDS')]        = [
                'url'    => Routing::getViewURL('fields'),
                'active' => $viewName === 'fields'
            ];
            $items[Text::_('GRIDS')]         = [
                'url'    => Routing::getViewURL('grids'),
                'active' => $viewName === 'grids'
            ];
            $items[Text::_('HOLIDAYS')]      = [
                'url'    => Routing::getViewURL('holidays'),
                'active' => $viewName === 'holidays'
            ];
            $items[Text::_('METHODS')]       = [
                'url'    => Routing::getViewURL('methods'),
                'active' => $viewName === 'methods'
            ];
            $items[Text::_('ORGANIZATIONS')] = [
                'url'    => Routing::getViewURL('organizations'),
                'active' => $viewName === 'organizations'
            ];
            $items[Text::_('PARTICIPANTS')]  = [
                'url'    => Routing::getViewURL('participants'),
                'active' => $viewName === 'participants'
            ];
            $items[Text::_('RUNS')]          = [
                'url'    => Routing::getViewURL('runs'),
                'active' => $viewName === 'runs'
            ];
            $items[Text::_('TERMS')]         = [
                'url'    => Routing::getViewURL('terms'),
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