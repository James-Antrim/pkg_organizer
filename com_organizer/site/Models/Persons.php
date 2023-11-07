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

use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\Database as DB;

/**
 * Class retrieves information for a filtered set of persons.
 */
class Persons extends ListModel
{
    use Activated;

    protected string $defaultOrdering = 'p.surname, p.forename';

    protected $filter_fields = ['organizationID', 'suppress'];

    /**
     * Method to get a list of resources from the database.
     * @return DatabaseQuery
     */
    protected function getListQuery():DatabaseQuery
    {
        $personID = DB::qn('p.id');
        $those = DB::qn(['surname', 'forename', 'username', 'p.active', 'code']);
        $these = ["DISTINCT $personID", DB::qn('o.id', 'organizationID')];
        $query = DB::getQuery();
        $query->select(array_merge($these, $those))
            ->from(DB::qn('#__organizer_persons', 'p'))
            ->leftJoin(DB::qn('#__organizer_associations', 'a'), DB::qn('a.personID') . " = $personID")
            ->leftJoin(DB::qn('#__organizer_organizations', 'o'), DB::qn('o.id') . ' = ' . DB::qn('a.id'))
            ->group($personID);

        $this->setActiveFilter($query, 'p');
        $this->setSearchFilter($query, ['surname', 'forename', 'username', 'code']);
        $this->setIDFilter($query, 'organizationID', 'filter.organizationID');
        $this->setValueFilters($query, ['p.suppress']);
        $this->setOrdering($query);

        return $query;
    }
}
