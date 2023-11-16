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
use THM\Organizer\Adapters\{Application, Database};
use THM\Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of categories.
 */
class Categories extends ListModel
{
    use Activated;

    protected string $defaultOrdering = 'name';

    protected $filter_fields = ['organizationID'];

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $tag = Application::getTag();

        $query = Database::getQuery();
        $query->select("DISTINCT cat.id, cat.code, cat.name_$tag AS name, cat.active")
            ->from('#__organizer_categories AS cat')
            ->innerJoin('#__organizer_associations AS a ON a.categoryID = cat.id');

        $authorized = implode(",", Helpers\Can::scheduleTheseOrganizations());
        $query->where("a.organizationID IN ($authorized)");

        $this->filterActive($query, 'cat');
        $this->filterSearch($query, ['cat.name_de', 'cat.name_en', 'cat.code']);
        $this->filterValues($query, ['organizationID', 'programID']);
        $this->orderBy($query);

        return $query;
    }
}
