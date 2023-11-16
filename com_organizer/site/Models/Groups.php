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
use THM\Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of groups.
 */
class Groups extends ListModel
{
    use Activated;

    protected string $defaultOrdering = 'gr.code';

    protected $filter_fields = ['categoryID', 'organizationID', 'gridID'];

    /**
     * Method to get a list of resources from the database.
     * @return DatabaseQuery
     */
    protected function getListQuery(): DatabaseQuery
    {
        $authorized = Helpers\Can::scheduleTheseOrganizations();
        $tag        = Application::getTag();

        $query = DB::getQuery();
        $query->select('DISTINCT gr.id, gr.code, gr.categoryID, gr.gridID, gr.active')
            ->select("gr.fullName_$tag AS fullName, gr.name_$tag AS name")
            ->from('#__organizer_groups AS gr')
            ->innerJoin('#__organizer_associations AS a ON a.groupID = gr.id')
            ->where('(a.organizationID IN (' . implode(',', $authorized) . ') OR a.organizationID IS NULL)');

        $this->filterActive($query, 'gr');
        $this->filterSearch($query, ['gr.fullName_de', 'gr.fullName_en', 'gr.name_de', 'gr.name_en', 'gr.code']);
        $this->filterValues($query, ['gr.categoryID', 'a.organizationID', 'gr.gridID']);

        $this->orderBy($query);

        return $query;
    }
}
