<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use JDatabaseQuery;
use THM\Organizer\Adapters\{Application, Database};
use THM\Organizer\Tables;

/**
 * Provides general functions for organization access checks, data retrieval and display.
 */
class Organizations extends ResourceHelper implements Selectable
{
    use Numbered;

    /**
     * Filters organizations according to user access and relevant resource associations.
     *
     * @param JDatabaseQuery $query  the query to modify
     * @param string         $access any access restriction which should be performed
     *
     * @return void modifies the query
     */
    private static function addAccessFilter(JDatabaseQuery $query, string $access)
    {
        if (!$access or !$view = Input::getView()) {
            return;
        }

        $resource = OrganizerHelper::getResource($view);

        switch ($access) {
            case 'allowScheduling':
                $query->innerJoin('#__organizer_associations AS a ON a.organizationID = o.id')
                    ->where('o.allowScheduling = 1');
                if (in_array($resource, ['category', 'person'])) {
                    $query->where("a.{$resource}ID IS NOT NULL");
                }
                $allowedIDs = Can::scheduleTheseOrganizations();
                break;
            case 'document':
                $query->innerJoin('#__organizer_associations AS a ON a.organizationID = o.id');
                if (in_array($resource, ['pool', 'program', 'subject'])) {
                    $query->where("a.{$resource}ID IS NOT NULL");
                }
                $allowedIDs = Can::documentTheseOrganizations();
                break;
            case 'manage':
                $allowedIDs = Can::manageTheseOrganizations();
                break;
            case 'schedule':
                $query->innerJoin('#__organizer_associations AS a ON a.organizationID = o.id');
                if (in_array($resource, ['category', 'person'])) {
                    $query->where("a.{$resource}ID IS NOT NULL");
                }
                $allowedIDs = Can::scheduleTheseOrganizations();
                break;
            case 'teach':
                $managedIDs   = Can::manageTheseOrganizations();
                $scheduledIDs = Can::scheduleTheseOrganizations();
                $taughtIDs    = Persons::taughtOrganizations();
                $viewedIDs    = Can::viewTheseOrganizations();
                $allowedIDs   = array_merge($managedIDs, $scheduledIDs, $taughtIDs, $viewedIDs);
                break;
            case 'view':
                $allowedIDs = Can::viewTheseOrganizations();
                break;
            default:
                // Access right does not exist for organization resource.
                return;
        }

        $query->where("o.id IN ( '" . implode("', '", $allowedIDs) . "' )");
    }

    /**
     * Checks whether direct scheduling has been allowed for the given organization id.
     *
     * @param int $organizationID the id of the organization
     *
     * @return bool true if direct scheduling is allowed otherwise false
     */
    public static function allowScheduling(int $organizationID): bool
    {
        $organization = new Tables\Organizations();

        if (!$organization->load($organizationID)) {
            Application::error(412);
        }

        return $organization->allowScheduling;
    }

    /**
     * Gets the categories associated with a given organization.
     *
     * @param int  $organizationID the organization to filter categories against
     * @param bool $active         whether to filter out inactive categories
     *
     * @return array[]
     */
    public static function getCategories(int $organizationID, bool $active = true): array
    {
        $tag   = Application::getTag();
        $query = Database::getQuery();
        $query->select("c.id, code, name_$tag AS name")
            ->from('#__organizer_categories AS c')
            ->innerJoin('#__organizer_associations AS a ON a.categoryID = c.id')
            ->where("a.organizationID = $organizationID");

        if ($active) {
            $query->where('c.active = 1');
        }

        Database::setQuery($query);

        return Database::loadAssocList();
    }

    /**
     * The default grid for an organization defined by current organization grid usage. 0 if no usage is available.
     *
     * @param int $organizationID
     *
     * @return int
     */
    public static function getDefaultGrid(int $organizationID): int
    {
        $query = Database::getQuery();
        $query->select('u.gridID, COUNT(*) AS occurrences')
            ->from('#__organizer_units AS u')
            ->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
            ->innerJoin('#__organizer_instance_persons AS ipe ON ipe.instanceID = i.id')
            ->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ipe.id')
            ->innerJoin('#__organizer_associations AS a ON a.groupID = ig.groupID')
            ->where("a.organizationID = $organizationID")
            ->group('u.gridID')
            ->order('occurrences DESC');
        Database::setQuery($query);

        if ($results = Database::loadAssoc()) {
            return (int) $results['gridID'];
        }

        return 0;
    }

    /**
     * @inheritDoc
     *
     * @param bool   $short  whether abbreviated names should be returned
     * @param string $access any access restriction which should be performed
     */
    public static function getOptions(bool $short = true, string $access = ''): array
    {
        $options = [];
        foreach (self::getResources($access) as $organization) {
            if ($organization['active']) {
                $name = $short ? $organization['shortName'] : $organization['name'];

                $options[] = HTML::_('select.option', $organization['id'], $name);
            }
        }

        uasort($options, function ($optionOne, $optionTwo) {
            return strcmp($optionOne->text, $optionTwo->text);
        });

        // Any out of sequence indexes cause JSON to treat this as an object
        return array_values($options);
    }

    /**
     * Retrieves a set of personIDs associated with the given organization.
     *
     * @param int $organizationID
     *
     * @return int[]
     */
    public static function getPersonIDs(int $organizationID): array
    {
        $query = Database::getQuery();
        $query->selectX('DISTINCT personID', 'associations', 'organizationID', [$organizationID]);
        Database::setQuery($query);

        return Database::loadIntColumn();
    }

    /**
     * @inheritDoc
     *
     * @param string $access any access restriction which should be performed
     */
    public static function getResources(string $access = ''): array
    {
        $query = Database::getQuery();
        $tag   = Application::getTag();
        $query->select("DISTINCT o.*, o.shortName_$tag AS shortName, o.name_$tag AS name")
            ->from('#__organizer_organizations AS o');
        self::addAccessFilter($query, $access);
        Database::setQuery($query);

        return Database::loadAssocList('id');
    }

    /**
     * Checks whether the plan resource is already associated with an organization, creating an entry if none already
     * exists.
     *
     * @param int    $resourceID the db id for the plan resource
     * @param string $column     the column in which the resource information is stored
     *
     * @return void
     */
    public static function setResource(int $resourceID, string $column)
    {
        $associations = new Tables\Associations();

        /**
         * If associations already exist for the resource, further associations should be made explicitly using the
         * appropriate edit view.
         */
        $data = [$column => $resourceID];
        if ($associations->load($data)) {
            return;
        }

        $data['organizationID'] = Input::getInt('organizationID');
        $associations->save($data);
    }
}
