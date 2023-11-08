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
use stdClass;
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads a filtered set of buildings into the display context.
 */
class CleaningGroups extends ListView
{
    /**
     * @inheritdoc
     */
    protected function addToolBar(): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('CleaningGroup.add');
        $toolbar->delete('CleaningGroups.delete')->message(Text::_('DELETE_CONFIRM'));
        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $tip = 'CLICK_TO_MARK_';
        $tip .= $item->relevant ? 'IRRELEVANT' : 'RELEVANT';

        $item->days      = $item->days === '0.00' ? '-' : $item->days;
        $item->link      = $options['query'] . $item->id;
        $item->relevant  = $this->getToggle('CleaningGroups', $item->id, $item->relevant, $tip);
        $item->valuation = $item->valuation === '0.00' ? '-' : $item->valuation;
    }

    /**
     * @inheritdoc
     */
    protected function completeItems(array $options = []): void
    {
        $options = ['query' => 'index.php?option=com_organizer&view=CleaningGroup&id='];
        parent::completeItems($options);
    }

    /**
     * @inheritdoc
     */
    public function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');
        $headers   = [
            'check'     => ['type' => 'check'],
            'name'      => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, 'name'),
                'type'       => 'value'
            ],
            'days'      => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('CLEANING_DAYS_PER_MONTH'),
                'type'       => 'text'
            ],
            'valuation' => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('CALCULATED_SURFACE_PERFORMANCE_VALUE'),
                'type'       => 'text'
            ],
            'relevant'  => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('COST_ACCOUNTING'),
                'type'       => 'value'
            ],
        ];

        $this->headers = $headers;
    }
}
