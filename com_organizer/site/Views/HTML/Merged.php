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
 * Handles code common to resources that can be merged.
 */
trait Merged
{
    /**
     * Adds aa button linking the resource merge view to the global toolbar.
     *
     * @return void
     */
    protected function addMerge(): void
    {
        /** @var ListView $this */
        $controller = $this->getName();

        Toolbar::getInstance()
            ->standardButton('merge', Text::_('MERGE'), "Merge$controller.display")
            ->icon('fa fa-compress')
            ->listCheck(true);
    }
}