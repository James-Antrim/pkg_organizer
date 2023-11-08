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

use THM\Organizer\Adapters\{Application, HTML, Text, Toolbar};
use THM\Organizer\Helpers;

/**
 * Class loads a filtered set of buildings into the display context.
 */
class Roomkeys extends ListView
{
    /**
     * @inheritdoc
     */
    protected function completeItems(): void
    {
        $link            = 'index.php?option=com_organizer&view=RoomkeyEdit&id=';
        $index           = 0;
        $structuredItems = [];

        foreach ($this->items as $item) {
            $structuredItems[$index] = $this->completeItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }

    /**
     * @inheritdoc
     */
    public function initializeColumns(): void
    {
        $column    = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox'      => '',
            'name'          => HTML::sort('NAME', 'name', $direction, $column),
            'rns'           => HTML::sort('RNS', 'rns', $direction, $column),
            'useGroup'      => HTML::sort('USE_GROUP', 'useGroup', $direction, $column),
            'cleaningGroup' => HTML::sort('CLEANING_GROUP', 'cleaningGroup', $direction, $column)
        ];

        $this->headers = $headers;
    }
}
