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
 * Class loads persistent information a filtered set of degrees into the display context.
 */
class Nomina extends ListView
{
    /** @inheritDoc */
    protected function addToolBar(): void
    {
        $this->addBasicButtons();
        parent::addToolBar();
    }

    /** @inheritDoc */
    public function initializeColumns(): void
    {
        $this->tossed(true);
    }
}
