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

use JDatabaseQuery;
use THM\Organizer\Adapters\{Application, Database, Queries\QueryMySQLi};

/**
 * Class provides a standardized framework for the display of listed methods.
 */
class Methods extends ListModel
{
    protected $defaultOrdering = 'abbreviation';

    /**
     * Method to get a list of resources from the database.
     * @return JDatabaseQuery
     */
    protected function getListQuery()
    {
        $tag = Application::getTag();
        /* @var QueryMySQLi $query */
        $query = Database::getQuery();

        $query->select("id, abbreviation_$tag AS abbreviation, name_$tag AS name")
            ->from('#__organizer_methods');

        $this->setSearchFilter($query, ['name_de', 'name_en', 'abbreviation_de', 'abbreviation_en']);

        $this->setOrdering($query);

        return $query;
    }
}
