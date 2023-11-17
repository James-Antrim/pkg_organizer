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
use THM\Organizer\Adapters\Text;

/**
 * Renders a button that checks that exactly one item from a list was selected.
 */
class Highlander extends StandardButton
{
    /**
     * @inheritDoc
     */
    protected function _getCommand(): string
    {
        Text::script('ERROR');
        Text::script('ORGANIZER_MAKE_SELECTION');
        Text::script('ORGANIZER_ONLY_ONE_SELECTION');

        $anyAlert     = "Joomla.renderMessages({error: [Joomla.Text._('ORGANIZER_MAKE_SELECTION')]})";
        $anyCondition = 'document.adminForm.boxchecked.value == 0';
        $hlAlert      = "Joomla.renderMessages({error: [Joomla.Text._('ORGANIZER_ONLY_ONE_SELECTION')]})";
        $hlCondition  = 'document.adminForm.boxchecked.value > 1';
        $submit       = "Joomla.submitbutton('" . $this->getTask() . "');";

        return "if ($anyCondition) { $anyAlert } else if ($hlCondition) { $hlAlert } else { $submit }";
    }
}
