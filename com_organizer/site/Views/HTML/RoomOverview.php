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

use THM\Organizer\Adapters\{Application, Input, Text, User};
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Roles;

/**
 * Loads lesson and event data for a filtered set of rooms into the view context.
 */
class RoomOverview extends TableView
{
    private const WEEK = 2, LAB = 14, UNKNOWN = 49;

    private array $grid;

    /**
     * Adds a toolbar and title to the view.
     * @return void  sets context variables
     */
    protected function addToolBar(): void
    {
        $resourceName = Text::_('ORGANIZER_ROOM_OVERVIEW');
        if (!Application::backend()) {
            if ($campusID = Input::getInt('campusID')) {
                $resourceName .= ': ' . Text::_('ORGANIZER_CAMPUS');
                $resourceName .= ' ' . Helpers\Campuses::name($campusID);
            }
        }

        $this->setTitle('ORGANIZER_ROOM_OVERVIEW', $resourceName);
    }

    /**
     * Gets the cells for an individual day.
     *
     * @param   object  $room  the room to retrieve the cells for
     * @param   string  $date  the Y-m-d date to retrieve the cells for
     *
     * @return array[] the cells for the specific day
     */
    private function getDailyCells(object $room, string $date): array
    {
        $cells      = [];
        $conditions = [
            'date'            => $date,
            'delta'           => false,
            'endDate'         => $date,
            'interval'        => 'day',
            'my'              => false,
            'roomIDs'         => [$room->id],
            'showUnpublished' => Helpers\Can::administrate(),
            'startDate'       => $date,
            'status'          => 1,
            'userID'          => User::id()
        ];

        $instances = Helpers\Instances::items($conditions);
        if (isset($instances['futureDate']) or isset($instances['pastDate'])) {
            $instances = [];
        }

        $data = ['date' => $date, 'instances' => $instances, 'name' => $room->name];

        if (empty($this->grid['periods'])) {
            if (empty($data['instances'])) {
                $cells[] = ['text' => ''];
            }
            else {
                $cells[] = $this->getDataCell($data);
            }
        }
        else {
            foreach (array_keys($this->grid['periods']) as $blockNo) {
                if (empty($data['instances'])) {
                    $cells[] = ['text' => ''];
                    continue;
                }

                $data['blockNo'] = $blockNo;
                $cells[]         = $this->getDataCell($data);
            }
        }

        return $cells;
    }

    /**
     * Creates an array of blocks.
     *
     * @param   bool  $short  true if the block labels should be abbreviated
     *
     * @return array[] the blocks of the time grid
     */
    private function getHeaderBlocks(bool $short = false): array
    {
        $blocks = [];
        $grid   = $this->grid;

        if (empty($grid['periods'])) {
            return $blocks;
        }

        // Suppress blocks for all day display
        if (count($grid['periods']) === 1) {
            $block     = reset($grid['periods']);
            $endTime   = Helpers\Dates::formatEndTime($block['endTime']);
            $startTime = Helpers\Dates::formatTime($block['startTime']);

            if ($endTime === '00:00' and $endTime === $startTime) {
                return $blocks;
            }
        }

        $labelIndex = 'label_' . Application::tag();

        foreach ($grid['periods'] as $number => $data) {
            $endTime   = Helpers\Dates::formatEndTime($data['endTime']);
            $endTime   = $endTime !== '00:00' ? $endTime : '23:59';
            $startTime = Helpers\Dates::formatTime($data['startTime']);
            $timeText  = "$startTime - $endTime";

            if (!empty($data[$labelIndex])) {
                $alias = $data[$labelIndex];
                $text  = $short ? mb_substr($alias, 0, 1) : $alias;
                $tip   = $short ? "<div class=\"cellTip\">$alias ($timeText)</div>" : '';
            }
            else {
                $text = $short ? $number : $timeText;
                $tip  = $short ? "<div class=\"cellTip\">$timeText</div>" : '';
            }

            if ($tip) {
                $tip  = htmlentities($tip);
                $html = "<span class=\"hasTooltip\" title=\"$tip\">$text</span>";
            }
            else {
                $html = $text;
            }

            $block = ['text' => $html];

            $blocks[$number] = $block;
        }

        return $blocks;
    }

    /**
     * Processes an individual list item resolving it to an array of table data values.
     *
     * @param   object  $resource  the resource whose information is displayed in the row
     *
     * @return array[] an array of property columns with their values
     */
    protected function getRow(object $resource): array
    {
        $date = $this->state->get('list.date');

        if ((int) $this->state->get('list.template') === self::WEEK) {
            $row         = [];
            $dates       = Helpers\Dates::week($date, $this->grid['startDay'], $this->grid['endDay']);
            $currentDate = $dates['startDate'];
            while ($currentDate <= $dates['endDate']) {
                $dailyCells  = $this->getDailyCells($resource, $currentDate);
                $row         = array_merge($row, $dailyCells);
                $currentDate = date('Y-m-d', strtotime("$currentDate + 1 days"));
            }
        }
        else {
            $row = $this->getDailyCells($resource, $date);
        }

        $label = $this->getRowLabel($resource);
        array_unshift($row, $label);

        return $row;
    }

    /**
     * Creates a label with tooltip for the resource row.
     *
     * @param   object  $resource  the resource to be displayed in the row
     *
     * @return string[]  the label inclusive tooltip to be displayed
     */
    protected function getRowLabel(object $resource): array
    {
        $tip = "<div class=\"cellTip\"><span class=\"cellTitle\">$resource->name</span>";
        $tip .= ($resource->typeName or $resource->effCapacity) ? "<div class=\"labelTip\">" : '';

        if ($resource->typeName) {
            $tip .= $resource->typeName;
            if ((int) $resource->roomtypeID === self::LAB) {
                if (!empty($resource->roomDesc)) {
                    $tip .= ":<br>$resource->roomDesc";
                }
            }
            elseif ((int) $resource->roomtypeID !== self::UNKNOWN and !empty($resource->typeDesc)) {
                $tip .= ":<br>$resource->typeDesc";
            }
            $tip .= $resource->effCapacity ? '<br>' : '';
        }

        if ($resource->effCapacity) {
            $tip .= Text::_('ORGANIZER_CAPACITY');
            $tip .= ": $resource->effCapacity";
        }

        $tip  .= ($resource->typeName or $resource->effCapacity) ? '</div></div>' : '</div>';
        $tip  = htmlentities($tip);
        $text = "<span class=\"hasTooltip\" title=\"$tip\">$resource->name</span>";

        return ['label' => $text];
    }

    /**
     * Processes an individual list item resolving it to an array of table data values.
     *
     * @param   array  $data  the data to be used to generate the cell contents
     *
     * @return string[] an array of property columns with their values
     */
    protected function getDataCell(array $data): array
    {
        if (empty($data['blockNo'])) {
            $noGrid    = true;
            $dEndTime  = '';
            $endTime   = '';
            $startTime = '';
        }
        else {
            $blockNo   = $data['blockNo'];
            $endTime   = $this->grid['periods'][$blockNo]['endTime'];
            $endTime   = Helpers\Dates::formatEndTime($endTime);
            $dEndTime  = $endTime !== '00:00' ? $endTime : '23:59';
            $noGrid    = false;
            $startTime = Helpers\Dates::formatTime($this->grid['periods'][$blockNo]['startTime']);
        }

        $instances = $data['instances'];

        $tips = [];

        foreach ($instances as $instance) {
            if (!$noGrid) {
                $allDay   = $startTime === $endTime;
                $tooEarly = $instance['endTime'] <= $startTime;
                $tooLate  = $instance['startTime'] >= $endTime;

                if (!$allDay and ($tooEarly or $tooLate)) {
                    continue;
                }
            }

            $times = "{$instance['startTime']} - {$instance['endTime']}";
            $tip   = '<div class="cellTip">';
            if ($noGrid or $instance['endTime'] !== $endTime or $instance['startTime'] !== $startTime) {
                $tip .= "($times)<br>";
            }

            $tip .= '<span class="cellTitle">' . $instance['name'];
            $tip .= $instance['method'] ? " - {$instance['method']}" : '';
            $tip .= '</span><br>';

            $tip .= Text::_('ORGANIZER_ORGANIZATION') . ":";
            $tip .= strlen($instance['organization']) > 20 ? '<br>' : ' ';
            $tip .= "{$instance['organization']}<br>";

            $persons = [];
            foreach ($instance['resources'] as $personID => $personAssoc) {
                if ((int) $personAssoc['roleID'] === Roles::TEACHER or (int) $personAssoc['roleID'] === Roles::SPEAKER) {
                    $persons[$personID] = $personAssoc['person'];
                }
            }

            if ($persons) {
                $tip     .= Text::_('ORGANIZER_PERSONS') . ":";
                $persons = implode(', ', $persons);
                $tip     .= strlen($persons) > 20 ? '<br>' : ' ';
                $tip     .= "$persons<br>";
            }

            if ($instance['comment']) {
                $tip .= Text::_('ORGANIZER_EXTRA_INFORMATION') . ":";
                $tip .= strlen($instance['comment']) > 20 ? '<br>' : ' ';
                $tip .= "{$instance['comment']}<br>";
            }

            $index = "$times {$instance['organizationID']} {$instance['name']} {$instance['method']}";

            $tip          .= '</div>';
            $tips[$index] = $tip;
        }

        $cell['text'] = '';

        if ($tips) {
            if ($noGrid) {
                $icons = [];
                foreach ($tips as $tip) {
                    $tip     = htmlentities($tip);
                    $icons[] = "<span class=\"icon-square hasTooltip\" title=\"$tip\"'></span>";
                }

                $cell['text'] = implode(' ', $icons);
            }
            else {
                $iconClass    = count($tips) > 1 ? 'grid' : 'square';
                $date         = Helpers\Dates::formatDate($data['date'], true);
                $cellTip      = '<div class="cellTip">';
                $cellTip      .= "<span class=\"cellTitle\">$date<br>$startTime - $dEndTime</span>";
                $cellTip      .= implode('', $tips);
                $cellTip      .= '<div>';
                $cellTip      = htmlentities($cellTip);
                $cell['text'] = "<span class=\"icon-$iconClass hasTooltip\" title=\"$cellTip\"></span>";
            }
        }

        return $cell;
    }

    /**
     * Sets the table header information
     * @return void sets the headers property
     */
    protected function setHeaders(): void
    {
        $date     = $this->state->get('list.date');
        $headers  = [];
        $template = $this->state->get('list.template');

        if ((int) $template === self::WEEK) {
            $blocks    = $this->getHeaderBlocks(true);
            $headers[] = ['text' => '', 'columns' => []];
            $dates     = Helpers\Dates::week($date, $this->grid['startDay'], $this->grid['endDay']);

            $currentDate = $dates['startDate'];
            while ($currentDate <= $dates['endDate']) {
                $formattedDate           = Helpers\Dates::formatDate($currentDate);
                $headers[$formattedDate] = ['text' => $formattedDate, 'columns' => $blocks];
                $currentDate             = date('Y-m-d', strtotime("$currentDate + 1 days"));
            }
        }
        elseif (empty($this->grid['periods'])) {
            $headers = [['text' => ''], ['text' => Helpers\Dates::formatDate($date)]];
        }
        else {
            $blocks  = $this->getHeaderBlocks();
            $headers = $blocks;
            array_unshift($headers, ['text' => '']);
        }

        $this->headers = $headers;
    }

    /**
     * Function to set attributes unique to individual tables.
     * @return void sets attributes specific to individual tables
     */
    protected function setOverrides(): void
    {
        if (!$gridID = $this->state->get('list.gridID') and $campusID = Input::getParams()->get('campusID')) {
            $gridID = Helpers\Campuses::gridID($campusID);
        }

        if (empty($gridID)) {
            $gridID = Helpers\Grids::getDefault();
        }

        $this->grid = json_decode(Helpers\Grids::getGrid($gridID), true);
    }
}
