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
        $tag   = Application::getTag();
        $query = DB::getQuery();

        $select  = DB::qn(['id', 'grid', 'isDefault']);
        $aliased = [DB::qn("name_$tag", 'name')];
        $query->select(array_merge($select, $aliased))->from(DB::qn('#__organizer_grids'));
        $this->orderBy($query);

        return $query;
    }
}
