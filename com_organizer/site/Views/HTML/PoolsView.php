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

use THM\Organizer\Adapters\{Application, HTML, Text};
use THM\Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of (subject) pools into the display context.
 */
abstract class PoolsView extends ListView
{
    protected array $rowStructure = ['checkbox' => '', 'name' => 'link', 'programID' => 'link'];

    /**
     * @inheritdoc
     */
    protected function authorize(): void
    {
        if (!Helpers\Can::documentTheseOrganizations()) {
            Application::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    public function setHeaders(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox'  => HTML::checkAll(),
            'name'      => HTML::sort('NAME', 'name', $direction, $ordering),
            'programID' => Text::_('PROGRAM')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems(): void
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
