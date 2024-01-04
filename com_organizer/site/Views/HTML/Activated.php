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
 * Handles code common to resources that can be activated or deactivated.
 */
trait Activated
{
    private const ACTIVE = 1, ALL = -1, INACTIVE = 0;

    /**
     * Adds activation and deactivation buttons to the global toolbar.
     *
     * @return void
     */
    protected function addActa(): void
    {
        /** @var ListView $this */
        $controller = $this->getName();
        $toolbar    = Toolbar::getInstance();

        switch ((int) $this->state->get('filter.active', self::ACTIVE)) {
            case self::ACTIVE:
                $toolbar->standardButton('deactivate', Text::_('DEACTIVATE'), "$controller.deactivate")
                    ->icon('fa fa-eye-slash')
                    ->listCheck(true);
                break;
            case self::ALL:
                $toolbar->standardButton('activate', Text::_('ACTIVATE'),
                    "$controller.activate")->icon('fa fa-eye')->listCheck(true);
                $toolbar->standardButton('deactivate', Text::_('DEACTIVATE'), "$controller.deactivate")
                    ->icon('fa fa-eye-slash')
                    ->listCheck(true);
                break;
            case self::INACTIVE:
                $toolbar->standardButton('activate', Text::_('ACTIVATE'),
                    "$controller.activate")->icon('fa fa-eye')->listCheck(true);
                break;
        }

    }
}