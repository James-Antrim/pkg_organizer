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
        $url   = 'index.php?option=com_organizer&view=degree&id=';

        $access = [DB::quote(1) . ' AS ' . DB::qn('access')];
        $select = DB::qn(['id', 'name', 'abbreviation', 'code']);
        $url    = [$query->concatenate([DB::quote($url), DB::qn('id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge($select, $access, $url))
            ->from(DB::qn('#__organizer_degrees'))
            ->order(DB::qn('name'));

        $this->filterSearch($query, ['abbreviation', 'code', 'name']);

        return $query;
    }
}
