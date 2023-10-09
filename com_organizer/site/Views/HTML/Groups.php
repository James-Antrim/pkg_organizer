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

use Joomla\CMS\Uri\Uri;
use Organizer\Adapters;
use Organizer\Adapters\Toolbar;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class loads persistent information a filtered set of (scheduled subject) pools into the display context.
 */
class Groups extends ListView
{
    protected $rowStructure = [
        'checkbox' => '',
        'fullName' => 'link',
        'this' => 'value',
        'next' => 'value',
        'name' => 'link',
        'active' => 'value',
        'grid' => 'link',
        'code' => 'link'
    ];

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true)
    {
        $this->setTitle('ORGANIZER_GROUPS');
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), 'groups.edit', true);

        $if          = "alert('" . Helpers\Languages::_('ORGANIZER_LIST_SELECTION_WARNING') . "');";
        $else        = "jQuery('#modal-publishing').modal('show'); return true;";
        $script      = 'onclick="if(document.adminForm.boxchecked.value==0){' . $if . '}else{' . $else . '}"';
        $batchButton = '<button id="group-publishing" data-toggle="modal" class="btn btn-small" ' . $script . '>';

        $title       = Helpers\Languages::_('ORGANIZER_BATCH');
        $batchButton .= '<span class="icon-stack" title="' . $title . '"></span>' . " $title";

        $batchButton .= '</button>';

        $toolbar->appendButton('Custom', $batchButton, 'batch');
        $toolbar->appendButton(
            'Standard',
            'eye-open',
            Helpers\Languages::_('ORGANIZER_ACTIVATE'),
            'groups.activate',
            false
        );
        $toolbar->appendButton(
            'Standard',
            'eye-close',
            Helpers\Languages::_('ORGANIZER_DEACTIVATE'),
            'groups.deactivate',
            false
        );

        if (Helpers\Can::administrate()) {
            $toolbar->appendButton(
                'Standard',
                'contract',
                Helpers\Languages::_('ORGANIZER_MERGE'),
                'groups.mergeView',
                true
            );

            $toolbar->appendButton(
                'Standard',
                'eye-open',
                Helpers\Languages::_('ORGANIZER_PUBLISH_EXPIRED_TERMS'),
                'groups.publishPast',
                false
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        if (!Helpers\Can::scheduleTheseOrganizations()) {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    public function display($tpl = null)
    {
        // Set batch template path
        $this->batch = ['batch_group_publishing'];

        parent::display($tpl);
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument()
    {
        parent::modifyDocument();

        Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/group_publishing.css');
        Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/modal.css');
    }

    /**
     * @inheritdoc
     */
    protected function setHeaders()
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox' => Helpers\HTML::_('grid.checkall'),
            'fullName' => Helpers\HTML::sort('FULL_NAME', 'gr.fullName', $direction, $ordering),
            'this' => Helpers\Terms::getName(Helpers\Terms::getCurrentID()),
            'next' => Helpers\Terms::getName(Helpers\Terms::getNextID()),
            'name' => Helpers\HTML::sort('SELECT_BOX_DISPLAY', 'gr.name', $direction, $ordering),
            'active' => Helpers\Languages::_('ORGANIZER_ACTIVE'),
            'grid' => Helpers\Languages::_('ORGANIZER_GRID'),
            'code' => Helpers\HTML::sort('UNTIS_ID', 'gr.code', $direction, $ordering)
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems()
    {
        $currentTerm     = Helpers\Terms::getCurrentID();
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=group_edit&id=';
        $nextTerm        = Helpers\Terms::getNextID();
        $publishing      = new Tables\GroupPublishing();
        $structuredItems = [];

        foreach ($this->items as $item) {
            $tip          = $item->active ? 'ORGANIZER_CLICK_TO_DEACTIVATE' : 'ORGANIZER_CLICK_TO_ACTIVATE';
            $item->active = $this->getToggle('groups', $item->id, $item->active, $tip, 'active');

            $termData   = ['groupID' => $item->id, 'termID' => $currentTerm];
            $item->grid = Helpers\Grids::getName($item->gridID);

            $thisValue  = $publishing->load($termData) ? $publishing->published : 1;
            $tip        = $thisValue ? 'ORGANIZER_CLICK_TO_UNPUBLISH' : 'ORGANIZER_CLICK_TO_PUBLISH';
            $item->this = $this->getToggle('groups', $item->id, $thisValue, $tip, $currentTerm);

            $termData['termID'] = $nextTerm;
            $nextValue          = $publishing->load($termData) ? $publishing->published : 1;
            $tip                = $nextValue ? 'ORGANIZER_CLICK_TO_UNPUBLISH' : 'ORGANIZER_CLICK_TO_PUBLISH';
            $item->next         = $this->getToggle('groups', $item->id, $nextValue, $tip, $nextTerm);

            $structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
