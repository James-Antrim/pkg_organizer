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

use Joomla\Utilities\ArrayHelper;
use THM\Organizer\Adapters\{Application, Database, User};
use THM\Organizer\Tables;

/**
 * Class provides generalized functions useful for several component files.
 */
class Can
{
    /**
     * Checks whether the user is an authorized administrator.
     * @return bool true if the user is an administrator, otherwise false
     */
    public static function administrate(): bool
    {
        $user = User::instance();

        if (!$user->id) {
            return false;
        }

        return ($user->authorise('core.admin') or $user->authorise('core.admin', 'com_organizer'));
    }

    /**
     * Performs ubiquitous authorization checks. Functions using this with is_bool() will get a false positive for a
     * null return value in their own return value suggestions.
     * @return bool|null true if the user has administrative authorization, false if the user is a guest, otherwise
     *                   null
     */
    private static function basic(): ?bool
    {
        if (!User::id()) {
            return false;
        }

        if (self::administrate()) {
            return true;
        }

        return null;
    }

    /**
     * Checks for user access to event coordination, optionally a specific event
     *
     * @param   string  $resource    the name of the resource to check for coordinating authorization against
     * @param   int     $resourceID  the id of the resource to check against, optional
     *
     * @return bool
     */
    public static function coordinate(string $resource, int $resourceID = 0): bool
    {
        if (is_bool($authorized = self::basic())) {
            return $authorized;
        }

        // If there is no ID there is no course to match against for new courses => falls back to event coordination
        return match ($resource) {
            'Course' => $resourceID ? in_array($resourceID, Courses::coordinates()) : Events::coordinates(),
            'Courses', 'Events' => (bool) Events::coordinates(),
            'Event' => $resourceID ? in_array($resourceID, Events::coordinates()) : Events::coordinates(),
            default => false,
        };

    }

    /**
     * Checks whether the user has access to documentation resources and their respective views.
     *
     * @param   string  $resourceType  the resource type being checked
     * @param   int     $resourceID    the resource id being checked or an array if resource ids to check
     *
     * @return bool true if the user is authorized to document the resources of an/the organization
     */
    public static function document(string $resourceType, int $resourceID = 0): bool
    {
        if (is_bool($authorized = self::basic())) {
            return $authorized;
        }

        if (!in_array($resourceType, ['fieldcolor', 'organization', 'pool', 'program', 'subject'])) {
            return false;
        }

        $query = Database::getQuery();
        $query->select('DISTINCT organizationID')->from('#__organizer_associations');
        $organizationIDs = [];

        if ($resourceID) {
            switch ($resourceType) {
                case 'fieldcolor':
                    $table = new Tables\FieldColors();

                    if (!$table->load($resourceID) or empty($table->organizationID)) {
                        return false;
                    }

                    $organizationIDs[] = $table->organizationID;
                    break;
                case 'organization':
                    $organizationIDs[] = $resourceID;
                    break;
                case 'pool':
                    $query->where("poolID = $resourceID");
                    Database::setQuery($query);

                    if (!$organizationIDs = Database::loadIntColumn()) {
                        return false;
                    }

                    break;
                case 'program':
                    $query->where("programID = $resourceID");
                    Database::setQuery($query);

                    if (!$organizationIDs = Database::loadIntColumn()) {
                        return false;
                    }

                    break;
                case 'subject':

                    if (Subjects::coordinates($resourceID)) {
                        return true;
                    }

                    $query->where("subjectID = $resourceID");
                    Database::setQuery($query);

                    if (!$organizationIDs = Database::loadIntColumn()) {
                        return false;
                    }

                    break;
                default:
                    return false;
            }

            if (!$organizationIDs) {
                return false;
            }
        }
        else {
            Database::setQuery($query);
            $organizationIDs = Database::loadIntColumn();
        }

        $user = User::instance();

        foreach ($organizationIDs as $organizationID) {
            if ($user->authorise('organizer.document', "com_organizer.organization.$organizationID")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the ids of organizations for which the user is authorized documentation access
     * @return int[]  the organization ids, empty if user has no access
     */
    public static function documentTheseOrganizations(): array
    {
        return self::getAuthorizedOrganizations('document');
    }

    /**
     * Checks whether the user has access to the participant information
     *
     * @param   string          $resourceType  the resource type being checked
     * @param   array|int|null  $resource      the resource id being checked or an array if resource ids to check
     *
     * @return bool true if the user is authorized to manage courses, otherwise false
     */
    public static function edit(string $resourceType, array|int|null $resource = null): bool
    {
        if (is_bool($authorized = self::basic())) {
            return $authorized;
        }

        switch ($resourceType) {
            case 'categories':
            case 'category':

                return self::editScheduleResource('Categories', $resource);

            case 'group':
            case 'groups':

                return self::editScheduleResource('Groups', $resource);

            case 'participant':

                if (!is_numeric($resource)) {
                    return false;
                }

                if (User::id() === $resource) {
                    return true;
                }

                return self::manage($resourceType, $resource);

            case 'person':
            case 'persons':

                return $resource ? self::editScheduleResource('Persons', $resource) : self::manage('persons');
        }

        return false;
    }

    /**
     * Returns whether the user is authorized to edit the schedule resource.
     *
     * @param   string     $helperClass  the name of the helper class
     * @param   array|int  $resource     the resource id being checked or an array if resource ids to check
     *
     * @return bool true if the user is authorized to manage courses, otherwise false
     * @noinspection PhpUndefinedMethodInspection
     */
    private static function editScheduleResource(string $helperClass, array|int $resource): bool
    {
        if (empty($resource)) {
            return false;
        }

        $authorized = self::scheduleTheseOrganizations();
        $helper     = "THM\\Organizer\\Helpers\\$helperClass";

        if (is_int($resource)) {
            $associated = $helper::getOrganizationIDs($resource);

            return (bool) array_intersect($associated, $authorized);
        }
        elseif (is_array($resource)) {
            $resource = ArrayHelper::toInteger($resource);

            foreach ($resource as $resourceID) {
                if (!array_intersect($helper::getOrganizationIDs($resourceID), $authorized)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Gets the organization ids of for which the user is authorized access
     *
     * @param   string  $function  the action for authorization
     *
     * @return int[]  the organization ids, empty if user has no access
     */
    private static function getAuthorizedOrganizations(string $function): array
    {
        if (!User::id()) {
            return [];
        }

        $organizationIDs = Organizations::getIDs();

        if (self::administrate()) {
            return $organizationIDs;
        }

        if (!method_exists('THM\\Organizer\\Helpers\\Can', $function)) {
            return [];
        }

        $authorized = [];

        foreach ($organizationIDs as $organizationID) {
            if (self::$function('organization', $organizationID)) {
                $authorized[] = $organizationID;
            }
        }

        return $authorized;
    }

    /**
     * Checks whether the user can manage the given resource.
     *
     * @param   string  $resourceType  the resource type being checked
     * @param   int     $resourceID    the resource id being checked or an array if resource ids to check
     *
     * @return bool true if the user is authorized for scheduling functions and views.
     */
    public static function manage(string $resourceType, int $resourceID = 0): bool
    {
        if (is_bool($authorized = self::basic())) {
            return $authorized;
        }

        switch ($resourceType) {
            case 'booking':
            case 'bookings':
                foreach (Bookings::getInstanceIDs($resourceID) as $instanceID) {
                    if (self::manage('instance', $instanceID)) {
                        return true;
                    }
                }

                return false;
            case 'instance':
            case 'instances':
                if (Instances::hasResponsibility($resourceID)) {
                    return true;
                }

                $instanceOrganizations = Instances::getOrganizationIDs($resourceID);
                $managedOrganizations  = self::manageTheseOrganizations();

                return (bool) array_intersect($managedOrganizations, $instanceOrganizations);
            case 'facilities':
                return User::instance()->authorise('organizer.fm', 'com_organizer');
            case 'organization':
                return User::instance()->authorise('organizer.manage', "com_organizer.organization.$resourceID");
            case 'participant':
                if ($resourceID === User::id()) {
                    return true;
                }

                $courseIDs = Participants::getCourseIDs($resourceID);

                return (bool) array_intersect($courseIDs, Courses::coordinates());
            case 'persons':
                return User::instance()->authorise('organizer.hr', 'com_organizer');
            case 'unit':
            case 'units':
                if (Units::teaches($resourceID)) {
                    return true;
                }

                return in_array(Units::getOrganizationID($resourceID), self::manageTheseOrganizations());
            default:
                return false;
        }
    }

    /**
     * Gets the ids of organizations for which the user is authorized managing access
     * @return int[]  the organization ids, empty if user has no access
     */
    public static function manageTheseOrganizations(): array
    {
        return self::getAuthorizedOrganizations('manage');
    }

    /**
     * Checks whether the user has access to scheduling resources and their respective views.
     *
     * @param   string  $resourceType  the resource type being checked
     * @param   int     $resourceID    the resource id being checked or an array if resource ids to check
     *
     * @return bool true if the user is authorized for scheduling functions and views.
     */
    public static function schedule(string $resourceType, int $resourceID): bool
    {
        if (is_bool($authorized = self::basic())) {
            return $authorized;
        }

        if (!$resourceID) {
            return false;
        }

        $user = User::instance();

        if ($resourceType === 'schedule') {
            $schedule = new Tables\Schedules();

            if (!$schedule->load($resourceID)) {
                return false;
            }

            return $user->authorise('organizer.schedule', "com_organizer.organization.$schedule->organizationID");
        }

        if ($resourceType === 'organization') {
            return $user->authorise('organizer.schedule', "com_organizer.organization.$resourceID");
        }

        return false;
    }

    /**
     * Gets the ids of organizations for which the user is authorized scheduling access
     * @return int[]  the organization ids, empty if user has no access
     */
    public static function scheduleTheseOrganizations(): array
    {
        return self::getAuthorizedOrganizations('schedule');
    }

    /**
     * Check whether the user is authorized to perform contact tracing.
     * @return bool
     */
    public static function traceContacts(): bool
    {
        if (is_bool($authorized = self::basic())) {
            return $authorized;
        }

        return User::instance()->authorise('organizer.ct', 'com_organizer');
    }

    /**
     * Checks whether the user has viewing access to the view.
     *
     * @param   string  $view        the name of the view being accessed
     * @param   int     $resourceID  the optional resource id
     *
     * @return bool true if the user is authorized for scheduling functions and views.
     */
    public static function view(string $view, int $resourceID = 0): bool
    {
        // Preempt any more complicated authorization checks.
        if (self::administrate()) {
            return true;
        }

        return match ($view) {
            // Administrative views and admin access was already checked
            'Color', 'Colors', 'Degree', 'Degrees', 'Field', 'Fields', 'Grid', 'Grids', 'Holiday', 'Holidays', 'Method',
            'Methods', 'Organization', 'Organizations', 'Participant', 'Participants', 'Run', 'Runs', 'Term', 'Terms'
            => false,
            // Scheduling resources and views with no intrinsic public value and import forms
            'Categories', 'CoursesImport', 'Groups', 'MergeCategories', 'MergeEvents', 'Schedule', 'Schedules',
            'Units'
            => (bool) self::scheduleTheseOrganizations(),
            // Edit views for scheduling resource with no intrinsic public value
            'Category', 'Group', 'Unit'
            => self::edit(strtolower($view), $resourceID),
            // Special dispensation for coordinators and teachers
            'Event' => self::coordinate($resourceID),
            'Events' => self::coordinate('events'),
            // Curriculum resources with no intrinsic public value
            'FieldColors', 'Pools', 'PoolSelection', 'SubjectSelection'
            => (bool) self::documentTheseOrganizations(),
            // Curriculum resources with public value
            'Programs', 'Subjects'
            => (!Application::backend() or self::documentTheseOrganizations()),
            // Edit views for curriculum resource with no intrinsic public value
            'FieldColor', 'Pool' => self::document(strtolower($view), $resourceID),
            // Edit views for curriculum resource with intrinsic public value
            'Program', 'Subject' => (!Application::backend() or self::document(strtolower($view), $resourceID)),
            'MergePersons', 'Person', 'Persons'
            => self::manage('persons'),
            // Facility resource views
            'Building', 'Buildings', 'Campus', 'Campuses', 'CleaningGroup', 'CleaningGroups', 'Equipment',
            'EquipmentItem', 'MergeRooms', 'Monitor', 'Monitors', 'Room', 'RoomKey', 'RoomKeys', 'Rooms',
            => self::manage('facilities'),

            /**
             * Restricted views with possible access over a login redirect
             * Booking, ContactTracking, CourseParticipants, Profile
             * Restricted views with complex authorization
             * MergeParticipants
             * Views restricted by view access levels
             * Statistics, Workload
             * Viewing is generally allowed, however functions, layouts and levels may still be restricted elsewhere.
             * Course, Courses, InstanceItem, Instances
             * Unrestricted
             * Curriculum, Help, Screen
             */
            default => true
        };
    }

    /**
     * Gets the ids of organizations for which the user is authorized privileged view access
     * @return int[]  the organization ids, empty if user has no access
     */
    public static function viewTheseOrganizations(): array
    {
        return self::getAuthorizedOrganizations('view');
    }
}
