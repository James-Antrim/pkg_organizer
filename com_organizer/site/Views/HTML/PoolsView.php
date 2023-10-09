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

use Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of (subject) pools into the display context.
 */
abstract class PoolsView extends ListView
{
    protected $rowStructure = ['checkbox' => '', 'name' => 'link', 'programID' => 'link'];

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        if (!Helpers\Can::documentTheseOrganizations()) {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    public function setHeaders()
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox' => Helpers\HTML::_('grid.checkall'),
            'name' => Helpers\HTML::sort('NAME', 'name', $direction, $ordering),
            'programID' => Helpers\Languages::_('ORGANIZER_PROGRAM')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems()
    {
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=pool_edit&id=';
        $structuredItems = [];

        foreach ($this->items as $item) {
            $item->programID         = Helpers\Pools::getProgramName($item->id);
            $structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
