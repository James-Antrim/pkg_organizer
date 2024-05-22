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
use THM\Organizer\Helpers\Can;

/**
 * Handles code common to resources that can be merged.
 */
trait Merged
{
    /**
     * Adds aa button linking the resource merge view to the global toolbar.
     */
    protected function addMerge(?Dropdown $dropdown = null): void
    {
        if (Can::administrate()) {
            /** @var ListView $this */
            $controller = $this->getName();

            if ($dropdown) {
                $dropdown->standardButton('merge', Text::_('MERGE'), "Merge$controller.display")->icon('fa fa-compress');
                return;
            }

            Toolbar::getInstance()
                ->standardButton('merge', Text::_('MERGE'), "Merge$controller.display")
                ->icon('fa fa-compress')
                ->listCheck(true);
        }
    }
}