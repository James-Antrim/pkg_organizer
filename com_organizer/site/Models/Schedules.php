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

use JDatabaseQuery;
use THM\Organizer\Adapters\{Application, Database, Queries\QueryMySQLi};
use THM\Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of schedules.
 */
class Schedules extends ListModel
{
    protected $filter_fields = ['organizationID', 'termID'];

    /**
     * Method to get a list of resources from the database.
     * @return JDatabaseQuery
     */
    protected function getListQuery(): JDatabaseQuery
    {
        $tag = Application::getTag();
        /* @var QueryMySQLi $query */
        $query = Database::getQuery();

        $createdParts = ['s.creationDate', 's.creationTime'];
        $query->select('s.id, s.creationDate, s.creationTime')
            ->select($query->concatenate($createdParts, ' ') . ' AS created ')
            ->select("o.id AS organizationID, o.shortName_$tag AS organizationName")
            ->select("term.id AS termID, term.name_$tag AS termName")
            ->select('u.name AS userName')
            ->from('#__organizer_schedules AS s')
            ->innerJoin('#__organizer_organizations AS o ON o.id = s.organizationID')
            ->innerJoin('#__organizer_terms AS term ON term.id = s.termID')
            ->leftJoin('#__users AS u ON u.id = s.userID')
            ->order('created DESC');

        $authorized = implode(', ', Helpers\Can::scheduleTheseOrganizations());
        $query->where("o.id IN ($authorized)");

        $this->setValueFilters($query, ['organizationID', 'termID']);

        return $query;
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param string $ordering  An optional ordering field.
     * @param string $direction An optional direction (asc|desc).
     *
     * @return void populates state properties
     */
    protected function populateState($ordering = null, $direction = null)
    {
        parent::populateState($ordering, $direction);

        $app     = Application::getApplication();
        $filters = $app->getUserStateFromRequest($this->context . '.filter', 'filter', [], 'array');

        if (!array_key_exists('active', $filters) or $filters['active'] === '') {
            $this->setState('filter.active', -1);
        }
    }
}
