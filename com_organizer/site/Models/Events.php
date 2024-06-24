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

use Joomla\Database\{DatabaseQuery, ParameterType};
use THM\Organizer\Adapters\{Application, Database as DB};
use THM\Organizer\Helpers\{Can, Events as Helper};

/**
 * Class retrieves information for a filtered set of events.
 */
class Events extends ListModel
{
    protected string $defaultOrdering = 'name';

    protected $filter_fields = ['campusID', 'categoryID', 'groupID', 'organizationID', 'preparatory'];

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::getQuery();
        $tag   = Application::getTag();
        $url   = 'index.php?option=com_organizer&view=event&id=';

        if (Can::administrate()) {
            $access = [DB::quote(1) . ' AS ' . DB::qn('access')];
        }
        elseif ($coordinates = Helper::coordinates()) {
            $access = [DB::qn('e.id') . ' IN (' . implode(',', $coordinates) . ')' . ' AS ' . DB::qn('access')];
        }
        else {
            $access = [DB::quote(0) . ' AS ' . DB::qn('access')];
        }

        $aliased = DB::qn(
            ["c.name_$tag", 'c.id', "e.name_$tag", "o.shortName_$tag", 'o.id'],
            ['campus', 'campusID', 'name', 'organization', 'organizationID']
        );
        $select  = ['DISTINCT ' . DB::qn('e.id', 'id'), DB::qn('e.organizationID'), DB::qn('e.code')];
        $url     = [$query->concatenate([DB::quote($url), DB::qn('e.id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge($select, $access, $aliased, $url))
            ->from(DB::qn('#__organizer_events', 'e'))
            ->innerJoin(DB::qn('#__organizer_organizations', 'o'), DB::qc('o.id', 'e.organizationID'))
            ->leftJoin(DB::qn('#__organizer_campuses', 'c'), DB::qc('c.id', 'e.campusID'));

        if (Application::backend()) {
            $access = 1;
            $query->having(DB::qn('access') . ' = :access')->bind(':access', $access, ParameterType::INTEGER);
        }

        $this->filterSearch($query, ['e.name_de', 'e.name_en', 'e.subjectNo']);
        $this->filterValues($query, ['e.organizationID', 'e.campusID', 'e.preparatory']);

        $this->orderBy($query);

        return $query;
    }
}