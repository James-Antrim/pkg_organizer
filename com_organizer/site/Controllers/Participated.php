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

use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Tables\Instances as iTable;

/**
 * Standard implementation for updating participation numbers.
 */
trait Participated
{
    /**
     * Initiates toggling of boolean values in a column.
     *
     * @param   string  $column  the column in which the values are stored
     * @param   bool    $value   the target value
     *
     * @return void
     */
    protected function toggleAssoc(string $column, bool $value): void
    {
        $this->checkToken();
        $this->authorize();

        $contextID   = Input::getID();
        $selectedIDs = Input::getSelectedIDs();
        $selected    = count($selectedIDs);
        $updated     = $this->updateAssoc($column, $contextID, $selectedIDs, $value);

        $this->farewell($selected, $updated);
    }

    /**
     * Updates a boolean column for multiple entries in a
     *
     * @param   string  $column       the table column / object property
     * @param   int     $contextID
     * @param   array   $selectedIDs  the ids of the resources whose properties will be updated
     * @param   bool    $value        the value to update to
     *
     * @return int
     */
    private function updateAssoc(string $column, int $contextID, array $selectedIDs, bool $value): int
    {
        /** @var CourseParticipants|InstanceParticipants $this */
        $table = $this->getTable();

        if (!property_exists($table, $column)) {
            Application::message('ORGANIZER_TABLE_COLUMN_NONEXISTENT', Application::ERROR);

            return 0;
        }

        $total = 0;
        $value = (int) $value;
        $keys  = [$this->context => $contextID];

        foreach ($selectedIDs as $selectedID) {
            $keys['participantID'] = $selectedID;
            $table                 = $this->getTable();

            if ($table->load($keys) and $table->$column !== $value) {
                $table->$column = $value;

                if ($table->store()) {
                    $total++;
                }
            }
        }

        return $total;
    }

    /**
     * Updates participation numbers for a single instance.
     *
     * @param   int  $instanceID
     *
     * @return bool
     */
    private function updateIPNumbers(int $instanceID): bool
    {
        $query = DB::getQuery();
        $query->select('*')->from(DB::qn('#__organizer_instance_participants'))->where("instanceID = $instanceID");
        DB::setQuery($query);

        if (!$results = DB::loadAssocList()) {
            return false;
        }

        $attended   = 0;
        $bookmarked = 0;
        $registered = 0;

        foreach ($results as $result) {
            $bookmarked++;
            $attended   = $attended + $result['attended'];
            $registered = $registered + $result['registered'];
        }

        $table = new iTable();
        $table->load($instanceID);

        $updated = false;

        if ($attended and $attended !== $table->attended) {
            $table->attended = $attended;
            $updated         = true;
        }

        if ($bookmarked and $bookmarked !== $table->bookmarked) {
            $table->bookmarked = $bookmarked;
            $updated           = true;
        }

        if ($registered and $registered !== $table->registered) {
            $table->registered = $registered;
            $updated           = true;
        }

        $table->store();

        return $updated;
    }
}