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
 * Class retrieves information for a filtered set of groups.
 */
class Groups extends ListModel
{
    use Activated;

    protected string $defaultOrdering = 'gr.code';

    protected $filter_fields = ['categoryID', 'organizationID', 'gridID'];

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $authorized = Can::scheduleTheseOrganizations();

        $query = DB::getQuery();
        $tag   = Application::getTag();
        $url   = 'index.php?option=com_organizer&view=Group&id=';

        $access  = [DB::quote(1) . ' AS ' . DB::qn('access')];
        $aliased = DB::qn(["gr.fullName_$tag", "gr.name_$tag"], ['fullName', 'name']);
        $select  = ['DISTINCT ' . DB::qn('gr.id'), 'gr.active', 'gr.categoryID', 'gr.code', 'gr.gridID'];
        $url     = [$query->concatenate([DB::quote($url), DB::qn('gr.id')], '') . ' AS ' . DB::qn('url'),];

        $query->select(array_merge($select, $access, $aliased, $url))
            ->from(DB::qn('#__organizer_groups', 'gr'))
            ->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.groupID', 'gr.id'));

        $organizationID = DB::qn('a.organizationID');
        $one            = "$organizationID IN (" . implode(',', $query->bindArray($authorized)) . ")";
        $two            = "organizationID IS NULL";
        $query->where("($one OR $two)");

        $this->activeFilter($query, 'gr');
        $this->filterSearch($query, ['gr.fullName_de', 'gr.fullName_en', 'gr.name_de', 'gr.name_en', 'gr.code']);
        $this->filterValues($query, ['gr.categoryID', 'a.organizationID', 'gr.gridID']);

        $this->orderBy($query);

        return $query;
    }
}
