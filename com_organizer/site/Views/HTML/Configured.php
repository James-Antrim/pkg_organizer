<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use THM\Organizer\Views\Named;

/**
 * Class sets commonly configured view properties.
 */
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

        $this->_setPath('template', $this->_basePath . '/templates');
    }
}