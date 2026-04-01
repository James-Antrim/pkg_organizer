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
class ProgramTypes extends ListView
{
    /** @inheritDoc */
    protected function addToolBar(): void
    {
        $this->addBasicButtons();
        parent::addToolBar();

        $this->title('PROGRAM_TYPES');
    }

    /** @inheritDoc */
    public function initializeColumns(): void
    {
        $this->tossed(true);
    }
}
