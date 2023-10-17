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

use THM\Organizer\Adapters\{Application, Input, Text, Toolbar};
use THM\Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of rooms into the display context.
 */
class Rooms extends ListView
{
    protected array $rowStructure = [
        'checkbox' => '',
        'roomName' => 'link',
        'buildingName' => 'link',
        'roomType' => 'link',
        'active' => 'value'
    ];

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $title = Text::_('ORGANIZER_ROOMS');

        if ($campusID = Input::getInt('campusID')) {
            $title .= ': ' . Text::_('ORGANIZER_CAMPUS');
            $title .= ' ' . Helpers\Campuses::getName($campusID);
        }
        $this->setTitle($title);

        if (Helpers\Can::manage('facilities')) {
            $toolbar = Toolbar::getInstance();
            $toolbar->appendButton('Standard', 'new', Text::_('ORGANIZER_ADD'), 'rooms.add', false);
            $toolbar->appendButton('Standard', 'edit', Text::_('ORGANIZER_EDIT'), 'rooms.edit', true);
            $toolbar->appendButton('Standard', 'eye-open', Text::_('ORGANIZER_ACTIVATE'), 'rooms.activate', false);
            $toolbar->appendButton('Standard', 'eye-close', Text::_('ORGANIZER_DEACTIVATE'), 'rooms.deactivate', false);

            if (Helpers\Can::administrate()) {
                $toolbar->appendButton('Standard', 'contract', Text::_('ORGANIZER_MERGE'), 'rooms.mergeView', true);
            }

            $toolbar->appendButton('NewTab', 'file-xls', Text::_('ORGANIZER_UNINOW_EXPORT'), 'Rooms.UniNow', false);
        }
    }

    /**
     * @inheritdoc
     */
    protected function authorize(): void
    {
        if (Application::backend() and !Helpers\Can::manage('facilities')) {
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
            'checkbox' => Helpers\HTML::_('grid.checkall'),
            'roomName' => Helpers\HTML::sort('NAME', 'roomName', $direction, $ordering),
            'buildingName' => Helpers\HTML::sort('BUILDING', 'buildingName', $direction, $ordering),
            'roomType' => Helpers\HTML::sort('TYPE', 'roomType', $direction, $ordering),
            'active' => Text::_('ORGANIZER_ACTIVE')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems(): void
    {
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=room_edit&id=';
        $structuredItems = [];

        foreach ($this->items as $item) {
            $tip          = $item->active ? 'ORGANIZER_CLICK_TO_DEACTIVATE' : 'ORGANIZER_CLICK_TO_ACTIVATE';
            $item->active = $this->getToggle('rooms', $item->id, $item->active, $tip, 'active');

            $structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
