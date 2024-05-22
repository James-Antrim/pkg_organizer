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

use THM\Organizer\Adapters\{HTML, Text};
use stdClass;
use THM\Organizer\Helpers;
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads a filtered set of campuses into the display context.
 */
class Campuses extends ListView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(): void
    {
        $this->addBasicButtons();
        parent::addToolBar();
    }

    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        if ($item->parentID) {
            $item->name = HTML::icon('fa fa-long-arrow-alt-right') . " $item->name";
        }

        $address = '';

        if ($item->address or $item->city or $item->zipCode) {
            $addressParts   = [];
            $addressParts[] = empty($item->address) ? empty($item->parentAddress) ? '' : $item->parentAddress : $item->address;
            $addressParts[] = empty($item->city) ? empty($item->parentCity) ? '' : $item->parentCity : $item->city;
            $addressParts[] = empty($item->zipCode) ? empty($item->parentZIPCode) ? '' : $item->parentZIPCode : $item->zipCode;
            $address        = implode(' ', $addressParts);
        }

        $item->address  = $address;
        $item->location = Helpers\Campuses::getPin($item->location);

        if (empty($item->gridName)) {
            $gridName = empty($item->parentGridName) ? Text::_('NONE_GIVEN') : $item->parentGridName;
        }
        else {
            $gridName = $item->gridName;
        }

        $item->gridID = $gridName;
    }

    /**
     * @inheritDoc
     */
    protected function completeItems(array $options = []): void
    {
        parent::completeItems($options);

        uasort($this->items, [$this, 'compare']);
    }

    /**
     * Compares the long names of the campuses for sorting.
     *
     * @param   stdClass  $item1  the first item
     * @param   stdClass  $item2  the second item
     *
     * @return int -1 if $item1 is before $item2; 1 if $item1 is after $item2
     */
    private function compare(stdClass $item1, stdClass $item2): int
    {
        if (empty($item1->parentName)) {

            // Both are cities
            if (empty($item2->parentName)) {
                return strcmp($item1->name, $item2->name);
            }

            // Thing 1 is a city
            return strcmp($item1->name, $item2->parentName);
        }
        // Thing 2 is a city
        elseif (empty($item2->parentName)) {
            return strcmp($item1->parentName, $item2->name);
        }

        // Different cities
        if ($parentDiff = strcmp($item1->parentName, $item2->parentName)) {
            return $parentDiff;
        }

        // Same city
        return strcmp($item1->name, $item2->name);
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $this->headers = [
            'check'    => ['type' => 'check'],
            'name'     => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('NAME'),
                'type'       => 'text'
            ],
            'address'  => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('STREET'),
                'type'       => 'text'
            ],
            'location' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('LOCATION'),
                'type'       => 'text'
            ],
            'gridID'   => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('GRID'),
                'type'       => 'text'
            ],
        ];
    }
}
