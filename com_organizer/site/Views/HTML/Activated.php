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

use Joomla\CMS\Toolbar\Toolbar as Dropdown;
use THM\Organizer\Adapters\{Text, Toolbar};

/**
 * Handles code common to resources that can be activated or deactivated.
 */
trait Activated
{
    private const ACTIVE = 1, ALL = -1, INACTIVE = 0;

    /**
     * Adds activation and deactivation buttons to the global toolbar.
     */
    protected function addActa(?Dropdown $dropdown = null): void
    {
        /** @var ListView $this */
        $controller = $this->getName();
        $toolbar    = $dropdown ?: Toolbar::instance();

        switch ((int) $this->state->get('filter.active', self::ACTIVE)) {
            case self::ACTIVE:
                $deactivate = $toolbar->standardButton('deactivate', Text::_('DEACTIVATE'), "$controller.deactivate")
                    ->icon('fa fa-eye-slash');

                if (!$dropdown) {
                    $deactivate->listCheck(true);
                }

                break;
            case self::ALL:
                $activate   = $toolbar->standardButton('activate', Text::_('ACTIVATE'), "$controller.activate")
                    ->icon('fa fa-eye')->listCheck(true);
                $deactivate = $toolbar->standardButton('deactivate', Text::_('DEACTIVATE'), "$controller.deactivate")
                    ->icon('fa fa-eye-slash');

                if (!$dropdown) {
                    $activate->listCheck(true);
                    $deactivate->listCheck(true);
                }

                break;
            case self::INACTIVE:
                $activate = $toolbar->standardButton('activate', Text::_('ACTIVATE'), "$controller.activate")->icon('fa fa-eye');

                if (!$dropdown) {
                    $activate->listCheck(true);
                }

                break;
        }

    }
}