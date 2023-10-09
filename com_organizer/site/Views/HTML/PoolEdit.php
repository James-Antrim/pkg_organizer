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

use Organizer\Adapters\Toolbar;
use Organizer\Helpers;

/**
 * Class loads the (subject) pool form into display context.
 */
class PoolEdit extends EditView
{
    use Subordinate;

    protected $layout = 'tabs';

    /**
     * Method to generate buttons for user interaction
     * @return void
     */
    protected function addToolBar()
    {
        if ($this->item->id) {
            $apply  = 'ORGANIZER_APPLY';
            $cancel = 'ORGANIZER_CLOSE';
            $save   = 'ORGANIZER_SAVE';
            $title  = "ORGANIZER_POOL_EDIT";
        } else {
            $apply  = 'ORGANIZER_CREATE';
            $cancel = 'ORGANIZER_CANCEL';
            $save   = 'ORGANIZER_CREATE';
            $title  = "ORGANIZER_POOL_NEW";
        }

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'apply', Helpers\Languages::_($apply), 'pools.apply', false);
        $toolbar->appendButton('Standard', 'save', Helpers\Languages::_($save), 'pools.save', false);

        if ($this->item->id) {
            $toolbar->appendButton(
                'Standard',
                'save-copy',
                Helpers\Languages::_('ORGANIZER_SAVE2COPY'),
                'pools.save2copy',
                false
            );
        }

        $baseURL = 'index.php?option=com_organizer&tmpl=component';
        $baseURL .= "&type=pool&id={$this->item->id}&view=";

        $poolLink = $baseURL . 'pool_selection';
        $toolbar->appendButton('Popup', 'list', Helpers\Languages::_('ORGANIZER_ADD_POOL'), $poolLink);

        $subjectLink = $baseURL . 'subject_selection';
        $toolbar->appendButton('Popup', 'book', Helpers\Languages::_('ORGANIZER_ADD_SUBJECT'), $subjectLink);

        $toolbar->appendButton('Standard', 'cancel', Helpers\Languages::_($cancel), 'pools.cancel', false);
    }
}
