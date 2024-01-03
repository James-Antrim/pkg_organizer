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
use THM\Organizer\Adapters\Database as DB;
use THM\Organizer\Helpers\Can;

/**
 * Class retrieves the data regarding a filtered set of buildings.
 */
class Buildings extends ListModel
{
    use Activated;

    protected $filter_fields = ['campusID', 'propertyType'];

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::getQuery();
        $url   = 'index.php?option=com_organizer&view=Building&id=';

        $access  = [DB::quote((int) Can::manage('facilities')) . ' AS ' . DB::qn('access')];
        $aliased = DB::qn(['c2.city'], ['parentCity']);
        $select  = DB::qn(['b.id', 'b.name', 'propertyType', 'campusID', 'c1.parentID', 'b.address', 'c1.city']);
        $url     = [$query->concatenate([DB::quote($url), DB::qn('b.id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge($select, $access, $aliased, $url))
            ->from(DB::qn('#__organizer_buildings', 'b'))
            ->innerJoin(DB::qn('#__organizer_campuses', 'c1'), DB::qc('c1.id', 'b.campusID'))
            ->leftJoin(DB::qn('#__organizer_campuses', 'c2'), DB::qc('c2.id', 'c1.parentID'));

        $this->activeFilter($query, 'b');
        $this->filterSearch($query, ['b.name', 'b.address', 'c1.city', 'c2.city']);
        $this->filterValues($query, ['propertyType']);

        if ($campusID = $this->state->get('filter.campusID', '')) {
            $column = DB::qn('campusID');
            if ($campusID === '-1') {
                $query->where("$column IS NULL");
            }
            else {
                $query->where("($column = :campusID OR c1.parentID = :pCampusID)")
                    ->bind(':campusID', $campusID, ParameterType::INTEGER)
                    ->bind(':pCampusID', $campusID, ParameterType::INTEGER);
            }
        }

        $this->orderBy($query);

        return $query;
    }
}
