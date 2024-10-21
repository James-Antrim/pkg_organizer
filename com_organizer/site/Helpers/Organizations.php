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

use Joomla\Database\ParameterType;
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input, User};
use THM\Organizer\Tables\Organizations as Table;

/**
 * Provides general functions for organization access checks, data retrieval and display.
 */
class Organizations extends ResourceHelper implements Documentable, Schedulable, Selectable
{
    use Active;
    use Numbered;

    /**
     * Checks whether direct scheduling has been allowed for the given organization id.
     *
     * @param   int  $organizationID  the id of the organization
     *
     * @return bool true if direct scheduling is allowed otherwise false
     */
    public static function allowsScheduling(int $organizationID): bool
    {
        $organization = new Table();

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
        $tag   = Application::tag();
        $query = DB::getQuery();
        $query->select(array_merge(DB::qn(['c.id', 'code'], [DB::qn("name_$tag", 'name')])))
            ->from(DB::qn('#__organizer_categories', 'c'))
            ->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.categoryID', 'c.id'))
            ->where(DB::qn('a.organizationID') . ' = :organizationID')
            ->bind(':organizationID', $organizationID, ParameterType::INTEGER);

        if ($active) {
            $query->where(DB::qn('c.active') . ' = 1');
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
        $query->select([DB::qn('u.gridID'), 'COUNT(*) AS ' . DB::qn('occurrences')])
            ->from(DB::qn('#__organizer_units', 'u'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.unitID', 'u.id'))
            ->innerJoin(DB::qn('#__organizer_instance_persons', 'ipe'), DB::qc('ipe.instanceID', 'i.id'))
            ->innerJoin(DB::qn('#__organizer_instance_groups', 'ig'), DB::qc('ig.assocID', 'ipe.id'))
            ->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.groupID', 'ig.groupID'))
            ->where(DB::qn('a.organizationID') . ' = :organizationID')
            ->bind(':organizationID', $organizationID, ParameterType::INTEGER)
            ->group(DB::qn('u.gridID'))
            ->order(DB::qn('occurrences') . ' DESC');
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
        if (!User::id()) {
            return [];
        }

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
        $tag   = Application::tag();
        $query->select(['DISTINCT ' . DB::qn('o') . '.*', DB::qn("o.shortName_$tag", 'shortName'), DB::qn("o.name_$tag", 'name')])
            ->from(DB::qn('#__organizer_organizations', 'o'));

        if ($access) {
            $allowedIDs = [];
            $view       = strtolower(Input::getView());

            switch ($access) {
                case 'schedule':

                    $allowedIDs = self::schedulableIDs();
                    $query->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.organizationID', 'o.id'));

                    switch ($view) {
                        case 'categories':
                            $query->where(DB::qn('a.categoryID') . ' IS NOT NULL');
                            break;
                        case 'events':
                            $query->innerJoin(DB::qn('#__organizer_events', 'e'), DB::qc('e.id', 'o.id'));
                            break;
                        case 'groups':
                            $query->where(DB::qn('a.groupID') . ' IS NOT NULL');
                            break;
                        case 'schedules':
                        default:
                            break;
                    }

                    break;

                case 'document':

                    $allowedIDs = self::documentableIDs();
                    $query->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.organizationID', 'o.id'));

                    switch ($view) {
                        case 'pools':
                            $query->where(DB::qn('a.poolID') . ' IS NOT NULL');
                            break;
                        case 'programs':
                            $query->where(DB::qn('a.programID') . ' IS NOT NULL');
                            break;
                        case 'subjects':
                            $query->where(DB::qn('a.subjectID') . ' IS NOT NULL');
                            break;
                        case 'schedules':
                        default:
                            break;
                    }

                    break;
                case 'teach':
                    $managedIDs     = Can::manageTheseOrganizations();
                    $schedulableIDs = self::schedulableIDs();
                    $taughtIDs      = Persons::taughtOrganizations();
                    $viewedIDs      = Can::viewTheseOrganizations();
                    $allowedIDs     = array_merge($managedIDs, $schedulableIDs, $taughtIDs, $viewedIDs);
                    break;
                default:
                    // Requested authorization does not exist for the organization resource.
                    Application::error(501);
            }

            $query->whereIn(DB::qn('o.id'), $allowedIDs);
        }

        DB::setQuery($query);

        return DB::loadAssocList('id');
    }

    /**
     * @inheritDoc
     */
    public static function schedulable(int $resourceID): bool
    {
        return User::instance()->authorise('organizer.schedule', "com_organizer.organization.$resourceID");
    }

    /**
     * @inheritDoc
     */
    public static function schedulableIDs(): array
    {
        if (!User::id()) {
            return [];
        }

        $organizationIDs = self::getIDs();

        foreach ($organizationIDs as $index => $organizationID) {
            if (!self::schedulable($organizationID)) {
                unset($organizationIDs[$index]);
            }
        }

        return $organizationIDs;
    }
}
