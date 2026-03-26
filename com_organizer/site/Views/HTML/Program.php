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

/** @inheritDoc */
class Program extends FormView
{
    /** @inheritDoc */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        $this->toDo[] = 'Add a no category option.';

        parent::addToolbar(['apply', 'save', 'save2copy']);
    }
}
