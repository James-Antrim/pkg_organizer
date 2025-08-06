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

use THM\Organizer\Adapters\{Application, User};

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
        if (!User::id()) {
            return false;
        }

        return (User::authorise() or User::authorise('core.admin', 'com_organizer'));
    }

    /**
     * Performs ubiquitous authorization checks for three fundamental values:
     * 0 - unauthenticated
     * null - indeterminate
     * 1 - component/site administrator
     * @return bool|null
     */
    public static function basic(): ?bool
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
     * Checks whether the user has access to the participant information
     *
     * @param   string          $resourceType  the resource type being checked
     * @param   array|int|null  $resource      the resource id being checked or an array if resource ids to check
     *
     * @return bool
     */
    public static function edit(string $resourceType, array|int|null $resource = null): bool
    {
        if (is_bool($authorized = self::basic())) {
            return $authorized;
        }

        switch ($resourceType) {
            case 'categories':
            case 'category':

                return Categories::schedulable($resource);

            case 'group':
            case 'groups':

                return Groups::schedulable($resource);

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
                if (self::manage('persons')) {
                    return true;
                }
                elseif ($resource) {
                    return Persons::schedulable($resource);
                }
                return false;
        }

        return false;
    }

    /**
     * Returns whether the user has access to manage facilities.
     * @return bool
     */
    public static function fm(): bool
    {
        if (is_bool($authorized = self::basic())) {
            return $authorized;
        }

        return User::authorise('organizer.fm', 'com_organizer');
    }

    /**
     * Checks whether the user can manage the given resource.
     *
     * @param   string  $resourceType  the resource type being checked
     * @param   int     $resourceID    the resource id being checked or an array if resource ids to check
     *
     * @return bool
     */
    public static function manage(string $resourceType, int $resourceID = 0): bool
    {
        if (is_bool($authorized = self::basic())) {
            return $authorized;
        }

        switch ($resourceType) {
            case 'booking':
            case 'bookings':
                foreach (Bookings::instanceIDs($resourceID) as $instanceID) {
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

                $iOrganizations = Instances::getOrganizationIDs($resourceID);
                $mOrganizations  = Organizations::manageableIDs();

                return (bool) array_intersect($mOrganizations, $iOrganizations);
            case 'participant':
                if ($resourceID === User::id()) {
                    return true;
                }

                $courseIDs = Participants::getCourseIDs($resourceID);

                return (bool) array_intersect($courseIDs, Courses::coordinatableIDs());
            case 'persons':
                return User::authorise('organizer.hr', 'com_organizer');
            case 'unit':
            case 'units':
                if (Units::teaches($resourceID)) {
                    return true;
                }

                return in_array(Units::getOrganizationID($resourceID), Organizations::manageableIDs());
            default:
                return false;
        }
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

        return User::authorise('organizer.ct', 'com_organizer');
    }

    /**
     * Checks whether the user has viewing access to the view.
     *
     * @param   string  $view        the name of the view being accessed
     * @param   int     $resourceID  the optional resource id
     *
     * @return bool
     */
    public static function view(string $view, int $resourceID = 0): bool
    {
        // Preempt any more complicated authorization checks.
        if (self::administrate()) {
            return true;
        }

        return match ($view) {
            // Administrative / developmental views and admin access was already checked
            'Color', 'Colors', 'Degree', 'Degrees', 'Equipment', 'EquipmentItem', 'Field', 'Fields', 'Grid', 'Grids',
            'Holiday', 'Holidays', 'ImportRooms', 'MergeCategories', 'MergeEvents', 'MergeGroups', 'MergePersons',
            'MergeRooms', 'Method', 'Methods', 'Organization', 'Organizations', 'Participant', 'Participants', 'Run',
            'Runs', 'Term', 'Terms'
            => false,
            // Scheduling resources and views with no intrinsic public value and import forms
            'Categories', 'Groups', 'ImportCourses', 'ImportSchedule', 'Schedules', 'Units'
            => (bool) Organizations::schedulableIDs(),
            // Edit views for scheduling resource with no intrinsic public value
            'Category', 'Group', 'Unit'
            => self::edit(strtolower($view), $resourceID),
            // Special dispensation for coordinators and teachers
            'Event' => Events::coordinatable($resourceID),
            'Events' => Events::coordinatable(),
            // Curriculum resources with no intrinsic public value
            'FieldColors', 'Pools', 'PoolSelection', 'SubjectSelection'
            => (bool) Organizations::documentableIDs(),
            // Curriculum resources with public value
            'Programs', 'Subjects'
            => (!Application::backend() or Organizations::documentableIDs()),
            // Edit views for curriculum resource with no intrinsic public value
            'FieldColor' => FieldColors::documentable($resourceID),
            'Pool' => Pools::documentable($resourceID),
            // Edit views for curriculum resource with intrinsic public value
            'Program' => (!Application::backend() or Programs::documentable($resourceID)),
            'Subject' => (!Application::backend() or Subjects::documentable($resourceID)),
            'Person', 'Persons' => self::manage('persons'),
            // Facility resource views
            'Building', 'Buildings', 'Campus', 'Campuses', 'CleaningGroup', 'CleaningGroups', 'Monitor', 'Monitors',
            'Room', 'RoomKey', 'RoomKeys', 'Rooms',
            => self::fm(),

            /**
             * Restricted views with possible access over a login redirect
             * Booking, ContactTracking, CourseParticipants, Profile
             *
             * Restricted views with complex authorization
             * MergeParticipants
             *
             * Views restricted by view access levels
             * Statistics, Workload
             *
             * Viewing is generally allowed, however functions, layouts and levels may still be restricted elsewhere.
             * Course, Courses, InstanceItem, Instances
             *
             * Unrestricted
             * Curriculum, Help, Screen
             */
            default => true
        };
    }
}
