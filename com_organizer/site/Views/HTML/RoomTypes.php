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
use THM\Organizer\Adapters\{HTML, Text};
use THM\Organizer\Helpers\{Can, RoomTypes as Helper};
use THM\Organizer\Layouts\HTML\Row;

/**
 * Class loads persistent information a filtered set of room types into the display context.
 */
class RoomTypes extends ListView
{
    use Suppressed;

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $this->toDo[] = 'Remove tags added per default by the description type having been editor.';

        $this->setTitle('ROOM_TYPES');
        $this->addAdd();
        $this->addSuppression();

        // Trust isn't there for this yet.
        if (Can::administrate()) {
            $this->addDelete();
        }

        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->suppress = HTML::toggle($index, Helper::SUPPRESSION_STATES[$item->suppress], 'RoomTypes');
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');
        $headers   = [
            'check'    => ['type' => 'check'],
            'name'     => [
                'link'       => Row::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, 'name'),
                'type'       => 'value'
            ],
            'rns'      => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ROOM_KEY'),
                'type'       => 'text'
            ],
            'useCode'  => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('USE_CODE_TEXT'),
                'type'       => 'text'
            ],
            'suppress' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('SHOWN'),
                'type'       => 'text'
            ],
        ];

        $this->headers = $headers;
    }
}
