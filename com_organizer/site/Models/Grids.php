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

/**
 * Class retrieves information for a filtered set of (schedule) grids.
 */
class Grids extends ListModel
{
    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::getQuery();
        $tag   = Application::getTag();
        $url   = 'index.php?option=com_organizer&view=grid&id=';

        $access  = [DB::quote(1) . ' AS ' . DB::qn('access')];
        $aliased = [DB::qn("name_$tag", 'name')];
        $url     = [$query->concatenate([DB::quote($url), DB::qn('id')], '') . ' AS ' . DB::qn('url')];
        $select  = DB::qn(['id', 'grid', 'isDefault']);

        $query->select(array_merge($select, $access, $aliased, $url))->from(DB::qn('#__organizer_grids'));
        $this->orderBy($query);

        return $query;
    }
}
