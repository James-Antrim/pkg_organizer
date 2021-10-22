<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Organizer\Helpers;

class Equipment extends ListView{
    protected $rowStructure = [
        'checkbox'     => '',
        'code'         => 'link',
        'name_en'      => 'link',
    ];
    protected function authorize()
    {
        if (!Helpers\Can::manage('facilities'))
        {
            Helpers\OrganizerHelper::error(403);
        }
    }
    function setHeaders()
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');

        $headers   = [
            'checkbox'     => Helpers\HTML::_('grid.checkall'),
            'code'     => Helpers\HTML::sort('CODE', 'code', $direction, $ordering),
            'name_en' => Helpers\HTML::sort('NAME', 'name_en', $direction, $ordering),
            /*'roomType'     => Helpers\HTML::sort('TYPE', 'roomType', $direction, $ordering),
            'active'       => Helpers\Languages::_('ORGANIZER_ACTIVE')*/
        ];

        $this->headers = $headers;
        // TODO: Implement setHeaders() method.
    }
    protected function structureItems()
    {
        $link            = 'index.php?option=com_organizer&view=equipment_edit&id=';
        $index           = 0;
        $structuredItems = [];

        foreach ($this->items as $item)
        {
            $structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
            $index++;
        }
        $this->items = $structuredItems;
    }
}
