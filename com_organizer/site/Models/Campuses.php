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

/**
 * Class retrieves information for a filtered set of campuses.
 */
class Campuses extends ListModel
{
    protected $filter_fields = ['city', 'gridID'];

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::getQuery();
        $tag   = Application::getTag();
        $url   = 'index.php?option=com_organizer&view=Campus&id=';

        $aliases = [
            'name',
            'parentAddress',
            'parentCity',
            'parentID',
            'parentName',
            'parentZIPCode',
            'gridID',
            'gridName',
            'parentGridID',
            'parentGridName'
        ];
        $columns = [
            "c1.name_$tag",
            'c2.address',
            'c2.city',
            'c2.id',
            "c2.name_$tag",
            'c2.zipCode',
            'g1.id',
            "g1.name_$tag",
            'g2.id',
            "g2.name_$tag"
        ];

        $aliased = DB::qn($columns, $aliases);
        $select  = DB::qn(['c1.id', 'c1.address', 'c1.city', 'c1.zipCode', 'c1.location']);
        $url     = [$query->concatenate([DB::quote($url), DB::qn('c1.id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge($select, $aliased, $url))
            ->from(DB::qn('#__organizer_campuses', 'c1'))
            ->leftJoin(DB::qn('#__organizer_grids', 'g1'), DB::qc('g1.id', 'c1.gridID'))
            ->leftJoin(DB::qn('#__organizer_campuses', 'c2'), DB::qc('c2.id', 'c1.parentID'))
            ->leftJoin(DB::qn('#__organizer_grids', 'g2'), DB::qc('g2.id', 'c2.gridID'))
            ->order(DB::qn(['parentName', 'name']));

        $searchColumns = [
            'c1.name_de',
            'c1.name_en',
            'c1.city',
            'c1.address',
            'c1.zipCode',
            'c2.city',
            'c2.address',
            'c2.zipCode'
        ];
        $this->filterSearch($query, $searchColumns);
        $this->setCityFilter($query);
        $this->setGridFilter($query);

        return $query;
    }

    /**
     * Filters according to the selected city.
     *
     * @param   DatabaseQuery  $query  the query to modify
     *
     * @return void
     */
    private function setCityFilter(DatabaseQuery $query): void
    {
        if (!$value = $this->state->get('filter.city')) {
            return;
        }

        /**
         * Special value reserved for empty filtering. Since an empty is dependent upon the column default, we must
         * check against multiple 'empty' values. Here we check against empty string and null. Should this need to
         * be extended we could maybe add a parameter for it later.
         */
        if ((int) $value === self::NONE) {
            $query->where(DB::qn('c1.city') . " = ''")->where(DB::qn('c2.city') . " = ''");

            return;
        }

        $cCity = DB::qn('c1.city');
        $query->where("($cCity = :city OR ($cCity = '' AND " . DB::qn('c2.city') . " = :pCity))")
            ->bind(':city', $value)->bind(':pCity', $value);
    }

    /**
     * Filters according to the selected grid.
     *
     * @param   DatabaseQuery  $query  the query to modify
     *
     * @return void
     */
    private function setGridFilter(DatabaseQuery $query): void
    {
        if (!$value = (int) $this->state->get('filter.gridID')) {
            return;
        }

        $grid  = DB::qn('g1.id');
        $pGrid = DB::qn('g2.id');

        /**
         * Special value reserved for empty filtering. Since an empty is dependent upon the column default, we must
         * check against multiple 'empty' values. Here we check against empty string and null. Should this need to
         * be extended we could maybe add a parameter for it later.
         */
        if ($value === self::NONE) {
            $query->where("$grid IS NULL AND $pGrid IS NULL");

            return;
        }

        $query->where("($grid = :gridID OR ($grid IS NULL AND $pGrid = :pGridID))")
            ->bind(':gridID', $value, ParameterType::INTEGER)->bind(':pGridID', $value, ParameterType::INTEGER);
    }
}
