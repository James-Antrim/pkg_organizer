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

use THM\Organizer\Adapters\{HTML, Text};
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Holidays as Helper;

/**
 * Class loads persistent information a filtered set of holidays into the display context.
 */
class Holidays extends ListView
{
    /**
     * @inheritDoc
     */
    protected function completeItems(): void
    {
        $index   = 0;
        $link    = 'index.php?option=com_organizer&view=holiday_edit&id=';
        $items   = [];
        $typeMap = [
            Helper::GAP     => 'ORGANIZER_GAP_DAYS',
            Helper::CLOSED  => 'ORGANIZER_CLOSED_DAYS',
            Helper::HOLIDAY => 'ORGANIZER_HOLIDAYS'
        ];

        foreach ($this->items as $item) {
            $today = date('Y-m-d');

            $dateString = Helpers\Dates::getDisplay($item->startDate, $item->endDate);
            $name       = $item->name;

            if (!$this->state->get('filter.termID')) {
                $name .= ". ($item->term)";
            }

            $status = $item->endDate < $today ? Text::_('ORGANIZER_EXPIRED') : Text::_('ORGANIZER_CURRENT');
            $type   = $typeMap[$item->type];

            $thisLink      = $link . $item->id;
            $items[$index] = [
                'checkbox' => HTML::checkBox($index, $item->id),
                'name'     => HTML::link($thisLink, $name),
                'dates'    => HTML::link($thisLink, $dateString),
                'type'     => HTML::link($thisLink, $type),
                'status'   => HTML::link($thisLink, $status)
            ];

            $index++;
        }

        $this->items = $items;
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');

        $headers = [
            'checkbox' => '',
            'name'     => HTML::sort('NAME', 'name', $direction, $ordering),
            'dates'    => Text::_('ORGANIZER_DATES'),
            'type'     => Text::_('ORGANIZER_TYPE'),
            'status'   => Text::_('ORGANIZER_STATUS')
        ];

        $this->headers = $headers;
    }
}