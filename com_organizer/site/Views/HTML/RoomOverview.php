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

use stdClass;
use THM\Organizer\Adapters\{Application, HTML, Input, Text};
use THM\Organizer\Helpers\{Campuses, Dates, Roles};
use THM\Organizer\Models\RoomOverview as Model;

/**
 * Loads lesson and event data for a filtered set of rooms into the view context.
 */
class RoomOverview extends TableView
{
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
                $resourceName .= ' ' . Campuses::name($campusID);
            }
        }

        $this->title('ORGANIZER_ROOM_OVERVIEW', $resourceName);
    }

    /**
     * Generates column headers for the blocks header row.
     *
     * @param   bool  $short  true if the block labels should be abbreviated
     *
     * @return array[] the blocks of the time grid
     */
    private function blockColumns(bool $short = false): array
    {
        /** @var Model $model */
        $model  = $this->getModel();
        $blocks = $model->blocks;
        $dates  = $model->dates;

        $supplement = count($dates) > 1;

        $class      = 'd-md-table-cell';
        $properties = ['class' => "$class ta-right", 'scope' => 'col'];
        $row        = ['name' => ['properties' => $properties, 'title' => Text::_('ROOMS'), 'type' => 'text']];

        $label               = 'label_' . Application::tag();
        $properties['class'] = "$class ta-center";

        // The unformatted dates are being used for structure and column keys
        foreach ($dates as $rawDate => $fDate) {
            $leftBorder = true;
            foreach ($blocks as $blockNo => $block) {
                $key = "$rawDate-$blockNo";
                // Special case where the time would otherwise technically be the beginning of the next day
                $endTime    = $block['endTime'] !== '00:00' ? $block['endTime'] : '23:59';
                $timeText   = "{$block['startTime']} - $endTime";
                $extendedTT = $supplement ? "$fDate $timeText" : $timeText;

                if ($block[$label]) {
                    $alias = $block[$label];
                    $tip   = $short ? "$alias ($extendedTT)" : '';
                    $title = $short ? mb_substr($alias, 0, 1) : $alias;
                }
                else {
                    $tip   = $short ? $extendedTT : '';
                    $title = $short ? $blockNo : $timeText;
                }

                $row[$key] = [
                    'properties' => $properties,
                    'tip'        => $tip,
                    'title'      => $title,
                    'type'       => 'tip'
                ];

                if ($leftBorder) {
                    $row[$key]['properties']['class'] .= ' lb-thick';
                    $leftBorder                       = false;
                }
                else {
                    $row[$key]['properties']['class'] .= ' lb-thin';
                }
            }
        }

        return $row;
    }

    /**
     * Readies an item for output.
     *
     * @param   int       $index  the current iteration number
     * @param   stdClass  $item   the current item being iterated
     * @param   array     $options
     *
     * @return void
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $headers = $this->colScope === false ? $this->headers : $this->headers[$this->colScope];

        foreach (array_keys($headers) as $column) {
            if ($column === 'name') {
                $item->name = [
                    'properties' => ['class' => 'd-md-table-cell ta-right'],
                    'value'      => $item->name,
                    'tip'        => $this->roomTip($item),
                    'type'       => 'header'
                ];
                continue;
            }

            $keyParts = explode('-', $column);
            $blockNo  = count($keyParts) === 4 ? (int) array_pop($keyParts) : 0;

            $class = 'd-md-table-cell ta-center';
            $class .= ($blockNo === 0 or $blockNo === 1) ? ' lb-thick' : ' lb-thin';

            $icon = match (count($item->$column)) {
                0 => '',
                1 => HTML::icon('fa fa-square-full'),
                2, 3 => HTML::icon('fa fa-clone'),
                4, 5, 6 => HTML::icon('fa fa-th-large'),
                default => HTML::icon('fa fa-th')
            };

            $item->$column = [
                'properties' => ['class' => $class],
                'value'      => $icon,
                'tip'        => empty($item->$column) ? '' : $this->instances($blockNo, $item->$column),
                'type'       => 'text'
            ];
        }
    }

    /**
     * Generates column headers for the dates header row.
     *
     * @param   array  $dates  an array of Y-m-d => display formatted dates
     * @param   int    $blocks
     *
     * @return array[]
     */
    private function dateColumns(array $dates, int $blocks): array
    {
        $class = 'd-md-table-cell ta-center';
        if ($blocksShown = $blocks > 1) {
            $properties = ['class' => "$class lb-thick"];
            $title      = '';
        }
        else {
            $properties = ['class' => $class, 'scope' => 'col'];
            $title      = Text::_('ROOM');
        }

        $columns = ['name' => ['properties' => $properties, 'title' => $title, 'type' => 'text']];

        if ($blocksShown) {
            $properties['colspan'] = $blocks;
        }

        foreach ($dates as $key => $date) {
            $columns[$key] = ['properties' => $properties, 'title' => $date, 'type' => 'text'];
        }

        return $columns;
    }

    /** @inheritDoc */
    protected function initializeColumns(): void
    {
        /** @var Model $model */
        $model  = $this->getModel();
        $blocks = $model->blocks;
        $dates  = $model->dates;
        $count  = count($blocks);

        $dateColumns = $this->dateColumns($dates, $count);
        if ($count === 1) {
            $this->headers = $dateColumns;
            return;
        }

        $this->colScope = 'blocks';
        $this->headers  = ['dates' => $dateColumns, 'blocks' => $this->blockColumns(count($dates) > 1)];
    }

    /** @inheritDoc */
    protected function initializeRows(): void
    {
        // The rows have identity with actual resources, no implementation is needed here.
        $this->identity = true;
    }

    /**
     * Processes an individual list item resolving it to an array of table data values.
     *
     * @param   int    $blockNo      the block number currently being iterated
     * @param   array  $instanceIDs  the ids of instances associated with the block & room being iterated
     *
     * @return string an array of property columns with their values
     */
    protected function instances(int $blockNo, array $instanceIDs): string
    {
        /** @var Model $model */
        $model     = $this->getModel();
        $instances = $model->instances;

        if (empty($model->blocks[$blockNo])) {
            $endTime = $startTime = null;
        }
        else {
            $endTime   = Dates::formatEndTime($model->blocks[$blockNo]['endTime']);
            $startTime = $model->blocks[$blockNo]['startTime'];
        }

        $tips = [];
        foreach ($instanceIDs as $instanceID) {
            if (empty($instances[$instanceID])) {
                continue;
            }

            $instance = $instances[$instanceID];

            $tip = "<h6>{$instance['name']}";
            $tip .= $instance['method'] ? " - {$instance['method']}" : '';
            $tip .= ($instance['endTime'] !== $endTime or $instance['startTime'] !== $startTime) ?
                " ({$instance['startTime']} - {$instance['endTime']})" : '';
            $tip .= '</h6>';

            $tip .= Text::_('ORGANIZATION') . ":";
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

            $tips[] = $tip;
        }

        if (empty($tips)) {
            return '';
        }

        return count($tips) > 1 ? '<div>' . implode('</div><div>', $tips) . '</div>' : reset($tips);
    }

    /**
     * Creates a tip for the room being iterated
     *
     * @param   object  $room  the room to be displayed in the row
     *
     * @return string
     */
    protected function roomTip(object $room): string
    {
        if (empty($room->effCapacity) and empty($room->type)) {
            return '';
        }

        $tip = "<h6>$room->name</h6>";

        if ($room->type) {
            $tip .= $room->type;
            if ($room->description) {
                // Room types with high specificity regarding their equipment
                $tip .= strlen($room->type) > 20 ? ':<br>' : ': ';

                $tip .= strip_tags($room->description);
            }
            $tip .= $room->effCapacity ? '<br>' : '';
        }

        if ($room->effCapacity) {
            $tip .= Text::_('CAPACITY') . ": $room->effCapacity";
        }

        return $tip;
    }
}
