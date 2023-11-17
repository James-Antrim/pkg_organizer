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
 * Class retrieves information for a filtered set of events.
 */
class Events extends ListModel
{
    protected string $defaultOrdering = 'code';

    protected $filter_fields = ['campusID', 'categoryID', 'groupID', 'organizationID', 'preparatory'];

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::getQuery();
        $tag   = Application::getTag();

        $aliased = DB::qn(
            ["c.name_$tag", 'c.id', "e.name_$tag", "o.shortName_$tag", 'o.id'],
            ['campus', 'campusID', 'name', 'organization', 'organizationID']
        );
        $select  = ['DISTINCT ' . DB::qn('e.id', 'id'), DB::qn('e.organizationID'), DB::qn('e.code')];

        $query->select(array_merge($select, $aliased))
            ->from(DB::qn('#__organizer_events', 'e'))
            ->innerJoin(DB::qn('#__organizer_organizations', 'o'), DB::qc('o.id', 'e.organizationID'))
            ->leftJoin(DB::qn('#__organizer_campuses', 'c'), DB::qc('c.id', 'e.campusID'));

        if (Application::backend()) {
            $authorized = implode(', ', Can::scheduleTheseOrganizations());
            $query->where("o.id IN ($authorized)");
        }

        $this->filterSearch($query, ['e.name_de', 'e.name_en', 'e.subjectNo']);
        $this->filterValues($query, ['e.organizationID', 'e.campusID', 'e.preparatory']);

        $this->orderBy($query);

        return $query;
    }
}