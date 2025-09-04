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
use THM\Organizer\Helpers\{Campuses, Can, Rooms as Helper};
use THM\Organizer\Layouts\HTML\Row;

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
        $this->toDo[] = 'Add filter for lessons.';
        $this->toDo[] = 'Apply the lesson filter automatically frontend.';

        $title = Text::_('ROOMS');

        if ($campusID = Input::integer('campusID')) {
            $title .= ': ' . Text::_('CAMPUS');
            $title .= ' ' . Campuses::name($campusID);
        }
        $this->title($title);

        if (Can::fm()) {
            $admin   = Can::administrate();
            $toolbar = Toolbar::getInstance();
            $toolbar->addNew('Rooms.add');

            if ($admin) {
                $toolbar->addNew('rooms.import', Text::_('IMPORT'))->icon('fa fa-upload');
            }

            $this->addActa();
            $this->addMerge();

            if ($admin) {
                $this->addDelete();
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
        $item->active = HTML::toggle($index, Helper::ACTIVE_STATES[$item->active], 'Rooms');
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
                'link'       => Row::DIRECT,
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
