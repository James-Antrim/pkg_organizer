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
use THM\Organizer\Adapters\{Application, Document, HTML, Text, Toolbar};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

/**
 * Class loads persistent information a filtered set of (scheduled subject) pools into the display context.
 */
class Groups extends ListView
{
    use Activated;
    use Merged;

    protected array $rowStructure = [
        'checkbox' => '',
        'fullName' => 'link',
        'this'     => 'value',
        'next'     => 'value',
        'name'     => 'link',
        'active'   => 'value',
        'grid'     => 'link',
        'code'     => 'link'
    ];

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        // Resource creation occurs in Untis and editing is done via links in the list.

        $toolbar = Toolbar::getInstance();

        $if          = "alert('" . Text::_('ORGANIZER_LIST_SELECTION_WARNING', true) . "');";
        $else        = "jQuery('#modal-publishing').modal('show'); return true;";
        $script      = 'onclick="if(document.adminForm.boxchecked.value==0){' . $if . '}else{' . $else . '}"';
        $batchButton = '<button id="group-publishing" data-toggle="modal" class="btn btn-small" ' . $script . '>';

        $title       = Text::_('ORGANIZER_BATCH');
        $batchButton .= '<span class="icon-stack" title="' . $title . '"></span>' . " $title";

        $batchButton .= '</button>';

        $toolbar->appendButton('Custom', $batchButton, 'batch');
        $this->addActa();

        if (Helpers\Can::administrate()) {
            $this->addMerge();
            $toolbar->standardButton('publish-expired', Text::_('PUBLISH_EXPIRED_TERMS'), 'Groups.publishPast')
                ->icon('fa fa-reply-all');
        }

        parent::addToolBar();
    }

    /**
     * @inheritdoc
     */
    protected function authorize(): void
    {
        if (!Helpers\Can::scheduleTheseOrganizations()) {
            Application::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    protected function completeItems(): void
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

            $structuredItems[$index] = $this->completeItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }

    /**
     * @inheritdoc
     */
    public function display($tpl = null): void
    {
        // Set batch template path
        $this->batch = ['batch_group_publishing'];

        parent::display($tpl);
    }

    /**
     * @inheritdoc
     */
    protected function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox' => HTML::checkAll(),
            'fullName' => HTML::sort('FULL_NAME', 'gr.fullName', $direction, $ordering),
            'this'     => Helpers\Terms::getName(Helpers\Terms::getCurrentID()),
            'next'     => Helpers\Terms::getName(Helpers\Terms::getNextID()),
            'name'     => HTML::sort('SELECT_BOX_DISPLAY', 'gr.name', $direction, $ordering),
            'active'   => Text::_('ACTIVE'),
            'grid'     => Text::_('GRID'),
            'code'     => HTML::sort('UNTIS_ID', 'gr.code', $direction, $ordering)
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/group_publishing.css');
        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/modal.css');
    }
}
