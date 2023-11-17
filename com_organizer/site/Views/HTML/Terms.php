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
use THM\Organizer\Helpers\Dates;
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads persistent information a filtered set of degrees into the display context.
 */
class Terms extends ListView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('Term.add');
        $toolbar->delete('Terms.delete')->message(Text::_('DELETE_CONFIRM'));
        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->endDate   = Dates::formatDate($item->endDate);
        $item->startDate = Dates::formatDate($item->startDate);
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $this->headers = [
            'check'     => ['type' => 'check'],
            'term'      => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('NAME'),
                'type'       => 'value'
            ],
            'startDate' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('START_DATE'),
                'type'       => 'text'
            ],
            'endDate'   => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('END_DATE'),
                'type'       => 'text'
            ],
        ];
    }
}
