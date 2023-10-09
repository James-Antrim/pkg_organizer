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
 * Class loads the holiday form into display context.
 */
class HolidayEdit extends EditView
{
    /**
     * Method to generate buttons for user interaction
     * @return void
     */
    protected function addToolBar()
    {
        if ($this->item->id) {
            $cancel = 'ORGANIZER_CLOSE';
            $save   = 'ORGANIZER_SAVE_CLOSE';
            $title  = "ORGANIZER_HOLIDAY_EDIT";
        } else {
            $cancel = 'ORGANIZER_CANCEL';
            $save   = 'ORGANIZER_CREATE_CLOSE';
            $title  = "ORGANIZER_HOLIDAY_NEW";
        }

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'save', Helpers\Languages::_($save), "holidays.save", false);

        if ($this->item->id) {
            $toolbar->appendButton(
                'Standard',
                'save-copy',
                Helpers\Languages::_('ORGANIZER_SAVE2COPY'),
                'holidays.save2copy',
                false
            );
        }

        $toolbar->appendButton('Standard', 'cancel', Helpers\Languages::_($cancel), "holidays.cancel", false);
    }
}