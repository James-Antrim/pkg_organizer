<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

/**
 * @inheritDoc
 */
class Organization extends FormView
{
    /**
     * @inheritDoc
     */
    protected function addToolbar(array $buttons = []): void
    {
        parent::addToolbar(['apply', 'save']);
    }
}
