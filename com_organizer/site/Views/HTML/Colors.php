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
use THM\Organizer\Adapters\{HTML, Text, Toolbar};
use THM\Organizer\Helpers;
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads persistent information a filtered set of colors into the display context.
 */
class Colors extends ListView
{
    /**
     * @inheritdoc
     */
    protected function addToolBar(): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('Color.add');
        $toolbar->delete('Colors.delete')->message(Text::_('DELETE_CONFIRM'));
        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->color = Helpers\Colors::getListDisplay($item->color, $item->id);
    }

    /**
     * @inheritdoc
     */
    protected function completeItems(array $options = []): void
    {
        $options = ['query' => 'index.php?option=com_organizer&view=Color&id='];
        parent::completeItems($options);
    }

    /**
     * @inheritdoc
     */
    public function initializeColumns(): void
    {
        $direction     = $this->state->get('list.direction');
        $this->headers = [
            'check' => ['type' => 'check'],
            'name'  => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, 'name'),
                'type'       => 'value'
            ],
            'color' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('COLOR'),
                'type'       => 'text'
            ],
        ];
    }
}
