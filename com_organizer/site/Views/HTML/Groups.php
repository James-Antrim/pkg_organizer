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
use THM\Organizer\Adapters\{HTML, Text, Toolbar};
use THM\Organizer\Helpers\{Can, Grids, Groups as Helper, Terms};
use THM\Organizer\Layouts\HTML\ListItem;
use THM\Organizer\Tables\GroupPublishing;

/**
 * Class loads persistent information a filtered set of (scheduled subject) pools into the display context.
 */
class Groups extends ListView
{
    use Activated;
    use Merged;

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        // Resource creation occurs in Untis and editing is done via links in the list.

        $toolbar = Toolbar::getInstance();

        /*$if          = "alert('" . Text::_('ORGANIZER_LIST_SELECTION_WARNING', true) . "');";
        $else        = "jQuery('#modal-publishing').modal('show'); return true;";
        $script      = 'onclick="if(document.adminForm.boxchecked.value==0){' . $if . '}else{' . $else . '}"';
        $batchButton = '<button id="group-publishing" data-toggle="modal" class="btn btn-small" ' . $script . '>';

        $batchButton .= '<span class="icon-stack" title="' . $title . '"></span>' . " $title";

        $batchButton .= '</button>';*/

        $toolbar->popupButton('batch', Text::_('BATCH'));
        $this->addActa();

        if (Can::administrate()) {
            $this->addMerge();
            $toolbar->standardButton('publish-expired', Text::_('PUBLISH_EXPIRED_TERMS'), 'Groups.publishPast')
                ->icon('fa fa-reply-all');
        }

        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->active = HTML::toggle($index, Helper::activeStates[$item->active], 'Groups');
        $item->grid   = Grids::getName($item->gridID);

        $publishing   = new GroupPublishing();
        $keys         = ['groupID' => $item->id, 'termID' => $options['currentID']];
        $currentValue = $publishing->load($keys) ? $publishing->published : 1;
        $item->this   = HTML::toggle($index, Helper::publishStates[$currentValue], 'Groups', $options['currentID']);

        $publishing     = new GroupPublishing();
        $keys['termID'] = $options['nextID'];
        $nextValue      = $publishing->load($keys) ? $publishing->published : 1;
        $item->next     = HTML::toggle($index, Helper::publishStates[$nextValue], 'Groups', $options['nextID']);
    }

    /**
     * @inheritDoc
     */
    protected function completeItems(array $options = []): void
    {
        $options = ['currentID' => Terms::getCurrentID(), 'nextID' => Terms::getNextID()];
        parent::completeItems($options);
    }

    /**
     * @inheritDoc
     */
    protected function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');
        $headers   = [
            'check'    => ['type' => 'check'],
            'fullName' => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('FULL_NAME', 'gr.fullName', $direction, 'fullName'),
                'type'       => 'text'
            ],
            'this'     => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Terms::getName(Terms::getCurrentID()),
                'type'       => 'value'
            ],
            'next'     => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Terms::getName(Terms::getNextID()),
                'type'       => 'value'
            ],
            'name'     => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('SELECT_BOX_DISPLAY'),
                'type'       => 'text'
            ],
            'active'   => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ACTIVE'),
                'type'       => 'value'
            ],
            'grid'     => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('GRID'),
                'type'       => 'text'
            ],
            'code'     => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('UNTIS_ID'),
                'type'       => 'text'
            ],
        ];

        $this->headers = $headers;
    }

    protected function initializeView(): void
    {
        parent::initializeView();
        //$this->batch = ['batch_group_publishing'];
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        //Document::style('group_publishing');
        //Document::style('modal');
    }
}
