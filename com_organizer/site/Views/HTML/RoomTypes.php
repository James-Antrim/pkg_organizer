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
        $this->setTitle('ROOM_TYPES');
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('RoomTypes.add');

        // Trust isn't there for this yet.
        if (Can::administrate()) {
            $toolbar->delete('RoomTypes.delete')->message(Text::_('DELETE_CONFIRM'))->listCheck(true);

            if (Application::backend()) {
                $toolbar->preferences('com_organizer');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');
        $headers   = [
            'check'   => ['type' => 'check'],
            'name'    => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, 'name'),
                'type'       => 'value'
            ],
            'rns'     => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ROOM_KEY'),
                'type'       => 'text'
            ],
            'useCode' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('USE_CODE_TEXT'),
                'type'       => 'text'
            ],
        ];

        $this->headers = $headers;
    }
}
