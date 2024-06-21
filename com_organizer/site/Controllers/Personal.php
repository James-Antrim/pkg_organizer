<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2024 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Controllers;


use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers\Can;

trait Personal
{
    /**
     * @inheritDoc
     */
    protected function authorize(): void
    {
        if (Can::administrate()) {
            return;
        }

        if (!Can::manage('persons')) {
            Application::error(403);
        }
    }
}