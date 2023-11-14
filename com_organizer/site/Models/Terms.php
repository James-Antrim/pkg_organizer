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
 * Class retrieves information for a filtered set of degrees.
 */
class Terms extends ListModel
{
    protected $filter_fields = ['name', 'abbreviation', 'code'];

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $link  = 'index.php?option=com_organizer&view=Term&id=';
        $query = DB::getQuery();
        $tag   = Application::getTag();

        // Admin access required for view.
        $access  = [DB::quote(1) . ' AS ' . DB::qn('access')];
        $select  = DB::qn(['id', 'startDate', 'endDate']);
        $link    = [$query->concatenate([DB::quote($link), DB::qn('id')], '') . ' AS ' . DB::qn('url')];
        $aliased = [DB::qn("fullName_$tag", 'term')];

        $query->select(array_merge($select, $access, $aliased, $link))
            ->from(DB::qn('#__organizer_terms'))
            ->order(DB::qn('startDate') . ' DESC');

        return $query;
    }
}
