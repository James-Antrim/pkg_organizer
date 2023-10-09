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
use THM\Organizer\Adapters\Database;
use THM\Organizer\Adapters\Queries\QueryMySQLi;

/**
 * Class retrieves information for a filtered set of degrees.
 */
class Degrees extends ListModel
{
    /**
     * Constructor to set up the configuration and call the parent constructor
     *
     * @param array $config the configuration  (default: array)
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['name', 'abbreviation', 'code'];
        }

        parent::__construct($config);
    }

    /**
     * Method to get a list of resources from the database.
     * @return JDatabaseQuery
     */
    protected function getListQuery(): JDatabaseQuery
    {
        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->select('id, name, abbreviation, code')
            ->from('#__organizer_degrees');

        $columns = ['name', 'abbreviation', 'code'];
        $this->setSearchFilter($query, $columns);
        $this->setOrdering($query);

        return $query;
    }
}
