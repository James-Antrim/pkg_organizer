<?php

namespace THM\Organizer\Controllers;

use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers\Can;

/**
 * Encapsulates authorization for FM controllers.
 */
trait FluMoxed
{
    /** @inheritDoc */
    protected function authorize(): void
    {
        if (Can::administrate()) {
            return;
        }

        if (!Can::manage('facilities')) {
            Application::error(403);
        }
    }
}