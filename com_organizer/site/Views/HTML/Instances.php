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

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\View\ListView as Core;
use THM\Organizer\Adapters\{Application, Document, HTML, Input, Text, Toolbar, User};
use THM\Organizer\Buttons\{FormTarget, Highlander};
use THM\Organizer\Helpers\{Dates, Instances as Helper, Organizations, Participation, Routing, Methods};
use THM\Organizer\Layouts\HTML\Row;
use THM\Organizer\Models\Instances as Model;
use stdClass;

/** @inheritDoc */
class Instances extends ListView
{
    use ListsInstances;

    protected Model $model;

    /**
     * @inheritDoc
     */
    protected function initializeView(): void
    {
        Core::initializeView();

        /** @var Model $model */
        $model = $this->getModel();

        $this->empty      = '';
        $this->model      = $model;
        $this->statusDate = date('Y-m-d 00:00:00', strtotime('-14 days'));

        $this->subTitle();
        $this->initializeColumns();
        $this->completeItems();
        $this->modifyDocument();
    }


    private bool $expired = true;

    public bool $noInstances = true;

    private bool $premature = true;

    /**
     * Whether the registration is allowed for any instance.
     * @var bool
     */
    private bool $registration = false;

    private string $statusDate;


    /** @inheritDoc */
    protected function addToolBar(bool $delete = true): void
    {
        $this->toDo[] = 'Access from model.';
        $this->toDo[] = 'Item URL from model.';
        $this->toDo[] = 'Revisit tools.';
        $this->toDo[] = 'Add the module number to the XLS output.';
        $this->toDo[] = 'Title in view from model conditions.';

        $model = $this->model;

        $this->title($model->title());
        $toolbar = Toolbar::getInstance();
        $expURL  = Routing::getViewURL('export');

        if (User::id() and $model->layout === Helper::LIST) {
            if (!$this->expired and !$this->teachesALL) {
                $bookmarkDD = $toolbar->dropdownButton('bookmark-dd', Text::_('INSTANCES'));
                $bookmarkDD->toggleSplit(false)->buttonClass('btn btn-action')->icon('fa fa-ellipsis-h')->listCheck(true);
                $bookmarkCB = $bookmarkDD->getChildToolbar();
                $bookmarkCB->standardButton('bookmark', Text::_('BOOKMARK'), 'InstanceParticipants.bookmark')
                    ->icon('fas fa-bookmark')->listCheck(true);
                $bookmarkCB->standardButton('unbookmark', Text::_('REMOVE_BOOKMARK'), 'InstanceParticipants.removeBookmark')
                    ->icon('far fa-bookmark')->listCheck(true);
            }

            /*if ($this->registration and !$this->premature and !$this->teachesALL) {
                $registrationDD = $toolbar->dropdownButton('registration', Text::_('PRESENCE_PARTICIPATION'));
                $registrationDD->toggleSplit(false)->buttonClass('btn btn-action')->icon('fa fa-ellipsis-h')->listCheck(true);
                $registrationCB = $registrationDD->getChildToolbar();
                $registrationCB->standardButton('register', Text::_('REGISTER'), 'InstanceParticipants.register')
                    ->icon('fa fa-sign-in-alt')->listCheck(true);
                $registrationCB->standardButton('deregister', Text::_('DEREGISTER'), 'InstanceParticipants.deregister')
                    ->icon('fa fa-sign-out-alt')->listCheck(true);
            }*/

            if (($this->manages or $this->teachesOne) and !$this->premature) {
                $manage = new Highlander('manage', Text::_('MANAGE_BOOKING'));
                $manage->icon('fa fa-users')->listCheck(true)->task('Booking.manage');
                $toolbar->appendButton($manage);
            }
        }

        $interval = match ((string) $this->state->get('list.interval')) {
            'half' => Text::_('HALF_YEAR'),
            'month' => Text::_('SELECTED_MONTH'),
            'quarter' => Text::_('QUARTER'),
            'term' => Text::_('SELECTED_TERM'),
            'week' => Text::_('SELECTED_WEEK'),
            default => Text::_('SELECTED_DAY'),
        };

        $keyMap = [
            'ICS_URL'  => Text::_('ICS_URL'),
            'PDF_A3'   => Text::sprintf('PDF_A3', $interval),
            'PDF_A4'   => Text::sprintf('PDF_A4', $interval),
            'XLS_LIST' => Text::sprintf('XLS_LIST', $interval),
        ];

        asort($keyMap);

        $exportDD = $toolbar->dropdownButton('export', Text::_('INSTANCES'));
        $exportDD->toggleSplit(false)->buttonClass('btn btn-action')->icon('fa fa-download')->listCheck(true);
        $exportCB = $exportDD->getChildToolbar();

        foreach ($keyMap as $key => $text) {
            switch ($key) {
                case 'ICS_URL':
                    $exportCB->standardButton('subscription', $text)->icon('fa fa-calendar-check')->onclick('makeLink()');
                    break;
                case 'PDF_A3':
                    $button = new FormTarget('pdfGridA3', $text);
                    $button->icon('fa fa-file-pdf')->task('Instances.gridA3');
                    $exportCB->appendButton($button);
                    break;
                case 'PDF_A4':
                    $button = new FormTarget('pdfGridA4', $text);
                    $button->icon('fa fa-file-pdf')->task('Instances.gridA4');
                    $exportCB->appendButton($button);
                    break;
                case 'XLS_LIST':
                    $button = new FormTarget('xls', $text);
                    $button->icon('fa fa-file-excel')->task('Instances.xls');
                    $exportCB->appendButton($button);
                    break;
            }
        }

        $exportCB->linkButton('export', Text::_('ADVANCED_EXPORT'))->url($expURL)->target('_blank')->icon('fa fa-sliders-h');
    }

    /** @inheritDoc */
    protected function authorize(): void
    {
        if (Application::backend()) {
            if (!$this->manages = (bool) Organizations::schedulableIDs()) {
                Application::error(403);
            }

            return;
        }

        if (Input::getBool('my') and !User::id()) {
            Application::error(401);
        }

        $organizationID = Input::getParams()->get('organizationID', 0);
        $this->manages  = $organizationID ? Organizations::manageable($organizationID) : (bool) Organizations::manageableIDs();
    }

    /** @inheritDoc */
    protected function completeItems(array $options = []): void
    {
        if ($this->items) {
            $this->noInstances = false;
            parent::completeItems($options);
        }

        /** @var Model $model */
        $model = $this->getModel();
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

    /** @inheritDoc */
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
        $tag    = Application::tag();

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
        $model      = $this->getModel();
        $conditions = $model->conditions;
        $rawGrid    = $model->grid;
        $holidays   = $model->holidays;

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

                    $busy = Participation::busy($current, $block['startTime'], $block['endTime']);

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

        $title = '<span class="date">' . Dates::formatDate($item->date) . '</span> ';
        $title .= '<span class="times">' . $item->startTime . ' - ' . $item->endTime . '</span><br>';
        $title .= HTML::link($item->link, $name, ['target' => '_blank']);
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
            $title = HTML::link($item->link, $title);

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

            if (User::id()) {
                $instanceID = $item->instanceID;

                if ($item->manageable) {
                    if ($item->presence !== Helper::ONLINE and !$item->premature) {
                        $icon = HTML::icon('fa fa-users');
                        $url  = '';

                        if ($item->bookingID) {
                            $url = Routing::getViewURL('booking', $item->bookingID);
                        }
                        elseif ($item->registration and !$item->expired) {
                            $url = Routing::getTaskURL('bookings.manage', $instanceID);
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
                            $url   = Routing::getTaskURL('InstanceParticipants.removeBookmarkBlock', $instanceID);
                        }
                        else {
                            $label = 'BOOKMARK';
                            $icon  = HTML::icon('far fa-bookmark');
                            $url   = Routing::getTaskURL('InstanceParticipants.bookmarkBlock', $instanceID);
                        }

                        $tools[] = HTML::tip($icon, "$context-bookmark", $label, [], $url);
                    }

                    /*if ($item->presence !== Helper::ONLINE)
                    {
                        if ($item->running)
                        {
                            $tools[] = HTML::tip(HTML::icon('fa fa-stop'), "$context-instance-status", 'REGISTRATION_CLOSED');
                        }
                        elseif (Helper::getMethodCode($item->instanceID) === Methods::FINALCODE)
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
                            $url     = Routing::getTaskURL('InstanceParticipants.deregister', $instanceID);
                            $tools[] = HTML::tip($icon, "$context-instance-status", 'REGISTERED_DEREGISTER', [], $url);
                        }
                        else
                        {
                            $icon    = HTML::icon('fa fa-play');
                            $url     = Routing::getTaskURL('InstanceParticipants.register', $instanceID);
                            $tools[] = HTML::tip($icon, "$context-instance-status", 'REGISTER', [], $url);
                        }
                    }*/
                }
            }

            if ($item->subjectID) {
                $icon    = HTML::icon('fa fa-book');
                $tip     = 'READ_SUBJECT_DOCUMENTATION';
                $url     = Routing::getViewURL('SubjectItem', $item->subjectID);
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
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $this->headers = [
            'check'   => [
                'properties' => ['class' => 'w-1 d-md-table-cell', 'scope' => 'col'],
                'title'      => '',
                'type'       => 'text'
            ],
            'name'    => [
                'link'       => Application::backend() ? Row::DIRECT : Row::TAB,
                'properties' => ['class' => 'w-20 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('INSTANCE'),
                'type'       => 'text'
            ],
            'status'  => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('STATUS'),
                'type'       => 'text'
            ],
            'persons' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('PERSONS'),
                'type'       => 'text'
            ],
            'groups'  => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('GROUPS'),
                'type'       => 'text'
            ],
            'rooms'   => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ROOMS'),
                'type'       => 'text'
            ],
        ];

        if (User::id() and !Application::mobile()) {
            $this->headers['check'] = ['type' => 'check'];
        }
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        /** @var Model $model */
        $model = $this->getModel();
        $state = $this->state;
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

            if (!$username = User::userName() and $authRequired) {
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
                $url .= "&username=$username&auth=" . User::token();
            }
        }

        $variables = ['ICS_URL' => $url];

        Text::useLocalization('ORGANIZER_GENERATE_LINK');
        Document::scriptLocalizations('variables', $variables);

        Document::script('ics');
        Document::script('jump');

        if ($model->layout === Helper::GRID) {
            Document::style('grid');
        }
    }

    /**
     * @inheritDoc
     */
    protected function subTitle(): void
    {
        if ($interval = $this->state->get('list.interval') and $interval === 'quarter') {
            $date           = $this->state->get('list.date');
            $interval       = Dates::ninetyDays($date);
            $interval       = Dates::intervalText($interval['startDate'], $interval['endDate']);
            $this->subtitle = "<h6 class=\"sub-title\">$interval</h6>";
        }
    }

    /**
     * Adds supplemental information to the display output.
     * @return void modifies the object property supplement
     */
    protected function setSupplement(): void
    {
        if ($this->noInstances) {
            $supplement = '<div class="tbox-yellow">';

            /** @var Model $model */
            $model = $this->getModel();
            if (!$model->noDate and $dates = Helper::jumpDates($model->conditions)) {
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
                if (User::id()) {
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
     * Structures the instances to be presented in a weekly/daily plan (grid).
     * @return void
     */
    private function structureGrid(): void
    {
        /** @var Model $model */
        $model = $this->getModel();
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
        $index = 0;
        foreach ($this->items as $item) {

            if (!$item->expired) {
                $this->expired = false;

                if ($item->bookmarked) {
                    $item->attributes = ['class' => 'bookmarked'];
                }
            }

            if (!$item->premature) {
                $this->premature = false;
            }

            if (Helper::methodCode($item->instanceID) !== Methods::FINALCODE and $item->registration === true) {
                $this->registration = true;
            }

            $item->check  = $this->getToolsColumn($item, $index);
            $item->name   = $this->getTitle($item);
            $item->status = $this->getStatus($item);

            $index++;
        }
    }
}
