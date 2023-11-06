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
use THM\Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of course participants into the display context.
 */
class Participants extends ListView
{
    /**
     * @inheritdoc
     */
    protected function addToolBar(): void
    {
        if (Helpers\Can::administrate()) {
            $toolbar = Toolbar::getInstance();
            $toolbar->standardButton('merge', Text::_('MERGE'), 'MergeParticipants.display')->icon('fa fa-compress');
        }

        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->editLink = $options['query'] . $item->id;
        $item->fullName = $item->forename ? $item->fullName : $item->surname;
        $item->program  = $item->programID ? $item->program : '';
    }

    /**
     * @param   array  $options  *
     *
     * @inheritdoc
     */
    protected function completeItems(array $options = []): void
    {
        $options = ['query' => 'index.php?option=com_organizer&view=Participant&id='];
        parent::completeItems($options);
    }

    /**
     * @inheritdoc
     */
    protected function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');

        $headers = [
            'check'    => ['type' => 'check'],
            'fullName' => [
                //'link'       => ListItem::DIRECT, editing currently unavailable
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'fullName', $direction, $ordering),
                'type'       => 'value'
            ],
            'email'    => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('EMAIL', 'email', $direction, $ordering),
                'type'       => 'text'
            ],
            'program'  => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('PROGRAM', 'program', $direction, $ordering),
                'type'       => 'text'
            ],
        ];

        if ($courseID = Input::getFilterID('course') and $courseID !== -1) {
            $headers['status']   = [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('STATUS', 'status', $direction, $ordering),
                'type'       => 'value'
            ];
            $headers['paid']     = [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('PAID', 'paid', $direction, $ordering),
                'type'       => 'value'
            ];
            $headers['attended'] = [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('ATTENDED', 'attended', $direction, $ordering),
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

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        //parent::modifyDocument();

        //Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/modal.css');
    }
}
