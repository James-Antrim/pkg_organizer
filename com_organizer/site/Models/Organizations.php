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
use THM\Organizer\Adapters\{Application, Database as DB};
use THM\Organizer\Helpers\Can;

/**
 * Class retrieves information for a filtered set of organizations.
 */
class Organizations extends ListModel
{
    protected string $defaultOrdering = 'shortName';

    /**
     * Method to get a list of resources from the database.
     * @return DatabaseQuery
     */
    protected function getListQuery(): DatabaseQuery
    {
        $authorized = Can::manageTheseOrganizations();
        $oID        = DB::qn('o.id');

        $tag     = Application::getTag();
        $aliased = DB::qn(["o.fullName_$tag", "o.shortName_$tag"], ['name', 'shortName']);

        $query = DB::getQuery();
        $query->select(array_merge([$oID, DB::qn('a.rules')], $aliased))
            ->from(DB::qn('#__organizer_organizations', 'o'))
            ->innerJoin(DB::qn('#__assets', 'a'), DB::qn('a.id') . ' = ' . DB::qn('o.asset_id'))
            ->whereIn($oID, $authorized);

        $searchColumns = [
            'abbreviation_de',
            'abbreviation_en',
            'fullName_de',
            'fullName_en',
            'name_de',
            'name_en',
            'shortName_de',
            'shortName_en'
        ];

        $this->setSearchFilter($query, $searchColumns);
        $this->orderBy($query);

        return $query;
    }
}
