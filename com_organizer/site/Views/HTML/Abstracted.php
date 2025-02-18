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

/**
 * Common initialization code for forms with no direct connection to a concrete resource.
 */
trait Abstracted
{
    /**
     * @inheritDoc
     * Overrides to avoid calling getItem and getTable as neither makes sense in a non-concrete context.
     */
    protected function initializeView(): void
    {
        $this->form  = $this->get('Form');
        $this->state = $this->get('State');
    }
}