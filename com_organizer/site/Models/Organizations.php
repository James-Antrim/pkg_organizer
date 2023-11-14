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
use Joomla\Database\QueryInterface;
use THM\Organizer\Helpers\Can;

/**
 * Class retrieves information for a filtered set of organizations.
 */
class Organizations extends ListModel
{
    protected string $defaultOrdering = 'shortName';

    /**
     * @inheritdoc
     */
    protected function addAccess(QueryInterface $query): void
    {
        if ($ids = Can::manageTheseOrganizations()) {
            $query->select(DB::qn('o.id') . ' IN (' . implode(',', $ids) . ')' . ' AS ' . DB::qn('access'));
        }
        else {
            $query->select(DB::quote(0) . ' AS ' . DB::qn('access'));
        }
    }

    /**
     * Method to get a list of resources from the database.
     * @return DatabaseQuery
     */
    protected function getListQuery(): DatabaseQuery
    {
        $link  = 'index.php?option=com_organizer&view=Organization&id=';
        $query = DB::getQuery();
        $tag   = Application::getTag();

        $aliased = DB::qn(["o.fullName_$tag", "o.shortName_$tag"], ['name', 'shortName']);
        $link    = [$query->concatenate([DB::quote($link), DB::qn('o.id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge([DB::qn('o.id'), DB::qn('a.rules')], $aliased, $link))
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

        $this->setSearchFilter($query, $searchColumns);
        $this->orderBy($query);

        return $query;
    }
}
