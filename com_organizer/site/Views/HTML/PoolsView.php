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
    protected function addToolBar(bool $delete = true): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('Pool.add');
        $toolbar->delete('Pools.delete')->message(Text::_('DELETE_CONFIRM'));

        parent::addToolBar();
    }

    /**
     * @inheritdoc
     */
    protected function completeItems(): void
    {
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=pool_edit&id=';
        $structuredItems = [];

        foreach ($this->items as $item) {
            $item->programID         = Helpers\Pools::programName($item->id);
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
        $headers   = [
            'checkbox'  => HTML::checkAll(),
            'name'      => HTML::sort('NAME', 'name', $direction, $ordering),
            'programID' => Text::_('PROGRAM')
        ];

        $this->headers = $headers;
    }
}
