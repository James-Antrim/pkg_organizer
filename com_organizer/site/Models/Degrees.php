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
use THM\Organizer\Adapters\Database as DB;

/**
 * Class retrieves information for a filtered set of degrees.
 */
class Degrees extends ListModel
{
    protected $filter_fields = ['name', 'abbreviation', 'code'];

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::getQuery();
        $query->select(DB::qn(['id', 'name', 'abbreviation', 'code']))
            ->from(DB::qn('#__organizer_degrees'));

        $columns = ['name', 'abbreviation', 'code'];
        $this->setSearchFilter($query, $columns);
        $this->orderBy($query);

        return $query;
    }
}
