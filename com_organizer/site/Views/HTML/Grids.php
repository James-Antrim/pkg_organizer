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

use THM\Organizer\Adapters\Text;
use THM\Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of schedule grids into the display context.
 */
class Grids extends ListView
{
    protected $rowStructure = [
        'checkbox' => '',
        'name' => 'link',
        'startDay' => 'value',
        'endDay' => 'value',
        'startTime' => 'value',
        'endTime' => 'value',
        'isDefault' => 'value'
    ];

    /**
     * @inheritdoc
     */
    public function setHeaders()
    {
        $headers = [
            'checkbox' => '',
            'name' => Text::_('ORGANIZER_NAME'),
            'startDay' => Text::_('ORGANIZER_START_DAY'),
            'endDay' => Text::_('ORGANIZER_END_DAY'),
            'startTime' => Text::_('ORGANIZER_START_TIME'),
            'endTime' => Text::_('ORGANIZER_END_TIME'),
            'isDefault' => Text::_('ORGANIZER_DEFAULT')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems()
    {
        $index           = 0;
        $structuredItems = [];
        $link            = "index.php?option=com_organizer&view=grid_edit&id=";

        foreach ($this->items as $item) {
            $grid = json_decode($item->grid, true);

            if (!empty($grid['periods'])) {
                // 'l' (lowercase L) in date function for full textual day of the week.
                $startDayConstant = strtoupper(date('l', strtotime("Sunday + {$grid['startDay']} days")));
                $endDayConstant   = strtoupper(date('l', strtotime("Sunday + {$grid['endDay']} days")));

                $item->startDay  = Text::_($startDayConstant);
                $item->endDay    = Text::_($endDayConstant);
                $item->startTime = Helpers\Dates::formatTime(reset($grid['periods'])['startTime']);
                $item->endTime   = Helpers\Dates::formatTime(end($grid['periods'])['endTime']);
            } else {
                $item->startDay  = '';
                $item->endDay    = '';
                $item->startTime = '';
                $item->endTime   = '';
            }

            $item->isDefault         = $this->getToggle('grids', $item->id, $item->isDefault, 'ORGANIZER_GRID_DESC');
            $structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
