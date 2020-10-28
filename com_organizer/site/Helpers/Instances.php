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
use Joomla\CMS\Factory;
use Organizer\Tables;

/**
 * Provides functions for XML lesson validation and modeling.
 */
class Instances extends ResourceHelper
{
	const NORMAL = '', CURRENT = 1, NEW = 2, REMOVED = 3, CHANGED = 4;

	const TEACHER = 1;

	/**
	 * Adds a delta clause for a joined table.
	 *
	 * @param   JDatabaseQuery  $query  the query to modify
	 * @param   string          $alias  the table alias
	 * @param   mixed           $delta  string the date for the delta or bool false
	 *
	 * @return void modifies the query
	 */
	private static function addDeltaClause($query, $alias, $delta)
	{
		if ($delta)
		{
			$query->where("($alias.delta != 'removed' OR ($alias.delta = 'removed' AND $alias.modified > '$delta'))");
		}
		else
		{
			$query->where("$alias.delta != 'removed'");
		}
	}

	/**
	 * Builds the array of parameters used for lesson retrieval.
	 *
	 * @return array the parameters used to retrieve lessons.
	 */
	public static function getConditions()
	{
		$conditions               = [];
		$conditions['userID']     = Users::getID();
		$conditions['mySchedule'] = empty($conditions['userID']) ? false : Input::getBool('mySchedule', false);

		$conditions['date'] = Input::getCMD('date', date('Y-m-d'));

		$delta               = Input::getInt('delta', 0);
		$conditions['delta'] = empty($delta) ? false : date('Y-m-d', strtotime('-' . $delta . ' days'));

		$interval               = Input::getCMD('interval', 'week');
		$intervals              = ['day', 'half', 'month', 'quarter', 'term', 'week'];
		$conditions['interval'] = in_array($interval, $intervals) ? $interval : 'week';

		// Reliant on date and interval properties
		self::setDates($conditions);

		$conditions['status'] = self::NORMAL;

		if (empty($conditions['mySchedule']))
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

				self::setOrganizationalPublishing($conditions);
			}
			else
			{
				$conditions['showUnpublished'] = Can::administrate();
			}

			if ($personID = Input::getInt('personID'))
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
	 * Creates a display of formatted dates for a course
	 *
	 * @param   int  $instanceID  the id of the course to be loaded
	 *
	 * @return string the dates to display
	 */
	public static function getDateDisplay($instanceID)
	{
		$instance = new Tables\Instances();
		if (!$instance->load($instanceID) or !$blockID = $instance->blockID)
		{
			return '';
		}

		$block = new Tables\Blocks();
		if (!$block->load($blockID) or !$date = $block->date)
		{
			return '';
		}

		return Dates::formatDate($date);
	}

	/**
	 * @param $conditions
	 *
	 * @return array
	 */
	public static function getItems($conditions)
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

			self::setPersons($instance, $conditions);
			if (empty($instance['resources']))
			{
				continue;
			}

			self::setCourse($instance);
			self::setSubject($instance, $conditions);

			$instances[] = $instance;
		}

		return $instances;
	}

	/**
	 * Retrieves the core information for one instance.
	 *
	 * @param   int  $instanceID  the id of the instance
	 *
	 * @return array an array modelling the instance
	 */
	public static function getInstance($instanceID)
	{
		$tag = Languages::getTag();

		$instancesTable = new Tables\Instances();
		if (!$instancesTable->load($instanceID))
		{
			return [];
		}

		$instance = [
			'blockID'            => $instancesTable->blockID,
			'eventID'            => $instancesTable->eventID,
			'instanceID'         => $instanceID,
			'instanceStatus'     => $instancesTable->delta,
			'instanceStatusDate' => $instancesTable->modified,
			'methodID'           => $instancesTable->methodID,
			'unitID'             => $instancesTable->unitID
		];

		unset($instancesTable);

		$blocksTable = new Tables\Blocks();
		if (!$blocksTable->load($instance['blockID']))
		{
			return [];
		}

		$block = [
			'date'      => $blocksTable->date,
			'endTime'   => date('H:i', strtotime($blocksTable->endTime)),
			'startTime' => date('H:i', strtotime($blocksTable->startTime))
		];

		unset($blocksTable);

		$eventsTable = new Tables\Events();
		if (!$eventsTable->load($instance['eventID']))
		{
			return [];
		}

		$event = [
			'campusID'         => $eventsTable->campusID,
			'deadline'         => $eventsTable->deadline,
			'description'      => $eventsTable->{"description_$tag"},
			'fee'              => $eventsTable->fee,
			'name'             => $eventsTable->{"name_$tag"},
			'registrationType' => $eventsTable->registrationType,
			'subjectNo'        => $eventsTable->subjectNo
		];

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

		$unit = [
			'comment'        => $unitsTable->comment,
			'courseID'       => $unitsTable->courseID,
			'organization'   => Organizations::getShortName($unitsTable->organizationID),
			'organizationID' => $unitsTable->organizationID,
			'gridID'         => $unitsTable->gridID,
			'unitStatus'     => $unitsTable->delta,
			'unitStatusDate' => $unitsTable->modified,
		];

		unset($unitsTable);

		return array_merge($block, $event, $instance, $method, $unit);
	}

	/**
	 * Retrieves a list of instance IDs for instances which fulfill the requirements.
	 *
	 * @param   array  $conditions  the conditions filtering the instances
	 *
	 * @return array the ids matching the conditions
	 */
	public static function getInstanceIDs($conditions)
	{
		$dbo   = Factory::getDbo();
		$query = self::getInstanceQuery($conditions);

		$query->select('DISTINCT i.id')
			->where("b.date BETWEEN '{$conditions['startDate']}' AND '{$conditions['endDate']}'")
			->order('b.date, b.startTime, b.endTime');

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Builds a general query to find instances matching the given conditions.
	 *
	 * @param   array  $conditions  the conditions for filtering the query
	 *
	 * @return JDatabaseQuery the query object
	 */
	public static function getInstanceQuery($conditions)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		// TODO: resolve course information (registration type, available capacity) and consequences
		$query->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
			->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
			->innerJoin('#__organizer_instance_persons AS ipe ON ipe.instanceID = i.id')
			->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ipe.id')
			->leftJoin('#__organizer_instance_rooms AS ir ON ir.assocID = ipe.id');

		$dDate = $conditions['delta'];

		switch ($conditions['status'])
		{
			case self::CURRENT:

				$query->where("i.delta != 'removed'");
				$query->where("u.delta != 'removed'");

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
			$gpConditions = "gp.groupID = ig.groupID AND gp.termID = u.termID";
			$query->leftJoin("#__organizer_group_publishing AS gp ON $gpConditions")
				->where('(gp.published = 1 OR gp.published IS NULL)');
		}

		if ($conditions['mySchedule'] and !empty($conditions['userID']))
		{
			// Aggregate of selected items and the teacher schedule
			if (!empty($conditions['personIDs']))
			{
				$personIDs = implode(',', $conditions['personIDs']);
				$query->leftJoin('#__organizer_instance_participants AS ipa ON ipa.instanceID = i.id')
					->innerJoin('#__organizer_instance_persons AS ipe ON ipe.instanceID = i.id')
					->where("(ipa.participantID = {$conditions['userID']} OR ipe.personID IN ($personIDs))");
			}
			else
			{
				$query->innerJoin('#__organizer_instance_participants AS ipa ON ipa.instanceID = i.id')
					->where("ipa.participantID = {$conditions['userID']}");
			}

			return $query;
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

		if (!empty($conditions['eventIDs']) or !empty($conditions['subjectIDs']) or !empty($conditions['isEventsRequired']))
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
	 * Gets the localized name of the event associated with the instance and the name of the instance's method.
	 *
	 * @param   int  $instanceID the id of the instance
	 *
	 * @return string
	 */
	public static function getName(int $instanceID)
	{
		$instance = new Tables\Instances();
		if (!$instance->load($instanceID) or !$eventID = $instance->eventID)
		{
			return '';
		}

		if (!$name = Events::getName($eventID))
		{
			return '';
		}

		if ($methodID = $instance->methodID)
		{
			$name .= ' - ' .  Methods::getName($methodID);
		}

		return $name;
	}

	/**
	 * Gets an array of participant IDs for a given instance
	 *
	 * @param   int  $instanceID  the instance id
	 *
	 * @return array list of participants in course
	 */
	public static function getParticipantIDs(int $instanceID)
	{
		if (empty($instanceID))
		{
			return [];
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('participantID')
			->from('#__organizer_instance_participants')
			->where("instanceID = $instanceID")
			->order('participantID');

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Retrieves the persons actively associated with the given instance.
	 *
	 * @param   int  $instanceID  the id of the instance
	 * @param   int  $roleID      the id of the role the person fills
	 *
	 * @return array
	 */
	public static function getPersonIDs(int $instanceID, int $roleID = 0)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('personID')
			->from('#__organizer_instance_persons')
			->where("instanceID = $instanceID")
			->where("delta != 'removed'");

		if ($roleID)
		{
			$query->where("roleID = $roleID");
		}

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Retrieves the rooms actively associated with the given instance.
	 *
	 * @param   int  $instanceID the id of the instance
	 *
	 * @return array
	 */
	public static function getRoomIDs(int $instanceID)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('roomID')
			->from('#__organizer_instance_rooms AS ir')
			->innerJoin('#__organizer_instance_persons AS ip ON ip.id = ir.assocID')
			->where("ip.instanceID = $instanceID")
			->where("ir.delta != 'removed'")
			->where("ip.delta != 'removed'");
		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Filters the person ids to view access
	 *
	 * @param   array &$personIDs  the person ids.
	 * @param   int    $userID     the id of the user whose authorizations will be checked
	 *
	 * @return void removes unauthorized entries from the array
	 */
	public static function filterPersonIDs(&$personIDs, $userID)
	{
		if (empty($userID))
		{
			$personIDs = [];

			return;
		}

		if (Can::administrate() or Can::manage('persons'))
		{
			return;
		}

		$thisPersonID = Persons::getIDByUserID($userID);
		$authorized   = Can::viewTheseOrganizations();

		foreach ($personIDs as $key => $personID)
		{
			if (!empty($thisPersonID) and $thisPersonID == $personID)
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
	public static function getJumpDates($conditions)
	{
		$futureQuery = self::getInstanceQuery($conditions);
		$pastQuery   = clone $futureQuery;

		$futureQuery->select('MIN(date)')->where("date > '" . $conditions['endDate'] . "'");
		$pastQuery->select('MAX(date)')->where("date < '" . $conditions['startDate'] . "'");

		$dbo     = Factory::getDbo();
		$results = [];
		$dbo->setQuery($futureQuery);
		if ($futureDate = OrganizerHelper::executeQuery('loadResult'))
		{
			$results['futureDate'] = $futureDate;
		}

		$dbo->setQuery($pastQuery);
		if ($pastDate = OrganizerHelper::executeQuery('loadResult'))
		{
			$results['pastDate'] = $pastDate;
		}

		return $results;
	}

	/**
	 * Sets the start and end date parameters and adjusts the date parameter as appropriate.
	 *
	 * @param   array &$parameters  the parameters used for event retrieval
	 *
	 * @return void modifies $parameters
	 */
	public static function setDates(&$parameters)
	{
		$date     = $parameters['date'];
		$dateTime = strtotime($date);
		$reqDoW   = date('w', $dateTime);

		$startDayNo   = empty($parameters['startDay']) ? 1 : $parameters['startDay'];
		$endDayNo     = empty($parameters['endDay']) ? 6 : $parameters['endDay'];
		$displayedDay = ($reqDoW >= $startDayNo and $reqDoW <= $endDayNo);
		if (!$displayedDay)
		{
			if ($reqDoW === 6)
			{
				$dateTime = strtotime('-1 day', $dateTime);
			}
			else
			{
				$dateTime = strtotime('+1 day', $dateTime);
			}
			$date = date('Y-m-d', strtotime($dateTime));
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
				$dates = Dates::getMonth($date);
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
	 * Sets/overwrites attributes based on subject associations.
	 *
	 * @param   array &$instance  the array of instance attributes
	 *
	 * @return void modifies the instance
	 */
	private static function setCourse(&$instance)
	{
		$coursesTable = new Tables\Courses();
		if (empty($instance['courseID']) or !$coursesTable->load($instance['courseID']))
		{
			return;
		}

		$tag                      = Languages::getTag();
		$instance['campusID']     = $coursesTable->campusID ? $coursesTable->campusID : $instance['campusID'];
		$instance['courseGroups'] = $coursesTable->groups ? $coursesTable->groups : '';
		$instance['courseName']   = $coursesTable->{"name_$tag"} ? $coursesTable->{"name_$tag"} : '';
		$instance['deadline']     = $coursesTable->deadline ? $coursesTable->deadline : $instance['deadline'];
		$instance['fee']          = $coursesTable->fee ? $coursesTable->fee : $instance['fee'];
		$instance['full']         = Courses::isFull($instance['courseID']);

		$instance['description']      = (empty($instance['description']) and $coursesTable->{"description_$tag"}) ?
			$coursesTable->{"description_$tag"} : $instance['description'];
		$instance['registrationType'] = $coursesTable->registrationType ?
			$coursesTable->registrationType : $instance['registrationType'];
	}

	/**
	 * Gets the groups associated with the instance => person association.
	 *
	 * @param   array &$person      the array of person attributes
	 * @param   array  $conditions  the conditions which instances must fulfill
	 *
	 * @return void modifies $person
	 */
	private static function setGroups(&$person, $conditions)
	{
		$dbo   = Factory::getDbo();
		$tag   = Languages::getTag();
		$query = $dbo->getQuery(true);

		$query->select('ig.groupID, ig.delta, ig.modified')
			->select("g.code AS code, g.name_$tag AS name, g.fullName_$tag AS fullName, g.gridID")
			->from('#__organizer_instance_groups AS ig')
			->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID')
			->where("ig.assocID = {$person['assocID']}");

		// If the instance itself has been removed the status of its associations do not play a role
		if ($conditions['instanceStatus'] !== 'removed')
		{
			self::addDeltaClause($query, 'ig', $conditions['delta']);
		}

		$dbo->setQuery($query);
		if (!$groupAssocs = OrganizerHelper::executeQuery('loadAssocList', []))
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
	 * Set the display of unpublished instances according to the user's access rights
	 *
	 * @param   array &$conditions  the conditions for instance retrieval
	 *
	 * @return void
	 */
	public static function setOrganizationalPublishing(&$conditions)
	{
		$allowedIDs   = Can::scheduleTheseOrganizations();
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
	 * Gets the persons and person associated resources associated with the instance.
	 *
	 * @param   array &$instance    the array of instance attributes
	 * @param   array  $conditions  the conditions which instances must fulfill
	 *
	 * @return void modifies the instance array
	 */
	public static function setPersons(&$instance, $conditions)
	{
		$conditions['instanceStatus'] = $instance['instanceStatus'];

		$tag   = Languages::getTag();
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

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

		$dbo->setQuery($query);
		if (!$personAssocs = OrganizerHelper::executeQuery('loadAssocList', []))
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
	 * Gets the rooms associated with the instance => person association.
	 *
	 * @param   array &$person      the array of person attributes
	 * @param   array  $conditions  the conditions which instances must fulfill
	 *
	 * @return void modifies $person
	 */
	private static function setRooms(&$person, $conditions)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('ir.roomID, ir.delta, ir.modified, r.name')
			->from('#__organizer_instance_rooms AS ir')
			->innerJoin('#__organizer_rooms AS r ON r.id = ir.roomID')
			->where("ir.assocID = {$person['assocID']}");

		// If the instance itself has been removed the status of its associations do not play a role
		if ($conditions['instanceStatus'] !== 'removed')
		{
			self::addDeltaClause($query, 'ir', $conditions['delta']);
		}

		$dbo->setQuery($query);
		if (!$roomAssocs = OrganizerHelper::executeQuery('loadAssocList', []))
		{
			return;
		}

		$rooms = [];
		foreach ($roomAssocs as $room)
		{
			$roomID = $room['roomID'];
			$room   = [
				'room'       => $room['name'],
				'status'     => $room['delta'],
				'statusDate' => $room['modified']
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
	public static function setSubject(&$instance, $conditions)
	{
		$dbo   = Factory::getDbo();
		$tag   = Languages::getTag();
		$query = $dbo->getQuery(true);
		$query->select("DISTINCT s.id, s.abbreviation_$tag AS code, s.fullName_$tag AS fullName, s.shortName_$tag AS name")
			->select("s.description_$tag AS description")
			->from('#__organizer_subjects AS s')
			->innerJoin('#__organizer_subject_events AS se ON se.subjectID = s.id')
			->innerJoin('#__organizer_associations AS a ON a.subjectID = s.id')
			->where("se.eventID = {$instance['eventID']}");

		$dbo->setQuery($query);

		if (!$subjects = OrganizerHelper::executeQuery('loadAssocList', []))
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
		$instance['name']      = empty($subject['name']) ? $instance['name'] : $subject['name'];

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
	public static function teaches($instanceID = 0, $personID = 0)
	{
		$personID = $personID ? $personID : Persons::getIDByUserID(Users::getID());

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('COUNT(*)')
			->from('#__organizer_instance_persons AS ip')
			->where("ip.personID = $personID")
			->where('ip.roleID = ' . self::TEACHER);

		if ($instanceID)
		{
			$query->where("ip.instanceID = $instanceID");
		}

		$dbo->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('loadResult', false);
	}
}
