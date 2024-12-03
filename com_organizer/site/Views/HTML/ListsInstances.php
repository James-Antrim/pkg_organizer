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

use THM\Organizer\Adapters\{Application, HTML, Text, User};
use THM\Organizer\Helpers\{Can, Dates, Instances as Helper, Roles, Routing};
use stdClass;

trait ListsInstances
{
    private bool $manages = false;

    private bool $teachesOne = false;

    private bool $teachesALL = true;

    /**
     * Searches for a pattern specified link in commentary text. If found it is added to the tools and removed from the
     * text.
     *
     * @param   string   $pattern  the pattern to search for
     * @param   int      $key      the matches index number for the discovered id
     * @param   string  &$text     the text to search in
     * @param   array   &$tools    the container to add discovered tools to
     * @param   string   $URL      the static portion of the dynamic link
     * @param   string   $link     the HTML a-Tag which will be added to the tools
     *
     * @return void
     */
    private function findTool(string $pattern, int $key, string &$text, array &$tools, string $URL, string $link): void
    {
        if (preg_match($pattern, $text, $matches)) {
            $tools[] = str_replace('URL', $URL . $matches[$key], $link);
            $text    = preg_replace($pattern, '', $text);
        }
    }

    /**
     * Created a structure for displaying status information as necessary.
     *
     * @param   stdClass  $instance  the instance item being iterated
     *
     * @return string
     */
    private function getStatus(stdClass $instance): string
    {
        $userID = User::id();

        if ($instance->instanceStatus !== 'removed' and $instance->unitStatus !== 'removed') {
            if ($instance->expired) {
                $value = Text::_('ORGANIZER_EXPIRED');
            }
            elseif ($instance->presence === Helper::ONLINE) {
                $value = Text::_('ORGANIZER_ONLINE');

                if ($userID) {
                    if ($instance->bookmarked) {
                        $value .= ' ' . HTML::tip(
                                HTML::icon('fa fa-bookmark'), "instance-bookmark-$instance->instanceID", 'BOOKMARKED'
                            );

                    }

                    if ($instance->manageable) {
                        $value .= '<br>' . $instance->interested . ' ';
                        $value .= HTML::tip(
                            HTML::icon('fa fa-bookmark'), "instance-subscribers-$instance->instanceID", 'SUBSCRIBERS'
                        );
                    }

                }
            }
            else {
                $interested = $instance->interested - $instance->current;
                $value      = $instance->presence === Helper::HYBRID ? Text::_('HYBRID') : Text::_('PRESENCE');

                if ($userID) {
                    if ($instance->bookmarked) {
                        $value .= ' ' . HTML::tip(
                                HTML::icon('fa fa-bookmark'), "instance-bookmark-$instance->instanceID", 'BOOKMARKED'
                            );

                        if ($instance->registered) {
                            $icon  = HTML::icon('fa fa-sign-in-alt');
                            $value .= ' ' . HTML::tip($icon, "instance-registration-$instance->instanceID", 'REGISTERED');
                        }
                    }

                    if ($instance->manageable) {
                        if ($interested) {
                            $icon  = HTML::icon('fa fa-bookmark');
                            $value .= "<br>$interested ";
                            $value .= HTML::tip($icon, "instance-subscribers-$instance->instanceID", 'SUBSCRIBERS');
                        }
                    }
                }

                /*if (Helper::getMethodCode($instance->instanceID) === Helpers\Methods::FINALCODE)
                {
                    $attribs = ['target' => '_blank'];
                    $icon    = HTML::icon('fa fa-share');
                    $value   = HTML::link('https://ecampus.thm.de', $icon, ['target' => '_blank']);
                }
                elseif ($instance->presence !== Helper::ONLINE)
                {
                    $value .= '<br>';

                    if ($instance->premature)
                    {
                        $icon  =  HTML::icon('fa fa-unlock');
                        $value .= HTML::tip($icon, "instance-status-$instance->instanceID", 'REGISTRATION_PREMATURE');
                        $value .= ' ' . $instance->registrationStart;
                    }
                    elseif ($instance->running)
                    {
                        $icon  =  HTML::icon('fa fa-stop');
                        $value .= HTML::tip($icon, "instance-status-$instance->instanceID", 'REGISTRATION_CLOSED');
                    }
                    else
                    {
                        if ($instance->full)
                        {
                            $icon  =  HTML::icon('fa fa-pause');
                            $value .= HTML::tip($icon, "instance-status-$instance->instanceID", 'INSTANCE_FULL') . ' ';
                        }
                        else
                        {
                            $icon  =  HTML::icon('fa fa-play');
                            $value .= HTML::tip($icon, "instance-status-$instance->instanceID", 'REGISTRATION_OPEN') . ' ';
                        }

                        // Forced output
                        $capacity = $instance->capacity ?: 'X';
                        $icon = HTML::icon('fa fa-users');
                        $value    .= "$instance->current/$capacity ";
                        $value .= HTML::tip($icon, "participants-$instance->instanceID", 'PARTICIPANTS') . ' ';
                    }
                }*/
            }
        }
        else {
            $value = Text::_('ORGANIZER_REMOVED');
        }

        return $value;
    }

    /**
     * Gets an icon displaying the instance's (unit's) status as relevant.
     *
     * @param   stdClass  $instance  the object modeling the instance
     *
     * @return array|string an icon representing the status of the instance, empty if the status is irrelevant
     */
    private function getToolsColumn(stdClass $instance, int $index): array|string
    {
        $class      = 'status-display hasToolTip';
        $instanceID = $instance->instanceID;
        $title      = '';
        $userID     = User::id();
        $value      = '';

        // If removed are here at all, the status holds relevance regardless of date
        if ($instance->unitStatus === 'removed') {
            $date  = Dates::formatDate($instance->unitStatusDate);
            $class .= ' unit-removed';
            $title = Text::sprintf('ORGANIZER_UNIT_REMOVED_ON', $date);
        }
        elseif ($instance->instanceStatus === 'removed') {
            $date  = Dates::formatDate($instance->instanceStatusDate);
            $class .= ' instance-removed';
            $title = Text::sprintf('ORGANIZER_INSTANCE_REMOVED_ON', $date);
        }
        elseif ($instance->unitStatus === 'new' and $instance->unitStatusDate >= $this->statusDate) {
            $date  = Dates::formatDate($instance->instanceStatusDate);
            $class .= ' unit-new';
            $title = Text::sprintf('ORGANIZER_INSTANCE_ADDED_ON', $date);
        }
        elseif ($instance->instanceStatus === 'new' and $instance->instanceStatusDate >= $this->statusDate) {
            $date  = Dates::formatDate($instance->instanceStatusDate);
            $class .= ' instance-new';
            $title = Text::sprintf('ORGANIZER_INSTANCE_ADDED_ON', $date);
        }

        if ($userID) {
            if (Application::mobile()) {
                $buttons = [];

                if ($instance->manageable) {
                    $attribs = ['class' => 'btn btn-checkbox'];
                    $icon    = HTML::icon('fa fa-users');

                    // Always allow management of existing
                    if ($instance->bookingID) {
                        $url       = Routing::getViewURL('booking', $instance->bookingID);
                        $buttons[] = HTML::tip($icon, "manage-$instance->instanceID", 'MANAGE_BOOKING', $attribs, $url);
                    } // Never allow creation of bookings for past instances
                    elseif ($instance->registration) {
                        $url       = Routing::getTaskURL('bookings.manage', $instanceID);
                        $buttons[] = HTML::tip($icon, "manage-$instance->instanceID", 'MANAGE_BOOKING', $attribs, $url);
                    }

                }

                // Future appointments can be added to the personal schedules of non-responsible individuals.
                if (!$instance->taught and !$instance->expired and !$instance->running) {
                    if ($instance->bookmarked) {
                        $label = 'REMOVE_BOOKMARK';
                        $icon  = HTML::icon('fa fa-bookmark');
                        $url   = Routing::getTaskURL('InstanceParticipants.removeBookmark', $instanceID);
                    }
                    else {
                        $label = 'BOOKMARK';
                        $icon  = HTML::icon('far fa-bookmark');
                        $url   = Routing::getTaskURL('InstanceParticipants.bookmark', $instanceID);
                    }

                    $attribs   = ['class' => 'btn'];
                    $buttons[] = HTML::tip($icon, "bookmark-instance-$instanceID", $label, $attribs, $url);

                    // Not virtual and not full
                    /*if ($instance->registration)
                    {
                        $attribs = ['class' => 'btn btn-checkbox'];

                        if (Helper::getMethodCode($instance->instanceID) === Helpers\Methods::FINALCODE)
                        {
                            $label     = 'EXTERNAL_TIP';
                            $icon      = HTML::icon('fa fa-share');
                            $url       = "https://ecampus.thm.de";
                            $buttons[] = HTML::tip($icon, "external-registration-$instanceID", $label, $attribs, $url);
                        }
                        elseif (!$instance->premature)
                        {
                            if ($instance->registered)
                            {
                                $label = 'DEREGISTER';
                                $icon  = HTML::icon('fa fa-sign-out-alt');
                                $url   = Routing::getTaskURL('InstanceParticipants.deregister', $instanceID);
                            }
                            else
                            {
                                $label = 'REGISTER';
                                $icon  = HTML::icon('fa fa-sign-in-alt');
                                $url   = Routing::getTaskURL('InstanceParticipants.register', $instanceID);
                            }

                            $buttons[] = HTML::tip($icon, "instance-register-$instanceID", $label, $attribs, $url);
                        }
                    }*/
                }

                $value .= implode('', $buttons);
            }
            elseif (!$instance->expired or ($instance->manageable and $instance->bookingID)) {
                $value = HTML::checkBox($index, $instanceID);
            }
        }

        if ($instance->manageable and $instance->presence !== Helper::ONLINE) {
            if ($instance->expired) {
                $icon  = HTML::icon('fa fa-folder red');
                $value .= '<br>' . HTML::tip($icon, "booking-$instanceID", 'BOOKING_CLOSED');
            }
            elseif (!$instance->premature) {
                $value .= '<br>';

                if ($instance->running) {
                    $icon  = HTML::icon('fa fa-folder-open green');
                    $value .= HTML::tip($icon, "booking-$instanceID", 'BOOKING_ONGOING');
                }
                else {
                    $icon  = HTML::icon('fa fa-folder-open yellow');
                    $value .= HTML::tip($icon, "booking-$instanceID", 'BOOKING_PENDING');
                }
            }

            // Premature
        }

        return $title ? ['properties' => ['class' => $class, 'title' => $title], 'value' => $value] : $value;
    }

    /**
     * Generates the common portion of the instance title for listed instances.
     *
     * @param   stdClass  $instance  the object containing instance information
     * @param   string    $title     the already processed portion of the title
     *
     * @return array
     */
    private function liGetTitle(stdClass $instance, string $title): array
    {
        $comment = $this->resolveLinks($instance->comment);

        if ($instance->courseID) {
            $title .= '<br>' . HTML::tip(HTML::icon('fa fa-link'), "course-$instance->instanceID", 'REGISTRATION_LINKED') . ' ';
            $title .= Text::_('ORGANIZER_INSTANCE_SERIES') . ": $instance->courseID";
        }

        $title .= empty($comment) ? '' : "<br><span class=\"comment\">$comment</span>";

        return ['attributes' => ['class' => 'title-column'], 'value' => $title];
    }

    /**
     * Resolves any links/link parameters to links with icons.
     *
     * @param   string      $text  the text to search
     * @param   array|null  $tools
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
        }
        else {
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
        }
        else {
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
        }
        else {
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
        }
        else {
            $template = "<a href=\"$1\" target=\"_blank\">$icon</a>";
            $text     = preg_replace($pattern, $template, $text);
        }

        return $text;
    }

    /**
     * Determines whether the item is conducted virtually: every person is assigned rooms, all assigned rooms are
     * virtual.
     *
     * @param   stdClass  $instance  the item being iterated
     *
     * @return void
     */
    private function setResources(stdClass $instance): void
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
        }
        else {
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
        }
        elseif ($presence) {
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

    /** @inheritDoc */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $now    = date('H:i');
        $today  = date('Y-m-d');
        $userID = User::id();

        $this->setResources($item);

        $instanceID = $item->instanceID;
        $isToday    = $item->date === $today;
        $then       = date('Y-m-d', strtotime('-2 days', strtotime($item->date)));

        $item->expired = ($item->date < $today or ($isToday and $item->endTime < $now));
        $item->full    = (!empty($item->capacity) and $item->current >= $item->capacity);
        $item->link    = Routing::getViewURL('InstanceItem', $instanceID);

        // Administrator, planer, or person of responsibility
        if ($userID and Can::manage('instance', $instanceID)) {
            $item->manageable = true;

            $teaches          = Helper::hasResponsibility($instanceID);
            $item->taught     = $teaches;
            $this->teachesOne = $teaches;
        }
        else {
            $item->manageable = false;
            $item->taught     = false;
            $this->teachesALL = false;
        }

        $item->premature         = $today < $then;
        $item->registration      = false;
        $item->registrationStart = Dates::formatDate($then);
        $item->running           = (!$item->expired and $item->date === $today and $item->startTime < $now);

        $validTiming = (!$item->expired and !$item->running);

        if ($validTiming and $item->presence !== Helper::ONLINE and !$item->full) {
            $item->registration = true;
        }
    }
}