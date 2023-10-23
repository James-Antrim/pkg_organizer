<?php
/**
 * @package     Groups
 * @extension   com_groups
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use THM\Organizer\Views\Named;

trait Configured
{
    use Named;

    /**
     * Corrects basic configuration that is used by all HTML Views.
     */
    public function configure(): void
    {
        $this->_basePath = JPATH_SITE . '/components/com_organizer';
        $this->_name     = $this->getName();

        // Set the default template search path
        $this->_setPath('helper', $this->_basePath . '/Helpers');
        $this->_setPath('layout', $this->_basePath . '/Layouts');
        $this->_setPath('template', $this->_basePath . '/templates');
    }
}