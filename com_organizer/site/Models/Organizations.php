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

/**
 * Class retrieves information for a filtered set of organizations.
 */
class Organizations extends ListModel
{
    /**
     * Method to get a list of resources from the database.
     * @return DatabaseQuery
     */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::getQuery();
        $tag   = Application::getTag();
        $url   = 'index.php?option=com_organizer&view=Organization&id=';

        if (Can::administrate()) {
            $access = [DB::quote(1) . ' AS ' . DB::qn('access')];
        }
        elseif ($ids = Can::manageTheseOrganizations()) {
            $access = [DB::qn('o.id') . ' IN (' . implode(',', $ids) . ')' . ' AS ' . DB::qn('access')];
        }
        else {
            $access = [DB::quote(0) . ' AS ' . DB::qn('access')];
        }

        $aliased = DB::qn(["o.fullName_$tag", "o.shortName_$tag"], ['name', 'shortName']);
        $url     = [$query->concatenate([DB::quote($url), DB::qn('o.id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge([DB::qn('o.id'), DB::qn('a.rules')], $access, $aliased, $url))
            ->from(DB::qn('#__organizer_organizations', 'o'))
            ->innerJoin(DB::qn('#__assets', 'a'), DB::qn('a.id') . ' = ' . DB::qn('o.asset_id'));

        $this->addAccess($query);

        $searchColumns = [
            'abbreviation_de',
            'abbreviation_en',
            'fullName_de',
            'fullName_en',
            'name_de',
            'name_en',
            'shortName_de',
            'shortName_en'
        ];

        $this->filterSearch($query, $searchColumns);
        $this->orderBy($query);

        return $query;
    }
}
