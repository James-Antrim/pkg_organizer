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

use THM\Organizer\Adapters\HTML;
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads a filtered set of buildings into the display context.
 */
class RoomKeys extends ListView
{
    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $column    = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'check'         => ['type' => 'check'],
            'name'          => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, $column),
                'type'       => 'value'
            ],
            'rns'           => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('RNS', 'rns', $direction, $column),
                'type'       => 'text'
            ],
            'useGroup'      => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('USE_GROUP', 'useGroup', $direction, $column),
                'type'       => 'text'
            ],
            'cleaningGroup' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('CLEANING_GROUP', 'cleaningGroup', $direction, $column),
                'type'       => 'text'
            ],
        ];

        $this->headers = $headers;
    }
}
