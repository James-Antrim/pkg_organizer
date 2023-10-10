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

use Joomla\CMS\Toolbar\Button\StandardButton;
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Application, Document, Input, Text, Toolbar};
use THM\Organizer\Buttons\Link;
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Instances as Helper;
use stdClass;

/**
 * Class loads information about a given instance.
 */
class InstanceItem extends ListView
{
    use ListsInstances;

    private array $buttons = [];
    private string $dateTime;
    public stdClass $instance;
    protected $layout = 'instance-item';
    private array $messages = [];
    public string $minibar = '';
    protected $rowStructure = [
        'tools' => '',
        'date' => 'value',
        'time' => 'value',
        'persons' => 'value',
        'rooms' => 'value'
    ];
    private string $status = '';
    private string $statusDate = '';
    private int $userID;
    private string $referrer = '';

    /**
     * @inheritDoc
     */
    protected function addSupplement()
    {
        $color    = 'blue';
        $instance = $this->instance;
        $text     = '';

        if ($instance->expired) {
            $color          = 'grey';
            $this->messages = [Text::_('ORGANIZER_INSTANCE_EXPIRED')];
        } elseif (!$this->userID) {
            $this->messages[] = Text::_('ORGANIZER_INSTANCE_LOG_IN_FIRST');
        } elseif ($instance->registered) {
            $color            = 'green';
            $this->messages[] = Text::_('ORGANIZER_INSTANCE_REGISTERED');
        } elseif ($instance->bookmarked) {
            $this->messages[] = Text::_('ORGANIZER_INSTANCE_BOOKMARKED');

            if ($instance->presence !== Helper::ONLINE) {
                $color = 'yellow';
            }
        } elseif (!$instance->taught) {
            $this->messages[] = Text::_('ORGANIZER_INSTANCE_NOT_BOOKMARKED');
        }

        if ($this->messages) {
            $text = "<div class=\"tbox-$color\">" . implode('<br>', $this->messages) . '</div>';
        }

        $this->supplement = $text;
    }

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true)
    {
        $instance = $this->instance;
        $method   = $instance->method ? " - $instance->method" : '';
        $this->setTitle($instance->name . $method);
        $this->setSubtitle();

        $link     = new Link();
        $minibar  = [];
        $standard = new StandardButton();
        $toolbar  = Toolbar::getInstance();

        if ($this->referrer) {
            $minibar[] = $link->fetchButton('Link', 'undo-2', Text::_('ORGANIZER_BACK_TO_OVERVIEW'), $this->referrer);
        }

        if ($this->userID and $this->buttons) {
            $buttons = $this->buttons;

            if ($buttons['schedule']) {
                $minibar[] = $standard->fetchButton(
                    'Standard',
                    'bookmark',
                    Text::_('ORGANIZER_ADD_INSTANCE'),
                    'InstanceParticipants.bookmarkThis',
                    false
                );
            } elseif ($buttons['deschedule']) {
                $minibar[] = $standard->fetchButton(
                    'Standard',
                    'bookmark-2',
                    Text::_('ORGANIZER_DELETE_INSTANCE'),
                    'InstanceParticipants.removeBookmarkThis',
                    false
                );
            }

            if ($buttons['scheduleBlock']) {
                $minibar[] = $standard->fetchButton(
                    'Standard',
                    'bookmark',
                    Text::_('ORGANIZER_ADD_BLOCK_INSTANCES'),
                    'InstanceParticipants.bookmarkBlock',
                    false
                );
            }

            if ($buttons['descheduleBlock']) {
                $minibar[] = $standard->fetchButton(
                    'Standard',
                    'bookmark-2',
                    Text::_('ORGANIZER_DELETE_BLOCK_INSTANCES'),
                    'InstanceParticipants.removeBookmarkBlock',
                    false
                );
            }

            /*if ($buttons['register'])
            {
                $minibar[] = $standard->fetchButton(
                    'Standard',
                    'signup',
                    Text::_('ORGANIZER_REGISTER'),
                    'InstanceParticipants.registerThis',
                    false
                );
            }
            elseif ($buttons['deregister'])
            {
                $minibar[] = $standard->fetchButton(
                    'Standard',
                    'exit',
                    Text::_('ORGANIZER_DEREGISTER'),
                    'InstanceParticipants.deregisterThis',
                    false
                );
            }*/

            if ($buttons['scheduleList']) {
                $toolbar->appendButton(
                    'Standard',
                    'bookmark',
                    Text::_('ORGANIZER_ADD_INSTANCES'),
                    'InstanceParticipants.bookmark',
                    true
                );
            }

            if ($buttons['descheduleList']) {
                $toolbar->appendButton(
                    'Standard',
                    'bookmark-2',
                    Text::_('ORGANIZER_DELETE_INSTANCES'),
                    'InstanceParticipants.removeBookmark',
                    true
                );
            }

            /*if ($buttons['registerList'])
            {
                $toolbar->appendButton(
                    'Standard',
                    'signup',
                    Text::_('ORGANIZER_REGISTER'),
                    'InstanceParticipants.register',
                    true
                );
            }

            if ($buttons['deregisterList'])
            {
                $toolbar->appendButton(
                    'Standard',
                    'exit',
                    Text::_('ORGANIZER_DEREGISTER'),
                    'InstanceParticipants.deregister',
                    true
                );
            }*/

            if ($buttons['manage']) {
                $minibar[] = $standard->fetchButton(
                    'NewTab',
                    'users',
                    Text::_('ORGANIZER_MANAGE_BOOKING'),
                    'bookings.manageThis',
                    false
                );
            }

            if ($buttons['manageList']) {
                $toolbar->appendButton(
                    'Highlander',
                    'users',
                    Text::_('ORGANIZER_MANAGE_BOOKINGS'),
                    'bookings.manage',
                    true
                );
            }
        }

        if ($instance->subjectID) {
            $url       = Helpers\Routing::getViewURL('SubjectItem', $instance->subjectID);
            $minibar[] = $link->fetchButton('Link', 'book', Text::_('ORGANIZER_SUBJECT_ITEM'), $url, true);
        }

        if ($minibar) {
            $this->minibar = '<div class="btn-toolbar" role="toolbar" aria-label="Toolbar" id="minibar">';
            $this->minibar .= implode('', $minibar) . '</div>';
        }
    }

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        if (!$instanceID = Input::getID()) {
            Application::error(400);
        }

        if ($this->userID = Helpers\Users::getID()) {
            $this->manages = Helpers\Can::manage('instance', $instanceID);

            return;
        }

        $this->manages = false;
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null)
    {
        $model = $this->getModel();
        $this->setInstance($model->instance);
        $this->referrer = $model->referrer;

        parent::display($tpl);
    }

    /**
     * Creates the event title.
     *
     * @param stdClass $item the event item being iterated
     *
     * @return array the title column
     */
    private function getTitle(stdClass $item): array
    {
        $title = '<span class="date">' . Helpers\Dates::formatDate($item->date) . '</span> ';
        $title .= $this->mobile ? '<br>' : '';
        $title .= '<span class="times">' . $item->startTime . ' - ' . $item->endTime . '</span>';
        $title .= empty($item->method) ? '' : "<br><span class=\"method\">$item->method</span>";
        $title = Helpers\HTML::link($item->link, $title);

        return $this->liGetTitle($item, $title);
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument()
    {
        parent::modifyDocument();
        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/item.css');
    }

    /**
     * Renders the persons section of the item.
     * @return void
     */
    public function renderPersons()
    {
        $instance = $this->instance;

        echo '<div class="attribute-item">';
        echo '<div class="attribute-label">' . Text::_('ORGANIZER_PERSONS') . '</div>';
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
                    echo '<li>' . Text::_('ORGANIZER_GROUPS') . '<ul>';
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
                    echo '<li>' . Text::_('ORGANIZER_ROOMS') . '<ul>';
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
    public function renderOrganizational()
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
                $formText = Text::_('ORGANIZER_HYBRID');
                break;
            case Helper::ONLINE:
                $formText = Text::_('ORGANIZER_ONLINE_TEXT');
                break;
            case Helper::PRESENCE:
                $formText = Text::_('ORGANIZER_PRESENCE_TEXT');
                break;
        }

        echo $comment ? "<li>$comment</li>" : '';
        echo $list ? "<li>$formText</li>" : $formText;

        /*if ($instance->registration)
        {
            if (Helper::getMethodCode($instance->instanceID) === Helpers\Methods::FINALCODE)
            {
                echo '<li>' . Text::_('ORGANIZER_REGISTRATION_EXTERNAL') . '</li>';
            }
            elseif ($instance->premature)
            {
                echo '<li>' . Text::sprintf('ORGANIZER_REGISTRATION_OPENS_ON', $instance->registrationStart) . '</li>';
            }
            elseif ($instance->running)
            {
                echo '<li>' . Text::_('ORGANIZER_REGISTRATION_CLOSED') . '</li>';
            }
            else
            {
                echo '<li>' . Text::_('ORGANIZER_REGISTRATION_OPEN') . '</li>';

                if ($instance->capacity)
                {
                    if ($available = $instance->capacity - $instance->current)
                    {
                        echo '<li>' . Text::sprintf('ORGANIZER_REGISTRATIONS_AVAILABLE_COUNT', $available) . '</li>';
                    }
                    else
                    {
                        echo '<li>' . Text::_('ORGANIZER_INSTANCE_FULL') . '</li>';
                    }
                }
                // No capacity => no idea
                else
                {
                    echo '<li>' . Text::_('ORGANIZER_REGISTRATIONS_AVAILABLE') . '</li>';
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
     * @param string $name     the resource name
     * @param string $status   the resource's status
     * @param string $dateTime the date time of the resource's last status update
     *
     * @return void
     */
    private function renderResource(string $name, string $status, string $dateTime)
    {
        $implied       = ($dateTime === $this->dateTime and $status === $this->status);
        $irrelevant    = $dateTime < $this->statusDate;
        $uninteresting = !$status;

        if ($implied or $irrelevant or $uninteresting) {
            echo $name;

            return;
        }

        $dateTime = Helpers\Dates::formatDateTime($dateTime);
        $delta    = $status === 'removed' ?
            Text::sprintf('ORGANIZER_REMOVED_ON', $dateTime) : Text::sprintf('ORGANIZER_ADDED_ON', $dateTime);

        echo "<span class=\"$status\">$name</span> $delta";
    }

    /**
     * Renders the persons section of the item.
     * @return void
     */
    public function renderResources(string $label, array $resources)
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
     * @inheritdoc
     */
    public function setHeaders()
    {
        $this->headers = [
            'tools' => ($this->userID and !$this->mobile) ? Helpers\HTML::_('grid.checkall') : '',
            'instance' => Text::_('ORGANIZER_INSTANCE'),
            'status' => Text::_('ORGANIZER_STATUS'),
            'persons' => Text::_('ORGANIZER_PERSONS'),
            'groups' => Text::_('ORGANIZER_GROUPS'),
            'rooms' => Text::_('ORGANIZER_ROOMS')
        ];
    }

    /**
     * Processes the instance to aid in simplifying/supplementing the item display.
     *
     * @param stdClass $instance the instance data
     *
     * @return void
     */
    private function setInstance(stdClass $instance)
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
            $constant       = $instance->unitStatus === 'removed' ? 'ORGANIZER_UNIT_REMOVED_ON' : 'ORGANIZER_UNIT_ADDED_ON';
            $status         = $instance->unitStatus;
            $this->status   = $instance->unitStatus;
            $statusDate     = Helpers\Dates::formatDateTime($instance->unitStatusDate);
            $message        = Text::sprintf($constant, $statusDate);
        }

        $dateTime = $instance->instanceStatusDate;

        if ($instance->instanceStatus and $dateTime >= $cutOff) {
            $earlier    = $instance->instanceStatusDate < $instance->unitStatusDate;
            $later      = $instance->instanceStatusDate > $instance->unitStatusDate;
            $modified   = max($dateTime, $modified);
            $statusDate = Helpers\Dates::formatDateTime($instance->instanceStatusDate);

            // Instance was removed...
            if ($instance->instanceStatus === 'removed') {
                $text = 'ORGANIZER_INSTANCE_REMOVED_ON';

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
                $message        = Text::sprintf('ORGANIZER_INSTANCE_ADDED_ON', $statusDate);
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
            $modified         = Helpers\Dates::formatDateTime($modified);
            $this->messages[] = Text::sprintf('ORGANIZER_LAST_UPDATED', $modified);
        }

        $this->instance = $instance;
    }

    /**
     * @param array   &$collection the aggregated collection for the resource
     * @param array   &$filtered   the resource filtered of attributes obfuscating resource uniqueness
     * @param string  &$modified   the date time string denoting the last modification date for the whole instance
     * @param int      $key        the resource's id in the database
     * @param string   $name       the name of the resource
     * @param array    $resource   the resource being iterated
     *
     * @return void
     */
    private function setResource(
        array  &$collection,
        array  &$filtered,
        string &$modified,
        int    $key,
        string $name,
        array  $resource
    )
    {
        $dateTime = $resource['statusDate'];

        if (empty($collection[$name]) or $dateTime > $collection[$name]['date']) {
            $collection[$name] = [
                'date' => $modified,
                'status' => $resource['status']
            ];
        }

        $modified = max($dateTime, $modified);

        $copy = $resource;
        unset($copy['status'], $copy['statusDate']);
        $filtered[$key] = $copy;
    }

    /**
     * @inheritdoc
     */
    protected function setSubtitle()
    {
        $instance       = $this->instance;
        $date           = Helpers\Dates::formatDate($instance->date);
        $this->subtitle = "<h4>$date $instance->startTime - $instance->endTime</h4>";
    }

    /**
     * @inheritdoc
     */
    protected function structureItems()
    {
        $this->setDerived($this->items);

        $buttons = [
            //'deregister'      => false,
            //'deregisterList'  => false,
            'deschedule' => false,
            'descheduleBlock' => false,
            'descheduleList' => false,
            'manage' => false,
            'manageList' => false,
            //'register'        => false,
            //'registerList'    => false,
            'schedule' => false,
            'scheduleBlock' => false,
            'scheduleList' => false
        ];

        $instance = $this->instance;

        if (!$instance->expired and !$instance->running and !$instance->taught) {
            if ($instance->bookmarked) {
                $buttons['deschedule'] = true;

            } else {
                $buttons['schedule'] = true;

            }

            /*$notFinal     = Helper::getMethodCode($instance->instanceID) !== Helpers\Methods::FINALCODE;
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
        } elseif ($instance->manageable and !$instance->premature) {
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
                } else {
                    $buttons['scheduleList'] = true;

                    /*$notFinal     = Helper::getMethodCode($item->instanceID) !== Helpers\Methods::FINALCODE;
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
            } elseif ($item->manageable and !$item->premature) {
                $buttons['manageList'] = true;
            }

            $structuredItems[$index]             = [];
            $structuredItems[$index]['tools']    = $this->getToolsColumn($item, $index);
            $structuredItems[$index]['instance'] = $this->getTitle($item);
            $structuredItems[$index]['status']   = $this->getStatus($item);
            $this->addResources($structuredItems[$index], $item);

            $index++;
        }

        if ($this->mobile) {
            $buttons['deregisterList'] = false;
            $buttons['descheduleList'] = false;
            $buttons['manageList']     = false;
            $buttons['registerList']   = false;
            $buttons['scheduleList']   = false;
        }

        $this->buttons = $buttons;
        $this->items   = $structuredItems;
    }
}
