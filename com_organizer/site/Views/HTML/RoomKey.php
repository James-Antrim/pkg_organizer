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

use THM\Organizer\Adapters\{Document, Input};

/**
 * @inheritDoc
 */
class RoomKey extends FormView
{
    /**
     * Adds resource related title, cancel/close and eventually help buttons.
     *
     * @param   string[]  $buttons  the names of the available button functions
     *
     * @return  void adds buttons to the global toolbar object
     */
    protected function addToolbar(array $buttons = []): void
    {
        Input::set('hidemainmenu', true);
        $this->setTitle('EDIT_ROOM_KEY');
        $toolbar   = Document::getToolbar();
        $saveGroup = $toolbar->dropdownButton('save-group');
        $saveBar   = $saveGroup->getChildToolbar();
        $saveBar->apply("RoomKey.apply", 'ORGANIZER_APPLY');
        $saveBar->save("RoomKey.save", 'ORGANIZER_SAVE_AND_CLOSE');
        $toolbar->cancel("RoomKey.cancel", 'ORGANIZER_CANCEL');
    }
}
