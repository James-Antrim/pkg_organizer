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

use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored grid data.
 */
class Grid extends BaseModel
{
    /**
     * @inheritDoc
     */
    public function getTable($name = '', $prefix = '', $options = [])
    {
        return new Tables\Grids();
    }

    /**
     * @inheritDoc
     */
    public function save(array $data = [])
    {
        $this->authorize();

        $data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

        // Save grids in json by foreach because the index is not numeric
        $periods = [];
        $index   = 1;
        if (!empty($data['grid'])) {
            foreach ($data['grid'] as $row) {
                $periods[$index] = $row;
                ++$index;
            }
        }

        $grid         = ['periods' => $periods, 'startDay' => $data['startDay'], 'endDay' => $data['endDay']];
        $data['grid'] = json_encode($grid, JSON_UNESCAPED_UNICODE);

        if ($data['isDefault'] and !$this->unDefaultAll()) {
            return false;
        }

        $table = new Tables\Grids();

        return $table->save($data) ? $table->id : false;
    }

    /**
     * Toggles the default grid.
     * @return bool true if the default grid was changed successfully, otherwise false
     */
    public function toggle(): bool
    {
        $this->authorize();

        $selected = Helpers\Input::getID();
        $table    = new Tables\Grids();

        // Entry not found or already set to default
        if (!$table->load($selected) or $table->isDefault) {
            return false;
        }

        if (!$this->unDefaultAll()) {
            return false;
        }

        $table->isDefault = 1;

        return $table->store();
    }

    /**
     * Removes the default status from all grids.
     * @return bool true if the default status was removed from all grids, otherwise false
     */
    private function unDefaultAll(): bool
    {
        $query = Database::getQuery();
        $query->update('#__organizer_grids')->set('isDefault = 0');
        Database::setQuery($query);

        return Database::execute();
    }
}
