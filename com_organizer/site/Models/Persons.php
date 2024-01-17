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
use THM\Organizer\Helpers\Can;

/**
 * Class retrieves information for a filtered set of persons.
 */
class Persons extends ListModel
{
    use Activated;

    protected string $defaultOrdering = 'surname, forename';

    protected $filter_fields = ['organizationID', 'suppress'];

    /**
     * Method to get a list of resources from the database.
     * @return DatabaseQuery
     */
    protected function getListQuery(): DatabaseQuery
    {
        $personID = DB::qn('p.id');
        $query    = DB::getQuery();
        $url      = 'index.php?option=com_organizer&view=Person&id=';

        $access = [DB::quote((int) Can::manage('persons')) . ' AS ' . DB::qn('access')];
        $these  = ["DISTINCT $personID", DB::qn('o.id', 'organizationID')];
        $those  = DB::qn(['surname', 'forename', 'username', 'p.active', 'code']);
        $url    = [$query->concatenate([DB::quote($url), DB::qn('p.id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge($these, $those, $access, $url))
            ->from(DB::qn('#__organizer_persons', 'p'))
            ->leftJoin(DB::qn('#__organizer_associations', 'a'), DB::qn('a.personID') . " = $personID")
            ->leftJoin(DB::qn('#__organizer_organizations', 'o'), DB::qn('o.id') . ' = ' . DB::qn('a.id'))
            ->group($personID);

        $this->activeFilter($query, 'p');
        $this->filterSearch($query, ['surname', 'forename', 'username', 'code']);

        // The joins are made anyway for retrieval of output data, no need for additional joins in a fit-for-purpose filter.
        $this->filterByKey($query, 'organizationID', 'organizationID');
        $this->filterValues($query, ['p.suppress']);
        $this->orderBy($query);

        return $query;
    }
}
