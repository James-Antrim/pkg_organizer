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

use Exception;
use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\{Application, Database, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

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
            Application::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $tag = Application::getTag();

        $query = Database::getQuery();
        $query->select("DISTINCT e.*, e.name_$tag AS name")
            ->from('#__organizer_equipment AS e');

        $this->filterSearch($query, ['e.code', 'e.name_de', 'e.name_en']);
        $this->orderBy($query);

        return $query;
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
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
     * @param   array  $data  the data from the form
     *
     * @return int|bool int id of the resource on success, otherwise bool false
     */
    public function save(array $data = [])
    {
        $this->authorize();

        $data = empty($data) ? Input::getFormItems()->toArray() : $data;

        try {
            $table = $this->getTable();
        }
        catch (Exception $exception) {
            Application::message($exception->getMessage(), Application::ERROR);

            return false;
        }

        return $table->save($data) ? $table->id : false;
    }
}
