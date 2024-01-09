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

use Joomla\Database\{DatabaseQuery, ParameterType};
use Joomla\Utilities\ArrayHelper;
use THM\Organizer\Adapters\{Application, Database as DB, Input, User};
use THM\Organizer\Tables;
use THM\Organizer\Tables\{Blocks as Block, Instances as Instance};
use THM\Organizer\Tables\{InstanceParticipants as PTable, InstancePersons as Responsibility};

/**
 * Provides functions for XML instance validation and modeling.
 */
class Instances extends ResourceHelper
{
    /**
     * Delta constants
     */
    public const NORMAL = '', CURRENT = 1, NEW = 2, REMOVED = 3, CHANGED = 4;

    private const IRRELEVANT = [self::CURRENT, self::NEW, self::REMOVED];

    /**
     * Jump constants
     */
    private const NONE = 0, FUTURE = 1, PAST = 2;

    /**
     * Layout constants
     */
    public const LIST = 0, GRID = 1;

    /**
     * Participation constants
     */
    public const BOOKMARKS = 1, REGISTRATIONS = 2;

    /**
     * Presence constants
     */
    public const HYBRID = 0, ONLINE = -1, PRESENCE = 1;

    /**
     * The number of in-person participants for the given instance.
     *
     * @param   int  $instanceID
     *
     * @return int
     */
    public static function attendance(int $instanceID): int
    {
        $query = DB::getQuery();
        $query->select(DB::qn('i.attended'))
            ->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.id', 'i.unitID'))
            ->where("i.id = $instanceID")
            ->where("i.delta != 'removed'")
            ->where("u.delta != 'removed'");
        DB::setQuery($query);

        return DB::loadInt();
    }

    /**
     * Instantiates the block row associated with the given instance ID.
     *
     * @param   int  $instanceID  the id of the instance
     *
     * @return Block
     */
    public static function block(int $instanceID): Block
    {
        $block    = new Block();
        $instance = new Instance();

        if ($instance->load($instanceID) and $blockID = $instance->blockID) {
            $block->load($blockID);
        }

        return $block;
    }

    /**
     * Gets the booking associated with an instance
     *
     * @param   int  $instanceID  the id of the instance for which to find a booking match
     *
     * @return Tables\Bookings
     */
    public static function booking(int $instanceID): Tables\Bookings
    {
        $booking  = new Tables\Bookings();
        $instance = new Tables\Instances();

        if ($instance->load($instanceID)) {
            $booking->load(['blockID' => $instance->blockID, 'unitID' => $instance->unitID]);
        }

        return $booking;
    }

    /**
     * Gets the booking associated with an instance
     *
     * @param   int  $instanceID  the id of the instance for which to find a booking match
     *
     * @return int|null the id of the booking entry
     */
    public static function bookingID(int $instanceID): ?int
    {
        $booking = self::booking($instanceID);

        return $booking->id;
    }

    /**
     * Retrieves the sum of the effective capacity of physical rooms associated with concurrent instances of the same
     * block and unit as the instance identified.
     *
     * @param   int  $instanceID  the id of the instance
     *
     * @return int
     */
    public static function capacity(int $instanceID): int
    {
        $physical = Rooms::PHYSICAL;
        $query    = DB::getQuery();
        $removed  = 'removed';

        $query->select(['DISTINCT ' . DB::qn('r.id'), DB::qn('r.effCapacity')])
            ->from(DB::qn('#__organizer_rooms', 'r'))
            ->innerJoin(DB::qn('#__organizer_instance_rooms', 'ir'), DB::qc('ir.roomID', 'r.id'))
            ->innerJoin(DB::qn('#__organizer_instance_persons', 'ipe'), DB::qc('ipe.id', 'ir.assocID'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i2'), DB::qc('i2.id', 'ipe.instanceID'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i1'),
                DB::qcs([['i1.unitID', 'i2.unitID'], ['i1.blockID', 'i2.blockID']]))
            ->where(DB::qn('r.virtual') . ' = :virtual')->bind(':virtual', $physical, ParameterType::INTEGER)
            ->where(DB::qn('i1.id') . ' = :instanceID')->bind(':instanceID', $instanceID, ParameterType::INTEGER)
            ->where(DB::qn('ipe.delta') . ' != :pDelta')->bind(':pDelta', $removed)
            ->where(DB::qn('ir.delta') . ' != :rDelta')->bind(':rDelta', $removed)
            ->order(DB::qn('r.effCapacity') . ' DESC');
        DB::setQuery($query);

        if ($capacities = DB::loadIntColumn(1)) {
            return array_sum($capacities);
        }

        return 0;
    }

    /**
     * Retrieves the ids of the categories associated with the instance.
     *
     * @param   int  $instanceID
     *
     * @return int[]
     */
    public static function categoryIDs(int $instanceID): array
    {
        $categoryIDs = [];

        foreach (self::groupIDs($instanceID) as $groupID) {
            $categoryID               = Groups::categoryID($groupID);
            $categoryIDs[$categoryID] = $categoryID;
        }

        return $categoryIDs;
    }

    /**
     * Sets/overwrites instance course attributes.
     *
     * @param   array &$instance  the array of instance attributes
     *
     * @return void modifies the instance
     */
    private static function course(array &$instance): void
    {
        $coursesTable = new Tables\Courses();
        if (empty($instance['courseID']) or !$coursesTable->load($instance['courseID'])) {
            return;
        }

        $tag                      = Application::getTag();
        $instance['campusID']     = $coursesTable->campusID ?: $instance['campusID'];
        $instance['courseGroups'] = $coursesTable->groups ?: '';
        $instance['courseName']   = $coursesTable->{"name_$tag"} ?: '';
        $instance['deadline']     = $coursesTable->deadline ?: $instance['deadline'];
        $instance['fee']          = $coursesTable->fee ?: $instance['fee'];
        $instance['full']         = Courses::full($instance['courseID']);

        $instance['description'] = (empty($instance['description']) and $coursesTable->{"description_$tag"}) ?
            $coursesTable->{"description_$tag"} : $instance['description'];

        $instance['registrationType'] = $coursesTable->registrationType ?: $instance['registrationType'];
    }

    /**
     * The current number of participants for all concurrent instances of the same block and unit as the given instance.
     *
     * @param   int  $instanceID
     *
     * @return int
     */
    public static function currentCapacity(int $instanceID): int
    {
        $query   = DB::getQuery();
        $removed = 'removed';

        $query->select('SUM(' . DB::qn('i2.registered') . ')')
            ->from(DB::qn('#__organizer_instances', 'i2'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i1'),
                DB::qcs([['i1.unitID', 'i2.unitID'], ['i1.blockID', 'i2.blockID']]))
            ->where(DB::qn('i1.id') . ' = :instanceID')->bind(':instanceID', $instanceID, ParameterType::INTEGER)
            ->where(DB::qn('i1.delta') . ' != :d1')->bind(':d1', $removed)
            ->where(DB::qn('i2.delta') . ' != :d2')->bind(':d2', $removed);

        DB::setQuery($query);

        return DB::loadInt();
    }

    /**
     * Calls various functions filling the properties and resources of a single instance.
     *
     * @param   array  $instance
     * @param          $conditions
     *
     * @return void modifies the instance array
     */
    public static function fill(array &$instance, $conditions): void
    {
        self::fillBookingID($instance);
        self::course($instance);
        self::participation($instance);
        self::persons($instance, $conditions);
        self::subjects($instance, $conditions);
        ksort($instance);
    }

    /**
     * Sets the instance's bookingID attribute as appropriate.
     *
     * @param   array  &$instance  the instance to modify
     *
     * @return void
     */
    public static function fillBookingID(array &$instance): void
    {
        $booking               = new Tables\Bookings();
        $exists                = $booking->load(['blockID' => $instance['blockID'], 'unitID' => $instance['unitID']]);
        $instance['bookingID'] = $exists ? $booking->id : null;
    }

    /**
     * Adds a delta clause for a joined table.
     *
     * @param   DatabaseQuery  $query       the query to modify
     * @param   string         $alias       the table alias
     * @param   array          $conditions  the conditions for queries
     *
     * @return void modifies the query
     */
    private static function filterResourceStatus(DatabaseQuery $query, string $alias, array $conditions): void
    {
        $newInstance = (!empty($conditions['instanceStatus']) and $conditions['instanceStatus'] !== 'removed');
        $status      = $conditions['status'] ?? '';

        if ($newInstance or in_array($status, self::IRRELEVANT)) {
            $column = DB::qn("$alias.delta");
            $value  = 'removed';
            $query->where("$column != :value")->bind(':value', $value);

            return;
        }

        self::filterStatus($query, $alias, $conditions['delta']);
    }

    /**
     * Adds a clause to filter instances by status and modification date.
     *
     * @param   DatabaseQuery  $query  the query to modify
     * @param   string         $alias  the table alias
     * @param   bool|string    $delta  string the date for the delta or bool false
     *
     * @return void modifies the query
     */
    private static function filterStatus(DatabaseQuery $query, string $alias, bool|string $delta): void
    {
        $wherray = ["$alias.delta != 'removed'"];

        if ($delta) {
            $wherray[] = "($alias.delta = 'removed' AND $alias.modified > '$delta')";
        }

        $query->where('(' . implode(' OR ', $wherray) . ')');
    }

    /**
     * Retrieves the groupIDs associated with the instance.
     *
     * @param   int  $instanceID  the id of the instance
     *
     * @return int[]
     */
    public static function groupIDs(int $instanceID): array
    {
        $instance = new Instance();
        if (!$instance->load($instanceID)) {
            return [];
        }

        $query   = DB::getQuery();
        $removed = 'removed';

        $query->select('DISTINCT groupID')
            ->from(DB::qn('#__organizer_instance_groups', 'ig'))
            ->innerJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.id', 'ig.assocID'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.id', 'ip.instanceID'))
            ->where(DB::qn('i.delta ') . ' != :id')->bind(':id', $removed)
            ->where(DB::qn('i.blockID') . ' = :blockID')->bind(':blockID', $instance->blockID, ParameterType::INTEGER)
            ->where(DB::qn('i.unitID') . ' = :unitID')->bind(':unitID', $instance->unitID, ParameterType::INTEGER)
            ->where(DB::qn('ig.delta ') . ' != :igd')->bind(':igd', $removed)
            ->where(DB::qn('ip.delta ') . ' != :ipd')->bind(':ipd', $removed);
        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Gets the groups associated with the person's role association.
     *
     * @param   array &$person      the array of role assignments
     * @param   array  $conditions  the conditions which instances must fulfill
     *
     * @return void
     */
    private static function groups(array &$person, array $conditions): void
    {
        $tag   = Application::getTag();
        $query = DB::getQuery();

        $aliased  = DB::qn(['g.code', "g.fullName_$tag", "g.name_$tag"], ['code', 'fullName', 'name']);
        $selected = DB::qn(['ig.groupID', 'ig.delta', 'ig.modified', 'g.gridID']);
        $query->select(array_merge($selected, $aliased))
            ->from(DB::qn('#__organizer_instance_groups', 'ig'))
            ->innerJoin(DB::qn('#__organizer_groups', 'g'), DB::qc('g.id', 'ig.groupID'))
            ->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.groupID', 'g.id'))
            ->where(DB::qn('ig.assocID') . ' = :associationID')->bind(':associationID', $person['assocID'],
                ParameterType::INTEGER);

        if (array_key_exists('categoryIDs', $conditions)) {
            $query->whereIn(DB::qn('g.categoryID'), $conditions['categoryIDs']);
        }

        self::filterResourceStatus($query, 'ig', $conditions);

        // Don't limit the group organization in non-standard contexts
        if (!empty($conditions['organizationIDs'])
            and (empty($conditions['instances']) or $conditions['instances'] === 'organization')) {
            $query->whereIn(DB::qn('a.organizationID'), $conditions['organizationIDs']);
        }

        DB::setQuery($query);

        if (!$associations = DB::loadAssocList()) {
            return;
        }

        $groups = [];
        foreach ($associations as $association) {
            $groupID = $association['groupID'];
            $group   = [
                'code'       => $association['code'],
                'fullName'   => $association['fullName'],
                'group'      => $association['name'],
                'status'     => $association['delta'],
                'statusDate' => $association['modified']
            ];

            $groups[$groupID] = $group;
        }

        $person['groups'] = $groups;
    }

    /**
     * Retrieves the core information for one instance.
     *
     * @param   int  $instanceID  the id of the instance
     *
     * @return array an array modeling the instance
     */
    public static function instance(int $instanceID): array
    {
        $tag = Application::getTag();

        $table = new Instance();
        if (!$table->load($instanceID)) {
            return [];
        }

        $instance = [
            'attended'           => 0,
            'blockID'            => $table->blockID,
            'eventID'            => $table->eventID,
            'instanceID'         => $instanceID,
            'instanceStatus'     => $table->delta,
            'instanceStatusDate' => $table->modified,
            'methodID'           => $table->methodID,
            'registered'         => 0,
            'unitID'             => $table->unitID
        ];

        $iComment = $table->comment;
        $title    = $table->title;

        unset($table);

        $table = new Block();

        if (!$table->load($instance['blockID'])) {
            return [];
        }

        $block = [
            'date'      => $table->date,
            'endTime'   => empty($instance['eventID']) ?
                Dates::formatTime($table->endTime) : Dates::formatEndTime($table->endTime),
            'startTime' => Dates::formatTime($table->startTime)
        ];

        unset($table);

        $table = new Tables\Events();

        // Planned events
        if ($instance['eventID'] and $table->load($instance['eventID'])) {
            $event = [
                'campusID'         => $table->campusID,
                'deadline'         => $table->deadline,
                'description'      => $table->{"description_$tag"},
                'fee'              => $table->fee,
                'name'             => $table->{"name_$tag"},
                'registrationType' => $table->registrationType,
                'subjectNo'        => $table->subjectNo
            ];
        }
        // Booked events
        else {
            $event = [
                'campusID'         => null,
                'deadline'         => 0,
                'description'      => null,
                'fee'              => 0,
                'name'             => $title,
                'registrationType' => null,
                'subjectNo'        => ''
            ];
        }

        unset($table);

        $method = ['methodCode' => '', 'methodName' => ''];
        $table  = new Tables\Methods();
        if ($table->load($instance['methodID'])) {
            $method = [
                'methodCode' => $table->{"abbreviation_$tag"},
                'method'     => $table->{"name_$tag"}
            ];
        }

        unset($table);

        $table = new Tables\Units();
        if (!$table->load($instance['unitID'])) {
            return [];
        }

        $orgName = $table->organizationID ? Organizations::getShortName($table->organizationID) : '';

        $unit = [
            'comment'        => $table->comment,
            'courseID'       => $table->courseID,
            'organization'   => $orgName,
            'organizationID' => $table->organizationID,
            'gridID'         => $table->gridID,
            'termID'         => $table->termID,
            'unitStatus'     => $table->delta,
            'unitStatusDate' => $table->modified,
        ];

        unset($table);

        $instance            = array_merge($block, $event, $instance, $method, $unit);
        $instance['comment'] .= $iComment ? " $iComment" : '';

        if ($courseID = $instance['courseID']) {
            $courseTable = new Tables\Courses();
            if ($courseTable->load($courseID)) {
                $instance['campusID']         = $courseTable->campusID;
                $instance['course']           = $courseTable->{"name_$tag"};
                $instance['deadline']         = $courseTable->deadline;
                $instance['fee']              = $courseTable->fee;
                $instance['registrationType'] = $courseTable->registrationType;

                if ($courseTable->{"description_$tag"}) {
                    $instance['description'] = $courseTable->{"description_$tag"};
                }
            }
        }

        if ($participantID = User::id()) {
            $participantsTable = new PTable();
            if ($participantsTable->load(['instanceID' => $instanceID, 'participantID' => $participantID])) {
                $instance['attended']           = $participantsTable->attended;
                $instance['registrationStatus'] = 1;
            }
        }

        ksort($instance);

        return $instance;
    }

    /**
     * Retrieves a list of instance IDs for instances which fulfill the requirements.
     *
     * @param   array  $conditions  the conditions filtering the instances
     *
     * @return int[] the ids matching the conditions
     */
    public static function instanceIDs(array $conditions): array
    {
        $query = self::getInstanceQuery($conditions);
        $query->select('DISTINCT ' . DB::qn('i.id'))->order(DB::qn(['b.date', 'b.startTime', 'b.endTime']));
        Dates::betweenValues($query, 'b.date', $conditions['startDate'], $conditions['endDate']);
        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Returns the current number of participants for all concurrent instances  of the same block and unit as the given
     * instance.
     *
     * @param   int  $instanceID
     *
     * @return int
     */
    public static function interested(int $instanceID): int
    {
        $query   = DB::getQuery();
        $removed = 'removed';

        $query->select('COUNT(DISTINCT ipa.participantID)')
            ->from(DB::qn('#__organizer_instance_participants', 'ipa'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i2'), DB::qc('i2.id', 'ipa.instanceID'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i1'),
                DB::qcs([['i1.unitID', 'i2.unitID'], ['i1.blockID', 'i2.blockID']]))
            ->where(DB::qn('i1.id') . ' = :instanceID')->bind(':instanceID', $instanceID, ParameterType::INTEGER)
            ->where(DB::qn('i1.delta') . ' != :d1')->bind(':d1', $removed)
            ->where(DB::qn('i2.delta') . ' != :d2')->bind(':d2', $removed);
        DB::setQuery($query);

        return DB::loadInt();
    }

    /**
     * @param $conditions
     *
     * @return array|array[]
     */
    public static function items($conditions): array
    {
        $instanceIDs = self::instanceIDs($conditions);
        if (empty($instanceIDs)) {
            return self::getJumpDates($conditions);
        }

        $instances = [];
        foreach ($instanceIDs as $instanceID) {
            if (!$instance = self::instance($instanceID)) {
                continue;
            }

            self::fill($instance, $conditions);
            $instances[] = $instance;
        }

        return $instances;
    }

    /**
     * Gets the code of the method associated with the instance.
     *
     * @param   int  $instanceID  the id of the instance
     *
     * @return string
     */
    public static function methodCode(int $instanceID): string
    {
        $instance = new Tables\Instances();

        if (!$instance->load($instanceID) or !$methodID = $instance->methodID) {
            return '';
        }

        return Methods::getCode($methodID);
    }

    /**
     * Gets the localized name of the method associated with the instance.
     *
     * @param   int  $instanceID  the id of the instance
     *
     * @return string
     */
    public static function methodName(int $instanceID): string
    {
        $instance = new Tables\Instances();

        if (!$instance->load($instanceID) or !$methodID = $instance->methodID) {
            return '';
        }

        return Methods::getName($methodID);
    }

    /**
     * Sets the instance's participation properties:
     * - 'bookmarked'  - the user has added the instance to their schedule
     * - 'busy'       - the user's schedule has an appointment in a block overlapping the instance
     * - 'capacity'   - the number of users who may physically attend the instance
     * - 'interested' - the number of users who have added this instance to their schedule
     * - 'registered' - the user has registered to physically participate in the instance
     *
     * @param   array  $instance  the array containing instance information
     *
     * @return void
     */
    public static function participation(array &$instance): void
    {
        $instance['capacity']   = self::capacity($instance['instanceID']);
        $instance['current']    = self::currentCapacity($instance['instanceID']);
        $instance['interested'] = self::interested($instance['instanceID']);

        if (!$userID = User::id()) {
            $instance['bookmarked'] = false;
            $instance['busy']       = false;
            $instance['registered'] = false;

            return;
        }

        $participation = new PTable();
        if ($participation->load(['instanceID' => $instance['instanceID'], 'participantID' => $userID])) {
            $instance['bookmarked'] = true;
            $instance['busy']       = true;
            $instance['registered'] = $participation->registered;

            return;
        }

        // The times in the instance have been pretreated, so that the endTime is no longer valid for comparisons.
        $block = new Block();
        if (!$block->load($instance['blockID'])) {
            $instance['busy'] = false;

            return;
        }

        $instance['bookmarked'] = false;
        $instance['registered'] = false;
        $instance['busy']       = Participation::busy($block->date, $block->startTime, $block->endTime);
    }

    /**
     * Gets the persons and person associated resources for the instance.
     *
     * @param   array &$instance    the array of instance attributes
     * @param   array  $conditions  the conditions which instances must fulfill
     *
     * @return void
     */
    public static function persons(array &$instance, array $conditions): void
    {
        $conditions['instanceStatus'] = $instance['instanceStatus'] ?? 'new';

        $query    = DB::getQuery();
        $tag      = Application::getTag();
        $aliased  = DB::qn(
            ['ip.id', "r.name_$tag", "r.abbreviation_$tag", 'ip.delta'],
            ['assocID', 'role', 'roleCode', 'status']
        );
        $selected = DB::qn(['ip.personID', 'ip.roleID', 'ip.modified']);

        $query->select(array_merge($aliased, $selected))
            ->from(DB::qn('#__organizer_instance_persons', 'ip'))
            ->innerJoin(DB::qn('#__organizer_roles', 'r'), DB::qc('r.id', 'ip.roleID'))
            ->where(DB::qn('ip.instanceID') . ' = :instanceID')
            ->bind(':instanceID', $instance['instanceID'], ParameterType::INTEGER);

        if (!empty($conditions['roleID'])) {
            $query->where(DB::qn('ip.roleID') . ' = :roleID')
                ->bind(':roleID', $instance['roleID'], ParameterType::INTEGER);
        }

        self::filterResourceStatus($query, 'ip', $conditions);

        DB::setQuery($query);
        if (!$associations = DB::loadAssocList()) {
            return;
        }

        $persons = [];
        foreach ($associations as $association) {
            $assocID  = $association['assocID'];
            $personID = $association['personID'];
            $person   = [
                'assocID'    => $assocID,
                'code'       => $association['roleCode'],
                'person'     => Persons::getLNFName($personID, true),
                'role'       => $association['role'],
                'roleID'     => $association['roleID'],
                'status'     => $association['status'],
                'statusDate' => $association['modified']
            ];

            self::groups($person, $conditions);
            self::rooms($person, $conditions);
            $persons[$personID] = $person;
        }

        $instance['resources'] = $persons;
    }

    /**
     * Gets the rooms associated with the person's role association.
     *
     * @param   array &$person      the array of role assignments
     * @param   array  $conditions  the conditions which instances must fulfill
     *
     * @return void
     */
    private static function rooms(array &$person, array $conditions): void
    {
        $aliased  = DB::qn(['c1.location', 'c2.location', 'b.location'], ['campusLocation', 'defaultLocation', 'location']);
        $selected = DB::qn(['ir.roomID', 'ir.delta', 'ir.modified', 'r.name', 'r.virtual']);
        $query    = DB::getQuery();
        $query->select(array_merge($selected, $aliased))
            ->from(DB::qn('#__organizer_instance_rooms', 'ir'))
            ->innerJoin(DB::qn('#__organizer_rooms', 'r'), DB::qc('r.id', 'ir.roomID'))
            ->leftJoin(DB::qn('#__organizer_buildings', 'b'), DB::qc('b.id', 'r.buildingID'))
            ->leftJoin(DB::qn('#__organizer_campuses', 'c1'), DB::qc('c1.id', 'b.campusID'))
            ->leftJoin(DB::qn('#__organizer_campuses', 'c2'), DB::qc('c2.id', 'c1.parentID'))
            ->where(DB::qn('ir.assocID') . ' = :associationID')
            ->bind(':associationID', $person['assocID'], ParameterType::INTEGER);

        self::filterResourceStatus($query, 'ir', $conditions);

        DB::setQuery($query);
        if (!$associations = DB::loadAssocList()) {
            return;
        }

        $rooms = [];
        foreach ($associations as $association) {
            $campus   = '';
            $location = empty($association['location']) ? '' : $association['location'];

            if (!empty($association['campusLocation'])) {
                $campus = $association['campusLocation'];
            }
            elseif (!empty($association['defaultLocation'])) {
                $campus = $association['defaultLocation'];
            }

            $roomID = $association['roomID'];
            $room   = [
                'campus'     => $campus,
                'location'   => $location,
                'room'       => $association['name'],
                'status'     => $association['delta'],
                'statusDate' => $association['modified'],
                'virtual'    => $association['virtual']
            ];

            $rooms[$roomID] = $room;
        }

        $person['rooms'] = $rooms;
    }




    ####################################################################################################################


    /**
     * Builds the array of parameters used for instance retrieval.
     * @return array the parameters used to retrieve instances.
     */
    public static function getConditions(): array
    {
        $conditions           = [];
        $conditions['userID'] = User::id();
        $conditions['my']     = (!empty($conditions['userID']) and Input::getBool('my'));

        $conditions['date'] = Dates::standardizeDate(Input::getCMD('date', date('Y-m-d')));

        $delta               = Input::getInt('delta');
        $conditions['delta'] = empty($delta) ? false : date('Y-m-d', strtotime('-' . $delta . ' days'));

        $interval               = Input::getCMD('interval', 'week');
        $intervals              = ['day', 'half', 'month', 'quarter', 'term', 'week'];
        $conditions['interval'] = in_array($interval, $intervals) ? $interval : 'week';

        // Reliant on date and interval properties
        self::setDates($conditions);

        $conditions['status'] = self::NORMAL;

        if (empty($conditions['my'])) {
            if ($eventID = Input::getInt('eventID')) {
                $conditions['eventIDs'] = [$eventID];
            }

            if ($courseID = Input::getInt('courseID')) {
                $conditions['courseIDs'] = [$courseID];
            }

            if ($groupID = Input::getInt('groupID')) {
                $conditions['groupIDs'] = [$groupID];
            }

            if ($categoryID = Input::getInt('categoryID')) {
                $conditions['categoryIDs'] = [$categoryID];
            }

            if ($organizationID = Input::getInt('organizationID')) {
                $conditions['organizationIDs'] = [$organizationID];

                self::setPublishingAccess($conditions);
            }
            else {
                $conditions['showUnpublished'] = Can::administrate();
            }

            $personID = Input::getInt('personID');
            if ($personIDs = $personID ? [$personID] : Input::getIntCollection('personIDs')) {
                self::filterPersons($personIDs, $conditions['userID']);
                if (!empty($personIDs)) {
                    $conditions['personIDs'] = $personIDs;
                }
            }

            $roomID = Input::getInt('roomID');
            if ($roomIDs = $roomID ? [$roomID] : Input::getIntCollection('roomIDs')) {
                $conditions['roomIDs'] = $roomIDs;
            }
            elseif ($room = Input::getCMD('room') and $roomID = Rooms::getID($room)) {
                $conditions['roomIDs'] = [$roomID];
            }

            if ($subjectID = Input::getInt('subjectID')) {
                $conditions['subjectIDs'] = [$subjectID];
            }

            $unitID = Input::getInt('unitID');
            if ($unitIDs = $unitID ? [$unitID] : Input::getIntCollection('unitIDs')) {
                $conditions['unitIDs'] = $unitIDs;
            }
        }
        elseif ($personID = Persons::getIDByUserID($conditions['userID'])) {
            // Schedule items which have been planned for the person should appear in their schedule
            $conditions['personIDs']       = [$personID];
            $conditions['showUnpublished'] = true;
        }

        return $conditions;
    }

    /**
     * Builds a general query to find instances matching the given conditions.
     *
     * @param   array  $conditions  the conditions for filtering the query
     * @param   int    $jump        the jump direction if applicable
     *
     * @return DatabaseQuery the query object
     */
    public static function getInstanceQuery(array $conditions, int $jump = self::NONE): DatabaseQuery
    {
        $query    = DB::getQuery();
        $subQuery = null;

        $query->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_blocks', 'b'), DB::qc('b.id', 'i.blockID'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.id', 'i.unitID'))
            ->innerJoin(DB::qn('#__organizer_instance_persons', 'ipe'), DB::qc('ipe.instanceID', 'i.id'))
            ->innerJoin(DB::qn('#__organizer_instance_groups', 'ig'), DB::qc('ig.assocID', 'ipe.id'))
            ->innerJoin(DB::qn('#__organizer_groups', 'g'), DB::qc('g.id', 'ig.groupID'))
            ->leftJoin(DB::qn('#__organizer_events', 'e'), DB::qc('e.id', 'i.eventID'))
            ->leftJoin(DB::qn('#__organizer_instance_rooms', 'ir'), DB::qc('ir.assocID', 'ipe.id'));

        if (empty($conditions['showUnpublished'])) {
            $subQuery = DB::getQuery();
            $subQuery->select('DISTINCT ' . DB::qn('i2.id', 'suppressed'))
                ->from(DB::qn('#__organizer_instances', 'i2'))
                ->innerJoin(DB::qn('#__organizer_blocks', 'b2'), DB::qc('b2.id', 'i2.blockID'))
                ->innerJoin(DB::qn('#__organizer_units', 'u2'), DB::qc('u2.id', 'i2.unitID'))
                ->innerJoin(DB::qn('#__organizer_instance_persons', 'ipe2'), DB::qc('ipe2.instanceID', 'i2.id'))
                ->innerJoin(DB::qn('#__organizer_instance_groups', 'ig2'), DB::qc('ig2.assocID', 'ipe2.id'))
                ->innerJoin(DB::qn('#__organizer_group_publishing', 'gp'), DB::qc('gp.groupID',
                    'ig2.groupID AND gp.termID', 'u2.termID'))
                ->where("gp.published = 0")
                ->where("i2.delta != 'removed'")
                ->where("ig2.delta != 'removed'")
                ->where("ipe2.delta != 'removed'");
        }

        $dDate = $conditions['delta'];

        switch ($conditions['status']) {
            case self::CURRENT:

                $query->where("i.delta != 'removed'")
                    ->where("ig.delta != 'removed'")
                    ->where("ipe.delta != 'removed'")
                    ->where("ir.delta != 'removed'")
                    ->where("u.delta != 'removed'");

                break;

            case self::NEW:

                self::filterStatus($query, 'i', false);
                self::filterStatus($query, 'u', false);
                $clause = "((i.delta = 'new' AND i.modified >= '$dDate') ";
                $clause .= "OR (u.delta = 'new' AND i.modified >= '$dDate'))";
                $query->where($clause);

                break;

            case self::REMOVED:

                $clause = "((i.delta = 'removed' AND i.modified >= '$dDate') ";
                $clause .= "OR (u.delta = 'removed' AND i.modified >= '$dDate'))";
                $query->where($clause);

                break;

            case self::CHANGED:

                $clause = "(((i.delta = 'new' OR i.delta = 'removed') AND i.modified >= '$dDate') ";
                $clause .= "OR ((u.delta = 'new' OR u.delta = 'removed') AND u.modified >= '$dDate'))";
                $query->where($clause);

                break;

            case self::NORMAL:
            default:

                self::filterStatus($query, 'i', $dDate);
                self::filterStatus($query, 'u', $dDate);
                self::filterStatus($query, 'ipe', $dDate);
                self::filterStatus($query, 'ig', $dDate);
                self::filterStatus($query, 'ir', $dDate);

                break;
        }

        switch ($jump) {
            case self::FUTURE:
                $lowDate  = date('Y-m-d', strtotime('+1 day', strtotime($conditions['endDate'])));
                $highDate = date('Y-m-d', strtotime('+3 months', strtotime($conditions['endDate'])));

                break;
            case self::PAST:
                $lowDate  = date('Y-m-d', strtotime('-3 months', strtotime($conditions['startDate'])));
                $highDate = date('Y-m-d', strtotime('-1 day', strtotime($conditions['startDate'])));
                break;
            case self::NONE:
            default:
                $lowDate  = $conditions['startDate'];
                $highDate = $conditions['endDate'];
                break;
        }

        Dates::betweenValues($query, 'b.date', $lowDate, $highDate);

        if ($subQuery) {
            Dates::betweenValues($subQuery, 'b2.date', $lowDate, $highDate);
        }

        $filterOrganization = true;

        if (!empty($conditions['my'])) {
            $my      = (int) $conditions['my'];
            $wherray = [];

            if ($userID = User::id()) {
                $exists = Participants::exists($userID);
                if ($my === self::REGISTRATIONS and $exists) {
                    $filterOrganization = false;
                    $query->innerJoin(DB::qn('#__organizer_instance_participants', 'ipa'), DB::qc('ipa.instanceID', 'i.id'))
                        ->where("ipa.participantID = $userID")
                        ->where("ipa.registered = 1");
                }
                else {
                    if ($personID = Persons::getIDByUserID($userID)) {
                        $filterOrganization = false;
                        $wherray[]          = "ipe.personID = $personID";
                    }

                    if ($exists) {
                        $filterOrganization = false;
                        $query->leftJoin(DB::qn('#__organizer_instance_participants', 'ipa'), DB::qc('ipa.instanceID', 'i.id'));
                        $wherray[] = "ipa.participantID = $userID";
                    }

                    if ($wherray) {
                        $query->where('(' . implode(' OR ', $wherray) . ')');
                    }
                    else {
                        $query->where('i.id = 0');
                    }
                }
            }
            else {
                $query->where('i.id = 0');
            }
        }

        if (!empty($conditions['eventIDs']) or !empty($conditions['subjectIDs'])) {
            $filterOrganization = false;

            if (!empty($conditions['eventIDs'])) {
                $eventIDs = implode(',', $conditions['eventIDs']);
                $query->where("e.id IN ($eventIDs)");
            }

            if (!empty($conditions['subjectIDs'])) {
                $subjectIDs = implode(',', $conditions['subjectIDs']);
                $query->innerJoin(DB::qn('#__organizer_subject_events', 'se'), DB::qc('se.eventID', 'e.id'))
                    ->where("se.subjectID IN ($subjectIDs)");
            }
        }
        elseif (!empty($conditions['unitIDs'])) {
            $unitIDs = implode(',', $conditions['unitIDs']);
            $query->where("i.unitID IN ($unitIDs)");
            $subQuery?->where("i2.unitID IN ($unitIDs)");
        }
        elseif (!empty($conditions['courseIDs'])) {
            $filterOrganization = false;
            $courseIDs          = implode(',', $conditions['courseIDs']);
            $query->where("u.courseID IN ($courseIDs)");
            $subQuery?->where("u2.courseID IN ($courseIDs)");
        }
        elseif (!empty($conditions['groupIDs'])) {
            $filterOrganization = false;
            $groupIDs           = implode(',', $conditions['groupIDs']);
            $query->where("ig.groupID IN ($groupIDs)");
        }
        elseif (!empty($conditions['categoryIDs'])) {
            $categoryIDs = implode(',', $conditions['categoryIDs']);
            $query->where("g.categoryID IN ($categoryIDs)");
        }

        if (!empty($conditions['personIDs'])) {
            $filterOrganization = false;
            $personIDs          = implode(',', $conditions['personIDs']);
            $query->where("ipe.personID IN ($personIDs)");
        }

        if (!empty($conditions['roleID'])) {
            $query->where("ipe.roleID = {$conditions['roleID']}");
        }

        if (!empty($conditions['roomIDs'])) {
            $filterOrganization = false;
            $roomIDs            = implode(',', $conditions['roomIDs']);
            $query->where("ir.roomID IN ($roomIDs)");
        }

        if ($filterOrganization and !empty($conditions['organizationIDs'])) {
            $organizationIDs = implode(',', ArrayHelper::toInteger($conditions['organizationIDs']));
            $query->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.groupID', 'ig.groupID'))
                ->where("a.organizationID IN ($organizationIDs)");
        }

        if ($subQuery) {
            $query->where("i.id NOT IN ($subQuery)");
        }

        return $query;
    }

    /**
     * Gets the localized name of the event associated with the instance and the name of the instance's method.
     *
     * @param   int   $resourceID  the id of the instance
     * @param   bool  $showMethod
     *
     * @return string
     */
    public static function getName(int $resourceID, bool $showMethod = true): string
    {
        $instance = new Tables\Instances();

        if (!$instance->load($resourceID)) {
            return '';
        }

        if (!$eventID = $instance->eventID) {
            return $instance->title;
        }

        if (!$name = Events::getName($eventID)) {
            return '';
        }

        if ($showMethod and $methodID = $instance->methodID) {
            $name .= ' - ' . Methods::getName($methodID);
        }

        return $name;
    }

    /**
     * Retrieves the
     *
     * @param   int  $instanceID
     *
     * @return int[]
     */
    public static function getOrganizationIDs(int $instanceID): array
    {
        $organizationIDs = [];

        foreach (self::groupIDs($instanceID) as $groupID) {
            $organizationIDs = array_merge($organizationIDs, Groups::organizationIDs($groupID));
        }

        return $organizationIDs;
    }

    /**
     * Retrieves the persons actively associated with the given instance.
     *
     * @param   int  $instanceID  the id of the instance
     * @param   int  $roleID      the id of the role the person fills
     *
     * @return int[]
     */
    public static function getPersonIDs(int $instanceID, int $roleID = 0): array
    {
        $query = DB::getQuery();
        $query->select('personID')
            ->from(DB::qn('#__organizer_instance_persons'))
            ->where("instanceID = $instanceID")
            ->where("delta != 'removed'");

        if ($roleID) {
            $query->where("roleID = $roleID");
        }

        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Returns the number of in-person participants for the given instance.
     *
     * @param   int  $instanceID
     *
     * @return int
     */
    public static function getRegistered(int $instanceID): int
    {
        $query = DB::getQuery();
        $query->select('i.registered')
            ->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.id', 'i.unitID'))
            ->where("i.id = $instanceID")
            ->where("i.delta != 'removed'")
            ->where("u.delta != 'removed'");
        DB::setQuery($query);

        return DB::loadInt();
    }

    /**
     * Retrieves the role id for the given instance and person.
     *
     * @param   int  $instanceID  the id of the instance
     * @param   int  $personID    the id of the person
     *
     * @return int the id of the role
     */
    public static function getRoleID(int $instanceID, int $personID): int
    {
        $responsibility = new Responsibility();

        if ($responsibility->load(['instanceID' => $instanceID, 'personID' => $personID])) {
            return $responsibility->roleID;
        }

        return 0;
    }

    /**
     * Retrieves the rooms actively associated with the given instance.
     *
     * @param   int  $instanceID  the id of the instance
     *
     * @return int[]
     */
    public static function getRoomIDs(int $instanceID): array
    {
        $query = DB::getQuery();
        $query->select('DISTINCT roomID')
            ->from(DB::qn('#__organizer_instance_rooms', 'ir'))
            ->innerJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.id', 'ir.assocID'))
            ->where("ip.instanceID = $instanceID")
            ->where("ir.delta != 'removed'")
            ->where("ip.delta != 'removed'");
        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Filters the persons to those authorized to the user.
     *
     * @param   array &$personIDs  the requested person ids
     * @param   int    $userID     the id of the user whose authorizations will be checked
     *
     * @return void
     */
    public static function filterPersons(array &$personIDs, int $userID): void
    {
        if (Can::administrate() or Can::manage('persons')) {
            return;
        }

        $thisPersonID = Persons::getIDByUserID($userID);
        $authorized   = Can::viewTheseOrganizations();

        foreach ($personIDs as $key => $personID) {
            // Identity or publicly released
            $identity = ($thisPersonID and $thisPersonID === $personID);
            $released = Persons::released($personID);
            if ($identity or $released) {
                continue;
            }

            $associations = Persons::organizationIDs($personID);
            $overlap      = array_intersect($authorized, $associations);

            if (empty($overlap)) {
                unset($personIDs[$key]);
            }
        }
    }

    /**
     * Searches for the next and most recent previous date where events matching the query can be found.
     *
     * @param   array  $conditions  the schedule configuration parameters
     *
     * @return string[] next and latest available dates
     */
    public static function getJumpDates(array $conditions): array
    {
        $dates = [];

        $pastQuery = self::getInstanceQuery($conditions, self::PAST);
        $pastQuery->select('MAX(DATE)')->where("date < '" . $conditions['startDate'] . "'");
        DB::setQuery($pastQuery);

        if ($pastDate = DB::loadString()) {
            $dates['pastDate'] = $pastDate;
        }

        $futureQuery = self::getInstanceQuery($conditions, self::FUTURE);
        $futureQuery->select('MIN(DATE)')->where("date > '" . $conditions['endDate'] . "'");
        DB::setQuery($futureQuery);

        if ($futureDate = DB::loadString()) {
            $dates['futureDate'] = $futureDate;
        }

        return $dates;
    }

    /**
     * Checks whether the instance takes place exclusively online.
     *
     * @param   int  $instanceID
     *
     * @return int
     */
    public static function getPresence(int $instanceID): int
    {
        $query = DB::getQuery();
        $query->select('DISTINCT r.virtual')
            ->from(DB::qn('#__organizer_rooms', 'r'))
            ->innerJoin(DB::qn('#__organizer_instance_rooms', 'ir'), DB::qc('ir.roomID', 'r.id'))
            ->innerJoin(DB::qn('#__organizer_instance_persons', 'ipe'), DB::qc('ipe.id', 'ir.assocID'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.id', 'ipe.instanceID'))
            ->where("i.id = $instanceID")
            ->where("ir.delta != 'removed'")
            ->where("ipe.delta != 'removed'");
        DB::setQuery($query);

        $results = DB::loadIntColumn();

        $online   = in_array(1, $results);
        $presence = in_array(0, $results);

        if ($presence === false) {
            return self::ONLINE;
        }

        if ($online === false) {
            return self::PRESENCE;
        }

        return self::HYBRID;
    }

    /**
     * Check if user has a course responsibility.
     *
     * @param   int  $instanceID  the optional id of the course
     * @param   int  $personID    the optional id of the person
     * @param   int  $roleID      the optional if of the person's role
     *
     * @return bool true if the user has a course responsibility, otherwise false
     */
    public static function hasResponsibility(int $instanceID = 0, int $personID = 0, int $roleID = 0): bool
    {
        if (!$personID and !$personID = Persons::getIDByUserID(User::id())) {
            return false;
        }

        $query = DB::getQuery();
        $query->select('COUNT(*)')
            ->from(DB::qn('#__organizer_instance_persons'))
            ->where("personID = $personID");

        if ($instanceID) {
            $query->where("instanceID = $instanceID");
        }

        if ($roleID) {
            $query->where("roleID = $roleID");
        }

        DB::setQuery($query);

        return DB::loadBool();
    }

    /**
     * Checks if the registrations are already at or above the sum of the effective capacity of the rooms.
     *
     * @param   int  $instanceID
     *
     * @return bool
     */
    public static function isFull(int $instanceID): bool
    {
        if (!$capacity = self::capacity($instanceID)) {
            return false;
        }

        return self::currentCapacity($instanceID) >= $capacity;
    }

    /**
     * Sets the start and end date parameters and adjusts the date parameter as appropriate.
     *
     * @param   array &$parameters  the parameters used for event retrieval
     *
     * @return void
     */
    public static function setDates(array &$parameters): void
    {
        $date     = $parameters['date'];
        $dateTime = strtotime($date);
        $reqDoW   = (int) date('w', $dateTime);

        $startDayNo   = empty($parameters['startDay']) ? 1 : $parameters['startDay'];
        $endDayNo     = empty($parameters['endDay']) ? 6 : $parameters['endDay'];
        $displayedDay = ($reqDoW >= $startDayNo and $reqDoW <= $endDayNo);

        if (!$displayedDay) {
            if ($reqDoW === 6) {
                $string = '-1 day';
            }
            else {
                $string = '+1 day';
            }

            $date = date('Y-m-d', strtotime($string, $dateTime));
        }

        $parameters['date'] = $date;

        $dates = match ($parameters['interval']) {
            'day' => ['startDate' => $date, 'endDate' => $date],
            'half' => Dates::getHalfYear($date),
            'month' => Dates::getMonth($date),
            'quarter' => Dates::getQuarter($date),
            'term' => Dates::getTerm($date),
            default => Dates::getWeek($date, $startDayNo, $endDayNo),
        };

        $parameters = array_merge($parameters, $dates);
    }

    /**
     * Set the display of unpublished instances according to the user's access rights
     *
     * @param   array &$conditions  the conditions for instance retrieval
     *
     * @return void
     */
    public static function setPublishingAccess(array &$conditions): void
    {
        $allowedIDs   = Can::viewTheseOrganizations();
        $overlap      = array_intersect($conditions['organizationIDs'], $allowedIDs);
        $overlapCount = count($overlap);

        // If the user has planning access to all requested organizations show unpublished automatically.
        if ($overlapCount and $overlapCount == count($conditions['organizationIDs'])) {
            $conditions['showUnpublished'] = true;
        }
        else {
            $conditions['showUnpublished'] = false;
        }
    }

    /**
     * Adds subject documentation information to the instance.
     *
     * @param   array  &$instance  the instance data
     * @param   array   $subject   the subject data
     *
     * @return void modifies the instance array
     */
    private static function subject(array &$instance, array $subject): void
    {
        $instance['subjectID'] = $subject['id'];
        $instance['code']      = empty($subject['code']) ? '' : $subject['code'];
        $instance['fullName']  = empty($subject['fullName']) ? '' : $subject['fullName'];

        if (empty($instance['description']) and !empty($subject['description'])) {
            $instance['description'] = $subject['description'];
        }
    }

    /**
     * Sets/overwrites instance attributes based on subject associations.
     *
     * @param   array &$instance    the instance
     * @param   array  $conditions  the conditions used to specify the instances
     *
     * @return void modifies the instance
     */
    public static function subjects(array &$instance, array $conditions): void
    {
        $tag   = Application::getTag();
        $query = DB::getQuery();
        $query->select("DISTINCT s.id, s.abbreviation_$tag AS code, s.fullName_$tag AS fullName")
            ->select("s.description_$tag AS description")
            ->from(DB::qn('#__organizer_subjects', 's'))
            ->innerJoin(DB::qn('#__organizer_subject_events', 'se'), DB::qc('se.subjectID', 's.id'))
            ->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.subjectID', 's.id'))
            ->where("se.eventID = {$instance['eventID']}");
        DB::setQuery($query);

        $default = ['id' => null, 'code' => '', 'fullName' => ''];

        // No subject <-> event associations
        if (!$subjects = DB::loadAssocList()) {
            self::subject($instance, $default);

            return;
        }

        // One subject <-> event association
        if (count($subjects) === 1) {
            self::subject($instance, $subjects[0]);

            return;
        }

        // Multiple subject <-> event associations

        // Which programs are associated with which subjects
        $programMap = [];
        foreach ($subjects as $key => $subject) {
            foreach (Subjects::programs($subject['id']) as $program) {
                $programMap[$program['programID']] = $key;
            }
        }

        // Determine the event categories
        $categoryIDs = [];
        if (!empty($conditions['categoryIDs'])) {
            $categoryIDs = $conditions['categoryIDs'];
        }
        elseif (!empty($conditions['groupIDs'])) {
            foreach ($conditions['groupIDs'] as $groupID) {
                $categoryID               = Groups::categoryID($groupID);
                $categoryIDs[$categoryID] = $categoryID;
            }
        }

        // Find the programs associated with the event categories
        if ($categoryIDs) {
            $pQuery = DB::getQuery();
            $pQuery->select('DISTINCT id')
                ->from('#__organizer_programs')
                ->whereIn('categoryID', $categoryIDs)
                ->order('accredited');
            DB::setQuery($pQuery);

            foreach (DB::loadColumn() as $programID) {
                if (isset($programMap[$programID])) {
                    // First match is the best match because of the accredited sort
                    self::subject($instance, $subjects[$programMap[$programID]]);

                    return;
                }
            }
        }

        self::subject($instance, $default);
    }

    /**
     * Check if person is associated with an instance as a teacher.
     *
     * @param   int  $instanceID  the optional id of the instance
     * @param   int  $personID    the optional id of the person
     *
     * @return bool true if the person is an instance teacher, otherwise false
     */
    public static function teaches(int $instanceID = 0, int $personID = 0): bool
    {
        return self::hasResponsibility($instanceID, $personID, Roles::TEACHER);
    }

    /**
     * Updates participation numbers for a single instance.
     *
     * @param   int  $instanceID
     *
     * @return void
     */
    public static function updateNumbers(int $instanceID): void
    {
        $query = DB::getQuery();
        $query->select('*')->from(DB::qn('#__organizer_instance_participants'))->where("instanceID = $instanceID");
        DB::setQuery($query);

        if (!$results = DB::loadAssocList()) {
            return;
        }

        $attended   = 0;
        $bookmarked = 0;
        $registered = 0;

        foreach ($results as $result) {
            $bookmarked++;
            $attended   = $attended + $result['attended'];
            $registered = $registered + $result['registered'];
        }

        $table = new Tables\Instances();
        $table->load($instanceID);
        $table->attended   = $attended;
        $table->bookmarked = $bookmarked;
        $table->registered = $registered;
        $table->store();
    }
}
