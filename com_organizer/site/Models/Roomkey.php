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

use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

/**
 * Class which manages stored building data.
 */
class Roomkey extends BaseModel
{
    /**
     * Authorizes the user.
     * @return void
     */
    protected function authorize()
    {
        if (!Helpers\Can::manage('facilities')) {
            Application::error(403);
        }
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param string $name    The table name. Optional.
     * @param string $prefix  The class prefix. Optional.
     * @param array  $options Configuration array for model. Optional.
     *
     * @return Tables\Roomkeys  A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = []): Tables\Roomkeys
    {
        return new Tables\Roomkeys();
    }

    /**
     * Attempts to save the resource.
     *
     * @param array $data the data from the form
     *
     * @return int|bool int id of the resource on success, otherwise bool false
     */
    public function save(array $data = [])
    {
        $this->authorize();

        $data = empty($data) ? Input::getFormItems()->toArray() : $data;

        if (empty($data) or empty($data['id'])) {
            return false;
        }

        $roomkey = new Tables\Roomkeys();

        if (!$roomkey->load((int) $data['id'])) {
            return false;
        }

        $cleaningGroup = new Tables\CleaningGroups();
        $cleaningID    = (int) $data['cleaningID'];

        $roomkey->cleaningID = $cleaningGroup->load($cleaningID) ? $cleaningID : null;

        return $roomkey->save($data) ? $roomkey->id : false;
    }
}
