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
 * Class retrieves information for a filtered set of persons.
 */
class Persons extends ListModel
{
    use Activated;

    protected $defaultOrdering = 'p.surname, p.forename';

    protected $filter_fields = ['organizationID', 'suppress'];

    /**
     * Method to get a list of resources from the database.
     * @return JDatabaseQuery
     */
    protected function getListQuery()
    {
        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->select('DISTINCT p.id, surname, forename, username, p.active, o.id AS organizationID, code')
            ->from('#__organizer_persons AS p')
            ->leftJoin('#__organizer_associations AS a ON a.personID = p.id')
            ->leftJoin('#__organizer_organizations AS o ON o.id = a.id')
            ->group('p.id');

        $this->setActiveFilter($query, 'p');
        $this->setSearchFilter($query, ['surname', 'forename', 'username', 'code']);
        $this->setIDFilter($query, 'organizationID', 'filter.organizationID');
        $this->setValueFilters($query, ['p.suppress']);
        $this->setOrdering($query);

        return $query;
    }
}
