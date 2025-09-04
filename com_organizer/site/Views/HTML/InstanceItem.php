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

use stdClass;
use THM\Organizer\Adapters\{Application, HTML, Input, Text, Toolbar, User};
use THM\Organizer\Helpers\{Can, Dates, Instances as Helper, Routing};
use THM\Organizer\Buttons\{FormTarget, Highlander};
use THM\Organizer\Models\InstanceItem as Model;

/**
 * Class loads information about a given instance.
 */
class InstanceItem extends ListView
{
    use ListsInstances;

    /** @var array A list of buttons to add to the toolbars. */
    private array $buttons = [];
    private string $dateTime;
    public stdClass $instance;
    protected string $layout = 'instance-item';

    private array $messages = [];
    public string $minibar = '';
    private string $status = '';
    private string $statusDate = '';
    private int $userID;
    private string $referrer = '';

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $instance = $this->instance;
        $method   = $instance->method ? " - $instance->method" : '';
        $this->title($instance->name . $method);
        $this->addSubtitle();

        $itembar = Toolbar::getInstance('itembar');
        $listbar = Toolbar::getInstance();

        if ($this->referrer) {
            $itembar->linkButton('back', Text::_('BACK_TO_OVERVIEW'))->url($this->referrer)->icon('fa fa-undo');
        }

        if ($this->userID and $this->buttons) {
            $buttons = $this->buttons;

            if ($buttons['schedule']) {
                $itembar->standardButton('bookmark', Text::_('ADD_INSTANCE'), 'InstanceParticipants.bookmarkThis')
                    ->icon('fas fa-bookmark');
            }
            elseif ($buttons['deschedule']) {
                $itembar->standardButton('unbookmark', Text::_('DELETE_INSTANCE'), 'InstanceParticipants.removeBookmarkThis')
                    ->icon('far fa-bookmark');
            }

            if ($buttons['scheduleBlock']) {
                $itembar->standardButton('bookmark-block', Text::_('ADD_BLOCK_INSTANCES'), 'InstanceParticipants.bookmarkBlock')
                    ->icon('fas fa-bookmark');
            }

            if ($buttons['descheduleBlock']) {
                $itembar->standardButton(
                    'unbookmark-block',
                    Text::_('DELETE_BLOCK_INSTANCES'),
                    'InstanceParticipants.removeBookmarkBlock'
                )->icon('far fa-bookmark');
            }

            /*if ($buttons['register'])
            {
                $itembar->standardButton('register', Text::_('REGISTER'), 'InstanceParticipants.registerThis')
                    ->icon('fa fa-sign-in-alt');
            }
            elseif ($buttons['deregister'])
            {
                $itembar->standardButton('deregister', Text::_('DEREGISTER'), 'InstanceParticipants.deregisterThis')
                    ->icon('fa fa-sign-out-alt');
            }*/

            if ($buttons['scheduleList']) {
                $listbar->standardButton('bookmark-list', Text::_('ADD_INSTANCES'), 'InstanceParticipants.bookmark')
                    ->icon('fas fa-bookmark')
                    ->listCheck(true);
            }

            if ($buttons['descheduleList']) {
                $listbar->standardButton('unbookmark-list', Text::_('DELETE_INSTANCES'), 'InstanceParticipants.removeBookmark')
                    ->icon('far fa-bookmark')
                    ->listCheck(true);
            }

            /*if ($buttons['registerList'])
            {
                $listbar->standardButton('register-list', Text::_('REGISTER'), 'InstanceParticipants.register')->listCheck(true)
                    ->icon('fa fa-sign-in-alt');;
            }

            if ($buttons['deregisterList'])
            {
                $listbar->standardButton('deregister-list', Text::_('DEREGISTER'), 'InstanceParticipants.deregister')
                    ->listCheck(true)
                    ->icon('fa fa-sign-out-alt');
            }*/

            if ($buttons['manage']) {
                $button = new FormTarget('booking', Text::_('MANAGE_BOOKING'));
                $button->icon('fa fa-users')->task('Booking.manageThis');
                $itembar->appendButton($button);
            }

            if ($buttons['manageList']) {
                $button = new Highlander('bookings', Text::_('MANAGE_BOOKINGS'));
                $button->icon('fa fa-users')->task('Bookings.manage');
                $listbar->appendButton($button);
            }
        }

        if ($instance->subjectID) {
            $url = Routing::getViewURL('SubjectItem', $instance->subjectID);
            $itembar->linkButton('subject', Text::_('SUBJECT_ITEM'))->target('_blank')->url($url)->icon('fa fa-book');
        }

        $this->minibar = '<div class="btn-toolbar" role="toolbar" aria-label="Toolbar" id="minibar">';
        $this->minibar .= Toolbar::render('itembar') . '</div>';
    }

    /**
     * @inheritDoc
     */
    protected function authorize(): void
    {
        if (!$instanceID = Input::id()) {
            Application::error(400);
        }

        if ($this->userID = User::id()) {
            $this->manages = Can::manage('instance', $instanceID);
        }
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null): void
    {
        /** @var Model $model */
        $model = $this->getModel();
        $this->setInstance($model->instance);
        $this->referrer = $model->referrer;

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
        $title = '<span class="date">' . Dates::formatDate($item->date) . '</span> ';
        $title .= Application::mobile() ? '<br>' : '';
        $title .= '<span class="times">' . $item->startTime . ' - ' . $item->endTime . '</span>';
        $title .= empty($item->method) ? '' : "<br><span class=\"method\">$item->method</span>";
        $title = HTML::link($item->link, $title);

        return $this->liGetTitle($item, $title);
    }

    /**
     * Renders the persons section of the item.
     * @return void
     */
    public function renderPersons(): void
    {
        $instance = $this->instance;

        echo '<div class="attribute-item">';
        echo '<div class="attribute-label">' . Text::_('PERSONS') . '</div>';
        echo '<div class="attribute-content"><ul>';

        foreach ($instance->persons as $persons) {
            if ($instance->showRoles) {
                $personIDs = array_keys($persons);
                $firstID   = reset($personIDs);
                echo '<u>' . $instance->resources[$firstID]['role'] . '</u><ul>';
            }

            foreach (array_keys($persons) as $personID) {
                $list   = ($instance->showRoles or count($persons) > 1);
                $person = $instance->resources[$personID];

                echo $list ? '<li>' : '';

                $this->renderResource($person['person'], $person['status'], $person['statusDate']);

                if ($instance->hideGroups or $instance->hideRooms) {
                    echo '<ul>';
                }

                if ($instance->hideGroups and !empty($person['groups'])) {
                    echo '<li>' . Text::_('GROUPS') . '<ul>';
                    foreach ($person['groups'] as $group) {
                        $list = count($person['groups']) > 1;
                        echo $list ? '<li>' : '';
                        $name = (strlen($group['fullName']) > 80 and $group['status']) ?
                            $group['group'] : $group['fullName'];
                        $this->renderResource($name, $group['status'], $group['statusDate']);
                        echo $list ? '</li>' : '';
                    }
                    echo '</ul></li>';
                }

                if ($instance->hideRooms and !empty($person['rooms'])) {
                    echo '<li>' . Text::_('ROOMS') . '<ul>';
                    foreach ($person['rooms'] as $room) {
                        $list = count($person['rooms']) > 1;
                        echo $list ? '<li>' : '';
                        $this->renderResource($room['room'], $room['status'], $room['statusDate']);
                        echo $list ? '</li>' : '';
                    }
                    echo '</ul></li>';
                }

                if ($instance->hideGroups or $instance->hideRooms) {
                    echo '</ul>';
                }

                echo $list ? '</li>' : '';
            }

            if ($instance->showRoles) {
                echo '</ul>';
            }
        }

        echo '</ul></div></div>';
    }

    /**
     * Renders texts about the organization of the appointment in terms of presence...
     * @return void
     */
    public function renderOrganizational(): void
    {
        $instance = $this->instance;

        $comment      = $this->resolveLinks($instance->comment);
        $registration = $instance->registration;

        $list = ($registration or $comment);

        if ($list) {
            echo '<ul>';
        }

        $formText = '';

        switch ($instance->presence) {
            case Helper::HYBRID:
                $formText = Text::_('HYBRID');
                break;
            case Helper::ONLINE:
                $formText = Text::_('ONLINE_TEXT');
                break;
            case Helper::PRESENCE:
                $formText = Text::_('PRESENCE_TEXT');
                break;
        }

        echo $comment ? "<li>$comment</li>" : '';
        echo $list ? "<li>$formText</li>" : $formText;

        /*if ($instance->registration)
        {
            if (Helper::getMethodCode($instance->instanceID) === Methods::FINALCODE)
            {
                echo '<li>' . Text::_('REGISTRATION_EXTERNAL') . '</li>';
            }
            elseif ($instance->premature)
            {
                echo '<li>' . Text::sprintf('REGISTRATION_OPENS_ON', $instance->registrationStart) . '</li>';
            }
            elseif ($instance->running)
            {
                echo '<li>' . Text::_('REGISTRATION_CLOSED') . '</li>';
            }
            else
            {
                echo '<li>' . Text::_('REGISTRATION_OPEN') . '</li>';

                if ($instance->capacity)
                {
                    if ($available = $instance->capacity - $instance->current)
                    {
                        echo '<li>' . Text::sprintf('REGISTRATIONS_AVAILABLE_COUNT', $available) . '</li>';
                    }
                    else
                    {
                        echo '<li>' . Text::_('INSTANCE_FULL') . '</li>';
                    }
                }
                // No capacity => no idea
                else
                {
                    echo '<li>' . Text::_('REGISTRATIONS_AVAILABLE') . '</li>';
                }
            }
        }*/

        if ($list) {
            echo '</ul>';
        }
    }

    /**
     * Renders the individual resource output.
     *
     * @param   string  $name      the resource name
     * @param   string  $status    the resource's status
     * @param   string  $dateTime  the date time of the resource's last status update
     *
     * @return void
     */
    private function renderResource(string $name, string $status, string $dateTime): void
    {
        $implied       = ($dateTime === $this->dateTime and $status === $this->status);
        $irrelevant    = $dateTime < $this->statusDate;
        $uninteresting = !$status;

        if ($implied or $irrelevant or $uninteresting) {
            echo $name;

            return;
        }

        $dateTime = Dates::formatDateTime($dateTime);
        $delta    = $status === 'removed' ?
            Text::sprintf('REMOVED_ON', $dateTime) : Text::sprintf('ADDED_ON', $dateTime);

        echo "<span class=\"$status\">$name</span> $delta";
    }

    /**
     * Renders the persons section of the item.
     *
     * @param   string  $label      the resources displayed in this section
     * @param   array   $resources  the resource items
     *
     * @return void
     */
    public function renderResources(string $label, array $resources): void
    {
        echo '<div class="attribute-item">';
        echo "<div class=\"attribute-label\">$label</div>";
        echo '<div class="attribute-content"><ul>';

        foreach ($resources as $name => $data) {
            $list = count($resources) > 1;
            echo $list ? '<li>' : '';
            $this->renderResource($name, $data['status'], $data['date']);
            echo $list ? '</li>' : '';
        }
        echo '</ul></div></div>';
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $this->headers = [
            'tools'    => ($this->userID and !Application::mobile()) ? HTML::checkAll() : '',
            'instance' => Text::_('INSTANCE'),
            'status'   => Text::_('STATUS'),
            'persons'  => Text::_('PERSONS'),
            'groups'   => Text::_('GROUPS'),
            'rooms'    => Text::_('ROOMS')
        ];
    }

    /**
     * Processes the instance to aid in simplifying/supplementing the item display.
     *
     * @param   stdClass  $instance  the instance data
     *
     * @return void
     */
    private function setInstance(stdClass $instance): void
    {
        $this->setSingle($instance);

        $this->statusDate = date('Y-m-d 00:00:00', strtotime('-14 days'));
        $cutOff           = $this->statusDate;

        $bookends = ['new', 'removed'];
        $message  = '';
        $status   = '';

        $dateTime       = $instance->unitStatusDate;
        $this->dateTime = $dateTime;
        $dtRelevant     = ($instance->unitStatus and $dateTime >= $cutOff and in_array($instance->unitStatus,
                $bookends));
        $modified       = $instance->unitStatusDate;

        // Set unit baseline for process dating.
        if ($dtRelevant) {
            $this->dateTime = $instance->unitStatusDate;
            $constant       = $instance->unitStatus === 'removed' ? 'UNIT_REMOVED_ON' : 'UNIT_ADDED_ON';
            $status         = $instance->unitStatus;
            $this->status   = $instance->unitStatus;
            $statusDate     = Dates::formatDateTime($instance->unitStatusDate);
            $message        = Text::sprintf($constant, $statusDate);
        }

        $dateTime = $instance->instanceStatusDate;

        if ($instance->instanceStatus and $dateTime >= $cutOff) {
            $earlier    = $instance->instanceStatusDate < $instance->unitStatusDate;
            $later      = $instance->instanceStatusDate > $instance->unitStatusDate;
            $modified   = max($dateTime, $modified);
            $statusDate = Dates::formatDateTime($instance->instanceStatusDate);

            // Instance was removed...
            if ($instance->instanceStatus === 'removed') {
                $text = 'INSTANCE_REMOVED_ON';

                // ...before the unit was removed.
                if ($status === 'removed' and $earlier) {
                    $this->dateTime = $instance->instanceStatusDate;
                    $message        = Text::sprintf($text, $statusDate);
                } // ...and the unit was not.
                elseif ($status !== 'removed' and $later) {
                    $this->dateTime = $instance->instanceStatusDate;
                    $this->status   = $instance->instanceStatus;
                    $message        = Text::sprintf($text, $statusDate);
                }
            } // Instance was recently added
            elseif ($status !== 'removed' and $instance->instanceStatus === 'new') {
                $this->dateTime = $instance->instanceStatusDate;
                $this->status   = $instance->instanceStatus;
                $message        = Text::sprintf('INSTANCE_ADDED_ON', $statusDate);
            }
        }

        $persons = [];

        // Aggregate resource containers.
        $groups = [];
        $rooms  = [];

        // Containers for unique resource configurations.
        $uniqueGroups = [];
        $uniqueRooms  = [];

        if (!empty($instance->resources)) {
            foreach ($instance->resources as $personID => $person) {
                $dateTime   = $person['statusDate'];
                $dtRelevant = ($person['status'] and $dateTime >= $cutOff);

                // Removed before cut off
                if (!$dtRelevant and $person['status'] === 'removed') {
                    unset($instance->resources[$personID]);
                    continue;
                }

                $filteredGroups = [];
                $filteredRooms  = [];
                $modified       = max($dateTime, $modified);

                if (empty($persons[$person['roleID']])) {
                    $persons[$person['roleID']] = [];
                }

                $persons[$person['roleID']][$personID] = $person['person'];

                if (!empty($person['groups'])) {
                    foreach ($person['groups'] as $groupID => $group) {
                        $dateTime   = $group['statusDate'];
                        $dtRelevant = ($group['status'] and $dateTime >= $cutOff);

                        // Removed before cut off
                        if (!$dtRelevant and $group['status'] === 'removed') {
                            unset($instance->resources[$personID]['groups'][$groupID]);
                            continue;
                        }

                        $name = $group['fullName'];
                        $this->setResource($groups, $filteredGroups, $modified, $groupID, $name, $group);
                    }
                }

                if (!in_array($filteredGroups, $uniqueGroups)) {
                    $uniqueGroups[] = $filteredGroups;
                }

                if (!empty($person['rooms'])) {
                    foreach ($person['rooms'] as $roomID => $room) {
                        $dateTime   = $room['statusDate'];
                        $dtRelevant = ($room['status'] and $dateTime >= $cutOff);

                        // Removed before cut off
                        if ((!$dtRelevant and $room['status'] === 'removed') or $room['virtual']) {
                            unset($instance->resources[$personID]['rooms'][$roomID]);
                            continue;
                        }

                        $name = $room['room'];
                        $this->setResource($rooms, $filteredRooms, $modified, $roomID, $name, $room);
                    }
                }

                if (!in_array($filteredRooms, $uniqueRooms)) {
                    $uniqueRooms[] = $filteredRooms;
                }
            }
        }

        // Alphabetize in role.
        foreach ($persons as $roleID => $entries) {
            asort($entries);
            $persons[$roleID] = $entries;
        }

        asort($groups);
        asort($rooms);

        $instance->groups     = $groups;
        $instance->hideGroups = count(array_filter($uniqueGroups)) > 1;
        $instance->hideRooms  = count(array_filter($uniqueRooms)) > 1;
        $instance->persons    = $persons;
        $instance->rooms      = $rooms;
        $instance->showRoles  = count($instance->persons) > 1;

        if ($message) {
            $this->messages[] = $message;
        }

        if ($modified and $status !== 'new' and $status !== 'removed') {
            $modified         = Dates::formatDateTime($modified);
            $this->messages[] = Text::sprintf('LAST_UPDATED', $modified);
        }

        $this->instance = $instance;
    }

    /**
     * @param   array   &$collection  the aggregated collection for the resource
     * @param   array   &$filtered    the resource filtered of attributes obfuscating resource uniqueness
     * @param   string  &$modified    the date time string denoting the last modification date for the whole instance
     * @param   int      $key         the resource's id in the database
     * @param   string   $name        the name of the resource
     * @param   array    $resource    the resource being iterated
     *
     * @return void
     */
    private function setResource(
        array &$collection,
        array &$filtered,
        string &$modified,
        int $key,
        string $name,
        array $resource
    ): void
    {
        $dateTime = $resource['statusDate'];

        if (empty($collection[$name]) or $dateTime > $collection[$name]['date']) {
            $collection[$name] = [
                'date'   => $modified,
                'status' => $resource['status']
            ];
        }

        $modified = max($dateTime, $modified);

        $copy = $resource;
        unset($copy['status'], $copy['statusDate']);
        $filtered[$key] = $copy;
    }

    /**
     * Adds a subtitle with supplemental information.
     */
    private function addSubtitle(): void
    {
        $instance       = $this->instance;
        $date           = Dates::formatDate($instance->date);
        $this->subtitle = "<h4>$date $instance->startTime - $instance->endTime</h4>";
    }

    /** @inheritDoc */
    protected function completeItems(array $options = []): void
    {
        parent::completeItems();

        $buttons = [
            //'deregister'      => false,
            //'deregisterList'  => false,
            'deschedule'      => false,
            'descheduleBlock' => false,
            'descheduleList'  => false,
            'manage'          => false,
            'manageList'      => false,
            //'register'        => false,
            //'registerList'    => false,
            'schedule'        => false,
            'scheduleBlock'   => false,
            'scheduleList'    => false
        ];

        $instance = $this->instance;

        if (!$instance->expired and !$instance->running and !$instance->taught) {
            if ($instance->bookmarked) {
                $buttons['deschedule'] = true;

            }
            else {
                $buttons['schedule'] = true;

            }

            /*$notFinal     = Helper::getMethodCode($instance->instanceID) !== Methods::FINALCODE;
            $notFull      = !$instance->full;
            $notOnline    = $instance->presence !== Helper::ONLINE;
            $notPremature = !$instance->premature;

            if ($instance->registered)
            {
                $buttons['deregister'] = true;
            }
            elseif ($notFinal and $notFull and $notOnline and $notPremature)
            {
                $buttons['register'] = true;
            }*/
        }
        elseif ($instance->manageable and !$instance->premature) {
            $buttons['manage'] = true;
        }

        $index           = 0;
        $structuredItems = [];
        $thisDOW         = strtoupper(date('l', strtotime($instance->date)));

        foreach ($this->items as $item) {
            if (!$item->expired and !$item->running and !$item->taught) {
                $sameDOW      = (strtoupper(date('l', strtotime($item->date))) === $thisDOW);
                $sameET       = $item->startTime === $instance->startTime;
                $sameST       = $item->startTime === $instance->startTime;
                $sameBlock    = ($sameDOW and $sameET and $sameST);
                $sameInstance = $item->instanceID === $instance->instanceID;

                if ($item->bookmarked) {
                    $buttons['descheduleList'] = true;

                    /*if ($item->registered)
                    {
                        $buttons['deregisterList'] = true;
                    }

                    if ($sameBlock and !$sameInstance)
                    {
                        $buttons['descheduleBlock'] = true;
                    }*/
                }
                else {
                    $buttons['scheduleList'] = true;

                    /*$notFinal     = Helper::getMethodCode($item->instanceID) !== Methods::FINALCODE;
                    $notFull      = !$item->full;
                    $notOnline    = $item->presence !== Helper::ONLINE;
                    $notPremature = !$item->premature;

                    if ($notFinal and $notFull and $notOnline and $notPremature)
                    {
                        $buttons['registerList'] = true;
                    }*/

                    if ($sameBlock and !$sameInstance) {
                        $buttons['scheduleBlock'] = true;
                    }
                }
            }
            elseif ($item->manageable and !$item->premature) {
                $buttons['manageList'] = true;
            }

            $structuredItems[$index]             = [];
            $structuredItems[$index]['tools']    = $this->getToolsColumn($item, $index);
            $structuredItems[$index]['instance'] = $this->getTitle($item);
            $structuredItems[$index]['status']   = $this->getStatus($item);

            $index++;
        }

        if (Application::mobile()) {
            $buttons['deregisterList'] = false;
            $buttons['descheduleList'] = false;
            $buttons['manageList']     = false;
            $buttons['registerList']   = false;
            $buttons['scheduleList']   = false;
        }

        $this->buttons = $buttons;
        $this->items   = $structuredItems;
    }

    /**
     * @inheritDoc
     */
    protected function supplement(): void
    {
        $color    = 'blue';
        $instance = $this->instance;
        $text     = '';

        if ($instance->expired) {
            $color          = 'grey';
            $this->messages = [Text::_('INSTANCE_EXPIRED')];
        }
        elseif (!$this->userID) {
            $this->messages[] = Text::_('INSTANCE_LOG_IN_FIRST');
        }
        elseif ($instance->registered) {
            $color            = 'green';
            $this->messages[] = Text::_('INSTANCE_REGISTERED');
        }
        elseif ($instance->bookmarked) {
            $this->messages[] = Text::_('INSTANCE_BOOKMARKED');

            if ($instance->presence !== Helper::ONLINE) {
                $color = 'yellow';
            }
        }
        elseif (!$instance->taught) {
            $this->messages[] = Text::_('INSTANCE_NOT_BOOKMARKED');
        }

        if ($this->messages) {
            $text = "<div class=\"tbox-$color\">" . implode('<br>', $this->messages) . '</div>';
        }

        $this->supplement = $text;
    }
}
