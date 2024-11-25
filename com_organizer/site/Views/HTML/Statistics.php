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
use THM\Organizer\Helpers\{Categories, Dates, Methods, Organizations, Terms};

/**
 * Class loads statistical information about appointments into the display context.
 */
class Statistics extends TableView
{
    /** @inheritDoc */
    public function __construct(array $config)
    {
        $this->toDo[] = 'Add export functionality.';
        $this->toDo[] = 'Figure out a way to improve the text/tip handling of the column selection tool.';
        parent::__construct($config);
    }

    /** @inheritDoc */
    protected function completeItems(array $options = []): void
    {
        $columnKeys    = array_keys($this->headers);
        $rowKeys       = array_keys($this->rows);
        $rows          =& $this->rows;
        $usedMethods   = [];
        $usedResources = [];

        foreach ($this->items as $lesson) {
            $methodID    = $lesson->methodID;
            $resourceIDs = explode(',', $lesson->resourceIDs);

            if (!in_array($methodID, $rowKeys)) {
                continue;
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
        foreach ($unusedColumnKeys = array_diff($columnKeys, $usedResources) as $index => $value) {
            if (is_numeric($value)) {
                unset($this->headers[$value]);
            }
            else {
                unset($unusedColumnKeys[$index]);
            }
        }

        foreach (array_keys($rows) as $rowKey) {
            foreach ($unusedColumnKeys as $unusedColumnKey) {
                unset($rows[$rowKey][$unusedColumnKey]);
            }
        }
    }

    /** @inheritDoc */
    protected function initializeColumns(): void
    {
        $state = $this->state;

        $headers = [
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
                    'type'       => 'text'
                ]
        ];

        $resources = [];

        if ($categoryID = $state->get('list.categoryID')) {
            $type = 'groups';
            foreach (Categories::groups($categoryID) as $group) {
                $resources[$group['id']] = $group;
            }
        }
        elseif ($organizationID = $state->get('list.organizationID')) {
            $type = 'categories';
            foreach (Organizations::categories($organizationID) as $category) {
                $resources[$category['id']] = $category;
            }
        }
        else {
            $type = 'organizations';
            foreach (Organizations::resources() as $organization) {
                if (!$organization['active']) {
                    continue;
                }

                $resources[$organization['id']] = $organization;
            }
        }

        $condense = count($resources) > 8;
        asort($resources);

        foreach ($resources as $columnID => $resource) {
            $class = $condense ? 'w-5 d-md-table-cell' : 'w-10 d-md-table-cell';
            $title = $tip = '';

            switch ($type) {
                case 'categories':
                case 'groups':
                    if ($condense or strlen($resource['name']) > 25) {
                        $title = $resource['code'];
                        $tip   = $resource['name'];
                    }
                    else {
                        $title = $resource['name'];
                    }
                    break;
                case 'organizations':
                    $class = 'w-5 d-md-table-cell';
                    $title = $resource['abbreviation'];
                    $tip   = $resource['fullName'];
                    break;
            }

            $headers[$columnID] = [
                'properties' => ['class' => $class, 'scope' => 'col'],
                'tip'        => $tip,
                'title'      => $title,
                'type'       => 'text'
            ];
        }

        $this->headers = $headers;
    }

    /** @inheritDoc */
    protected function initializeRows(): void
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

    /** @inheritDoc */
    protected function setSubtitle(): void
    {
        $termID    = $this->state->get('list.termID');
        $endDate   = Dates::formatDate(Terms::endDate($termID));
        $startDate = Dates::formatDate(Terms::startDate($termID));

        $text = Text::sprintf('ORGANIZER_METHOD_USE_DESC', $startDate, $endDate);

        $this->subtitle = $text ? "<h4>$text</h4>" : $text;
    }
}
