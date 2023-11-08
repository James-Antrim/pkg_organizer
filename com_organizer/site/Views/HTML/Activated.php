<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use THM\Organizer\Adapters\Text;
use THM\Organizer\Adapters\Toolbar;

/**
 * Handles code common to resources that can be activated or deactiviated.
 */
trait Activated
{
    /**
     * Adds activation and deactivation buttons to the global toolbar.
     *
     * @param   bool  $check  whether a list item has to be selected for deactivation
     *
     * @return void
     */
    protected function addActa(bool $check = false): void
    {
        /** @var ListView $this */
        $controller = $this->getName();

        $toolbar = Toolbar::getInstance();

        // Activation always requires a check to avoid unintended activation of resources purposefully deactivated.
        $toolbar->standardButton('activate', Text::_('ACTIVATE'), "$controller.activate")
            ->icon('fa fa-eye')
            ->listCheck(true);

        // Deactivation needs to be checked where no global deactivation has been implimented.
        $toolbar->standardButton('deactivate', Text::_('DEACTIVATE'), "$controller.deactivate")
            ->icon('fa fa-eye-slash')
            ->listCheck($check);

    }
}