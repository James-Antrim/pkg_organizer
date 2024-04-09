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
    private const EXPIRED = 1, NOT_EXPIRED = 0;

    protected $filter_fields = ['name', 'abbreviation', 'code'];

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::getQuery();
        $tag   = Application::getTag();
        $url   = 'index.php?option=com_organizer&view=term&id=';

        // Admin access required for view.
        $access  = [DB::quote(1) . ' AS ' . DB::qn('access')];
        $select  = DB::qn(['id', 'startDate', 'endDate']);
        $url     = [$query->concatenate([DB::quote($url), DB::qn('id')], '') . ' AS ' . DB::qn('url')];
        $aliased = [DB::qn("fullName_$tag", 'term')];

        $query->select(array_merge($select, $access, $aliased, $url))
            ->from(DB::qn('#__organizer_terms'))
            ->order(DB::qn('startDate') . ' DESC');

        $qED = DB::qn('endDate');
        switch ((int) $this->state->get('filter.status')) {
            case self::EXPIRED:
                $query->where($qED . ' < CURDATE()');
                break;
            case self::NOT_EXPIRED:
                $query->where($qED . ' >= CURDATE()');
                break;
        }

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        $status = (int) $this->state->get('filter.status');
        $this->setState('filter.status', $status);
    }
}
