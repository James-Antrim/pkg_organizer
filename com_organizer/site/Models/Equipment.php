<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Exception;
use JDatabaseQuery;
use Organizer\Adapters\Database;
use Organizer\Adapters\Queries\QueryMySQLi;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class retrieves information for a filtered set of room types.
 */
class Equipment extends ListModel
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
     * Method to get a list of resources from the database.
     * @return JDatabaseQuery
     */
    protected function getListQuery(): JDatabaseQuery
    {
        $tag = Helpers\Languages::getTag();

        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->select("DISTINCT e.*, e.name_$tag AS name")
            ->from('#__organizer_equipment AS e');

        $this->setSearchFilter($query, ['e.code', 'e.name_de', 'e.name_en']);
        $this->setOrdering($query);

        return $query;
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param string $name    The table name. Optional.
     * @param string $prefix  The class prefix. Optional.
     * @param array  $options Configuration array for model. Optional.
     *
     * @return Tables\Equipment  A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = []): Tables\Equipment
    {
        return new Tables\Equipment();
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

        $data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

        try {
            $table = $this->getTable();
        } catch (Exception $exception) {
            Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

            return false;
        }

        return $table->save($data) ? $table->id : false;
    }
}
