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

use THM\Organizer\Adapters\{Text, Toolbar};

/**
 * Class loads the (subject) pool form into display context.
 */
class Pool extends FormView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        if ($this->item->id) {
            $title = "ORGANIZER_POOL_EDIT";
        }
        else {
            $title = "ORGANIZER_POOL_NEW";
        }

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'apply', 'applytextfrombutton', 'pools.apply', false);
        $toolbar->appendButton('Standard', 'save', 'savetxtfrombutton', 'pools.save', false);

        if ($this->item->id) {
            $toolbar->appendButton(
                'Standard',
                'save-copy',
                Text::_('ORGANIZER_SAVE2COPY'),
                'pools.save2copy',
                false
            );
        }

        $baseURL = 'index.php?option=com_organizer&tmpl=component';
        $baseURL .= "&type=pool&id={$this->item->id}&view=";

        $poolLink = $baseURL . 'pool_selection';
        $toolbar->appendButton('Popup', 'list', Text::_('ORGANIZER_ADD_POOL'), $poolLink);

        $subjectLink = $baseURL . 'subject_selection';
        $toolbar->appendButton('Popup', 'book', Text::_('ORGANIZER_ADD_SUBJECT'), $subjectLink);

        $toolbar->appendButton('Standard', 'cancel', 'button-text', 'pools.cancel', false);
    }
}
