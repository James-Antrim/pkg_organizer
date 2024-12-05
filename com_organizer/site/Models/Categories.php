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
use THM\Organizer\Helpers\Organizations;

/**
 * Class retrieves information for a filtered set of categories.
 */
class Categories extends ListModel
{
    use Activated;

    protected string $defaultOrdering = 'name';

    protected $filter_fields = ['organizationID'];

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::query();
        $tag   = Application::tag();
        $url   = 'index.php?option=com_organizer&view=category&id=';

        $select = [
            'DISTINCT ' . DB::qn('cat.id'),
            DB::quote(1) . ' AS ' . DB::qn('access'),
            DB::qn('cat.active'),
            DB::qn('cat.code'),
            DB::qn("cat.name_$tag", 'name'),
            $query->concatenate([DB::quote($url), DB::qn('cat.id')], '') . ' AS ' . DB::qn('url'),
        ];

        $query->select($select)
            ->from(DB::qn('#__organizer_categories', 'cat'))
            ->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.categoryID', 'cat.id'))
            ->whereIn(DB::qn('a.organizationID'), Organizations::schedulableIDs());

        $this->activeFilter($query, 'cat');
        $this->filterSearch($query, ['cat.name_de', 'cat.name_en', 'cat.code']);
        $this->filterValues($query, ['organizationID', 'programID']);
        $this->orderBy($query);

        return $query;
    }
}
