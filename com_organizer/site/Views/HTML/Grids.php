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
use THM\Organizer\Adapters\Text;
use THM\Organizer\Adapters\Toolbar;
use THM\Organizer\Helpers;
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads persistent information a filtered set of schedule grids into the display context.
 */
class Grids extends ListView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('Grids.add');
        $toolbar->delete('Grids.delete')->message(Text::_('DELETE_CONFIRM'));
        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $grid = json_decode($item->grid, true);

        if (!empty($grid['periods'])) {
            // 'l' (lowercase L) in date function for full textual day of the week.
            $startDayConstant = strtoupper(date('l', strtotime("Sunday + {$grid['startDay']} days")));
            $endDayConstant   = strtoupper(date('l', strtotime("Sunday + {$grid['endDay']} days")));

            $item->startDay  = Text::_($startDayConstant);
            $item->endDay    = Text::_($endDayConstant);
            $item->startTime = Helpers\Dates::formatTime(reset($grid['periods'])['startTime']);
            $item->endTime   = Helpers\Dates::formatTime(end($grid['periods'])['endTime']);
        }
        else {
            $item->startDay  = '';
            $item->endDay    = '';
            $item->startTime = '';
            $item->endTime   = '';
        }

        $item->isDefault = $this->getToggle('grids', $item->id, $item->isDefault, 'ORGANIZER_GRID_DESC');
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $this->headers = [
            'check'     => ['type' => 'check'],
            'name'      => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('NAME'),
                'type'       => 'value'
            ],
            'startDay'  => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('START_DAY'),
                'type'       => 'text'
            ],
            'endDay'    => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('END_DAY'),
                'type'       => 'text'
            ],
            'startTime' => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('START_TIME'),
                'type'       => 'text'
            ],
            'endTime'   => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('END_TIME'),
                'type'       => 'text'
            ],
            'isDefault' => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('DEFAULT'),
                'type'       => 'value'
            ],
        ];
    }
}
