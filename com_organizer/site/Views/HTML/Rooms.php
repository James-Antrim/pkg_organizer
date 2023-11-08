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

use THM\Organizer\Adapters\{Application, HTML, Input, Text, Toolbar};
use THM\Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of rooms into the display context.
 */
class Rooms extends ListView
{
    use Activated;
    use Merged;

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $title = Text::_('ROOMS');

        if ($campusID = Input::getInt('campusID')) {
            $title .= ': ' . Text::_('CAMPUS');
            $title .= ' ' . Helpers\Campuses::getName($campusID);
        }
        $this->setTitle($title);

        if (Helpers\Can::manage('facilities')) {
            $toolbar = Toolbar::getInstance();
            $toolbar->addNew('Room.add');
            $this->addActa();

            if (Helpers\Can::administrate()) {
                $this->addMerge();
                $toolbar->delete('Rooms.delete')->message(Text::_('DELETE_CONFIRM'));
            }

            $toolbar->appendButton('NewTab', 'file-xls', Text::_('UNINOW_EXPORT'), 'Rooms.UniNow', false);
        }
    }

    /**
     * @inheritdoc
     */
    protected function completeItems(): void
    {
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=room_edit&id=';
        $structuredItems = [];

        foreach ($this->items as $item) {
            $tip          = $item->active ? 'ORGANIZER_CLICK_TO_DEACTIVATE' : 'ORGANIZER_CLICK_TO_ACTIVATE';
            $item->active = $this->getToggle('rooms', $item->id, $item->active, $tip, 'active');

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
            'checkbox'     => HTML::checkAll(),
            'roomName'     => HTML::sort('NAME', 'roomName', $direction, $ordering),
            'buildingName' => HTML::sort('BUILDING', 'buildingName', $direction, $ordering),
            'roomType'     => HTML::sort('TYPE', 'roomType', $direction, $ordering),
            'active'       => Text::_('ACTIVE')
        ];

        $this->headers = $headers;
    }
}
