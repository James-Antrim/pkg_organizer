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
use THM\Organizer\Helpers\Can;

/**
 * Class retrieves the data regarding a filtered set of buildings.
 */
class RoomKeys extends ListModel
{
    protected $filter_fields = ['cleaningID', 'inUse', 'useID'];

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::getQuery();
        $tag   = Application::getTag();
        $url   = 'index.php?option=com_organizer&view=roomkey&id=';

        $access  = [DB::quote((int) Can::manage('facilities')) . ' AS ' . DB::qn('access')];
        $aliased = DB::qn(
            ["cg.name_$tag", "rk.name_$tag", 'rk.key', "ug.name_$tag"],
            ['cleaningGroup', 'name', 'rns', 'useGroup']
        );
        $url     = [$query->concatenate([DB::quote($url), DB::qn('rk.id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge([DB::qn('rk') . '.*'], $access, $aliased, $url))
            ->from(DB::qn('#__organizer_roomkeys', 'rk'))
            ->innerJoin(DB::qn('#__organizer_use_groups', 'ug'), DB::qc('ug.id', 'rk.useID'));

        $this->filterSearch($query, ['name_de', 'name_en']);
        $this->filterValues($query, ['cleaningID']);

        $useID = (int) $this->state->get('filter.useID');

        if ($useID) {
            $query->where(DB::qn('useID') . ' = :useID')
                ->bind(':useID', $useID, ParameterType::INTEGER);
        }

        $cgConditions = DB::qc('cg.id', 'rk.cleaningID');
        $cgTable      = DB::qn('#__organizer_cleaning_groups', 'cg');
        if ($cleaningID = (int) $this->state->get('filter.cleaningID')) {
            $column = DB::qn('rk.cleaningID');
            if ($cleaningID !== self::NONE) {
                $query->innerJoin($cgTable, $cgConditions)
                    ->where("$column = :cleaningID")
                    ->bind(':cleaningID', $cleaningID, ParameterType::INTEGER);
            }
            else {
                $query->where("$column IS NULL");
            }
        }
        else {
            $query->leftJoin($cgTable, $cgConditions);
        }

        $this->orderBy($query);

        return $query;
    }
}
