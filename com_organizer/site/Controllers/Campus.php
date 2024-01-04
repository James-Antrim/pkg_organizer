<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

/**
 * @inheritDoc
 */
class Campus extends FormController
{
    use FluMoxed;

    protected string $list = 'Campuses';

    /**
     * @inheritDoc
     */
    /*public function save(array $data = [])
    {
        if ($parentID = Input::getInt('parentID')) {
            $table = new Tables\Campuses();
            $table->load($parentID);

            // The chosen superordinate campus is in itself subordinate.
            if (!empty($table->parentID)) {
                return false;
            }
        }

        return parent::save($data);
    }*/
}