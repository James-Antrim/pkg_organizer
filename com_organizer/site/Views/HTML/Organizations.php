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
 * Class loads persistent information a filtered set of organizations into the display context.
 */
class Organizations extends ListView
{
    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        // Schedule access is a
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('Organization.add');
        $toolbar->delete('Organizations.delete')->message(Text::_('DELETE_CONFIRM'));

        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->editLink = $options['query'] . $item->id;
    }

    /**
     * @inheritDoc
     */
    protected function completeItems(array $options = []): void
    {
        $options = ['query' => 'index.php?option=com_organizer&view=Organization&id='];
        parent::completeItems($options);
    }

    /**
     * @inheritdoc
     */
    public function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'check'     => ['type' => 'check'],
            'shortName' => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('SHORT_NAME', 'shortName', $direction, $ordering),
                'type'       => 'value'
            ],
            'name'      => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, $ordering),
                'type'       => 'value'
            ],
        ];

        $this->headers = $headers;
    }
}
