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

/** @inheritDoc */
class RoomTypes extends ListModel
{
    protected $filter_fields = ['cleaningID', 'keyID', 'suppress', 'useID'];

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::query();
        $tag   = Application::tag();
        $url   = 'index.php?option=com_organizer&view=roomtype&id=';

        $access   = [DB::quote((int) Can::fm()) . ' AS ' . DB::qn('access')];
        $aliased  = DB::qn(["t.name_$tag", 'k.key'], ['name', 'rns']);
        $selected = ['DISTINCT ' . DB::qn('t.id'), DB::qn('t.suppress')];
        $url      = [$query->concatenate([DB::quote($url), DB::qn('t.id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge($selected, $access, $aliased, $url))
            ->select($query->concatenate(["c.name_$tag", "' ('", 'c.code', "')'"], '') . ' AS useCode')
            ->from(DB::qn('#__organizer_roomtypes', 't'))
            ->innerJoin(DB::qn('#__organizer_use_codes', 'c'), DB::qc('c.id', 't.usecode'))
            ->innerJoin(DB::qn('#__organizer_roomkeys', 'k'), DB::qc('k.id', 'c.keyID'))
            ->innerJoin(DB::qn('#__organizer_use_groups', 'g'), DB::qc('g.id', 'k.useID'));

        $this->filterByKey($query, 'k.cleaningID', 'cleaningID');
        $this->filterByKey($query, 'k.id', 'keyID');
        $this->filterByKey($query, 'k.useID', 'useID');
        $this->filterBinary($query, 'filter.suppress');
        $this->filterSearch($query, ['t.name_de', 't.name_en', 't.capacity', 'c.code']);
        $this->orderBy($query);

        return $query;
    }
}
