<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Buttons;

use Joomla\CMS\Toolbar\Button\StandardButton;
use THM\Organizer\Adapters\{Document, Text};

/**
 * Renders a button whose contents open in a new tab.
 */
class FormTarget extends StandardButton
{
    public string $task = '';

    /**
     * @inheritDoc
     */
    protected function _getCommand(): string
    {
        Document::script('formTarget');

        $cmd = "Joomla.formTarget('" . $this->task . "');";

        if ($this->getListCheck()) {
            Text::script('ORGANIZER_MAKE_SELECTION');
            $anyAlert     = "Joomla.renderMessages({error: [Joomla.Text._('ORGANIZER_MAKE_SELECTION')]})";
            $anyCondition = 'document.adminForm.boxchecked.value == 0';
            $cmd          = "if ($anyCondition) { $anyAlert } else { $cmd }";
        }

        return $cmd;
    }
}
