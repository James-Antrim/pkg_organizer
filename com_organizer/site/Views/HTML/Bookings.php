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
use THM\Organizer\Adapters\{Text, Toolbar};
use THM\Organizer\Layouts\HTML\ListItem;

/** @inheritDoc */
class Bookings extends ListView
{
    /** @inheritDoc */
    protected function addToolBar(): void
    {
        $this->toDo[] = 'Encapsulate the executed code in the controller clean function into a separate function.';
        $this->toDo[] = 'Add form and authentication checks to the public facing code in the clean function.';
        $this->toDo[] = 'The original clean call from organizer had true as a parameter.';

        $toolbar = Toolbar::getInstance();
        $toolbar->delete('bookings.clean', Text::_('CLEAN_BOOKINGS'));
        parent::addToolBar();
    }

    /** @inheritDoc */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        // Method stub assuming individualization.
    }

    /** @inheritDoc */
    public function initializeColumns(): void
    {
        $this->headers = [
            'check'   => ['type' => 'check'],
            'subject' => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('SUBJECT'),
                'type'       => 'text'
            ],
            'date'    => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('DATE'),
                'type'       => 'text'
            ],
            'times'   => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('TIMES'),
                'type'       => 'text'
            ],
            'status'  => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('STATUS'),
                'type'       => 'text'
            ],
        ];
    }
}
