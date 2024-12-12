<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

/** @inheritDoc */
class CleaningGroups extends ListController
{
    use FacilityManageable;

    protected string $item = 'CleaningGroup';

    /**
     * Activates selected resources.
     * @return void
     */
    public function exclude(): void
    {
        parent::toggle('relevant', false);
    }

    /**
     * De-activates selected resources.
     * @return void
     */
    public function include(): void
    {
        parent::toggle('relevant', true);
    }
}
