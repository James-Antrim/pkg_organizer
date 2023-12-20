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
 * Class loads a filtered set of buildings into the display context.
 */
class RoomKeys extends ListView
{

    /**
     * @inheritDoc
     * ListView adds the title and configuration button if user has access. Inheriting classes are responsible for
     * their own buttons.
     */
    protected function addToolBar(): void
    {
        // MVC name identity is now the internal standard
        $this->setTitle('ROOM_KEYS');

        if (Application::backend() and Can::administrate()) {
            $toolbar = Toolbar::getInstance();
            $toolbar->preferences('com_organizer');
        }
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');
        $headers   = [
            'check'         => ['type' => 'check'],
            'name'          => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, 'name'),
                'type'       => 'value'
            ],
            'rns'           => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('RNS'),
                'type'       => 'text'
            ],
            'useGroup'      => [
                'properties' => ['class' => 'w-15 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('USE_GROUP'),
                'type'       => 'text'
            ],
            'cleaningGroup' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('CLEANING_GROUP'),
                'type'       => 'text'
            ],
        ];

        $this->headers = $headers;
    }
}
