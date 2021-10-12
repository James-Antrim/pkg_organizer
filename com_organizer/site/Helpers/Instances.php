<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use JDatabaseQuery;
use Joomla\Utilities\ArrayHelper;
use Organizer\Adapters\Database;
use Organizer\Tables;

/**
 * Provides functions for XML instance validation and modeling.
 */
class Instances extends ResourceHelper
{
	public const HYBRID = 0, ONLINE = -1, PRESENCE = 1;

	private const NORMAL = '', CURRENT = 1, NEW = 2, REMOVED = 3, CHANGED = 4;

	private const TEACHER = 1;

	public const BOOKMARKS = 1, REGISTRATIONS = 2;

	/**
	 * Adds a delta clause for a joined table.
	 *
	 * @param   JDatabaseQuery  $query  the query to modify
	 * @param   string          $alias  the table alias
	 * @param   string|bool     $delta  string the date for the delta or bool false
	 *
	 * @return void modifies the query
	 */
	private static function addDeltaClause(JDatabaseQuery $query, string $alias, $delta)
	{
		$wherray = ["$alias.delta != 'removed'"];

		if ($delta)
		{
			$wherray[] = "($alias.delta = 'removed' AND $alias.modified > '$delta')";
		}

		$query->where('(' . implode(' OR ', $wherray) . ')');
	}

	/**
	 * Calls various functions filling the properties and resources of a single instance.
	 *
	 * @param   array  $instance
	 * @param          $conditions
	 *
	 * @return void
	 */
	public static function fill(array &$instance, $conditions)
	{
		self::setBooking($instance);
		self::setCourse($instance);
		self::setParticipation($instance);
		self::setPersons($instance, $conditions);
		self::setSubject($instance, $conditions);
		ksort($instance);
	}

	/**
	 * Gets the block associated with the instance.
	 *
	 * @param   int  $instanceID  the id of the instance
	 *
	 * @return Tables\Blocks
	 */
	public static function getBlock(int $instanceID): Tables\Blocks
	{
		$block    = new Tables\Blocks();
		$instance = new Tables\Instances();

		if ($instance->load($instanceID) and $blockID = $instance->blockID)
		{
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
	public static function getBooking(int $instanceID): Tables\Bookings
	{
		$booking  = new Tables\Bookings();
		$instance = new Tables\Instances();

		if ($instance->load($instanceID))
		{
			$booking->load(['blockID' => $instance->blockID, 'unitID' => $instance->unitID]);
		}

		return $booking;
	}

	/**
	 * Gets the booking associated with an instance
	 *
	 * @param   int  $instanceID  the id of the instance for which to find a booking match
	 *
	 * @return int the id of the booking entry
	 */
	public static function getBookingID(int $instanceID): ?int
	{
		$booking = self::getBooking($instanceID);

		return $booking->id;
	}

	/**
	 * Builds the array of parameters used for instance retrieval.
	 *
	 * @return array the parameters used to retrieve instances.
	 */
	public static function getConditions(): array
	{
		$conditions           = [];
		$conditions['userID'] = Users::getID();
		$conditions['my']     = (!empty($conditions['userID']) and Input::getBool('my'));

		$conditions['date'] = Input::getCMD('date', date('Y-m-d'));

		$delta               = Input::getInt('delta');
		$conditions['delta'] = empty($delta) ? false : date('Y-m-d', strtotime('-' . $delta . ' days'));

		$interval               = Input::getCMD('interval', 'week');
		$intervals              = ['day', 'half', 'month', 'quarter', 'term', 'week'];
		$conditions['interval'] = in_array($interval, $intervals) ? $interval : 'week';

		// Reliant on date and interval properties
		self::setDates($conditions);

		$conditions['status'] = self::NORMAL;

		if (empty($conditions['my']))
		{
			if ($courseID = Input::getInt('courseID'))
			{
				$conditions['courseIDs'] = [$courseID];
			}

			if ($eventID = Input::getInt('eventID'))
			{
				$conditions['eventIDs'] = [$eventID];
			}

			if ($groupID = Input::getInt('groupID'))
			{
				$conditions['groupIDs'] = [$groupID];
			}

			if ($organizationID = Input::getInt('organizationID'))
			{
				$conditions['organizationIDs'] = [$organizationID];

				self::setPublishingAccess($conditions);
			}
			else
			{
				$conditions['showUnpublished'] = Can::administrate();
			}

			$personID = Input::getInt('personID');
			if ($personIDs = $personID ? [$personID] : Input::getIntCollection('personIDs'))
			{
				self::filterPersonIDs($personIDs, $conditions['userID']);
				if (!empty($personIDs))
				{
					$conditions['personIDs'] = $personIDs;
				}
			}

			$roomID = Input::getInt('roomID');
			if ($roomIDs = $roomID ? [$roomID] : Input::getIntCollection('roomIDs'))
			{
				$conditions['roomIDs'] = $roomIDs;
			}
			elseif ($room = Input::getCMD('room') and $roomID = Rooms::getID($room))
			{
				$conditions['roomIDs'] = [$roomID];
			}

			if ($subjectID = Input::getInt('subjectID'))
			{
				$conditions['subjectIDs'] = [$subjectID];
			}

			$unitID = Input::getInt('unitID');
			if ($unitIDs = $unitID ? [$unitID] : Input::getIntCollection('unitIDs'))
			{
				$conditions['unitIDs'] = $unitIDs;
			}
		}
		elseif ($personID = Persons::getIDByUserID($conditions['userID']))
		{
			// Schedule items which have been planned for the person should appear in their schedule
			$conditions['personIDs']       = [$personID];
			$conditions['showUnpublished'] = true;
		}

		return $conditions;
	}

	/**
	 * Retrieves the sum of the effective capacity of physical rooms associated with concurrent instances of the same
	 * block and unit as the instance identified.
	 *
	 * @param   int  $instanceID  the id of the instance
	 *
	 * @return int
	 */
	public static function getCapacity(int $instanceID): int
	{
		$query = Database::getQuery();
		$query->select('DISTINCT r.id, r.effCapacity')
			->from('#__organizer_rooms AS r')
			->innerJoin('#__organizer_instance_rooms AS ir ON ir.roomID = r.id')
			->innerJoin('#__organizer_instance_persons AS ipe ON ipe.id = ir.assocID')
			->innerJoin('#__organizer_instances AS i2 ON i2.id = ipe.instanceID')
			->innerJoin('#__organizer_instances AS i1 ON i1.unitID = i2.unitID AND i1.blockID = i2.blockID')
			->where('r.virtual = 0')
			->where("i1.id = $instanceID")
			->where("ir.delta != 'removed'")
			->where("ipe.delta != 'removed'")
			->order('r.effCapacity DESC');
		Database::setQuery($query);

		if ($capacities = Database::loadIntColumn(1))
		{
			return array_sum($capacities);
		}

		return 0;
	}

	/**
	 * Returns the current number of participants for all concurrent instances  of the same block and unit as the given
	 * instance.
	 *
	 * @param   int  $instanceID
	 *
	 * @return int
	 */
	public static function getCurrentCapacity(int $instanceID): int
	{
		$query = Database::getQuery();
		$query->select('COUNT(DISTINCT ipa.participantID)')
			->from('#__organizer_instance_participants AS ipa')
			->innerJoin('#__organizer_instances AS i2 ON i2.id = ipa.instanceID')
			->innerJoin('#__organizer_instances AS i1 ON i1.unitID = i2.unitID AND i1.blockID = i2.blockID')
			->where('ipa.registered = 1')
			->where("i1.id = $instanceID")
			->where("i1.delta != 'removed'")
			->where("i2.delta != 'removed'");
		Database::setQuery($query);

		return Database::loadInt();
	}

	/**
	 * Creates a display of formatted dates for a course
	 *
	 * @param   int  $instanceID  the id of the course to be loaded
	 *
	 * @return string the dates to display
	 */
	public static function getDateDisplay(int $instanceID): string
	{
		$block = self::getBlock($instanceID);

		return $block->date ? Dates::formatDate($block->date) : '';
	}

	/**
	 * Retrieves the groupIDs associated with the instance.
	 *
	 * @param   int  $instanceID  the id of the instance
	 *
	 * @return array
	 */
	public static function getGroupIDs(int $instanceID): array
	{
		$instance = new Tables\Instances();
		if (!$instance->load($instanceID))
		{
			return [];
		}

		$query = Database::getQuery();
		$query->select('DISTINCT groupID')
			->from('#__organizer_instance_groups AS ig')
			->where("ig.delta != 'removed'")
			->innerJoin('#__organizer_instance_persons AS ip ON ip.id = ig.assocID')
			->where("ip.delta != 'removed'")
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->where("i.blockID = $instance->blockID")
			->where("i.delta != 'removed'")
			->where("i.unitID = $instance->unitID");
		Database::setQuery($query);

		return Database::loadIntColumn();
	}

	/**
	 * @param $conditions
	 *
	 * @return array
	 */
	public static function getItems($conditions): array
	{
		$instanceIDs = self::getInstanceIDs($conditions);
		if (empty($instanceIDs))
		{
			return self::getJumpDates($conditions);
		}

		$instances = [];
		foreach ($instanceIDs as $instanceID)
		{
			if (!$instance = self::getInstance($instanceID))
			{
				continue;
			}

			self::fill($instance, $conditions);
			$instances[] = $instance;
		}

		return $instances;
	}

	/**
	 * Retrieves the core information for one instance.
	 *
	 * @param   int  $instanceID  the id of the instance
	 *
	 * @return array an array modeling the instance
	 */
	public static function getInstance(int $instanceID): array
	{
		$tag = Languages::getTag();

		$instancesTable = new Tables\Instances();
		if (!$instancesTable->load($instanceID))
		{
			return [];
		}

		$instance = [
			'attended'           => 0,
			'blockID'            => $instancesTable->blockID,
			'eventID'            => $instancesTable->eventID,
			'instanceID'         => $instanceID,
			'instanceStatus'     => $instancesTable->delta,
			'instanceStatusDate' => $instancesTable->modified,
			'methodID'           => $instancesTable->methodID,
			'registered'         => 0,
			'unitID'             => $instancesTable->unitID
		];
		$title    = $instancesTable->title;

		unset($instancesTable);

		$blocksTable = new Tables\Blocks();

		if (!$blocksTable->load($instance['blockID']))
		{
			return [];
		}

		$endTime = empty($instance['eventID']) ? Dates::formatTime($blocksTable->endTime) : Dates::formatEndTime($blocksTable->endTime);
		$block   = [
			'date'      => $blocksTable->date,
			'endTime'   => $endTime,
			'startTime' => Dates::formatTime($blocksTable->startTime)
		];

		unset($blocksTable);

		$eventsTable = new Tables\Events();

		if ($instance['eventID'] and $eventsTable->load($instance['eventID']))
		{
			$event = [
				'campusID'         => $eventsTable->campusID,
				'deadline'         => $eventsTable->deadline,
				'description'      => $eventsTable->{"description_$tag"},
				'fee'              => $eventsTable->fee,
				'name'             => $eventsTable->{"name_$tag"},
				'registrationType' => $eventsTable->registrationType,
				'subjectNo'        => $eventsTable->subjectNo
			];
		}
		else
		{
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

		unset($eventsTable);

		$method       = ['methodCode' => '', 'methodName' => ''];
		$methodsTable = new Tables\Methods();
		if ($methodsTable->load($instance['methodID']))
		{
			$method = [
				'methodCode' => $methodsTable->{"abbreviation_$tag"},
				'method'     => $methodsTable->{"name_$tag"}
			];
		}

		unset($methodsTable);

		$unitsTable = new Tables\Units();
		if (!$unitsTable->load($instance['unitID']))
		{
			return [];
		}

		$orgName = $unitsTable->organizationID ? Organizations::getShortName($unitsTable->organizationID) : '';

		$unit = [
			'comment'        => $unitsTable->comment,
			'courseID'       => $unitsTable->courseID,
			'organization'   => $orgName,
			'organizationID' => $unitsTable->organizationID,
			'gridID'         => $unitsTable->gridID,
			'termID'         => $unitsTable->termID,
			'unitStatus'     => $unitsTable->delta,
			'unitStatusDate' => $unitsTable->modified,
		];

		unset($unitsTable);

		$instance = array_merge($block, $event, $instance, $method, $unit);

		if ($courseID = $instance['courseID'])
		{
			$courseTable = new Tables\Courses();
			if ($courseTable->load($courseID))
			{
				$instance['campusID']         = $courseTable->campusID;
				$instance['course']           = $courseTable->{"name_$tag"};
				$instance['deadline']         = $courseTable->deadline;
				$instance['fee']              = $courseTable->fee;
				$instance['registrationType'] = $courseTable->registrationType;

				if ($courseTable->{"description_$tag"})
				{
					$instance['description'] = $courseTable->{"description_$tag"};
				}
			}
		}

		// TODO Calculate space available. rooms, seats, factoring, presence

		if ($participantID = Users::getID())
		{
			$participantsTable = new Tables\InstanceParticipants();
			if ($participantsTable->load(['instanceID' => $instanceID, 'participantID' => $participantID]))
			{
				$instance['attended']           = (int) $participantsTable->attended;
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
	 * @return array the ids matching the conditions
	 */
	public static function getInstanceIDs(array $conditions): array
	{
		$query = self::getInstanceQuery($conditions);
		$query->select('DISTINCT i.id')
			->where("b.date BETWEEN '{$conditions['startDate']}' AND '{$conditions['endDate']}'")
			->order('b.date, b.startTime, b.endTime');
		Database::setQuery($query);

		return Database::loadIntColumn();
	}

	/**
	 * Builds a general query to find instances matching the given conditions.
	 *
	 * @param   array  $conditions  the conditions for filtering the query
	 *
	 * @return JDatabaseQuery the query object
	 */
	public static function getInstanceQuery(array $conditions): JDatabaseQuery
	{
		$query = Database::getQuery();

		// TODO: resolve course information (registration type, available capacity) and consequences
		$query->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
			->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
			->innerJoin('#__organizer_instance_persons AS ipe ON ipe.instanceID = i.id')
			->leftJoin('#__organizer_instance_groups AS ig ON ig.assocID = ipe.id')
			->leftJoin('#__organizer_groups AS g ON g.id = ig.groupID')
			->leftJoin('#__organizer_instance_rooms AS ir ON ir.assocID = ipe.id');

		$dDate = $conditions['delta'];

		switch ($conditions['status'])
		{
			case self::CURRENT:

				$query->where("i.delta != 'removed'")
					->where("(ig.delta != 'removed' OR ig.id IS NULL)")
					->where("ipe.delta != 'removed'")
					->where("u.delta != 'removed'");

				break;

			case self::NEW:

				$query->where("i.delta != 'removed'");
				$query->where("u.delta != 'removed'");
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

				self::addDeltaClause($query, 'i', $dDate);
				self::addDeltaClause($query, 'u', $dDate);
				self::addDeltaClause($query, 'ipe', $dDate);
				self::addDeltaClause($query, 'ig', $dDate);

				break;
		}

		if (empty($conditions['showUnpublished']))
		{
			$upQuery = Database::getQuery();
			$upQuery->select('i.id')
				->from('#__organizer_instances AS i')
				->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
				->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
				->innerJoin('#__organizer_instance_persons AS ipe ON ipe.instanceID = i.id')
				->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ipe.id')
				->innerJoin('#__organizer_group_publishing AS gp ON gp.groupID = ig.groupID AND gp.termID = u.termID')
				->where("i.delta != 'removed'")
				->where("b.date BETWEEN '{$conditions['startDate']}' AND '{$conditions['endDate']}'")
				->where("u.delta != 'removed'")
				->where("ipe.delta != 'removed'")
				->where("ig.delta != 'removed'")
				->where('gp.published = 0');

			if (!empty($conditions['organizationIDs']))
			{
				$organizationIDs = implode(',', ArrayHelper::toInteger($conditions['organizationIDs']));
				$upQuery->innerJoin('#__organizer_associations AS ag ON ag.groupID = ig.groupID')
					->where("ag.organizationID IN ($organizationIDs)");
			}

			Database::setQuery($upQuery);

			if ($upIDs = Database::loadIntColumn())
			{
				$upIDs = implode(',', $upIDs);
				$query->where("i.id NOT IN ($upIDs)");
			}

		}

		if (!empty($conditions['my']))
		{
			$my      = (int) $conditions['my'];
			$wherray = [];
			if ($userID = Users::getID())
			{
				$exists = Participants::exists($userID);
				if ($my === self::REGISTRATIONS and $exists)
				{
					$query->innerJoin('#__organizer_instance_participants AS ipa ON ipa.instanceID = i.id')
						->where("ipa.participantID = $userID")
						->where("ipa.registered = 1");
				}
				else
				{
					if ($personID = Persons::getIDByUserID($userID))
					{
						$wherray[] = "ipe.personID = $personID";
					}
					if ($exists)
					{
						$query->leftJoin('#__organizer_instance_participants AS ipa ON ipa.instanceID = i.id');
						$wherray[] = "ipa.participantID = $userID";
					}

					if ($wherray)
					{
						$query->where('(' . implode(' OR ', $wherray) . ')');
					}
				}
			}
			else
			{
				$query->where('i.id = 0');
			}
		}

		if (!empty($conditions['categoryIDs']))
		{
			$categoryIDs = implode(',', $conditions['categoryIDs']);
			$query->where("g.categoryID IN ($categoryIDs)");
		}

		if (!empty($conditions['courseIDs']))
		{
			$courseIDs = implode(',', $conditions['courseIDs']);
			$query->where("u.courseID IN ($courseIDs)");
		}

		if (!empty($conditions['groupIDs']))
		{
			$groupIDs = implode(',', $conditions['groupIDs']);
			$query->where("ig.groupID IN ($groupIDs)");
		}

		if (!empty($conditions['personIDs']))
		{
			$personIDs = implode(',', $conditions['personIDs']);
			$query->where("ipe.personID IN ($personIDs)");
		}

		if (!empty($conditions['roomIDs']))
		{
			$roomIDs = implode(',', $conditions['roomIDs']);
			$query->where("ir.roomID IN ($roomIDs)");
			self::addDeltaClause($query, 'ir', $conditions['delta']);
		}

		if (!empty($conditions['eventIDs']) or !empty($conditions['subjectIDs']) or !empty($conditions['eventsRequired']))
		{
			$query->innerJoin('#__organizer_events AS e ON e.id = i.eventID');

			if (!empty($conditions['eventIDs']))
			{
				$eventIDs = implode(',', $conditions['eventIDs']);
				$query->where("e.id IN ($eventIDs)");
			}

			if (!empty($conditions['subjectIDs']))
			{
				$subjectIDs = implode(',', $conditions['subjectIDs']);
				$query->innerJoin('#__organizer_subject_events AS se ON se.eventID = e.id')
					->where("se.subjectID IN ($subjectIDs)");
			}
		}

		if (!empty($conditions['unitIDs']))
		{
			$unitIDs = implode(',', $conditions['unitIDs']);
			$query->where("i.unitID IN ($unitIDs)");
		}

		return $query;
	}

	/**
	 * Returns the current number of participants for all concurrent instances  of the same block and unit as the given
	 * instance.
	 *
	 * @param   int  $instanceID
	 *
	 * @return int
	 */
	public static function getInterested(int $instanceID): int
	{
		$query = Database::getQuery();
		$query->select('COUNT(DISTINCT ipa.participantID)')
			->from('#__organizer_instance_participants AS ipa')
			->innerJoin('#__organizer_instances AS i2 ON i2.id = ipa.instanceID')
			->innerJoin('#__organizer_instances AS i1 ON i1.unitID = i2.unitID AND i1.blockID = i2.blockID')
			->where("i1.id = $instanceID")
			->where("i1.delta != 'removed'")
			->where("i2.delta != 'removed'");
		Database::setQuery($query);

		return Database::loadInt();
	}

	/**
	 * Gets the localized name of the event associated with the instance and the name of the instance's method.
	 *
	 * @param   int  $instanceID  the id of the instance
	 *
	 * @return string
	 */
	public static function getMethod(int $instanceID): string
	{
		$instance = new Tables\Instances();

		if (!$instance->load($instanceID) or !$methodID = $instance->methodID)
		{
			return '';
		}

		return Methods::getName($methodID);
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

		if (!$instance->load($resourceID))
		{
			return '';
		}

		if (!$eventID = $instance->eventID)
		{
			return $instance->title;
		}

		if (!$name = Events::getName($eventID))
		{
			return '';
		}

		if ($showMethod and $methodID = $instance->methodID)
		{
			$name .= ' - ' . Methods::getName($methodID);
		}

		return $name;
	}

	/**
	 * Retrieves the
	 *
	 * @param   int  $instanceID
	 *
	 * @return array
	 */
	public static function getOrganizationIDs(int $instanceID): array
	{
		$organizationIDs = [];

		foreach (self::getGroupIDs($instanceID) as $groupID)
		{
			$organizationIDs = array_merge($organizationIDs, Groups::getOrganizationIDs($groupID));
		}

		return $organizationIDs;
	}

	/**
	 * Retrieves the persons actively associated with the given instance.
	 *
	 * @param   int  $instanceID  the id of the instance
	 * @param   int  $roleID      the id of the role the person fills
	 *
	 * @return array
	 */
	public static function getPersonIDs(int $instanceID, int $roleID = 0): array
	{
		$query = Database::getQuery();
		$query->select('personID')
			->from('#__organizer_instance_persons')
			->where("instanceID = $instanceID")
			->where("delta != 'removed'");

		if ($roleID)
		{
			$query->where("roleID = $roleID");
		}

		Database::setQuery($query);

		return Database::loadIntColumn();
	}

	/**
	 * Retrieves the role id for the the given instance and person.
	 *
	 * @param   int  $instanceID  the id of the instance
	 * @param   int  $personID    the id of the person
	 *
	 * @return int the id of the role
	 */
	public static function getRoleID(int $instanceID, int $personID): int
	{
		$table = new Tables\InstancePersons();

		if ($table->load(['instanceID' => $instanceID, 'personID' => $personID]))
		{
			return $table->roleID;
		}

		return 0;
	}

	/**
	 * Retrieves the rooms actively associated with the given instance.
	 *
	 * @param   int  $instanceID  the id of the instance
	 *
	 * @return array
	 */
	public static function getRoomIDs(int $instanceID): array
	{
		$query = Database::getQuery();
		$query->select('DISTINCT roomID')
			->from('#__organizer_instance_rooms AS ir')
			->innerJoin('#__organizer_instance_persons AS ip ON ip.id = ir.assocID')
			->where("ip.instanceID = $instanceID")
			->where("ir.delta != 'removed'")
			->where("ip.delta != 'removed'");
		Database::setQuery($query);

		return Database::loadIntColumn();
	}

	/**
	 * Filters the person ids to view access
	 *
	 * @param   array &$personIDs  the person ids.
	 * @param   int    $userID     the id of the user whose authorizations will be checked
	 *
	 * @return void removes unauthorized entries from the array
	 */
	public static function filterPersonIDs(array &$personIDs, int $userID)
	{
		if (Can::administrate() or Can::manage('persons'))
		{
			return;
		}

		$thisPersonID = Persons::getIDByUserID($userID);
		$authorized   = Can::viewTheseOrganizations();

		foreach ($personIDs as $key => $personID)
		{
			// Identity or publicly released
			$identity = ($thisPersonID and $thisPersonID === $personID);
			$released = Persons::released($personID);
			if ($identity or $released)
			{
				continue;
			}

			$associations = Persons::getOrganizationIDs($personID);
			$overlap      = array_intersect($authorized, $associations);

			if (empty($overlap))
			{
				unset($personIDs[$key]);
			}
		}
	}

	/**
	 * Searches for the next and most recent previous date where events matching the query can be found.
	 *
	 * @param   array  $conditions  the schedule configuration parameters
	 *
	 * @return array next and latest available dates
	 */
	public static function getJumpDates(array $conditions): array
	{
		$futureQuery = self::getInstanceQuery($conditions);
		$jumpDates   = [];
		$pastQuery   = clone $futureQuery;

		$futureQuery->select('MIN(date)')->where("date > '" . $conditions['endDate'] . "'");
		Database::setQuery($futureQuery);

		if ($futureDate = Database::loadString())
		{
			$jumpDates['futureDate'] = $futureDate;
		}

		$pastQuery->select('MAX(date)')->where("date < '" . $conditions['startDate'] . "'");
		Database::setQuery($pastQuery);

		if ($pastDate = Database::loadString())
		{
			$jumpDates['pastDate'] = $pastDate;
		}

		return $jumpDates;
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
		if (Can::administrate())
		{
			return true;
		}

		if (!$personID and !$personID = Persons::getIDByUserID(Users::getID()))
		{
			return false;
		}

		$query = Database::getQuery();
		$query->select('COUNT(*)')->from('#__organizer_instance_persons')->where("personID = $personID");

		if ($instanceID)
		{
			$query->where("instanceID = $instanceID");
		}

		if ($roleID)
		{
			$query->where("roleID = $roleID");
		}

		Database::setQuery($query);

		return Database::loadBool();
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
		if (!$capacity = self::getCapacity($instanceID))
		{
			return false;
		}

		return self::getCurrentCapacity($instanceID) >= $capacity;
	}

	/**
	 * Checks whether the instance takes place exclusively online.
	 *
	 * @param   int  $instanceID
	 *
	 * @return bool
	 */
	public static function isOnline(int $instanceID): bool
	{
		$query = Database::getQuery();
		$query->select('r.id')
			->from('#__organizer_rooms AS r')
			->innerJoin('#__organizer_instance_rooms AS ir ON ir.roomID = r.id')
			->innerJoin('#__organizer_instance_persons AS ipe ON ipe.id = ir.assocID')
			->innerJoin('#__organizer_instances AS i ON i.id = ipe.instanceID')
			->where('r.virtual = 0')
			->where("i.id = $instanceID")
			->where("ir.delta != 'removed'")
			->where("ipe.delta != 'removed'");
		Database::setQuery($query);

		// No non-virtual rooms associated
		return !Database::loadBool();
	}

	/**
	 * Sets the instance's bookingID
	 *
	 * @param   array  &$instance  the instance to modify
	 *
	 * @return void
	 */
	public static function setBooking(array &$instance)
	{
		$booking               = new Tables\Bookings();
		$exists                = $booking->load(['blockID' => $instance['blockID'], 'unitID' => $instance['unitID']]);
		$instance['bookingID'] = $exists ? $booking->id : null;
	}

	/**
	 * Sets/overwrites attributes based on subject associations.
	 *
	 * @param   array &$instance  the array of instance attributes
	 *
	 * @return void modifies the instance
	 */
	private static function setCourse(array &$instance)
	{
		$coursesTable = new Tables\Courses();
		if (empty($instance['courseID']) or !$coursesTable->load($instance['courseID']))
		{
			return;
		}

		$tag                      = Languages::getTag();
		$instance['campusID']     = $coursesTable->campusID ?: $instance['campusID'];
		$instance['courseGroups'] = $coursesTable->groups ?: '';
		$instance['courseName']   = $coursesTable->{"name_$tag"} ?: '';
		$instance['deadline']     = $coursesTable->deadline ?: $instance['deadline'];
		$instance['fee']          = $coursesTable->fee ?: $instance['fee'];
		$instance['full']         = Courses::isFull($instance['courseID']);

		$instance['description']      = (empty($instance['description']) and $coursesTable->{"description_$tag"}) ?
			$coursesTable->{"description_$tag"} : $instance['description'];
		$instance['registrationType'] = $coursesTable->registrationType ?: $instance['registrationType'];
	}

	/**
	 * Sets the start and end date parameters and adjusts the date parameter as appropriate.
	 *
	 * @param   array &$parameters  the parameters used for event retrieval
	 *
	 * @return void modifies $parameters
	 */
	public static function setDates(array &$parameters)
	{
		$date     = $parameters['date'];
		$dateTime = strtotime($date);
		$reqDoW   = (int) date('w', $dateTime);

		$startDayNo   = empty($parameters['startDay']) ? 1 : $parameters['startDay'];
		$endDayNo     = empty($parameters['endDay']) ? 6 : $parameters['endDay'];
		$displayedDay = ($reqDoW >= $startDayNo and $reqDoW <= $endDayNo);

		if (!$displayedDay)
		{
			if ($reqDoW === 6)
			{
				$string = '-1 day';
			}
			else
			{
				$string = '+1 day';
			}

			$date = date('Y-m-d', strtotime($string, $dateTime));
		}

		$parameters['date'] = $date;

		switch ($parameters['interval'])
		{
			case 'day':
				$dates = ['startDate' => $date, 'endDate' => $date];
				break;

			case 'half':
				$dates = Dates::getHalfYear($date);
				break;

			case 'month':
				$dates = Dates::getMonth($date, $startDayNo, $endDayNo);
				break;

			case 'quarter':
				$dates = Dates::getQuarter($date);
				break;

			case 'term':
				$dates = Dates::getTerm($date);
				break;

			case 'week':
			default:
				$dates = Dates::getWeek($date, $startDayNo, $endDayNo);
		}

		$parameters = array_merge($parameters, $dates);
	}

	/**
	 * Gets the groups associated with the instance => person association.
	 *
	 * @param   array &$person      the array of person attributes
	 * @param   array  $conditions  the conditions which instances must fulfill
	 *
	 * @return void modifies $person
	 */
	private static function setGroups(array &$person, array $conditions)
	{
		$tag   = Languages::getTag();
		$query = Database::getQuery();

		$query->select('ig.groupID, ig.delta, ig.modified')
			->select("g.code AS code, g.name_$tag AS name, g.fullName_$tag AS fullName, g.gridID")
			->from('#__organizer_instance_groups AS ig')
			->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID')
			->where("ig.assocID = {$person['assocID']}");

		if (array_key_exists('categoryIDs', $conditions))
		{
			$query->where('g.categoryID IN (' . implode($conditions['categoryIDs']) . ')');
		}

		// If the instance itself has been removed the status of its associations do not play a role
		if ($conditions['instanceStatus'] !== 'removed')
		{
			self::addDeltaClause($query, 'ig', $conditions['delta']);
		}

		Database::setQuery($query);
		if (!$groupAssocs = Database::loadAssocList())
		{
			return;
		}

		$groups = [];
		foreach ($groupAssocs as $groupAssoc)
		{
			$groupID = $groupAssoc['groupID'];
			$group   = [
				'code'       => $groupAssoc['code'],
				'fullName'   => $groupAssoc['fullName'],
				'group'      => $groupAssoc['name'],
				'status'     => $groupAssoc['delta'],
				'statusDate' => $groupAssoc['modified']
			];

			$groups[$groupID] = $group;
		}

		$person['groups'] = $groups;
	}

	/**
	 * Sets the instance's participation properties:
	 * - 'busy'       - the user's schedule has an appointment in a block overlapping the instance
	 * - 'capacity'   - the number of users who may physically attend the instance
	 * - 'interested' - the number of users who have added this instance to their schedule
	 * - 'registered' - the user has registered to physically participate in the instance
	 * - 'scheduled'  - the user has added the instance to their schedule
	 *
	 * @param   array  $instance  the array containing instance inforation
	 *
	 * @return void
	 */
	public static function setParticipation(array &$instance)
	{
		$instance['capacity']   = self::getCapacity($instance['instanceID']);
		$instance['current']    = self::getCurrentCapacity($instance['instanceID']);
		$instance['interested'] = self::getInterested($instance['instanceID']);

		if (!$userID = Users::getID())
		{
			$instance['busy']       = false;
			$instance['registered'] = false;
			$instance['scheduled']  = false;

			return;
		}

		$participation = new Tables\InstanceParticipants();
		if ($participation->load(['instanceID' => $instance['instanceID'], 'participantID' => $userID]))
		{
			$instance['busy']       = true;
			$instance['registered'] = $participation->registered;
			$instance['scheduled']  = true;

			return;
		}

		// The times in the instance have been pretreated, so that the endTime is no longer valid for comparisons.
		$block = new Tables\Blocks();
		if (!$block->load($instance['blockID']))
		{
			$instance['busy'] = false;

			return;
		}

		$instance['registered'] = false;
		$instance['scheduled']  = false;

		$blockConditions = [
			"b.startTime <= '$block->startTime' AND b.endTime >= '$block->endTime'",
			"b.startTime BETWEEN '$block->startTime' AND '$block->endTime'",
			"b.endTime BETWEEN '$block->startTime' AND '$block->endTime'"
		];
		$blockConditions = '((' . implode(') OR (', $blockConditions) . '))';

		$query = Database::getQuery();
		$query->select('ip.id')
			->from('#__organizer_instance_participants AS ip')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
			->where($blockConditions)
			->where("ip.participantID = $userID");
		Database::setQuery($query);
		$instance['busy'] = Database::loadBool();
	}

	/**
	 * Gets the persons and person associated resources associated with the instance.
	 *
	 * @param   array &$instance    the array of instance attributes
	 * @param   array  $conditions  the conditions which instances must fulfill
	 *
	 * @return void modifies the instance array
	 */
	public static function setPersons(array &$instance, array $conditions)
	{
		$conditions['instanceStatus'] = $instance['instanceStatus'] ?? 'new';

		$tag   = Languages::getTag();
		$query = Database::getQuery();
		$query->select('ip.id AS assocID, ip.personID, ip.roleID, ip.delta AS status, ip.modified')
			->select("r.abbreviation_$tag AS roleCode, r.name_$tag AS role")
			->from('#__organizer_instance_persons AS ip')
			->innerJoin('#__organizer_roles AS r ON r.id = ip.roleID')
			->where("ip.instanceID = {$instance['instanceID']}");

		// If the instance itself has been removed the status of its associations do not play a role
		if ($conditions['instanceStatus'] !== 'removed')
		{
			self::addDeltaClause($query, 'ip', $conditions['delta']);
		}

		Database::setQuery($query);
		if (!$personAssocs = Database::loadAssocList())
		{
			return;
		}

		$persons = [];
		foreach ($personAssocs as $personAssoc)
		{
			$assocID  = $personAssoc['assocID'];
			$personID = $personAssoc['personID'];
			$person   = [
				'assocID'    => $assocID,
				'code'       => $personAssoc['roleCode'],
				'person'     => Persons::getLNFName($personID, true),
				'role'       => $personAssoc['role'],
				'roleID'     => $personAssoc['roleID'],
				'status'     => $personAssoc['status'],
				'statusDate' => $personAssoc['modified']
			];

			self::setGroups($person, $conditions);
			self::setRooms($person, $conditions);
			$persons[$personID] = $person;
		}

		$instance['resources'] = $persons;
	}

	/**
	 * Set the display of unpublished instances according to the user's access rights
	 *
	 * @param   array &$conditions  the conditions for instance retrieval
	 *
	 * @return void
	 */
	public static function setPublishingAccess(array &$conditions)
	{
		$allowedIDs   = Can::viewTheseOrganizations();
		$overlap      = array_intersect($conditions['organizationIDs'], $allowedIDs);
		$overlapCount = count($overlap);

		// If the user has planning access to all requested organizations show unpublished automatically.
		if ($overlapCount and $overlapCount == count($conditions['organizationIDs']))
		{
			$conditions['showUnpublished'] = true;
		}
		else
		{
			$conditions['showUnpublished'] = false;
		}
	}

	/**
	 * Gets the rooms associated with the instance => person association.
	 *
	 * @param   array &$person      the array of person attributes
	 * @param   array  $conditions  the conditions which instances must fulfill
	 *
	 * @return void modifies $person
	 */
	private static function setRooms(array &$person, array $conditions)
	{
		$query = Database::getQuery();
		$query->select('ir.roomID, ir.delta, ir.modified, r.name, r.virtual')
			->select('b.location AS location, c1.location AS campusLocation, c2.location AS defaultLocation')
			->from('#__organizer_instance_rooms AS ir')
			->innerJoin('#__organizer_rooms AS r ON r.id = ir.roomID')
			->leftJoin('#__organizer_buildings AS b ON b.id = r.buildingID')
			->leftJoin('#__organizer_campuses AS c1 ON c1.id = b.campusID')
			->leftJoin('#__organizer_campuses AS c2 ON c2.id = c1.parentID')
			->where("ir.assocID = {$person['assocID']}");

		// If the instance itself has been removed the status of its associations do not play a role
		if ($conditions['instanceStatus'] !== 'removed')
		{
			self::addDeltaClause($query, 'ir', $conditions['delta']);
		}

		Database::setQuery($query);
		if (!$roomAssocs = Database::loadAssocList())
		{
			return;
		}

		$rooms = [];
		foreach ($roomAssocs as $room)
		{
			$campus   = '';
			$location = empty($room['location']) ? '' : $room['location'];

			if (!empty($room['campusLocation']))
			{
				$campus = $room['campusLocation'];
			}
			elseif (!empty($room['defaultLocation']))
			{
				$campus = $room['defaultLocation'];
			}

			$roomID = $room['roomID'];
			$room   = [
				'campus'     => $campus,
				'location'   => $location,
				'room'       => $room['name'],
				'status'     => $room['delta'],
				'statusDate' => $room['modified'],
				'virtual'    => $room['virtual']
			];

			$rooms[$roomID] = $room;
		}

		$person['rooms'] = $rooms;
	}

	/**
	 * Sets/overwrites attributes based on subject associations.
	 *
	 * @param   array &$instance    the instance
	 * @param   array  $conditions  the conditions used to specify the instances
	 *
	 * @return void modifies the instance
	 */
	public static function setSubject(array &$instance, array $conditions)
	{
		$tag   = Languages::getTag();
		$query = Database::getQuery();
		$query->select("DISTINCT s.id, s.abbreviation_$tag AS code, s.fullName_$tag AS fullName")
			->select("s.description_$tag AS description")
			->from('#__organizer_subjects AS s')
			->innerJoin('#__organizer_subject_events AS se ON se.subjectID = s.id')
			->innerJoin('#__organizer_associations AS a ON a.subjectID = s.id')
			->where("se.eventID = {$instance['eventID']}");
		Database::setQuery($query);

		if (!$subjects = Database::loadAssocList())
		{
			$instance['subjectID'] = null;
			$instance['code']      = '';
			$instance['fullName']  = '';

			return;
		}

		$subject = [];

		// In the event of multiple results take the first one to fulfill the organization condition
		if (!empty($conditions['organizationIDs']) and count($subjects) > 1)
		{
			foreach ($subjects as $subjectItem)
			{
				$organizationIDs = Subjects::getOrganizationIDs($subjectItem['id']);
				if (array_intersect($organizationIDs, $conditions['organizationIDs']))
				{
					$subject = $subjectItem;
					break;
				}
			}
		}

		// Default
		if (empty($subject))
		{
			$subject = $subjects[0];
		}

		$instance['subjectID'] = $subject['id'];
		$instance['code']      = empty($subject['code']) ? '' : $subject['code'];
		$instance['fullName']  = empty($subject['fullName']) ? '' : $subject['fullName'];

		if (empty($instance['description']) and !empty($subject['description']))
		{
			$instance['description'] = $subject['description'];
		}
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
		return self::hasResponsibility($instanceID, $personID, self::TEACHER);
	}
}
