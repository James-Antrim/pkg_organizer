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

use THM\Organizer\Adapters\{HTML, Text, Toolbar};
use THM\Organizer\Helpers\Can;
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads persistent information a filtered set of room types into the display context.
 */
class RoomTypes extends ListView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('RoomTypes.add');

        // Trust isn't there for this yet.
        if (Can::administrate()) {
            $toolbar->delete('RoomTypes.delete')->message(Text::_('DELETE_CONFIRM'));
        }

        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'check'   => ['type' => 'check'],
            'name'    => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, $ordering),
                'type'       => 'value'
            ],
            'rns'     => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('ROOMKEY', 'rns', $direction, $ordering),
                'type'       => 'text'
            ],
            'useCode' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('USE_CODE_TEXT', 'useCode', $direction, $ordering),
                'type'       => 'text'
            ],
        ];

        $this->headers = $headers;
    }
}
