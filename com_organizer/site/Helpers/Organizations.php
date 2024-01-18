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

use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input, User};
use THM\Organizer\Tables;

/**
 * Provides general functions for organization access checks, data retrieval and display.
 */
class Organizations extends ResourceHelper implements Documentable, Selectable
{
    use Active;
    use Numbered;

    /**
     * Filters organizations according to user access and relevant resource associations.
     *
     * @param   DatabaseQuery  $query   the query to modify
     * @param   string         $access  any access restriction which should be performed
     *
     * @return void modifies the query
     */
    private static function addAccessFilter(DatabaseQuery $query, string $access): void
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
                $allowedIDs = self::documentableIDs();
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
     * @param   int  $organizationID  the id of the organization
     *
     * @return bool true if direct scheduling is allowed otherwise false
     */
    public static function allowsScheduling(int $organizationID): bool
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
     * @param   int   $organizationID  the organization to filter categories against
     * @param   bool  $active          whether to filter out inactive categories
     *
     * @return array[]
     */
    public static function categories(int $organizationID, bool $active = true): array
    {
        $tag   = Application::getTag();
        $query = DB::getQuery();
        $query->select("c.id, code, name_$tag AS name")
            ->from('#__organizer_categories AS c')
            ->innerJoin('#__organizer_associations AS a ON a.categoryID = c.id')
            ->where("a.organizationID = $organizationID");

        if ($active) {
            $query->where('c.active = 1');
        }

        DB::setQuery($query);

        return DB::loadAssocList();
    }

    /**
     * The default grid for an organization defined by current organization grid usage. 0 if no usage is available.
     *
     * @param   int  $organizationID
     *
     * @return int
     */
    public static function defaultGrid(int $organizationID): int
    {
        $query = DB::getQuery();
        $query->select('u.gridID, COUNT(*) AS occurrences')
            ->from('#__organizer_units AS u')
            ->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
            ->innerJoin('#__organizer_instance_persons AS ipe ON ipe.instanceID = i.id')
            ->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ipe.id')
            ->innerJoin('#__organizer_associations AS a ON a.groupID = ig.groupID')
            ->where("a.organizationID = $organizationID")
            ->group('u.gridID')
            ->order('occurrences DESC');
        DB::setQuery($query);

        if ($results = DB::loadAssoc()) {
            return (int) $results['gridID'];
        }

        return 0;
    }

    /**
     * @inheritDoc
     */
    public static function documentable(int $resourceID): bool
    {
        return User::instance()->authorise('organizer.document', "com_organizer.organization.$resourceID");
    }

    /**
     * @inheritDoc
     */
    public static function documentableIDs(): array
    {
        $organizationIDs = self::getIDs();

        foreach ($organizationIDs as $index => $organizationID) {
            if (!self::documentable($organizationID)) {
                unset($organizationIDs[$index]);
            }
        }

        return $organizationIDs;
    }

    /**
     * @inheritDoc
     *
     * @param   bool    $short   whether abbreviated names should be returned
     * @param   string  $access  any access restriction which should be performed
     */
    public static function options(bool $short = true, string $access = ''): array
    {
        $options = [];
        foreach (self::resources($access) as $organization) {
            if ($organization['active']) {
                $name = $short ? $organization['shortName'] : $organization['name'];

                $options[] = HTML::option($organization['id'], $name);
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
     * @param   int  $organizationID
     *
     * @return int[]
     */
    public static function personIDs(int $organizationID): array
    {
        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('personID'))
            ->from(DB::qn('#__organizer_associations'))
            ->whereIn(DB::qn('organizationID'), [$organizationID]);
        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function resources(string $access = ''): array
    {
        $query = DB::getQuery();
        $tag   = Application::getTag();
        $query->select(['DISTINCT ' . DB::qn('o') . '.*', DB::qn("o.shortName_$tag", 'shortName'), DB::qn("o.name_$tag", 'name')])
            ->from(DB::qn('#__organizer_organizations', 'o'));
        self::addAccessFilter($query, $access);
        DB::setQuery($query);

        return DB::loadAssocList('id');
    }
}
