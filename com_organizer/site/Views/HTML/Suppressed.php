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

use THM\Organizer\Adapters\{Text, Toolbar};

/**
 * Handles code common to resources that can be activated or deactivated.
 */
trait Suppressed
{
    private const SUPPRESSED = 1, ALL = -1, REVEALED = 0;

    /**
     * Adds activation and deactivation buttons to the global toolbar.
     *
     * @return void
     */
    protected function addSuppression(): void
    {
        $controller = $this->getName();
        $toolbar    = Toolbar::getInstance();

        switch ((int) $this->state->get('filter.suppress', self::ALL)) {
            case self::REVEALED:
                $toolbar->standardButton('suppress', Text::_('SUPPRESS'), "$controller.suppress")
                    ->icon('fa fa-times-circle')
                    ->listCheck(true);
                break;
            case self::ALL:
                $toolbar->standardButton('reveal', Text::_('SHOW'), "$controller.reveal")
                    ->icon('fa fa-check-circle')
                    ->listCheck(true);
                $toolbar->standardButton('suppress', Text::_('SUPPRESS'), "$controller.suppress")
                    ->icon('fa fa-times-circle')
                    ->listCheck(true);
                break;
            case self::SUPPRESSED:
                $toolbar->standardButton('reveal', Text::_('SHOW'), "$controller.reveal")
                    ->icon('fa fa-check-circle')
                    ->listCheck(true);
                break;
        }

    }
}