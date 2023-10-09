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

/**
 * Class loads persistent information a filtered set of fields (of expertise) into the display context.
 */
class Fields extends ListView
{
    protected $rowStructure = ['checkbox' => '', 'name' => 'link', 'code' => 'link', 'colors' => 'value'];

    /**
     * @inheritdoc
     */
    public function setHeaders()
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');

        $headers = [
            'checkbox' => '',
            'name' => Helpers\HTML::sort('NAME', 'name', $direction, $ordering),
            'code' => Helpers\HTML::sort('CODE', 'code', $direction, $ordering),
            'colors' => Helpers\Languages::_('ORGANIZER_COLORS')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems()
    {
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=field_edit&id=';
        $structuredItems = [];
        $organizationID  = (int) $this->state->get('filter.organizationID');

        foreach ($this->items as $item) {
            $item->colors = Helpers\Fields::getFieldColorDisplay($item->id, $organizationID);

            $structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
