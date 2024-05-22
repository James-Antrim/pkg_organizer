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

use stdClass;
use THM\Organizer\Adapters\{HTML, Input, Text};
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads persistent information a filtered set of course participants into the display context.
 */
class Participants extends ListView
{
    use Merged;

    /**
     * @inheritDoc
     */
    protected function addToolBar(): void
    {
        $this->addMerge();
        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->fullName = $item->forename ? $item->fullName : $item->surname;
        $item->program  = $item->programID ? $item->program : '';
    }

    /**
     * @inheritDoc
     */
    protected function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');

        $headers = [
            'check'    => ['type' => 'check'],
            'fullName' => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'fullName', $direction, $ordering),
                'type'       => 'value'
            ],
            'email'    => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('EMAIL'),
                'type'       => 'text'
            ],
            'program'  => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('PROGRAM'),
                'type'       => 'text'
            ],
        ];

        if ($courseID = Input::getFilterID('courseID') and $courseID !== -1) {
            $headers['status']   = [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('STATUS'),
                'type'       => 'value'
            ];
            $headers['paid']     = [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('PAID'),
                'type'       => 'value'
            ];
            $headers['attended'] = [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ATTENDED'),
                'type'       => 'value'
            ];
        }
        else {
            $headers['registerDate'] = [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('REGISTRATION_DATE', 'registerDate', $direction, $ordering),
                'type'       => 'text'
            ];
        }

        $this->headers = $headers;
    }
}
