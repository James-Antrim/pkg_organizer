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

use THM\Organizer\Adapters\Toolbar;
use THM\Organizer\Helpers;

/**
 * Class loads the (degree) program form into display context.
 */
class ProgramEdit extends EditView
{
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
            $title  = "ORGANIZER_PROGRAM_EDIT";
        } else {
            $apply  = 'ORGANIZER_CREATE';
            $cancel = 'ORGANIZER_CANCEL';
            $save   = 'ORGANIZER_CREATE_CLOSE';
            $title  = "ORGANIZER_PROGRAM_NEW";
        }

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'apply', Helpers\Languages::_($apply), 'programs.apply', false);
        $toolbar->appendButton('Standard', 'save', Helpers\Languages::_($save), 'programs.save', false);

        if ($this->item->id) {
            $toolbar->appendButton(
                'Standard',
                'save-copy',
                Helpers\Languages::_('ORGANIZER_SAVE2COPY'),
                'programs.save2copy',
                false
            );

            $poolLink = 'index.php?option=com_organizer&tmpl=component';
            $poolLink .= "&type=program&id={$this->item->id}&view=pool_selection";
            $toolbar->appendButton('Popup', 'list', Helpers\Languages::_('ORGANIZER_ADD_POOL'), $poolLink);
        }

        $toolbar->appendButton('Standard', 'cancel', Helpers\Languages::_($cancel), 'programs.cancel', false);
    }
}
