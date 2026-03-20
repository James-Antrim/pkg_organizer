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

/** @inheritDoc */
class Nomina extends ListModel
{
    protected $filter_fields = ['name', 'abbreviation', 'code'];

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::query();
        $tag   = Application::tag();
        $url   = 'index.php?option=com_organizer&view=nomen&id=';

        $access  = [DB::quote(1) . ' AS ' . DB::qn('access')];
        $aliased = DB::qn(["alias_$tag", "name_$tag"], ['alias', 'name']);
        $select  = DB::qn(['id', 'code', 'statisticCode']);
        $url     = [$query->concatenate([DB::quote($url), DB::qn('id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge($select, $access, $aliased, $url))
            ->from(DB::qn('#__organizer_nomina'))
            ->order(DB::qn("name_$tag"));

        $this->filterSearch($query, ['alias_de', 'alias_en', 'code', 'name_de', 'name_en', 'statisticCode']);

        return $query;
    }
}
