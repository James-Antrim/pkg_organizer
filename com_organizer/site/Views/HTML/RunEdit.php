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
 * Class loads the run form into display context.
 */
class RunEdit extends EditView
{
    /**
     * Method to generate buttons for user interaction
     * @return void
     */
    protected function addToolBar()
    {
        if ($this->item->id) {
            $apply     = 'ORGANIZER_APPLY';
            $applyIcon = 'apply';
            $cancel    = 'ORGANIZER_CLOSE';
            $save      = 'ORGANIZER_SAVE_CLOSE';
            $title     = "ORGANIZER_RUN_EDIT";
        } else {
            $apply     = 'ORGANIZER_CREATE';
            $applyIcon = 'save-new';
            $cancel    = 'ORGANIZER_CANCEL';
            $save      = 'ORGANIZER_CREATE_CLOSE';
            $title     = "ORGANIZER_RUN_NEW";
        }

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', $applyIcon, $apply, 'runs.apply', false);
        $toolbar->appendButton('Standard', 'save', Helpers\Languages::_($save), 'runs.save', false);

        if ($this->item->id) {
            $toolbar->appendButton(
                'Standard',
                'save-copy',
                Helpers\Languages::_('ORGANIZER_SAVE2COPY'),
                'runs.save2copy',
                false
            );
        }

        $toolbar->appendButton('Standard', 'cancel', Helpers\Languages::_($cancel), "runs.cancel", false);
    }
}
