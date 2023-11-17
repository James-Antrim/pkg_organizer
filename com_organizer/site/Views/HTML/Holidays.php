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
use THM\Organizer\Helpers\{Can, Dates, Holidays as Helper};
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads persistent information a filtered set of holidays into the display context.
 */
class Holidays extends ListView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('Holiday.add');
        $toolbar->delete('Holidays.delete')->message(Text::_('DELETE_CONFIRM'));
        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->dates = Dates::getDisplay($item->startDate, $item->endDate);

        if (!$this->state->get('filter.termID')) {
            $item->name .= " ($item->term)";
        }

        $item->status = $item->endDate < $options['today'] ? Text::_('EXPIRED') : Text::_('CURRENT');
        $item->type   = Text::_($options['map'][$item->type]);
    }

    /**
     * @inheritDoc
     */
    protected function completeItems(array $options = []): void
    {
        $options = [
            'map'   => [
                Helper::GAP     => 'GAP_DAYS',
                Helper::CLOSED  => 'CLOSED_DAYS',
                Helper::HOLIDAY => 'HOLIDAYS'
            ],
            'today' => date('Y-m-d'),
        ];
        parent::completeItems($options);
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');

        $this->headers = [
            'check'  => ['type' => 'check'],
            'name'   => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, $ordering),
                'type'       => 'value'
            ],
            'dates'  => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('DATES'),
                'type'       => 'text'
            ],
            'type'   => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('TYPE'),
                'type'       => 'text'
            ],
            'status' => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('STATUS'),
                'type'       => 'text'
            ],
        ];
    }
}