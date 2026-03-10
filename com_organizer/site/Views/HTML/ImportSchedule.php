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
class ImportSchedule extends ImportView
{

    /** @inheritDoc */
    protected function addToolbar(array $buttons = [], string $constant = ''): void
    {
        $this->toDo[] = 'Update program code resolution to reflect changes to the programs table.';
        parent::addToolbar($buttons, $constant);
    }
}
