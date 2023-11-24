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
class PoolEdit extends EditViewOld
{
    use Subordinate;

    protected string $layout = 'tabs';

    /**
     * Method to generate buttons for user interaction
     * @return void
     */
    protected function addToolBar(): void
    {
        if ($this->item->id) {
            $apply  = 'ORGANIZER_APPLY';
            $cancel = 'ORGANIZER_CLOSE';
            $save   = 'ORGANIZER_SAVE';
            $title  = "ORGANIZER_POOL_EDIT";
        }
        else {
            $apply  = 'ORGANIZER_CREATE';
            $cancel = 'ORGANIZER_CANCEL';
            $save   = 'ORGANIZER_CREATE';
            $title  = "ORGANIZER_POOL_NEW";
        }

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'apply', Text::_($apply), 'pools.apply', false);
        $toolbar->appendButton('Standard', 'save', Text::_($save), 'pools.save', false);

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

        $toolbar->appendButton('Standard', 'cancel', Text::_($cancel), 'pools.cancel', false);
    }
}
