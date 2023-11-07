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
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads persistent information a filtered set of degrees into the display context.
 */
class Degrees extends ListView
{
    /**
     * @inheritdoc
     */
    protected function addToolBar(): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('Degree.add');
        $toolbar->delete('Degrees.delete')->message(Text::_('DELETE_CONFIRM'));
        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->link = $options['query'] . $item->id;
    }

    /**
     * @inheritdoc
     */
    protected function completeItems(array $options = []): void
    {
        $options = ['query' => 'index.php?option=com_organizer&view=Degree&id=',];
        parent::completeItems($options);
    }

    /**
     * @inheritdoc
     */
    public function initializeColumns(): void
    {
        $ordering      = $this->state->get('list.ordering');
        $direction     = $this->state->get('list.direction');
        $this->headers = [
            'check'        => ['type' => 'check'],
            'name'         => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, $ordering),
                'type'       => 'value'
            ],
            'abbreviation' => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('ABBREVIATION', 'abbreviation', $direction, $ordering),
                'type'       => 'value'
            ],
            'code'         => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('DEGREE_CODE', 'code', $direction, $ordering),
                'type'       => 'value'
            ],
        ];
    }
}
