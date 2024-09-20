<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\PDF\Instances;

use stdClass;
use THM\Organizer\Adapters\{Application, Text};
use THM\Organizer\Helpers;
use THM\Organizer\Layouts\{Exported, PDF\BaseLayout};
use THM\Organizer\Models\Instances as iModel;
use THM\Organizer\Tables\Roles;
use THM\Organizer\Views\PDF\Instances as iView;

/**
 * Base class for a Joomla View
 * Class holding methods for displaying presentation data.
 */
abstract class GridLayout extends BaseLayout
{
    use Exported;

    protected const TIME_HEIGHT = 15, TIME_WIDTH = 11;

    protected const CORNER_BORDER = [
        'R' => ['width' => '.5', 'color' => [74, 92, 102]],
        'B' => ['width' => '.5', 'color' => [74, 92, 102]]
    ];

    protected const INSTANCE_BORDER = [
        'R' => ['width' => '.5', 'color' => [74, 92, 102]],
        'B' => ['width' => '.5', 'color' => [210, 210, 210]]
    ];

    protected const TIME_BORDER = [
        'R' => ['width' => '.5', 'color' => [74, 92, 102]],
        'B' => ['width' => '.5', 'color' => [255, 255, 255]]
    ];

    protected bool $bookmark = false;

    protected int $currentGroupID = 0;

    /**
     * The time grid.
     * @var array
     */
    protected array $grid;

    /**
     * The planned instances.
     * @var stdClass[]
     */
    protected array $instances;

    /**
     * The text pertaining to the selected resources.
     * @var string
     */
    private string $resourceHeader;

    /** @inheritDoc */
    public function __construct(iView $view)
    {
        parent::__construct($view);

        // 10 bottom m because of the footer
        $view->margins(5, 25, 5, 10);
        $view->setCellPaddings('', 1, '', 1);
        $view->setPageOrientation($view::LANDSCAPE);

        // This allows new header data per page.
        $view->setHeaderTemplateAutoreset();
    }

    /**
     * Aggregates resource collections of the same type and creates an output text.
     *
     * @param   array   $resources  the resources to be aggregated
     * @param   string  $key        the name key for the resource type
     * @param   bool    $showCode   whether the resource code should be displayed in lieu of the name
     *
     * @return string
     */
    private function aggregate(array $resources, string $key, bool $showCode = false): string
    {
        $aggregate = [];

        foreach ($resources as $set) {
            $aggregate = array_merge($aggregate, $set);
        }

        return $this->resourceText($aggregate, $key, $showCode) . '<br>';
    }

    /**
     * Filters the instances to those planned in the block being iterated.
     *
     * @param   string  $date       the block date
     * @param   string  $startTime  the block start time
     * @param   string  $endTime    the block end time
     *
     * @return stdClass[] the relevant instances
     */
    protected function blockInstances(string $date, string $startTime, string $endTime): array
    {
        $instances = [];

        foreach ($this->instances as $instance) {
            if ($instance->date !== $date) {
                continue;
            }

            if ($instance->endTime <= $startTime) {
                continue;
            }

            if ($instance->startTime > $endTime) {
                continue;
            }

            $name              = $instance->name;
            $method            = $instance->methodCode ?: 'ZZZ';
            $instanceID        = $instance->instanceID;
            $index             = "$name-$method-$instanceID";
            $instances[$index] = $instance;
        }

        ksort($instances);

        return $instances;
    }

    /**
     * Generates the grid body.
     *
     * @param   string  $monDate    the page start date
     * @param   string  $saturDate  the page end date
     *
     * @return void
     */
    protected function body(string $monDate, string $saturDate): void
    {
        foreach (array_reverse(array_keys($this->grid)) as $endKey) {
            if (empty($this->grid[$endKey]['cells'])) {
                unset($this->grid[$endKey]);
                continue;
            }

            break;
        }

        foreach ($this->grid as $block) {
            $label = $this->label($block);

            if (!empty($block['cells'])) {
                $this->row($label, $block['cells'], $monDate, $saturDate);
                continue;
            }

            $this->emptyRow($label, $monDate, $saturDate);
        }
    }

    /**
     * Gets the text to be displayed in the row cells
     *
     * @param   string  $startDate  the page start date
     * @param   string  $endDate    the page end date
     * @param   array   $block      the block being iterated
     *
     * @return array
     */
    protected function cells(string $startDate, string $endDate, array $block): array
    {
        $cells = [];

        for ($currentDT = strtotime($startDate); $currentDT <= strtotime($endDate);) {
            $date      = date('Y-m-d', $currentDT);
            $endTime   = date('H:i', strtotime($block['endTime']));
            $row       = 0;
            $startTime = date('H:i', strtotime($block['startTime']));

            foreach ($this->blockInstances($date, $startTime, $endTime) as $instance) {
                if (!$this->relevant($instance)) {
                    continue;
                }

                if (empty($cells[$row])) {
                    $cells[$row] = ['height' => []];
                }

                $contents = $this->instance($instance, $startTime, $endTime);

                $cells[$row][$date]      = $contents;
                $cells[$row]['height'][] = $this->cellHeight($this::DATA_WIDTH, $contents);

                $row++;
            }

            $currentDT = strtotime("+1 day", $currentDT);
        }

        foreach ($cells as $row => $instances) {
            $subRowHeight          = max($instances['height']);
            $cells[$row]['height'] = $subRowHeight;
            $cells['height']       = array_key_exists('height', $cells) ?
                $cells['height'] + $subRowHeight : $subRowHeight;
        }

        return $cells;
    }

    /**
     * Measures a cell's rendered height.
     *
     * @param   float   $width     the width of the column to be rendered
     * @param   string  $contents  the contents to be rendered
     *
     * @return float
     */
    private function cellHeight(float $width, string $contents): float
    {
        $view = $this->view;
        $view->AddPage();
        $start = $view->GetY();
        $view->writeHTMLCell($width, 15, $view->GetX(), $start, $contents, self::INSTANCE_BORDER, 1);
        $height = $view->GetY() - $start;
        $view->deletePage($view->getPage());

        return $height;
    }

    /**
     * Adds an empty page to the document.
     *
     * @param   string  $startDate  the page start date
     * @param   string  $endDate    the page end date
     *
     * @return void
     */
    protected function emptyPage(string $startDate, string $endDate): void
    {
        $this->layoutPage($startDate, $endDate);
        $endDate   = Helpers\Dates::formatDate($endDate);
        $startDate = Helpers\Dates::formatDate($startDate);
        $this->view->Cell('', '', Text::sprintf('ORGANIZER_NO_INSTANCES', $startDate, $endDate));
    }

    /**
     * Adds a page to the document.
     *
     * @param   string  $startDate  the page start date
     * @param   string  $saturDate  the page end date
     *
     * @return void
     */
    protected function gridPage(string $startDate, string $saturDate): void
    {
        $this->layoutPage($startDate, $saturDate);
        $this->headers($startDate, $saturDate);
    }

    /**
     * Adds a page to the document.
     *
     * @param   string  $startDate  the page start date
     * @param   string  $endDate    the page end date
     *
     * @return void
     */
    private function layoutPage(string $startDate, string $endDate): void
    {
        $subTitle = $this->resourceHeader ? $this->resourceHeader . "\n" : '';

        $fEndDate   = Helpers\Dates::formatDate($endDate);
        $fStartDate = Helpers\Dates::formatDate($startDate);
        $subTitle   .= "$fStartDate - $fEndDate";

        /** @var iView $view */
        $view = $this->view;

        $view->setHeaderData('pdf_logo.png', '55', $view->title, $subTitle, $view::BLACK, $view::WHITE);
        $view->AddPage();

        if ($this->bookmark) {
            $view->Bookmark($this->resourceHeader, 0, -10);
            $this->bookmark = false;
        }
    }

    /** @inheritDoc */
    public function fill(array $data): void
    {
        /** @var iView $view */
        $view = $this->view;

        $endDate   = $view->conditions['endDate'];
        $startDate = $view->conditions['startDate'];

        if (!$data) {
            $this->emptyPage($startDate, $endDate);

            return;
        }

        $this->instances = $data;

        $conditions = $view->conditions;
        $this->setFlags($conditions);

        $monDate = $startDate;

        $atomic      = (!empty($conditions['my']) or !empty($conditions['groupIDs']) or !empty($conditions['personIDs']) or !empty($conditions['roomIDs']));
        $noAggregate = (empty($conditions['organizationIDs']) and empty($conditions['categoryIDs']));

        if (empty($conditions['separate']) or $atomic or $noAggregate) {
            $this->resourceHeader();
            $this->pages($monDate, $endDate);
        }
        else {
            $groups = $this->scrapeGroups();

            uasort($groups, function ($groupOne, $groupTwo) {
                return strcmp($groupOne['fullName'], $groupTwo['fullName']);
            });

            foreach ($groups as $groupID => $group) {
                $this->resourceHeader($group['fullName']);
                $this->bookmark       = true;
                $this->currentGroupID = $groupID;
                $this->pages($monDate, $endDate);
            }
        }
    }

    /**
     * Renders a row in which no instances are planned.
     *
     * @param   string  $label      the label for the row
     * @param   string  $monDate    the page start date
     * @param   string  $saturDate  the page end date
     *
     * @return void
     */
    protected function emptyRow(string $label, string $monDate, string $saturDate): void
    {
        $view = $this->view;

        $xPos = $view->GetX();
        $yPos = $view->GetY();

        $this->pageBreak(self::TIME_HEIGHT, $monDate, $saturDate);
        $view->writeHTMLCell(self::TIME_WIDTH, self::TIME_HEIGHT, $xPos, $yPos, $label, self::CORNER_BORDER);
        $xPos += $this::TIME_WIDTH;

        for ($currentDT = strtotime($monDate); $currentDT <= strtotime($saturDate);) {
            $view->writeHTMLCell($this::DATA_WIDTH, self::TIME_HEIGHT, $xPos, $yPos, '', self::CORNER_BORDER);
            $xPos += $this::DATA_WIDTH;

            $currentDT = strtotime("+1 day", $currentDT);
        }

        $view->Ln();
    }

    /**
     * Renders the grid headers.
     *
     * @param   string  $startDate  the page start date
     * @param   string  $endDate    the page end date
     *
     * @return void
     */
    protected function headers(string $startDate, string $endDate): void
    {
        $view = $this->view;
        $view->SetFont('helvetica', '', 10);
        $view->SetLineStyle(['width' => 0.5, 'dash' => 0, 'color' => [74, 92, 102]]);
        $view->renderMultiCell(
            self::TIME_WIDTH,
            0,
            Text::_('ORGANIZER_TIME'),
            $view::CENTER,
            $view::HORIZONTAL
        );

        for ($currentDT = strtotime($startDate); $currentDT <= strtotime($endDate);) {
            $view->renderMultiCell(
                $this::DATA_WIDTH,
                0,
                Helpers\Dates::formatDate(date('Y-m-d', $currentDT)),
                $view::CENTER,
                $view::HORIZONTAL
            );

            $currentDT = strtotime("+1 day", $currentDT);
        }

        $view->Ln();
        $view->SetFont('helvetica', '', $this::FONT_SIZE);
    }

    /**
     * Implodes resource names to a comma and ampersand seperated string with a soft wrap at $this::BREAK characters
     * as necessary.
     *
     * @param   array  $resources  the resources to be aggregated
     *
     * @return string
     */
    private function implode(array $resources): string
    {
        asort($resources);
        $lastResource = array_pop($resources);

        if ($resources) {
            $index  = 0;
            $length = 0;
            $texts  = [];

            foreach ($resources as $resource) {
                if (empty($texts[$index])) {
                    $texts[$index] = $resource;
                }
                else {
                    $probe = $texts[$index] . ", $resource";

                    if (strlen($probe) > $this::LINE_LENGTH) {
                        $texts[$index] .= ",<br>";
                        $index++;
                        $texts[$index] = $resource;
                    }
                    else {
                        $texts[$index] = $probe;
                    }
                }

                $length = strlen($texts[$index]);
            }

            $text = implode('', $texts);
            $text .= ($length + strlen($lastResource)) > $this::LINE_LENGTH ? '<br>' : ' ';
            $text .= "& $lastResource";

            return $text;
        }

        return $lastResource ?: '';
    }

    /**
     * Generates the HTML to be output for the instance.
     *
     * @param   stdClass  $instance   the instance information
     * @param   string    $startTime  the block start time
     * @param   string    $endTime    the block end time
     *
     * @return string
     */
    protected function instance(stdClass $instance, string $startTime, string $endTime): string
    {
        /** @var iView $view */
        $view = $this->view;

        $conditions = $view->conditions;

        $html = '<div style="font-size: 10px; text-align: center">';

        $showOrganization = ($this->showOrganizations and !in_array($instance->organizationID, $conditions['organizationIDs']));

        if ($showOrganization) {
            $html .= "<span style=\"font-style: italic\">$instance->organization</span>";
        }

        // Understood end time the exclusive minute at which the instance has ended
        $iEndTime   = $instance->endTime;
        $altEndTime = date('H:i', strtotime('+1 minute', strtotime($endTime)));

        // Instance time not coincidental with block
        if ($instance->startTime != $startTime or ($iEndTime != $endTime and $iEndTime != $altEndTime)) {
            $html           .= $showOrganization ? '<br>' : '';
            $formattedStart = Helpers\Dates::formatTime($instance->startTime);
            $formattedEnd   = Helpers\Dates::formatTime($iEndTime);

            $html .= "<span style=\"font-style: italic\">$formattedStart - $formattedEnd</span>";
        }

        $html .= '<br>';

        $name = $instance->name;

        if ($instance->subjectNo) {
            $name .= " ($instance->subjectNo)";
        }

        $html .= "<span style=\"font-size: 10pt;\">$name</span><br>";

        if ($this->showMethods and $instance->methodID) {
            $html .= "<span>$instance->method</span><br>";
        }

        if ($instance->comment) {
            $comment = $this->processLinks($instance->comment);
            $html    .= "<span style=\"font-style: italic\">$comment</span><br>";
        }

        $my = !empty($conditions['my']);

        // If groups/rooms were restricted their output is redundant.
        $showGroups = (!$my and empty($conditions['groupIDs']) and !$this->currentGroupID);

        // Aggregation containers
        $groups      = [];
        $persons     = (array) $instance->resources;
        $personTexts = [];
        $rooms       = [];

        foreach ($persons as $personID => $person) {
            $irrelevant = (!empty($conditions['personIDs']) and !in_array($personID, $conditions['personIDs']));

            if ($this->currentGroupID) {
                $irrelevant = ($irrelevant or empty($person['groups']) or empty($person['groups'][$this->currentGroupID]));
            }

            // No delta display in pdf
            if ($person['status'] === 'removed' or $irrelevant) {
                unset($persons[$personID]);
                continue;
            }

            if (!$this->showPersons and !in_array($personID, $conditions['personIDs'])) {
                unset($persons[$personID]);
                continue;
            }

            if (!array_key_exists($person['roleID'], $personTexts)) {
                $personTexts[$person['roleID']] = [];
            }

            $personTexts[$person['roleID']][$personID] = $person['person'];

            if (array_key_exists('groups', $person)) {
                // The group status creates false negatives using in_array
                $filteredGroups = [];
                foreach ($person['groups'] as $groupID => $group) {
                    // No delta display in pdf
                    if ($group['status'] === 'removed') {
                        unset($persons[$personID]['groups'][$groupID]);
                        continue;
                    }

                    unset($group['status'], $group['statusDate']);
                    $filteredGroups[$groupID] = $group;
                }

                if (!in_array($filteredGroups, $groups)) {
                    $groups[] = $filteredGroups;
                }
            }

            if (array_key_exists('rooms', $person) and !in_array($person['rooms'], $rooms)) {
                // The room status creates false negatives using in_array
                $filteredRooms = [];
                foreach ($person['rooms'] as $roomID => $room) {
                    // No delta display in pdf
                    if ($room['status'] === 'removed') {
                        unset($persons[$personID]['rooms'][$roomID]);
                        continue;
                    }

                    unset($room['status'], $room['statusDate']);
                    $filteredRooms[$roomID] = $room;
                }

                if (!in_array($filteredRooms, $rooms)) {
                    $rooms[] = $filteredRooms;
                }
            }

            if ($this->currentGroupID) {
                unset($instance->resources[$personID]['groups'][$this->currentGroupID]);

                if (empty($instance->resources[$personID]['groups'])) {
                    unset($instance->resources[$personID]);
                }
            }
        }

        // The role ids are in order of relative importance
        ksort($personTexts);

        $groupCount = count($groups);
        $roleCount  = count($personTexts);
        $roomCount  = count($rooms);

        // Status: share and share alike, all persons are assigned the same groups and rooms or none
        if ($groupCount < 2 and $roomCount < 2) {
            if ($this->showPersons) {
                if ($roleCount === 1) {
                    $html .= $this->resourceText($persons, 'person') . '<br>';
                }
                else {
                    foreach ($personTexts as $roleID => $rolePersons) {
                        asort($rolePersons);
                        $html .= $this->roleText($roleID, $rolePersons);
                        $html .= $this->personalTexts($rolePersons, $persons, false, false);
                    }
                }
            }

            if ($groups and $showGroups) {
                $html .= $this->resourceText($groups[0], 'group', $this->showGroupCodes) . '<br>';
            }

            if ($rooms and $this->showRooms) {
                $html .= $this->resourceText($rooms[0], 'room') . '<br>';
            }
        } // Status: laser focused, all persons are assigned the same groups or none, rooms may vary between persons
        elseif ($groupCount < 2) {
            // Assumption specific room assignments => small number per person => no need to further process rooms per line
            if ($groups and $showGroups) {
                $html .= $this->resourceText($groups[0], 'group', $this->showGroupCodes) . '<br>';
            }

            if ($this->showPersons) {
                if ($roleCount === 1) {
                    $rolePersons = array_shift($personTexts);
                    asort($rolePersons);
                    $html .= $this->personalTexts($rolePersons, $persons, false, $this->showRooms);
                }
                else {
                    foreach ($personTexts as $roleID => $rolePersons) {
                        asort($rolePersons);
                        $html .= $this->roleText($roleID, $rolePersons);
                        $html .= $this->personalTexts($rolePersons, $persons, false, $this->showRooms);
                    }
                }
            }
            elseif ($rooms and $this->showRooms) {
                $html .= $this->aggregate($rooms, 'room');
            }
        } // Status: claustrophobic, all persons are assigned the same rooms or none, groups may vary between persons
        elseif ($roomCount < 2) {
            if ($this->showPersons) {
                if ($roleCount === 1) {
                    $rolePersons = array_shift($personTexts);
                    asort($rolePersons);
                    $html .= $this->personalTexts($rolePersons, $persons, $showGroups, false);
                }
                else {
                    foreach ($personTexts as $roleID => $rolePersons) {
                        asort($rolePersons);
                        $html .= $this->roleText($roleID, $rolePersons);
                        $html .= $this->personalTexts($rolePersons, $persons, $showGroups, false);
                    }
                }
            }
            elseif ($groups and $showGroups) {
                $html .= $this->aggregate($groups, 'group', $this->showGroupCodes);
            }

            if ($rooms and $this->showRooms) {
                $html .= $this->resourceText($rooms[0], 'room') . '<br>';
            }
        } // Status: varying degrees of complexity, most easily handled by individual output,
        elseif ($this->showPersons) {
            // Suppress for less output where differentiation is not necessary.
            if ($roleCount === 1) {
                $personTexts = array_shift($personTexts);
                asort($personTexts);
                $html .= $this->personalTexts($personTexts, $persons, $showGroups, $this->showRooms);
            }
            else {
                foreach ($personTexts as $roleID => $rolePersons) {
                    asort($rolePersons);
                    $html .= $this->roleText($roleID, $rolePersons);
                    $html .= $this->personalTexts($rolePersons, $persons, $showGroups, $this->showRooms);
                }
            }
        }
        else {
            if ($groups and $showGroups) {
                $html .= $this->aggregate($groups, 'group', $this->showGroupCodes);
            }

            if ($rooms and $this->showRooms) {
                $html .= $this->aggregate($rooms, 'room');
            }
        }

        // Check if return is the last character before shortening
        $length    = strlen($html);
        $lastBreak = strrpos($html, '<br>');

        if ($lastBreak + 1 === $length) {
            $html = substr($html, 0, strlen($html) - 1);
        }

        return $html . '</div>';
    }

    /**
     * Gets the text to be displayed for the given block.
     *
     * @param   array  $block  the block being iterated
     *
     * @return string
     */
    protected function label(array $block): string
    {
        $label = 'label_' . Application::getTag();

        if ($block[$label]) {
            $value = $block[$label];
        }
        else {
            $startTime = Helpers\Dates::formatTime($block['startTime']);

            // Special case where the block would otherwise bleed into the next day
            $endTime = $block['endTime'] === '2359' ? '2358' : $block['endTime'];
            $endTime = date('H:i', strtotime('+1 minute', strtotime($endTime)));
            $endTime = Helpers\Dates::formatTime($endTime);

            $value = $startTime . "<br>-<br>" . $endTime;
        }

        return '<div style="font-size: 8.5pt; text-align: center">' . $value . '</div>';
    }

    /**
     * Adds a page break as necessary.
     *
     * @param   float   $rowHeight  the height of the row to be rendered
     * @param   string  $monDate    the page start date
     * @param   string  $saturDate  the page end date
     *
     * @return bool  true if a page was added
     */
    protected function pageBreak(float $rowHeight, string $monDate, string $saturDate): bool
    {
        $view       = $this->view;
        $dimensions = $view->getPageDimensions();

        $bottomMargin = $dimensions['bm'];
        $pageHeight   = $dimensions['hk'];
        $yPos         = $view->GetY();

        if (($yPos + $rowHeight + $bottomMargin) > $pageHeight) {
            $view->Ln();
            $this->gridPage($monDate, $saturDate);

            return true;
        }

        return false;
    }

    /**
     * Adds an iterated set of grid pages.
     *
     * @param   string  $monDate  the start date of the week
     * @param   string  $endDate  the end date of the week
     *
     * @return void
     */
    private function pages(string $monDate, string $endDate): void
    {
        while ($monDate < $endDate) {
            $saturDate = date('Y-m-d', strtotime('saturday this week', strtotime($monDate)));
            $this->scrapeGrid($monDate, $saturDate);

            if ($this->grid) {
                $this->gridPage($monDate, $saturDate);
                $this->body($monDate, $saturDate);
            }

            $monDate = date('Y-m-d', strtotime('next monday', strtotime($monDate)));
        }
    }

    /**
     * Generates a text for an individual person, inclusive their group and room assignments as requested.
     *
     * @param   array  $rolePersons  the persons of a single role in the form personID => name
     * @param   array  $persons      the instance person resources hierarchy
     * @param   bool   $showGroups   whether person specific groups should be displayed
     * @param   bool   $showRooms    whether person specific rooms should be displayed
     *
     * @return string
     */
    private function personalTexts(array $rolePersons, array $persons, bool $showGroups, bool $showRooms): string
    {
        $html = '';

        if ($showGroups or $showRooms) {
            foreach ($rolePersons as $personID => $name) {
                $html .= $name;

                if ($showGroups and array_key_exists('groups', $persons[$personID])) {
                    $glue = count($persons[$personID]['groups']) > 2 ? '<br>' : ' - ';
                    $html .= $glue . $this->resourceText($persons[$personID]['groups'], 'group');
                }

                if ($showRooms and array_key_exists('rooms', $persons[$personID])) {
                    $glue = count($persons[$personID]['rooms']) > 2 ? '<br>' : ' - ';
                    $html .= $glue . $this->resourceText($persons[$personID]['rooms'], 'room');
                }

                $html .= '<br>';
            }
        }
        else {
            $html .= $this->implode($rolePersons) . '<br>';
        }

        return $html;
    }

    /**
     * Resolves any links/link parameters to links with icons.
     *
     * @param   string  $text  the text to search
     *
     * @return string
     */
    private function processLinks(string $text): string
    {
        $course = Text::_('ORGANIZER_MOODLE_COURSE');

        $courseURL      = 'https://moodle.thm.de/course/view.php?id=CID';
        $courseTemplate = "<a href=\"MOODLEURL\" target=\"_blank\" style=\"text-decoration: underline\">$course: CID</a>";

        // Complete Course URL
        $template = str_replace('CID', '$4', str_replace('MOODLEURL', $courseURL, $courseTemplate));
        $text     = preg_replace('/(((https?):\/\/)moodle.thm.de\/course\/view.php\?id=(\d+))/', $template, $text);

        // Course ID
        $template = str_replace('CID', '$1', str_replace('MOODLEURL', $courseURL, $courseTemplate));
        $text     = preg_replace('/moodle=(\d+)/', $template, $text);

        // Category URL
        $category         = Text::_('ORGANIZER_MOODLE_COURSE');
        $categoryURL      = 'https://moodle.thm.de/course/index.php?categoryid=CID';
        $categoryTemplate =
            "<a href=\"MOODLEURL\" target=\"_blank\" style=\"text-decoration: underline\">$category: CID</a>";
        $template         = str_replace('CID', '$4', str_replace('MOODLEURL', $categoryURL, $categoryTemplate));
        $text             = preg_replace(
            '/(((https?):\/\/)moodle\.thm\.de\/course\/index\.php\\?categoryid=(\\d+))/',
            $template,
            $text
        );

        $template = "<a href=\"$1\" target=\"_blank\">NetAcad</a>";
        $text     = preg_replace('/(((https?):\/\/)\d+.netacad.com\/courses\/\d+)/', $template, $text);

        $panoptoURL      = 'https://panopto.thm.de/Panopto/Pages/Viewer.aspx?id=PID';
        $panoptoTemplate = "<a href=\"$panoptoURL\" target=\"_blank\">Panopto</a>";

        $template = str_replace('PID', '$4', $panoptoTemplate);
        $text     = preg_replace(
            '/(((https?):\/\/)panopto.thm.de\/Panopto\/Pages\/Viewer.aspx\?id=[\d\w\-]+)/',
            $template,
            $text
        );
        $template = str_replace('PID', '$1', $panoptoTemplate);
        $text     = preg_replace('/panopto=([\d\w\-]+)/', $template, $text);

        $pilosREGEX = '/(((https?):\/\/)(\d+|roxy).pilos-thm.de\/(b\/)?[\d\w]{3}-[\d\w]{3}-[\d\w]{3})/';
        $template   = "<a href=\"$1\" target=\"_blank\">Pilos</a>";

        return preg_replace($pilosREGEX, $template, $text);
    }

    /**
     * Generates the text for the given resources.
     *
     * @param   array   $resources  the rooms associated with the person or persons
     * @param   string  $name       the name and index of the resource within the respective array
     * @param   bool    $code       whether the resource code should be used for the text
     *
     * @return string
     */
    private function resourceText(array $resources, string $name, bool $code = false): string
    {
        $container = [];

        foreach ($resources as $resource) {
            $container[] = $code ? $resource['code'] : $resource[$name];
        }

        return '<span>' . $this->implode($container) . '</span>';
    }

    /**
     * Generates the text labeling the role being currently iterated.
     *
     * @param   int    $roleID       the id of the role being iterated
     * @param   array  $rolePersons  the persons associated with the role being iterated
     *
     * @return string
     */
    private function roleText(int $roleID, array $rolePersons): string
    {
        $role = new Roles();
        $role->load($roleID);
        $tag    = Application::getTag();
        $column = count($rolePersons) > 1 ? "plural_$tag" : "name_$tag";

        return $role->$column . ":<br>";
    }

    /**
     * @param   string  $label      the block label
     * @param   array   $cells      the block instance data
     * @param   string  $monDate    the page start date
     * @param   string  $saturDate  the page end date
     *
     * @return void
     */
    protected function row(string $label, array $cells, string $monDate, string $saturDate): void
    {
        // Less one because of the 'lines' count index
        $lastIndex = count($cells) - 1;
        $rowNumber = 1;
        $view      = $this->view;

        foreach ($cells as $index => $row) {
            if ($index === 'height') {
                continue;
            }

            $border = $rowNumber === $lastIndex ? self::CORNER_BORDER : self::TIME_BORDER;
            $xPos   = $view->GetX();
            $yPos   = $view->GetY();

            if ($this->pageBreak($row['height'], $monDate, $saturDate)) {
                // The page and with it the page's y position has been changed.
                /** @noinspection PhpConditionAlreadyCheckedInspection */
                $yPos = $view->GetY();
                $view->writeHTMLCell(self::TIME_WIDTH, $row['height'], $xPos, $yPos, $label, $border);
            }
            elseif ($rowNumber === 1) {
                $view->writeHTMLCell(self::TIME_WIDTH, $row['height'], $xPos, $yPos, $label, $border);
            }
            else {
                $view->writeHTMLCell(self::TIME_WIDTH, $row['height'], $xPos, $yPos, '', $border);
            }

            $border = $rowNumber === $lastIndex ? self::CORNER_BORDER : self::INSTANCE_BORDER;
            $rowNumber++;
            $xPos += self::TIME_WIDTH;

            for ($currentDT = strtotime($monDate); $currentDT <= strtotime($saturDate);) {
                $date  = date('Y-m-d', $currentDT);
                $value = empty($row[$date]) ? '' : $row[$date];
                $view->writeHTMLCell($this::DATA_WIDTH, $row['height'], $xPos, $yPos, $value, $border);

                $xPos      += $this::DATA_WIDTH;
                $currentDT = strtotime("+1 day", $currentDT);
            }

            $view->Ln();
        }
    }

    /**
     * Scrapes the gridIDs from the groups associated with the instances, and determines the one most relevant one.
     *
     * @param   string  $startDate  the page start date
     * @param   string  $endDate    the page end date
     *
     * @return void
     */
    private function scrapeGrid(string $startDate, string $endDate): void
    {
        $gridIDs = [];

        foreach ($this->instances as $instance) {
            if ($instance->date < $startDate) {
                continue;
            }

            if ($instance->date > $endDate) {
                continue;
            }

            if ($this->currentGroupID) {
                foreach ($instance->resources as $person) {
                    if (!in_array($this->currentGroupID, $person['groups'])) {
                        continue;
                    }

                    $gridIDs[$instance->gridID] = empty($gridIDs[$instance->gridID]) ? 1 : $gridIDs[$instance->gridID] + 1;
                    break;
                }
            }
            else {
                $gridIDs[$instance->gridID] = empty($gridIDs[$instance->gridID]) ? 1 : $gridIDs[$instance->gridID] + 1;
            }
        }

        // Descending by grid use keeping gridID association.
        arsort($gridIDs);

        if (empty($gridIDs) or !$gridID = array_key_first($gridIDs)) {
            $gridID = Helpers\Grids::getDefault();
        }

        $grid = json_decode(Helpers\Grids::getGrid($gridID), true);
        $grid = $grid['periods'];

        $hasCells = false;

        foreach ($grid as $blockNo => $block) {
            if ($cells = $this->cells($startDate, $endDate, $block)) {
                $hasCells                = true;
                $grid[$blockNo]['cells'] = $cells;
            }
        }

        $this->grid = $hasCells ? $grid : [];

        for ($currentDT = strtotime($startDate); $currentDT <= strtotime($endDate);) {
            $date = date('Y-m-d', $currentDT);

            foreach ($this->instances as $key => $instance) {
                if ((!$this->currentGroupID and $instance->date < $date) or empty($instance->resources)) {
                    unset($this->instances[$key]);
                }
            }

            $currentDT = strtotime("+1 day", $currentDT);
        }
    }

    /**
     * Retrieves the individual groups associated with the instances and replaces the groups arrays with their
     * respective ids to reduce memory overhead.
     * @return array[]
     */
    private function scrapeGroups(): array
    {
        $groups = [];

        foreach ($this->instances as $key => $instance) {
            if (empty($instance->resources)) {
                unset($this->instances[$key]);
                continue;
            }

            foreach ($instance->resources as $personID => $person) {
                if (empty($person['groups'])) {
                    unset($instance->resources[$personID]);
                    continue;
                }

                foreach ($person['groups'] as $groupID => $group) {
                    $groups[$groupID]                                   = $group;
                    $instance->resources[$personID]['groups'][$groupID] = $groupID;
                }
            }
        }

        return $groups;
    }

    /**
     * Checks whether the current instance is relevant in regard to the current group.
     *
     * @param $instance
     *
     * @return bool
     */
    private function relevant($instance): bool
    {
        if (!$this->currentGroupID) {
            return true;
        }

        foreach ($instance->resources as $person) {
            if (in_array($this->currentGroupID, $person['groups'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets the subtitle line dedicated to the selected resources.
     *
     * @param   string  $resource
     *
     * @return void
     */
    private function resourceHeader(string $resource = ''): void
    {
        if ($resource) {
            $this->resourceHeader = $resource;

            return;
        }

        /** @var iView $view */
        $view = $this->view;

        $conditions = $view->conditions;
        $resources  = [];

        if (array_key_exists('personIDs', $conditions)
            and count($conditions['personIDs']) === 1
            and $person = Helpers\Persons::name($conditions['personIDs'][0])) {
            $resources['person'] = $person;
        }

        if (array_key_exists('categoryIDs', $conditions)
            and count($conditions['categoryIDs']) === 1
            and $category = Helpers\Categories::name($conditions['categoryIDs'][0])) {
            $resources['group'] = $category;
        }

        if (array_key_exists('groupIDs', $conditions)) {
            if (count($conditions['groupIDs']) === 1) {
                $resources['group'] = Helpers\Groups::getFullName($conditions['groupIDs'][0]);
            }
            else {
                $groups    = [];
                $lastID    = array_pop($conditions['groupIDs']);
                $lastGroup = Helpers\Groups::name($lastID);

                // If there was no specific category filter, the groups are not necessarily of the same category and should be ignored.
                if (array_key_exists('group', $resources)) {
                    foreach ($conditions['groupIDs'] as $groupID) {
                        $groups[] = Helpers\Groups::name($groupID);
                    }

                    $resources['group'] .= implode(', ', $groups) . " & $lastGroup";
                }
            }
        }

        if (array_key_exists('roomIDs', $conditions)) {
            if (count($conditions['roomIDs']) === 1) {
                $resources['room'] = Helpers\Rooms::name($conditions['roomIDs'][0]);
            }
            else {
                $rooms    = [];
                $lastID   = array_pop($conditions['roomIDs']);
                $lastRoom = Helpers\Rooms::name($lastID);

                foreach ($conditions['roomIDs'] as $roomID) {
                    $rooms[] = Helpers\Rooms::name($roomID);
                }

                $resources['room'] .= implode(', ', $rooms) . " & $lastRoom";
            }
        }

        $this->resourceHeader = implode(' - ', $resources);
    }

    /** @inheritDoc */
    public function title(): void
    {
        /** @var iModel $model */
        $model = $this->view->getModel();

        $title = $model->getTitle();
        $this->view->titles($title);
    }
}
