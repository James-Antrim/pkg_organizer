<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Views\HTML;

use THM\Organizer\Adapters\Text;
use THM\Organizer\Helpers\Can;
use THM\Organizer\Helpers\Dates;
use THM\Organizer\Helpers\HTML;
use THM\Organizer\Helpers\Instances as Helper;
use THM\Organizer\Helpers\Roles;
use THM\Organizer\Helpers\Routing;
use THM\Organizer\Helpers\Users;
use stdClass;

trait ListsInstances
{
    private bool $manages = false;

    private bool $teachesOne = false;

    private bool $teachesALL = true;

    /**
     * Adds previously set resources to the structured item.
     *
     * @param array    $index
     * @param stdClass $instance
     *
     * @return void
     */
    private function addResources(array &$index, stdClass $instance)
    {
        $index['persons'] = $instance->persons;
        $index['groups']  = $instance->groups;
        $index['rooms']   = $instance->rooms;
    }

    /**
     * Searches for a pattern specified link in commentary text. If found it is added to the tools and removed from the
     * text.
     *
     * @param string   $pattern the pattern to search for
     * @param int      $key     the matches index number for the discovered id
     * @param string  &$text    the text to search in
     * @param array   &$tools   the container to add discovered tools to
     * @param string   $URL     the static portion of the dynamic link
     * @param string   $link    the HTML a-Tag which will be added to the tools
     *
     * @return void
     */
    private function findTool(string $pattern, int $key, string &$text, array &$tools, string $URL, string $link)
    {
        if (preg_match($pattern, $text, $matches)) {
            $tools[] = str_replace('URL', $URL . $matches[$key], $link);
            $text    = preg_replace($pattern, '', $text);
        }
    }

    /**
     * Created a structure for displaying status information as necessary.
     *
     * @param stdClass $instance the instance item being iterated
     *
     * @return string
     */
    private function getStatus(stdClass $instance): string
    {
        $userID = Users::getID();

        if ($instance->instanceStatus !== 'removed' and $instance->unitStatus !== 'removed') {
            if ($instance->expired) {
                $value = Text::_('ORGANIZER_EXPIRED');
            } elseif ($instance->presence === Helper::ONLINE) {
                $value = Text::_('ORGANIZER_ONLINE');

                if ($userID) {
                    if ($instance->bookmarked) {
                        $value .= ' ' . HTML::icon('bookmark', Text::_('ORGANIZER_BOOKMARKED'));
                    }

                    if ($instance->manageable) {
                        $value .= '<br>' . $instance->interested . ' ';
                        $value .= HTML::icon('bookmark', Text::_('ORGANIZER_SUBSCRIBERS'));
                    }

                }
            } else {
                $interested = $instance->interested - $instance->current;
                $value      = $instance->presence === Helper::HYBRID ? Text::_('ORGANIZER_HYBRID') : Text::_('ORGANIZER_PRESENCE');

                if ($userID) {
                    if ($instance->bookmarked) {
                        $value .= ' ' . HTML::icon('bookmark', Text::_('ORGANIZER_BOOKMARKED'));

                        if ($instance->registered) {
                            $value .= ' ' . HTML::icon('signup', Text::_('ORGANIZER_REGISTERED'));
                        }
                    }

                    if ($instance->manageable) {
                        if ($interested) {
                            $value .= "<br>$interested ";
                            $value .= HTML::icon('bookmark', Text::_('ORGANIZER_SUBSCRIBERS'));
                        }
                    }
                }

                /*if (Helper::getMethodCode($instance->instanceID) === Helpers\Methods::FINALCODE)
                {
                    $attribs = ['class' => 'hasTip', 'target' => '_blank', 'title' => Text::_('ORGANIZER_FINALS_REGISTRATION')];
                    $icon    = HTML::icon('out');
                    $value   = HTML::link('https://ecampus.thm.de', $icon, $attribs);
                }
                elseif ($instance->presence !== Helper::ONLINE)
                {
                    $value .= '<br>';

                    if ($instance->premature)
                    {
                        $value .= HTML::icon('unlock', Text::_('ORGANIZER_REGISTRATION_PREMATURE'));
                        $value .= ' ' . $instance->registrationStart;
                    }
                    elseif ($instance->running)
                    {
                        $value .= HTML::icon('stop', Text::_('ORGANIZER_REGISTRATION_CLOSED'));
                    }
                    else
                    {
                        if ($instance->full)
                        {
                            $value .= HTML::icon('pause', Text::_('ORGANIZER_INSTANCE_FULL')) . ' ';
                        }
                        else
                        {
                            $value .= HTML::icon('play', Text::_('ORGANIZER_REGISTRATION_OPEN'));
                        }

                        // Forced output
                        $capacity = $instance->capacity ?: 'X';
                        $value    .= "$instance->current/$capacity ";
                        $value    .= HTML::icon('users', Text::_('ORGANIZER_PARTICIPANTS'));
                    }
                }*/
            }
        } else {
            $value = Text::_('ORGANIZER_REMOVED');
        }

        return $value;
    }

    /**
     * Gets an icon displaying the instance's (unit's) status as relevant.
     *
     * @param stdClass $instance the object modeling the instance
     *
     * @return array|string an icon representing the status of the instance, empty if the status is irrelevant
     */
    private function getToolsColumn(stdClass $instance, int $index)
    {
        $class      = 'status-display hasToolTip';
        $instanceID = $instance->instanceID;
        $title      = '';
        $userID     = Users::getID();
        $value      = '';

        // If removed are here at all, the status holds relevance regardless of date
        if ($instance->unitStatus === 'removed') {
            $date  = Dates::formatDate($instance->unitStatusDate);
            $class .= ' unit-removed';
            $title = Text::sprintf('ORGANIZER_UNIT_REMOVED_ON', $date);
        } elseif ($instance->instanceStatus === 'removed') {
            $date  = Dates::formatDate($instance->instanceStatusDate);
            $class .= ' instance-removed';
            $title = Text::sprintf('ORGANIZER_INSTANCE_REMOVED_ON', $date);
        } elseif ($instance->unitStatus === 'new' and $instance->unitStatusDate >= $this->statusDate) {
            $date  = Dates::formatDate($instance->instanceStatusDate);
            $class .= ' unit-new';
            $title = Text::sprintf('ORGANIZER_INSTANCE_ADDED_ON', $date);
        } elseif ($instance->instanceStatus === 'new' and $instance->instanceStatusDate >= $this->statusDate) {
            $date  = Dates::formatDate($instance->instanceStatusDate);
            $class .= ' instance-new';
            $title = Text::sprintf('ORGANIZER_INSTANCE_ADDED_ON', $date);
        }

        if ($userID) {
            if ($this->mobile) {
                $buttons = [];

                if ($instance->manageable) {
                    $label   = Text::_('ORGANIZER_MANAGE_BOOKING');
                    $icon    = HTML::icon('users', $label, true);
                    $attribs = ['aria-label' => $label, 'class' => 'btn btn-checkbox'];

                    // Always allow management of existing
                    if ($instance->bookingID) {
                        $url       = Routing::getViewURL('booking', $instance->bookingID);
                        $buttons[] = HTML::link($url, $icon, $attribs);
                    } // Never allow creation of bookings for past instances
                    elseif ($instance->registration) {
                        $url       = Routing::getTaskURL('bookings.manage', $instanceID);
                        $buttons[] = HTML::link($url, $icon, $attribs);
                    }
                }

                // Future appointments can be added to the personal schedules of non-responsible individuals.
                if (!$instance->taught and !$instance->expired and !$instance->running) {
                    if ($instance->bookmarked) {
                        $label = Text::_('ORGANIZER_REMOVE_BOOKMARK');
                        $icon  = HTML::icon('bookmark-2', $label, true);
                        $url   = Routing::getTaskURL('InstanceParticipants.removeBookmark', $instanceID);
                    } else {
                        $label = Text::_('ORGANIZER_BOOKMARK');
                        $icon  = HTML::icon('bookmark', $label, true);
                        $url   = Routing::getTaskURL('InstanceParticipants.bookmark', $instanceID);
                    }

                    $attribs   = ['aria-label' => $label, 'class' => 'btn'];
                    $buttons[] = HTML::link($url, $icon, $attribs);

                    // Not virtual and not full
                    /*if ($instance->registration)
                    {
                        $attribs = ['aria-label' => $label, 'class' => 'btn btn-checkbox'];

                        if (Helper::getMethodCode($instance->instanceID) === Helpers\Methods::FINALCODE)
                        {
                            $label     = Text::_('ORGANIZER_REGISTRATION_EXTERNAL_TIP');
                            $icon      = HTML::icon('out', $label, true);
                            $url       = "https://ecampus.thm.de";
                            $buttons[] = HTML::link($url, $icon, $attribs);
                        }
                        elseif (!$instance->premature)
                        {
                            if ($instance->registered)
                            {
                                $label = Text::_('ORGANIZER_DEREGISTER');
                                $icon  = HTML::icon('exit', $label, true);
                                $url   = Routing::getTaskURL('InstanceParticipants.deregister', $instanceID);
                            }
                            else
                            {
                                $label = Text::_('ORGANIZER_REGISTER');
                                $icon  = HTML::icon('signup', $label, true);
                                $url   = Routing::getTaskURL('InstanceParticipants.register', $instanceID);
                            }

                            $buttons[] = HTML::link($url, $icon, $attribs);
                        }
                    }*/
                }

                $value .= implode('', $buttons);
            } elseif (!$instance->expired or ($instance->manageable and $instance->bookingID)) {
                $value = HTML::_('grid.id', $index, $instanceID);
            }
        }

        if ($instance->manageable and $instance->presence !== Helper::ONLINE) {
            if ($instance->expired) {
                $value .= '<br>' . HTML::icon('folder-2 red', Text::_('ORGANIZER_BOOKING_CLOSED'));
            } elseif (!$instance->premature) {
                $value .= '<br>';

                if ($instance->running) {
                    $value .= HTML::icon('folder-open green', Text::_('ORGANIZER_BOOKING_ONGOING'));
                } else {
                    $value .= HTML::icon('folder-open yellow', Text::_('ORGANIZER_BOOKING_PENDING'));
                }
            }

            // Premature
        }

        return $title ? ['attributes' => ['class' => $class, 'title' => $title], 'value' => $value] : $value;
    }

    /**
     * Generates the common portion of the instance title for listed instances.
     *
     * @param stdClass $instance the object containing instance information
     * @param string   $title    the already processed portion of the title
     *
     * @return array
     */
    private function liGetTitle(stdClass $instance, string $title): array
    {
        $comment = $this->resolveLinks($instance->comment);

        if ($instance->courseID) {
            $title .= '<br>' . HTML::icon('link hasToolTip', Text::_('ORGANIZER_REGISTRATION_LINKED')) . ' ';
            $title .= Text::_('ORGANIZER_INSTANCE_SERIES') . ": $instance->courseID";
        }

        $title .= empty($comment) ? '' : "<br><span class=\"comment\">$comment</span>";

        return ['attributes' => ['class' => 'title-column'], 'value' => $title];
    }

    /**
     * Resolves any links/link parameters to links with icons.
     *
     * @param string     $text the text to search
     * @param array|null $tools
     *
     * @return string
     */
    private function resolveLinks(string $text, array &$tools = null): string
    {
        $icon     = '<span class="icon-moodle hasTooltip" title="Moodle Link"></span>';
        $pattern1 = '/(((https?):\/\/)moodle.thm.de\/course\/view.php\?id=(\d+))/';
        $pattern2 = '/moodle=(\d+)/';
        $pattern3 = '/(((https?):\/\/)moodle\.thm\.de\/course\/index\.php\\?categoryid=(\\d+))/';
        $link     = "<a href=\"URL\" target=\"_blank\">$icon</a>";

        if ($tools) {
            $URL1 = 'https://moodle.thm.de/course/view.php?id=';
            $URL2 = 'https://moodle.thm.de/course/index.php?categoryid=';

            self::findTool($pattern1, 4, $text, $tools, $URL1, $link);
            self::findTool($pattern2, 1, $text, $tools, $URL1, $link);
            self::findTool($pattern3, 4, $text, $tools, $URL2, $link);
        } else {
            $URL1 = 'https://moodle.thm.de/course/view.php?id=PID';
            $URL2 = 'https://moodle.thm.de/course/index.php?categoryid=PID';

            $template = str_replace('PID', '$4', str_replace('URL', $URL1, $link));
            $text     = preg_replace($pattern1, $template, $text);
            $template = str_replace('PID', '$1', str_replace('URL', $URL1, $link));
            $text     = preg_replace($pattern2, $template, $text);
            $template = str_replace('PID', '$4', str_replace('URL', $URL2, $link));
            $text     = preg_replace($pattern3, $template, $text);
        }

        $icon    = '<span class="icon-cisco hasTooltip" title="Networking Academy Link"></span>';
        $pattern = '/(((https?):\/\/)\d+.netacad.com\/courses\/\d+)/';

        if ($tools and preg_match($pattern, $text, $matches)) {
            $tools[] = "<a href=\"$matches[1]\" target=\"_blank\">$icon</a>";
            $text    = preg_replace($pattern, '', $text);
        } else {
            $template = "<a href=\"$1\" target=\"_blank\">$icon</a>";
            $text     = preg_replace($pattern, $template, $text);
        }

        $icon     = '<span class="icon-panopto hasTooltip" title="Panopto Link"></span>';
        $pattern1 = '/(((https?):\/\/)panopto.thm.de\/Panopto\/Pages\/Viewer.aspx\?id=[\d\w\-]+)/';
        $pattern2 = '/panopto=([\d\w\-]+)/';

        if ($tools) {
            $URL  = 'https://panopto.thm.de/Panopto/Pages/Viewer.aspx?id=';
            $link = "<a href=\"URL\" target=\"_blank\">$icon</a>";

            self::findTool($pattern1, 4, $text, $tools, $URL, $link);
            self::findTool($pattern2, 1, $text, $tools, $URL, $link);
        } else {
            $URL  = 'https://panopto.thm.de/Panopto/Pages/Viewer.aspx?id=PID';
            $link = "<a href=\"$URL\" target=\"_blank\">$icon</a>";

            $template = str_replace('PID', '$4', $link);
            $text     = preg_replace($pattern1, $template, $text);

            $template = str_replace('PID', '$1', $link);
            $text     = preg_replace($pattern2, $template, $text);
        }

        $icon    = '<span class="icon-pilos hasTooltip" title="Pilos Link"></span>';
        $pattern = '/(((https?):\/\/)(\d+|roxy).pilos-thm.de\/(b\/)?[\d\w]{3}-[\d\w]{3}-[\d\w]{3})/';

        if ($tools and preg_match($pattern, $text, $matches)) {
            $tools[] = "<a href=\"$matches[1]\" target=\"_blank\">$icon</a>";
            $text    = preg_replace($pattern, '', $text);
        } else {
            $template = "<a href=\"$1\" target=\"_blank\">$icon</a>";
            $text     = preg_replace($pattern, $template, $text);
        }

        return $text;
    }

    /**
     * Adds derived attributes/resource output for the instances.
     *
     * @param array $instances
     *
     * @return void
     */
    private function setDerived(array $instances)
    {
        foreach ($instances as $instance) {
            $this->setSingle($instance);
        }
    }

    /**
     * Determines whether the item is conducted virtually: every person is assigned rooms, all assigned rooms are
     * virtual.
     *
     * @param stdClass $instance the item being iterated
     *
     * @return void
     */
    private function setResources(stdClass $instance)
    {
        $instance->groups   = '';
        $instance->persons  = '';
        $instance->presence = Helper::ONLINE;
        $instance->rooms    = '';
        $removed            = ($instance->instanceStatus === 'removed' or $instance->unitStatus === 'removed');

        if (empty($instance->resources)) {
            return;
        }

        $groups   = [];
        $presence = false;
        $roles    = [];
        $rooms    = [];
        $virtual  = false;

        foreach ($instance->resources as $person) {
            if (($removed and $person['status'] === 'new') or $person['status'] === 'removed') {
                continue;
            }

            $name = $person['person'];

            if (empty($roles[$person['roleID']])) {
                $roles[$person['roleID']] = [];
            }

            $roles[$person['roleID']][$name] = $name;

            if (!empty($person['groups'])) {
                foreach ($person['groups'] as $group) {
                    if (($removed and $group['status'] === 'new') or $group['status'] === 'removed') {
                        continue;
                    }

                    $name = $group['code'];

                    if (empty($groups[$name])) {
                        $groups[$name] = $group;
                    }
                }
            }

            if (!empty($person['rooms'])) {
                foreach ($person['rooms'] as $room) {
                    if (($removed and $room['status'] === 'new') or $room['status'] === 'removed') {
                        continue;
                    }

                    if ($room['virtual']) {
                        $virtual = true;
                        continue;
                    }

                    $name     = $room['room'];
                    $presence = true;

                    if (empty($rooms[$name])) {
                        $rooms[$name] = $name;
                    }
                }
            }
        }

        ksort($groups);

        foreach ($groups as $code => $group) {
            $title = "title=\"{$group['fullName']}\"";

            $groups[$code] = "<span class=\"hasToolTip\" $title>{$group['code']}</span>";
        }

        $glue = (isset($this->model->layout) and $this->model->layout === Helper::GRID) ? ', ' : '<br>';

        $instance->groups = implode($glue, $groups);

        if (count($roles) === 1) {
            $persons = array_shift($roles);
            ksort($persons);

            $instance->persons = implode($glue, $persons);
        } else {
            $displayRoles = [];
            foreach ($roles as $roleID => $persons) {
                $roleDisplay = '';

                if (!$roleTitle = Roles::getLabel($roleID, count($persons))) {
                    continue;
                }

                $roleDisplay .= "<span class=\"role-title\">$roleTitle:</span><br>";

                ksort($persons);
                $roleDisplay           .= implode($glue, $persons);
                $displayRoles[$roleID] = $roleDisplay;
            }

            ksort($roles);
            $instance->persons = implode('<br>', $displayRoles);
        }

        if ($presence and $virtual) {
            $instance->presence = Helper::HYBRID;
        } elseif ($presence) {
            $instance->presence = Helper::PRESENCE;
        }

        if ($instance->presence === Helper::ONLINE) {
            $instance->rooms = Text::_('ORGANIZER_ONLINE');

            return;
        }

        ksort($rooms);

        if ($instance->presence === Helper::HYBRID) {
            array_unshift($rooms, Text::_('ORGANIZER_ONLINE'));
        }

        $instance->rooms = implode($glue, $rooms);
    }

    /**
     * Sets derived attributes for a single instance.
     *
     * @param stdClass $instance
     *
     * @return void
     */
    private function setSingle(stdClass $instance)
    {
        $now    = date('H:i');
        $today  = date('Y-m-d');
        $userID = Users::getID();

        $this->setResources($instance);

        $instanceID = $instance->instanceID;
        $isToday    = $instance->date === $today;
        $then       = date('Y-m-d', strtotime('-2 days', strtotime($instance->date)));

        $instance->expired = ($instance->date < $today or ($isToday and $instance->endTime < $now));
        $instance->full    = (!empty($instance->capacity) and $instance->current >= $instance->capacity);
        $instance->link    = Routing::getViewURL('InstanceItem', $instanceID);

        // Administrator, planer, or person of responsibility
        if ($userID and Can::manage('instance', $instanceID)) {
            $instance->manageable = true;

            $teaches          = Helper::hasResponsibility($instanceID);
            $instance->taught = $teaches;
            $this->teachesOne = $teaches;
        } else {
            $instance->manageable = false;
            $instance->taught     = false;
            $this->teachesALL     = false;
        }

        $instance->premature         = $today < $then;
        $instance->registration      = false;
        $instance->registrationStart = Dates::formatDate($then);
        $instance->running           = (!$instance->expired and $instance->date === $today and $instance->startTime < $now);

        $validTiming = (!$instance->expired and !$instance->running);

        if ($validTiming and $instance->presence !== Helper::ONLINE and !$instance->full) {
            $instance->registration = true;
        }
    }
}