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

/**
 * Class loads persistent information a filtered set of fields (of expertise) into the display context.
 */
class Fields extends ListView
{
    protected array $rowStructure = ['checkbox' => '', 'name' => 'link', 'code' => 'link', 'colors' => 'value'];

    /**
     * @inheritdoc
     */
    protected function completeItems(): void
    {
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=field_edit&id=';
        $structuredItems = [];
        $organizationID  = (int) $this->state->get('filter.organizationID');

        foreach ($this->items as $item) {
            $item->colors = Helpers\Fields::getFieldColorDisplay($item->id, $organizationID);

            $structuredItems[$index] = $this->completeItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }

    /**
     * @inheritdoc
     */
    public function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');

        $headers = [
            'checkbox' => '',
            'name'     => HTML::sort('NAME', 'name', $direction, $ordering),
            'code'     => HTML::sort('CODE', 'code', $direction, $ordering),
            'colors'   => Text::_('COLORS')
        ];

        $this->headers = $headers;
    }
}
