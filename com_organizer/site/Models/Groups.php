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
use Organizer\Adapters\Database;
use Organizer\Adapters\Queries\QueryMySQLi;
use Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of groups.
 */
class Groups extends ListModel
{
    use Activated;

    protected $defaultOrdering = 'gr.code';

    protected $filter_fields = ['categoryID', 'organizationID', 'gridID'];

    /**
     * Method to get a list of resources from the database.
     * @return JDatabaseQuery
     */
    protected function getListQuery()
    {
        $authorized = Helpers\Can::scheduleTheseOrganizations();
        $tag        = Helpers\Languages::getTag();

        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->select('DISTINCT gr.id, gr.code, gr.categoryID, gr.gridID, gr.active')
            ->select("gr.fullName_$tag AS fullName, gr.name_$tag AS name")
            ->from('#__organizer_groups AS gr')
            ->innerJoin('#__organizer_associations AS a ON a.groupID = gr.id')
            ->where('(a.organizationID IN (' . implode(',', $authorized) . ') OR a.organizationID IS NULL)');

        $this->setActiveFilter($query, 'gr');
        $this->setSearchFilter($query, ['gr.fullName_de', 'gr.fullName_en', 'gr.name_de', 'gr.name_en', 'gr.code']);
        $this->setValueFilters($query, ['gr.categoryID', 'a.organizationID', 'gr.gridID']);

        $this->setOrdering($query);

        return $query;
    }
}
