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

use THM\Organizer\Adapters\Text;

//use THM\Organizer\Helpers\Bookings;
use THM\Organizer\Helpers\{Categories, Dates, Instances, Methods, Organizations, Terms};

/**
 * Class loads statistical information about appointments into the display context.
 */
class Statistics extends TableView
{
    public const METHOD = 1, PRESENCE_TYPE = 2;
    //public const CAPACITY = 3, REGISTRATIONS = 4;

    /**
     * Set in setSubtitle to avoid multiple
     * @var int
     */
    private int $statistic;

    /**
     * Creates lesson statistics about actual attendance vs lesson capacity.
     * @return void
     */
    /*private function byCapacity(): void
    {
        $categoryID     = $this->state->get('list.categoryID');
        $columnKeys     = array_keys($this->headers);
        $organizationID = $this->state->get('list.organizationID');
        $rows           =& $this->rows;
        $usedKeys       = [];
        $usedMondays    = [];
        $usedResources  = [];

        foreach ($this->items as $instance) {
            $instanceID = $instance->instanceID;
            $presence   = Instances::getPresence($instanceID);

            // Online attendance is not recorded
            if ($presence === Instances::ONLINE) {
                continue;
            }

            $attended  = Instances::attendance($instanceID);
            $capacity  = Instances::capacity($instanceID);
            $monday    = date('Y-m-d', strtotime('monday this week', strtotime($instance->date)));
            $uniqueKey = "$instance->unitID-$instance->blockID";
            $upTCap    = false;

            if (empty($usedKeys[$uniqueKey])) {
                $upTCap               = true;
                $usedKeys[$uniqueKey] = 1;

                if ($attended) {
                    $usedMondays[$monday] = $monday;
                }
            }

            if ($categoryID) {
                $resourceIDs = Instances::groupIDs($instanceID);
            }
            elseif ($organizationID) {
                $resourceIDs = Instances::categoryIDs($instanceID);
            }
            else {
                $resourceIDs = Instances::getOrganizationIDs($instanceID);
            }

            if ($columnIDs = array_intersect($columnKeys, $resourceIDs)) {
                if ($capacity) {

                    $rows['sum']['sum']['attended']   = $rows['sum']['sum']['attended'] + $attended;
                    $rows[$monday]['sum']['attended'] = $rows[$monday]['sum']['attended'] + $attended;

                    if ($upTCap) {
                        $rows['sum']['sum']['capacity']   = $rows['sum']['sum']['capacity'] + $capacity;
                        $rows[$monday]['sum']['capacity'] = $rows[$monday]['sum']['capacity'] + $capacity;
                    }
                }

                // The true total is increased whether the rooms have a capacity value
                $rows['sum']['sum']['total']   = $rows['sum']['sum']['total'] + $attended;
                $rows[$monday]['sum']['total'] = $rows[$monday]['sum']['total'] + $attended;

                foreach ($columnIDs as $columnID) {
                    if ($capacity) {
                        $usedResources[$columnID] = $columnID;

                        $rows['sum'][$columnID]['attended']   = $rows['sum'][$columnID]['attended'] + $attended;
                        $rows[$monday][$columnID]['attended'] = $rows[$monday][$columnID]['attended'] + $attended;

                        if ($upTCap) {
                            $rows['sum'][$columnID]['capacity']   = $rows['sum'][$columnID]['capacity'] + $capacity;
                            $rows[$monday][$columnID]['capacity'] = $rows[$monday][$columnID]['capacity'] + $capacity;
                        }
                    }

                    $rows['sum'][$columnID]['total']   = $rows['sum'][$columnID]['total'] + $attended;
                    $rows[$monday][$columnID]['total'] = $rows[$monday][$columnID]['total'] + $attended;

                }
            }
        }

        $this->removePRUnused($usedMondays, $usedResources);
    }*/

    /**
     * Creates lesson statistics about the planning of lessons by method of instruction.
     * @return void
     */
    private function byMethod(): void
    {
        $categoryID     = $this->state->get('list.categoryID');
        $columnKeys     = array_keys($this->headers);
        $organizationID = $this->state->get('list.organizationID');
        $rowKeys        = array_keys($this->rows);
        $rows           =& $this->rows;
        $usedMethods    = [];
        $usedResources  = [];

        foreach ($this->items as $instance) {
            $instanceID = $instance->instanceID;
            $methodID   = $instance->methodID;

            if (!in_array($methodID, $rowKeys)) {
                continue;
            }

            if ($categoryID) {
                $resourceIDs = Instances::groupIDs($instanceID);
            }
            elseif ($organizationID) {
                $resourceIDs = Instances::categoryIDs($instanceID);
            }
            else {
                $resourceIDs = Instances::getOrganizationIDs($instanceID);
            }

            if ($columnIDs = array_intersect($columnKeys, $resourceIDs)) {
                $rows['sum']['sum']     = $rows['sum']['sum'] + 1;
                $rows[$methodID]['sum'] = $rows[$methodID]['sum'] + 1;
                $usedMethods[$methodID] = $methodID;

                foreach ($columnIDs as $columnID) {
                    $rows['sum'][$columnID]     = $rows['sum'][$columnID] + 1;
                    $rows[$methodID][$columnID] = $rows[$methodID][$columnID] + 1;
                    $usedResources[$columnID]   = $columnID;
                }
            }
        }

        // Unused Rows
        foreach (array_diff($rowKeys, $usedMethods) as $unusedRowKey) {
            if (is_numeric($unusedRowKey)) {
                unset($rows[$unusedRowKey]);
            }
        }

        // Unused columns
        foreach ($unusedColumnKeys = array_diff($columnKeys, $usedResources) as $key => $value) {
            if (!is_numeric($value)) {
                unset($this->headers[$key]);
            }
        }

        foreach (array_keys($rows) as $rowKey) {
            foreach ($unusedColumnKeys as $unusedColumnKey) {
                unset($rows[$rowKey][$unusedColumnKey]);
            }
        }
    }

    /**
     * Creates lesson statistics in regard to the planned presence type
     * @return void
     */
    private function byPresenceType(): void
    {
        $categoryID     = $this->state->get('list.categoryID');
        $columnKeys     = array_keys($this->headers);
        $organizationID = $this->state->get('list.organizationID');
        $rows           =& $this->rows;
        $usedMondays    = [];
        $usedResources  = [];

        foreach ($this->items as $instance) {
            $instanceID = $instance->instanceID;
            $presence   = Instances::getPresence($instanceID);
            $monday     = date('Y-m-d', strtotime('monday this week', strtotime($instance->date)));

            if ($categoryID) {
                $resourceIDs = Instances::groupIDs($instanceID);
            }
            elseif ($organizationID) {
                $resourceIDs = Instances::categoryIDs($instanceID);
            }
            else {
                $resourceIDs = Instances::getOrganizationIDs($instanceID);
            }

            if ($columnIDs = array_intersect($columnKeys, $resourceIDs)) {
                $rows['sum']['sum'][$presence]   = $rows['sum']['sum'][$presence] + 1;
                $rows['sum']['sum']['total']     = $rows['sum']['sum']['total'] + 1;
                $rows[$monday]['sum'][$presence] = $rows[$monday]['sum'][$presence] + 1;
                $rows[$monday]['sum']['total']   = $rows[$monday]['sum']['total'] + 1;

                $usedMondays[$monday] = $monday;

                foreach ($columnIDs as $columnID) {
                    $rows['sum'][$columnID][$presence]   = $rows['sum'][$columnID][$presence] + 1;
                    $rows['sum'][$columnID]['total']     = $rows['sum'][$columnID]['total'] + 1;
                    $rows[$monday][$columnID][$presence] = $rows[$monday][$columnID][$presence] + 1;
                    $rows[$monday][$columnID]['total']   = $rows[$monday][$columnID]['total'] + 1;

                    $usedResources[$columnID] = $columnID;
                }
            }
        }

        $this->removePRUnused($usedMondays, $usedResources);
    }

    /**
     * Creates lesson statistics in regard to registrations vs attendance.
     * @return void
     */
    /*private function byRegistrations(): void
    {
        $categoryID     = $this->state->get('list.categoryID');
        $columnKeys     = array_keys($this->headers);
        $organizationID = $this->state->get('list.organizationID');
        $rows           =& $this->rows;
        $usedKeys       = [];
        $usedMondays    = [];
        $usedResources  = [];

        foreach ($this->items as $instance) {
            $instanceID = $instance->instanceID;
            $presence   = Instances::getPresence($instanceID);

            if ($presence === Instances::ONLINE) {
                continue;
            }

            $attendance    = Bookings::participantCount($instance->bookingID);
            $registrations = Instances::currentCapacity($instanceID);

            $monday       = date('Y-m-d', strtotime('monday this week', strtotime($instance->date)));
            $attended     = Instances::attendance($instanceID);
            $noShows      = max(($registrations - $attendance), 0);
            $registered   = Instances::getRegistered($instanceID);
            $unregistered = max(($attendance - $registrations), 0);
            $uniqueKey    = "$instance->unitID-$instance->blockID";
            $updateUnique = false;

            if (empty($usedKeys[$uniqueKey])) {
                $updateUnique         = true;
                $usedKeys[$uniqueKey] = 1;

                if ($attended or $registered) {
                    $usedMondays[$monday] = $monday;
                }
            }

            if ($categoryID) {
                $resourceIDs = Instances::groupIDs($instanceID);
            }
            elseif ($organizationID) {
                $resourceIDs = Instances::categoryIDs($instanceID);
            }
            else {
                $resourceIDs = Instances::getOrganizationIDs($instanceID);
            }

            if ($columnIDs = array_intersect($columnKeys, $resourceIDs)) {
                $rows['sum']['sum']['attended']     = $rows['sum']['sum']['attended'] + $attended;
                $rows['sum']['sum']['registered']   = $rows['sum']['sum']['registered'] + $registered;
                $rows[$monday]['sum']['attended']   = $rows[$monday]['sum']['attended'] + $attended;
                $rows[$monday]['sum']['registered'] = $rows[$monday]['sum']['registered'] + $registered;

                if ($updateUnique) {
                    $rows['sum']['sum']['no-shows']       = $rows['sum']['sum']['no-shows'] + $noShows;
                    $rows[$monday]['sum']['no-shows']     = $rows[$monday]['sum']['no-shows'] + $noShows;
                    $rows['sum']['sum']['unregistered']   = $rows['sum']['sum']['unregistered'] + $unregistered;
                    $rows[$monday]['sum']['unregistered'] = $rows[$monday]['sum']['unregistered'] + $unregistered;
                }

                foreach ($columnIDs as $columnID) {
                    $usedResources[$columnID] = $columnID;

                    $rows['sum'][$columnID]['attended']     = $rows['sum'][$columnID]['attended'] + $attended;
                    $rows['sum'][$columnID]['registered']   = $rows['sum'][$columnID]['registered'] + $registered;
                    $rows[$monday][$columnID]['attended']   = $rows[$monday][$columnID]['attended'] + $attended;
                    $rows[$monday][$columnID]['registered'] = $rows[$monday][$columnID]['registered'] + $registered;

                    if ($updateUnique) {
                        $rows['sum'][$columnID]['no-shows']       = $rows['sum'][$columnID]['no-shows'] + $noShows;
                        $rows[$monday][$columnID]['no-shows']     = $rows[$monday][$columnID]['no-shows'] + $noShows;
                        $rows['sum'][$columnID]['unregistered']   = $rows['sum'][$columnID]['unregistered'] + $unregistered;
                        $rows[$monday][$columnID]['unregistered'] = $rows[$monday][$columnID]['unregistered'] + $unregistered;
                    }
                }
            }
        }

        $this->removePRUnused($usedMondays, $usedResources);
    }*/

    /** @inheritDoc */
    protected function completeItems(array $options = []): void
    {
        switch ($this->statistic) {
            /*case self::CAPACITY:
                $this->byCapacity();
                break;*/
            case self::PRESENCE_TYPE:
                $this->byPresenceType();
                break;
            /*case self::REGISTRATIONS:
                $this->byRegistrations();
                break;*/
            case self::METHOD:
            default:
                $this->byMethod();
                break;
        }
    }

    /**
     * @inheritDoc
     */
    protected function initializeColumns(): void
    {
        $state = $this->state;

        $headers = $this->statistic === self::METHOD ?
            [
                'method' =>
                    [
                        'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                        'title'      => Text::_('METHOD_SIMPLE'),
                        'type'       => 'header'
                    ],
                'sum'    =>
                    [
                        'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                        'title'      => Text::_('SUM'),
                        'type'       => 'header'
                    ]
            ] :
            [
                'week' =>
                    [
                        'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                        'title'      => Text::_('WEEK'),
                        'type'       => 'header'
                    ],
                'sum'  =>
                    [
                        'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                        //self::CAPACITY ? Text::_('AVERAGE') : Text::_('SUM'),
                        'title'      => Text::_('SUM'),
                        'type'       => 'header'
                    ]
            ];

        $resources = [];
        if ($categoryID = $state->get('list.categoryID')) {
            foreach (Categories::groups($categoryID) as $group) {
                $resources[$group['id']] = $group['name'];
            }
        }
        elseif ($organizationID = $state->get('list.organizationID')) {
            foreach (Organizations::categories($organizationID) as $category) {
                $resources[$category['id']] = $category['name'];
            }
        }
        else {
            foreach (Organizations::resources() as $organization) {
                if (!$organization['active']) {
                    continue;
                }

                $resources[$organization['id']] = $organization['shortName'];
            }
        }

        asort($resources);
        foreach ($resources as $columnID => $columnName) {
            $headers[$columnID] = [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => $columnName,
                'type'       => 'text'
            ];
        }

        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    protected function initializeRows(): void
    {
        switch ($this->statistic) {
            //case self::CAPACITY:
            case self::PRESENCE_TYPE:
                //case self::REGISTRATIONS:
                $this->participationRows();
                break;
            case self::METHOD:
            default:
                $this->methodRows();
                break;
        }
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        //Document::script('statistics');
        //Document::style('statistics');
    }

    /**
     * Create rows for the sum and each individual method.
     * @return void
     */
    private function methodRows(): void
    {
        $columnIDs   = array_keys($this->headers);
        $rows        =& $this->rows;
        $rows['sum'] = [];

        foreach ($columnIDs as $columnID) {
            if ($columnID === 'method') {
                $rows['sum'][$columnID] = Text::_('SUM');
                continue;
            }

            $rows['sum'][$columnID] = 0;
        }

        foreach (Methods::resources() as $method) {
            $methodID        = $method['id'];
            $rows[$methodID] = [];

            foreach ($columnIDs as $columnID) {
                if ($columnID === 'method') {
                    $rows[$methodID][$columnID] = $method['name'];
                    continue;
                }

                $rows[$methodID][$columnID] = 0;
            }
        }
    }

    /**
     * Create rows for the sum and each individual method.
     *
     * @return void
     */
    private function participationRows(): void
    {
        $rows =& $this->rows;

        $template = match ($this->statistic) {
            //self::REGISTRATIONS => ['attended' => 0, 'no-shows' => 0, 'registered' => 0, 'unregistered' => 0],
            self::PRESENCE_TYPE => [
                Instances::HYBRID   => 0,
                Instances::ONLINE   => 0,
                Instances::PRESENCE => 0,
                'total'             => 0
            ],
            //self::CAPACITY => ['attended' => 0, 'capacity' => 0, 'total' => 0]
        };

        $columnIDs = array_keys($this->headers);

        foreach ($columnIDs as $columnID) {
            if ($columnID === 'week') {
                //$this->statistic === self::CAPACITY ? Text::_('AVERAGE') : Text::_('SUM');
                $rows['sum'][$columnID] = Text::_('SUM');
                continue;
            }

            $rows['sum'][$columnID] = $template;
        }

        $termID    = $this->state->get('list.termID');
        $startDate = Terms::startDate($termID);
        $endDate   = Terms::endDate($termID);

        for ($current = $startDate; $current < $endDate;) {
            $weekEndDate    = date('Y-m-d', strtotime('+7 days', strtotime($current)));
            $rows[$current] = [];

            foreach ($columnIDs as $columnID) {
                if ($columnID === 'week') {
                    $rows[$current][$columnID] = Dates::formatDate($current);
                    continue;
                }

                $rows[$current][$columnID] = $template;
            }

            $current = $weekEndDate;
        }
    }

    /**
     * Removed participation-related table columns and rows with no data to present.
     *
     * @param   array  $usedMondays
     * @param   array  $usedResources
     *
     * @return void
     */
    private function removePRUnused(array $usedMondays, array $usedResources): void
    {
        $columnKeys = array_keys($this->headers);
        $rows       =& $this->rows;

        // Unused Rows
        foreach (array_diff(array_keys($rows), $usedMondays) as $unusedMonday) {
            if (in_array($unusedMonday, ['headers', 'sum'])) {
                continue;
            }

            unset($rows[$unusedMonday]);
        }

        // Unused columns
        foreach ($unusedResources = array_diff($columnKeys, $usedResources) as $key => $value) {
            if (in_array($value, ['week', 'sum'])) {
                unset($unusedResources[$key]);
            }
        }

        foreach (array_keys($rows) as $monday) {
            foreach ($unusedResources as $unusedResource) {
                unset($this->headers[$unusedResource]);
                unset($rows[$monday][$unusedResource]);
            }
        }
    }

    /**
     * Adds a text describing the selected layout as a subtitle.
     * @return void modifies the course
     */
    protected function setSubtitle(): void
    {
        $termID    = $this->state->get('list.termID');
        $endDate   = Terms::endDate($termID);
        $endDate   = Dates::formatDate($endDate);
        $startDate = Terms::startDate($termID);
        $startDate = Dates::formatDate($startDate);

        $this->statistic = (int) $this->state->get('list.statistic', self::METHOD);

        $text = match ($this->statistic) {
            //self::CAPACITY => Text::sprintf('ORGANIZER_PRESENCE_USE_DESC', $startDate, $endDate),
            self::PRESENCE_TYPE => Text::sprintf('ORGANIZER_PLANNED_PRESENCE_TYPE_DESC', $startDate, $endDate),
            //self::REGISTRATIONS => Text::sprintf('ORGANIZER_REGISTRATIONS_DESC', $startDate, $endDate),
            default => Text::sprintf('ORGANIZER_METHOD_USE_DESC', $startDate, $endDate),
        };

        $this->subtitle = $text ? "<h4>$text</h4>" : $text;
    }
}
