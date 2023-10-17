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
use THM\Organizer\Adapters\{Application, Database, Queries\QueryMySQLi};
use THM\Organizer\Helpers;

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
        $authorized = Helpers\Can::manageTheseOrganizations();
        $tag        = Application::getTag();

        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->select("o.id, o.shortName_$tag AS shortName, o.fullName_$tag AS name, a.rules")
            ->from('#__organizer_organizations AS o')
            ->innerJoin('#__assets AS a ON a.id = o.asset_id')
            ->where('o.id IN (' . implode(',', $authorized) . ')');

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

        $this->setOrdering($query);

        return $query;
    }
}
