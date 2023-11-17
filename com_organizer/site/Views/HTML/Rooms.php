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

use THM\Organizer\Adapters\{HTML, Input, Text, Toolbar};
use stdClass;
use THM\Organizer\Buttons\FormTarget;
use THM\Organizer\Helpers;
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads persistent information a filtered set of rooms into the display context.
 */
class Rooms extends ListView
{
    use Activated;
    use Merged;

    /**
     * @inheritDoc
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

            $export = new FormTarget('export', Text::_('UNINOW_EXPORT'));
            $export->icon('fa fa-file-excel')->task('Rooms.UniNow');
            $toolbar->appendButton($export);
        }
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $tip = $item->active ? 'CLICK_TO_DEACTIVATE' : 'CLICK_TO_ACTIVATE';

        $item->active = $this->getToggle('rooms', $item->id, $item->active, $tip, 'active');
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'check'        => ['type' => 'check'],
            'roomName'     => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-none d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'roomName', $direction, $ordering),
                'type'       => 'value'
            ],
            'buildingName' => [
                'properties' => ['class' => 'w-10 d-none d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('BUILDING', 'buildingName', $direction, $ordering),
                'type'       => 'text'
            ],
            'roomType'     => [
                'properties' => ['class' => 'w-10 d-none d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('TYPE', 'roomType', $direction, $ordering),
                'type'       => 'text'
            ],
            'active'       => [
                'properties' => ['class' => 'w-5 d-none d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ACTIVE'),
                'type'       => 'value'
            ],
        ];

        $this->headers = $headers;
    }
}
