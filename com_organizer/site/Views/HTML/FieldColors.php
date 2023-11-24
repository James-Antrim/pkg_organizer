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
use THM\Organizer\Helpers\{Can, Colors};
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads persistent information a filtered set of fields (of expertise) into the display context.
 */
class FieldColors extends ListView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        // Divergent naming scheme
        Toolbar::setTitle('FIELD_COLORS');

        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('FieldColors.add')->icon('fa fa-link');
        $toolbar->delete('FieldColors.delete')->message(Text::_('DELETE_CONFIRM'));

        $toolbar->standardButton('newField', Text::_('FIELD_NEW'), 'Fields.add')->icon('fa fa-lightbulb');
        $toolbar->standardButton('newColor', Text::_('COLOR_NEW'), 'Colors.add')->icon('fa fa-palette');


        if (Can::administrate()) {
            $toolbar = Toolbar::getInstance();
            $toolbar->preferences('com_organizer');
        }
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->color = Colors::getListDisplay($item->color, $item->colorID);
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');

        $this->headers = [
            'check'        => ['type' => 'check'],
            'field'        => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('FIELD', 'field', $direction, $ordering),
                'type'       => 'text'
            ],
            'organization' => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('ORGANIZATION', 'organization', $direction, $ordering),
                'type'       => 'text'
            ],
            'color'        => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('COLOR'),
                'type'       => 'value'
            ],
        ];
    }
}
