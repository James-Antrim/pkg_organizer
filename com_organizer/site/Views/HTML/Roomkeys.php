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

use THM\Organizer\Adapters\{Application, Text, Toolbar};
use THM\Organizer\Helpers;

/**
 * Class loads a filtered set of buildings into the display context.
 */
class Roomkeys extends ListView
{
    protected $rowStructure = [
        'checkbox' => '',
        'name' => 'link',
        'key' => 'link',
        'useGroup' => 'link',
        'cleaningGroup' => 'link'
    ];

    /**
     * Adds a toolbar and title to the view.
     * @return void  sets context variables
     */
    protected function addToolBar(bool $delete = true)
    {
        $this->setTitle("ORGANIZER_ROOMKEYS");

        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'edit', Text::_('ORGANIZER_EDIT'), "Roomkeys.edit", true);
    }

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        if (!Helpers\Can::manage('facilities')) {
            Application::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    public function setHeaders()
    {
        $column    = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox' => '',
            'name' => Helpers\HTML::sort('NAME', 'name', $direction, $column),
            'rns' => Helpers\HTML::sort('RNS', 'rns', $direction, $column),
            'useGroup' => Helpers\HTML::sort('USE_GROUP', 'useGroup', $direction, $column),
            'cleaningGroup' => Helpers\HTML::sort('CLEANING_GROUP', 'cleaningGroup', $direction, $column)
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems()
    {
        $link            = 'index.php?option=com_organizer&view=RoomkeyEdit&id=';
        $index           = 0;
        $structuredItems = [];

        foreach ($this->items as $item) {
            $structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
