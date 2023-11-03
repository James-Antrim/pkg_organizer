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

use Joomla\CMS\Toolbar\Button\StandardButton;
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Application, Document, HTML, Input, Text, Toolbar};
use THM\Organizer\Buttons;
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\{Dates, HTML as Deprecated, Instances as Helper};
use THM\Organizer\Models\Instances as Model;
use stdClass;

/**
 * Class loads persistent information a filtered set of instances into the display context.
 */
class Instances extends ListView
{
    use ListsInstances;

    /**
     * Will later determine whether an edit button will be displayed
     * @var bool
     */
    private bool $allowEdit = false;

    private array $courses = [];

    private bool $expired = true;

    public bool $noInstances = true;

    private bool $premature = true;

    /**
     * Whether the registration is allowed for any instance.
     * @var bool
     */
    private bool $registration = false;

    private string $statusDate;

    protected bool $structureEmpty = true;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->statusDate = date('Y-m-d 00:00:00', strtotime('-14 days'));
    }

    /**
     * @inheritDoc
     */
    protected function addSubtitle(): void
    {
        if ($interval = $this->state->get('list.interval') and $interval === 'quarter') {
            $date           = $this->state->get('list.date');
            $interval       = Helpers\Dates::getQuarter($date);
            $interval       = Helpers\Dates::getDisplay($interval['startDate'], $interval['endDate']);
            $this->subtitle = "<h6 class=\"sub-title\">$interval</h6>";
        }
    }

    /**
     * Adds supplemental information to the display output.
     * @return void modifies the object property supplement
     */
    protected function addSupplement(): void
    {
        if ($this->noInstances) {
            $supplement = '<div class="tbox-yellow">';

            /** @var Model $model */
            $model = $this->model;
            if (!$model->noDate and $dates = Helper::getJumpDates($model->conditions)) {
                $supplement .= Text::_('ORGANIZER_NO_INSTANCES_IN_INTERVAL');
                $supplement .= '<ul><li>';

                foreach ($dates as $key => $date) {
                    $constant      = $key === 'futureDate' ? 'ORGANIZER_NEXT_INSTANCE' : 'ORGANIZER_PREVIOUS_INSTANCE';
                    $formattedDate = Dates::formatDate($date);
                    $text          = Text::_($constant);

                    $template    = "TEXT: <a onclick=\"jump('DATE')\">formatted date</a>";
                    $output      = str_replace('formatted date', $formattedDate, $template);
                    $output      = str_replace('DATE', $date, $output);
                    $dates[$key] = str_replace('TEXT', $text, $output);
                }

                $supplement .= implode('</li><li>', $dates) . '</li></ul>';
            }
            elseif (Input::getInt('my')) {
                if (Helpers\Users::getID()) {
                    $supplement .= Text::_('ORGANIZER_EMPTY_PERSONAL_RESULT_SET');
                }
                else {
                    $supplement .= Text::_('ORGANIZER_401');
                }
            }
            else {
                $supplement .= Text::_('ORGANIZER_NO_INSTANCES_IN_INTERVAL');
            }

            $supplement .= '</div>';

            $this->supplement = $supplement;
        }
    }

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $this->setTitle($this->get('title'));
        $toolbar  = Toolbar::getInstance();
        $link     = new Buttons\Link();
        $newTab   = new Buttons\NewTab();
        $script   = new Buttons\Script();
        $standard = new StandardButton();
        $expURL   = Helpers\Routing::getViewURL('export');

        if (Application::mobile()) {
            $toolbar->appendButton('Script', 'info-calender', Text::_('ORGANIZER_ICS_CALENDAR'), 'onclick', 'makeLink()');
            $toolbar->appendButton('Link', 'equalizer', Text::_('ORGANIZER_ADVANCED_EXPORT'), $expURL);
        }
        else {
            /** @var Model $model */
            $model = $this->model;
            if (Helpers\Users::getID() and $model->layout === Helper::LIST) {
                if (!$this->expired and !$this->teachesALL) {
                    $add    = $standard->fetchButton(
                        'Standard',
                        'bookmark',
                        Text::_('ORGANIZER_BOOKMARK'),
                        'InstanceParticipants.bookmark'
                    );
                    $remove = $standard->fetchButton(
                        'Standard',
                        'bookmark-2',
                        Text::_('ORGANIZER_REMOVE_BOOKMARK'),
                        'InstanceParticipants.removeBookmark'
                    );
                    $toolbar->appendButton(
                        'Buttons',
                        'buttons',
                        Text::_('ORGANIZER_INSTANCES'),
                        [$add, $remove],
                        'bookmark'
                    );
                }

                /*if ($this->registration and !$this->premature and !$this->teachesALL)
                {
                    $register   = $standard->fetchButton(
                        'Standard',
                        'signup',
                        Text::_('ORGANIZER_REGISTER'),
                        'InstanceParticipants.register',
                        true
                    );
                    $deregister = $standard->fetchButton(
                        'Standard',
                        'exit',
                        Text::_('ORGANIZER_DEREGISTER'),
                        'InstanceParticipants.deregister',
                        true
                    );
                    $toolbar->appendButton(
                        'Buttons',
                        'buttons',
                        Text::_('ORGANIZER_PRESENCE_PARTICIPATION'),
                        [$register, $deregister],
                        'signup'
                    );
                }*/

                if (($this->manages or $this->teachesOne) and !$this->premature) {
                    $toolbar->appendButton(
                        'Highlander',
                        'users',
                        Text::_('ORGANIZER_MANAGE_BOOKING'),
                        'bookings.manage',
                        true
                    );
                }
            }

            $interval = match ((string) $this->state->get('list.interval')) {
                'half' => Text::_('ORGANIZER_HALF_YEAR'),
                'month' => Text::_('ORGANIZER_SELECTED_MONTH'),
                'quarter' => Text::_('ORGANIZER_QUARTER'),
                'term' => Text::_('ORGANIZER_SELECTED_TERM'),
                'week' => Text::_('ORGANIZER_SELECTED_WEEK'),
                default => Text::_('ORGANIZER_SELECTED_DAY'),
            };

            $icsText     = Text::_('ORGANIZER_ICS_URL');
            $icsButton   = $script->fetchButton('Script', 'info-calender', $icsText, 'onclick', 'makeLink()');
            $pdfA3Text   = Text::sprintf('ORGANIZER_PDF_A3', $interval);
            $pdfA3Button = $newTab->fetchButton('NewTab', 'file-pdf', $pdfA3Text, 'Instances.gridA3', false);
            $pdfA4Text   = Text::sprintf('ORGANIZER_PDF_A4', $interval);
            $pdfA4Button = $newTab->fetchButton('NewTab', 'file-pdf', $pdfA4Text, 'Instances.gridA4', false);
            $xlsText     = Text::sprintf('ORGANIZER_XLS_LIST', $interval);
            $xlsButton   = $newTab->fetchButton('NewTab', 'file-xls', $xlsText, 'Instances.xls', false);

            $exportButtons = [
                $icsText   => $icsButton,
                $pdfA3Text => $pdfA3Button,
                $pdfA4Text => $pdfA4Button,
                $xlsText   => $xlsButton
            ];

            ksort($exportButtons);

            $expText                 = Text::_('ORGANIZER_ADVANCED_EXPORT');
            $expButton               = $link->fetchButton('Link', 'equalizer', $expText, $expURL);
            $exportButtons[$expText] = $expButton;

            $toolbar->appendButton('Buttons', 'buttons', Text::_('ORGANIZER_EXPORT'), $exportButtons, 'download');
        }
    }

    /**
     * @inheritdoc
     */
    protected function authorize(): void
    {
        if (Application::backend()) {
            if (!$this->manages = (bool) Helpers\Can::scheduleTheseOrganizations()) {
                Application::error(403);
            }

            return;
        }

        if (Input::getBool('my') and !Helpers\Users::getID()) {
            Application::error(401);
        }

        $organizationID = Input::getParams()->get('organizationID', 0);
        $this->manages  = $organizationID ?
            Helpers\Can::manage('organization', $organizationID) : (bool) Helpers\Can::manageTheseOrganizations();
    }

    /**
     * @inheritdoc
     */
    protected function completeItems(): void
    {
        if (!empty($this->items)) {
            $this->noInstances = false;
            $this->setDerived($this->items);
        }

        /** @var Model $model */
        $model = $this->model;
        if ($model->layout === Helper::GRID) {

            // Prevent setting the grid id without having the context from items at least once
            if (empty($this->items)) {
                $this->filterForm->removeField('gridID', 'list');
            }

            $this->layout = 'grid';
            $this->structureGrid();

            return;
        }

        $this->layout = 'list';
        $this->structureList();
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null): void
    {
        $this->empty = '';

        parent::display($tpl);
    }

    /**
     * Creates the blocks used to display a grid schedule from the raw grid periods.
     *
     * @param   array  $periods  the raw periods data
     * @param   bool   $allDay   whether the grid consists of a single block for the whole day
     *
     * @return array[]
     */
    private function getBlocks(array $periods, bool &$allDay): array
    {
        $blocks = [];
        $tag    = Application::getTag();

        foreach ($periods as $period) {
            $block              = [];
            $block['key']       = "{$period['startTime']}-{$period['endTime']}";
            $block['endTime']   = Dates::formatEndTime($period['endTime']);
            $block['startTime'] = Dates::formatTime($period['startTime']);
            $block['type']      = $period['type'];

            if (!empty($period["label_$tag"])) {
                $block['label'] = $period["label_$tag"];
            }
            else {
                $allDay         = ($block['endTime'] === '00:00' and $block['startTime'] === '00:00');
                $block['label'] = $allDay ? '' : "{$block['startTime']}<br>-<br>{$block['endTime']}";
            }

            $blocks[] = $block;
        }

        return $blocks;
    }

    /**
     * Generates a grid structure based upon the frame parameters, which can then later be filled with appointments.
     *
     * @param   array   $blocks   the daily structure of the grid
     * @param   array  &$headers  the column headers
     * @param   bool    $allDay   whether the grid consists of a single block lasting the whole day
     *
     * @return array[] the grid structure to fill with appointments
     */
    private function getGrid(array $blocks, array &$headers, bool $allDay): array
    {
        /** @var Model $model */
        $model      = $this->model;
        $conditions = $model->conditions;
        $holidays   = $model->holidays;
        $rawGrid    = $model->grid;

        $endDate   = $conditions['endDate'];
        $endDoW    = empty($rawGrid['endDay']) ? 6 : $rawGrid['endDay'];
        $startDate = $conditions['startDate'];
        $startDoW  = empty($rawGrid['startDay']) ? 1 : $rawGrid['startDay'];
        $grid      = [];

        for ($current = $startDate; $current <= $endDate;) {
            $currentDT = strtotime($current);
            $day       = date('w', $currentDT);

            $dayLabel = '';
            $dayType  = '';

            if (!empty($holidays[$current])) {
                $dayLabel = $holidays[$current]['name'];
                $dayType  = $holidays[$current]['type'];
            }

            if ($day >= $startDoW and $day <= $endDoW) {
                $day               = date('l', $currentDT);
                $headers[$current] = Text::_($day) . '<br>' . Dates::formatDate($current);

                foreach ($blocks as $block) {
                    $key = $block['key'];

                    if (!$allDay) {
                        $grid[$key]['times'] = $block['label'];
                        $grid[$key]['type']  = $block['type'];
                    }

                    $busy = Helpers\InstanceParticipants::isBusy($current, $block['startTime'], $block['endTime']);

                    $grid[$key]['endTime']   = $block['endTime'] === '00:00' ? '23:59' : $block['endTime'];
                    $grid[$key]['startTime'] = $block['startTime'];

                    // Create a container for instances to appear
                    $grid[$key][$current] = ['busy' => $busy, 'instances' => []];

                    if ($dayLabel) {
                        $grid[$key][$current]['label'] = $dayLabel;
                        $grid[$key][$current]['type']  = $dayType;
                    }
                }
            }

            $current = date('Y-m-d', strtotime('+1 Day', $currentDT));
        }


        return $grid;
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
        $name = '<span class="event">' . $item->name . '</span>';

        $title = '<span class="date">' . Helpers\Dates::formatDate($item->date) . '</span> ';
        $title .= '<span class="times">' . $item->startTime . ' - ' . $item->endTime . '</span><br>';
        $title .= Deprecated::_('link', $item->link, $name, ['target' => '_blank']);
        $title .= empty($item->method) ? '' : "<br><span class=\"method\">$item->method</span>";

        return $this->liGetTitle($item, $title);
    }

    /**
     * Creates output for individual instances and assigns them to the day/block coordinates in which they will be
     * displayed.
     *
     * @param   array  $grid  the grid used to structure the instances for display
     *
     * @return void
     */
    private function fillGrid(array &$grid): void
    {
        foreach ($this->items as $item) {
            $cClass  = 'grid-item';
            $context = "instance-$item->instanceID";
            $iClass  = 'fa fa-exclamation-triangle';
            $notice  = '';

            // If removed are here at all, the status holds relevance regardless of date
            if ($item->unitStatus === 'removed') {
                $cClass  .= ' removed';
                $icon    = HTML::icon($iClass . ' unit-removed');
                $message = Text::sprintf('UNIT_REMOVED_ON', Dates::formatDate($item->unitStatusDate));
                $notice  = HTML::tip($icon, "$context-delta-status", $message);
            }
            elseif ($item->instanceStatus === 'removed') {
                $cClass  .= ' removed';
                $icon    = HTML::icon($iClass . ' instance-removed');
                $message = Text::sprintf('INSTANCE_REMOVED_ON', Dates::formatDate($item->instanceStatusDate));
                $notice  = HTML::tip($icon, "$context-delta-status", $message);
            }
            elseif ($item->unitStatus === 'new' and $item->unitStatusDate >= $this->statusDate) {
                $cClass  .= ' new';
                $icon    = HTML::icon($iClass . ' unit-new');
                $message = Text::sprintf('UNIT_ADDED_ON', Dates::formatDate($item->instanceStatusDate));
                $notice  = HTML::tip($icon, "$context-delta-status", $message);
            }
            elseif ($item->instanceStatus === 'new' and $item->instanceStatusDate >= $this->statusDate) {
                $cClass  .= ' new';
                $icon    = HTML::icon($iClass . ' instance-new');
                $message = Text::sprintf('ORGANIZER_INSTANCE_ADDED_ON', Dates::formatDate($item->instanceStatusDate));
                $notice  = HTML::tip($icon, "$context-delta-status", $message);
            }

            $times = '<span class="times">' . $item->startTime . ' - ' . $item->endTime . '</span><br>';
            $title = $item->name;
            $key   = $title;
            Text::unpack($title);
            $title = '<span class="event">' . $title . '</span>';
            $title = Deprecated::_('link', $item->link, $title);

            if (empty($item->method)) {
                $method = '';
            }
            else {
                $method = "<br><span class=\"method\">$item->method</span>";
                $key    .= $item->method;
            }

            $key .= "$item->instanceID";

            $persons = '';

            if (empty($this->state->get('filter.personID')) and $item->persons) {
                $persons = '<br>' . $item->persons;
            }

            $groups = '';

            if (empty($this->state->get('filter.groupID')) and $item->groups) {
                $groups = '<br>' . $item->groups;
            }

            $rooms = '';

            if (empty($this->state->get('filter.roomID')) and $item->rooms) {
                $rooms = '<br>' . $item->rooms;
            }

            $chain = '';

            if ($item->courseID) {
                $chain = HTML::tip(HTML::icon('fa fa-link'), "$context-series", Text::_('INSTANCE_SERIES') . ": $item->courseID");
            }

            $tools = [];

            if (Helpers\Users::getID()) {
                $instanceID = $item->instanceID;

                if ($item->manageable) {
                    if ($item->presence !== Helper::ONLINE and !$item->premature) {
                        $icon = HTML::icon('fa fa-users');
                        $url  = '';

                        if ($item->bookingID) {
                            $url = Helpers\Routing::getViewURL('booking', $item->bookingID);
                        }
                        elseif ($item->registration and !$item->expired) {
                            $url = Helpers\Routing::getTaskURL('bookings.manage', $instanceID);
                        }

                        if ($url) {
                            $tools[] = HTML::tip($icon, "$context-manage", 'MANAGE_BOOKING', [], $url);
                        }
                    }
                }

                // Virtual and full appointments can still be added to the personal calendar
                if (!$item->taught and !$item->expired) {
                    if (!$item->running) {
                        if ($item->bookmarked) {
                            $label = 'REMOVE_BOOKMARK';
                            $icon  = HTML::icon('fa fa-bookmark');
                            $url   = Helpers\Routing::getTaskURL('InstanceParticipants.removeBookmarkBlock', $instanceID);
                        }
                        else {
                            $label = 'BOOKMARK';
                            $icon  = HTML::icon('far fa-bookmark');
                            $url   = Helpers\Routing::getTaskURL('InstanceParticipants.bookmarkBlock', $instanceID);
                        }

                        $tools[] = HTML::tip($icon, "$context-bookmark", $label, [], $url);
                    }

                    /*if ($item->presence !== Helper::ONLINE)
                    {
                        if ($item->running)
                        {
                            $tools[] = HTML::tip(HTML::icon('fa fa-stop'), "$context-instance-status", 'REGISTRATION_CLOSED');
                        }
                        elseif (Helper::getMethodCode($item->instanceID) === Helpers\Methods::FINALCODE)
                        {
                            $icon    = HTML::icon('fa fa-share');
                            $url     = "https://ecampus.thm.de";
                            $tools[] = HTML::tip($icon, "$context-instance-status", 'REGISTRATION_EXTERNAL_TIP', [], $url);
                        }
                        elseif ($item->premature)
                        {
                            $icon    = HTML::icon('fa fa-unlock');
                            $tip     = Text::sprintf('REGISTRATION_BEGINS_ON', $item->registrationStart);
                            $tools[] = HTML::tip($icon, "$context-instance-status", $tip, [], $url)
                        }
                        elseif ($item->full)
                        {
                            $tools[] = HTML::tip(HTML::icon('fa fa-pause'), "$context-instance-status", 'INSTANCE_FULL');
                        }
                        elseif ($item->registered)
                        {
                            $icon    = HTML::icon('fa fa-sign-in-alt');
                            $url     = Helpers\Routing::getTaskURL('InstanceParticipants.deregister', $instanceID);
                            $tools[] = HTML::tip($icon, "$context-instance-status", 'REGISTERED_DEREGISTER', [], $url);
                        }
                        else
                        {
                            $icon    = HTML::icon('fa fa-play');
                            $url     = Helpers\Routing::getTaskURL('InstanceParticipants.register', $instanceID);
                            $tools[] = HTML::tip($icon, "$context-instance-status", 'REGISTER', [], $url);
                        }
                    }*/
                }
            }

            if ($item->subjectID) {
                $icon    = HTML::icon('fa fa-book');
                $tip     = 'READ_SUBJECT_DOCUMENTATION';
                $url     = Helpers\Routing::getViewURL('SubjectItem', $item->subjectID);
                $tools[] = HTML::tip($icon, "$context-instance-documentation", $tip, [], $url);
            }

            $tools[] = HTML::tip(HTML::icon('fa fa-info-circle'), "$context-instance-item", 'ITEM_VIEW', [], $item->link);

            $comment = $this->resolveLinks($item->comment, $tools);
            $comment = empty(trim($comment)) ? '' : "<br><span class=\"comment\">$comment</span>";

            foreach ($grid as &$items) {
                // Date is assumed to exist
                if ($items['startTime'] >= $item->endTime or $items['endTime'] <= $item->startTime) {
                    continue;
                }

                $blockKey = str_replace(':', '', "$item->startTime$item->endTime");
                $iKey     = $blockKey . $key;

                $entry = $cClass ? "<div class=\"$cClass\"><div class=\"notice\">$notice</div>" : '<div>';
                $entry .= ($items['startTime'] !== $item->startTime or $item->endTime !== $items['endTime']) ? $times : '';
                $entry .= $title . $method . $persons . $groups . $rooms . $comment . $chain;
                $entry .= "<div class=\"grid-tools\">" . implode(' ', $tools) . '</div>';
                $entry .= '</div>';

                $items[$item->date]['instances'][$iKey] = $entry;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function initializeColumns(): void
    {
        $this->headers = [
            'tools'   => '',
            'title'   => ['attributes' => ['class' => 'title-column'], 'value' => Text::_('ORGANIZER_INSTANCE')],
            'status'  => Text::_('ORGANIZER_STATUS'),
            'persons' => Text::_('ORGANIZER_PERSONS'),
            'groups'  => Text::_('ORGANIZER_GROUPS'),
            'rooms'   => Text::_('ORGANIZER_ROOMS')
        ];

        if (Helpers\Users::getID() and !Application::mobile()) {
            $this->headers['tools'] = Deprecated::_('grid.checkall');
        }
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        /** @var Model $model */
        $model = $this->model;
        $state = $model->getState();
        $url   = '';

        $fields = [
            'campusID'       => $state->get('filter.campusID', 0),
            'categoryID'     => $state->get('filter.categoryID', 0),
            'eventID'        => $state->get('filter.eventID', 0),
            'groupID'        => $state->get('filter.groupID', 0),
            'methodID'       => $state->get('filter.methodID', 0),
            'my'             => $state->get('list.my', 0),
            'organizationID' => $state->get('filter.organizationID', 0),
            'personID'       => $state->get('filter.personID', 0),
            'roomID'         => $state->get('filter.roomID', 0)
        ];

        $params = Input::getParams();

        foreach ($fields as $field => $value) {
            if (empty($value)) {
                unset($fields[$field]);
            }
        }

        foreach (['my' => 'my', 'methodIDs' => 'methodID'] as $param => $field) {
            if ($value = $params->get($param)) {
                $fields[$field] = $value;
            }
        }

        if ($fields) {
            $authRequired = (!empty($fields['my']) or !empty($fields['personID']));

            if (!$username = Helpers\Users::getUserName() and $authRequired) {
                Application::error(401);

                return;
            }

            $url = Uri::base() . '?option=com_organizer&view=instances&format=ics';

            // Resource links
            if (empty($fields['my'])) {
                foreach ($fields as $field => $value) {
                    $value = is_array($value) ? implode(',', $value) : $value;
                    $url   .= "&$field=$value";
                }
            } // 'My' link
            else {
                $url .= "&my=1";
            }

            if ($authRequired) {
                $url .= "&username=$username&auth=" . Helpers\Users::getAuth();
            }
        }

        $variables = ['ICS_URL' => $url];

        Text::useLocalization('ORGANIZER_GENERATE_LINK');
        Document::addScriptOptions('variables', $variables);
        Document::addScript(Uri::root() . 'components/com_organizer/js/ics.js');
        Document::addScript(Uri::root() . 'components/com_organizer/js/jump.js');

        if ($model->layout === Helper::GRID) {
            Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/grid.css');
        }
    }

    /**
     * Structures the instances to be presented in a weekly/daily plan (grid).
     * @return void
     */
    private function structureGrid(): void
    {
        /** @var Model $model */
        $model = $this->model;
        $this->filterForm->setValue('gridID', 'list', $model->gridID);

        $allDay  = false;
        $rawGrid = $model->grid;
        $blocks  = $this->getBlocks($rawGrid['periods'], $allDay);

        $headers = $allDay ? [] : ['times' => Text::_('ORGANIZER_TIMES')];
        $grid    = $this->getGrid($blocks, $headers, $allDay);

        $this->fillGrid($grid);

        foreach ($grid as $key => $dates) {
            foreach ($dates as $date => $instances) {
                if (in_array($date, ['endTime', 'startTime', 'times', 'type'])) {
                    continue;
                }

                ksort($instances['instances']);
                $grid[$key][$date]['instances'] = implode('', $instances['instances']);
            }
        }

        $this->headers = $headers;
        $this->items   = $grid;
    }

    /**
     * Structures the instances to be presented in a list/html table.
     * @return void
     */
    private function structureList(): void
    {
        $index     = 0;
        $listItems = [];

        foreach ($this->items as $item) {
            $listItems[$index] = [];

            if (!$item->expired) {
                $this->expired = false;

                if ($item->bookmarked) {
                    $listItems[$index]['attributes'] = ['class' => 'bookmarked'];
                }
            }

            if (!$item->premature) {
                $this->premature = false;
            }

            if (Helper::getMethodCode($item->instanceID) !== Helpers\Methods::FINALCODE and $item->registration === true) {
                $this->registration = true;
            }

            $listItems[$index]['tools']  = $this->getToolsColumn($item, $index);
            $listItems[$index]['title']  = $this->getTitle($item);
            $listItems[$index]['status'] = $this->getStatus($item);
            $this->addResources($listItems[$index], $item);

            $index++;
        }

        $this->items = $listItems;
    }
}
