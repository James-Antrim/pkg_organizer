<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use THM\Organizer\Adapters\Input;
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

/**
 * Class which manages stored campus data.
 */
class Campus extends BaseModel
{
    /**
     * Authorizes the user.
     * @return void
     */
    protected function authorize()
    {
        if (!Helpers\Can::manage('facilities')) {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param string $name    The table name. Optional.
     * @param string $prefix  The class prefix. Optional.
     * @param array  $options Configuration array for model. Optional.
     *
     * @return Tables\Campuses  A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = []): Tables\Campuses
    {
        return new Tables\Campuses();
    }

    /**
     * @inheritDoc
     */
    public function save(array $data = [])
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
    }
}
