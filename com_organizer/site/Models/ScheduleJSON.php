<?php
/**
 * @package     THM_Organizer
 * @extension   com_thm_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Organizer\Helpers;
use Organizer\Tables;
use stdClass;

/**
 * Class which models, validates and compares schedule data to and from json objects.
 */
class ScheduleJSON extends BaseModel
{
	/**
	 * Array containing already processed calendar ids.
	 * @var array
	 */
	private $calendarIDs = [];

	/**
	 * Array containing already processed calendar configuration mapping ids.
	 * @var array
	 */
	private $ccmIDs = [];

	/**
	 * Object containing information from the current schedule
	 *
	 * @var stdClass
	 */
	private $current = null;

	/**
	 * Array containing already processed lesson configuration ids.
	 * @var array
	 */
	private $lessonConfigurationIDs = [];

	/**
	 * Array containing already processed lesson subject ids.
	 * @var array
	 */
	private $lessonSubjectIDs = [];

	/**
	 * Array containing already processed lesson pool ids.
	 * @var array
	 */
	private $lessonTeacherIDs = [];

	/**
	 * The id of the organization entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $organizationID;

	/**
	 * Object containing information from the reference schedule
	 *
	 * @var stdClass
	 */
	private $reference = null;

	/**
	 * The id of the term entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $termID;

	/**
	 * Array containing already processed unit ids.
	 * @var array
	 */
	private $unitIDs = [];

	/**
	 * Removes 'removed' instances from the calendar, empty times from dates and empty dates from the calendar.
	 *
	 * @param   object  $calendar  the object containing the mapping of configurations to instances
	 *
	 * @return void
	 */
	private function cleanCalendar($calendar)
	{
		foreach ($calendar as $date => $blocks)
		{
			foreach ($blocks as $blockTimes => $lessons)
			{
				$this->cleanCollection($lessons);
				if (empty((array) $lessons))
				{
					unset($this->reference->calendar->$date->$blockTimes);
				}
			}

			if (empty((array) $blocks))
			{
				unset($this->reference->calendar->$date);
			}
		}
	}

	/**
	 * Removes delta information from object collections
	 *
	 * @param   object &$collection  the object collection being currently iterated
	 *
	 * @return void removes delta information and unsets removed schedule entries
	 */
	private function cleanCollection($collection)
	{
		foreach ($collection as $id => $item)
		{
			if (!empty($item->delta) and $item->delta === 'removed')
			{
				unset($collection->$id);
				continue;
			}

			$item->delta = '';

			foreach ($item as $property => $value)
			{
				if ($property === 'configurations')
				{
					$this->confirmIntegrity($id, $value);
					$item->$property = $value;
					continue;
				}

				if (is_object($value))
				{
					if ($property === 'pools' or $property === 'teachers')
					{
						$this->cleanIndexedCollection($value);
						$item->$property = $value;
						continue;
					}

					// Subjects
					$this->cleanCollection($value);
				}
			}
		}
	}

	/**
	 * Removes 'removed' array entries.
	 *
	 * @param   object  $collection  the array to filter
	 *
	 * @return void
	 */
	private function cleanIndexedCollection($collection)
	{
		if (!is_object($collection) or empty((array) $collection))
		{
			$collection = new class {
			};
		}

		foreach ($collection as $resourceID => $delta)
		{
			if (!empty($delta) and $delta == 'removed')
			{
				unset($collection->$resourceID);
			}
		}
	}

	/**
	 * Eliminates configurations inconsistent via their lesson ids and removes 'removed' groups and persons.
	 *
	 * @return void
	 */
	private function cleanConfigurations()
	{
		foreach ($this->reference->configurations as $index => $configuration)
		{
			$configuration = json_decode($configuration);

			if (empty($configuration->lessonID) or !isset($this->reference->lessons->{$configuration->lessonID}))
			{
				$configuration = new class {
				};
			}
			else
			{

				$this->cleanIndexedCollection($configuration->teachers);
				$this->cleanIndexedCollection($configuration->rooms);
			}

			$this->reference->configurations[$index] = json_encode($configuration);
		}
	}

	/**
	 * Removes inconsistent configuration references.
	 *
	 * @param   int     $lessonID       the untis id of the unit
	 * @param   mixed  &$configIndexes  the configuration indexes to which this instance is mapped (array|object)
	 *
	 * @return void
	 */
	private function confirmIntegrity(int $lessonID, &$configIndexes)
	{
		$configIndexes = (array) $configIndexes;

		foreach ($configIndexes as $arrayIndex => $configurationsIndex)
		{
			if (!isset($this->reference->configurations[$configurationsIndex]))
			{
				unset($configIndexes[$arrayIndex]);
			}

			$configuration = json_decode($this->reference->configurations[$configurationsIndex]);

			if (empty($configuration->lessonID) or $configuration->lessonID !== $lessonID)
			{
				unset($configIndexes[$arrayIndex]);
			}
		}

		// Force a revert to automatic indexing so any later json_encoding doesn't model it as an object
		$configIndexes = array_values($configIndexes);
	}

	/**
	 * Retrieves the id of the schedule to use as a reference object.
	 *
	 * @param   Tables\OldSchedules  $current  the current schedule
	 *
	 * @return int the id of the schedule to reference if found, otherwise 0
	 */
	private function getReferenceID(Tables\OldSchedules $current)
	{
		$creationDate = $current->creationDate;
		$creationTime = $current->creationTime;
		$diffDate     = "creationDate < '$creationDate'";
		$sameDate     = "(creationDate = '$creationDate' AND creationTime < '$creationTime')";

		$query = $this->_db->getQuery(true);
		$query->select('id')
			->from('#__thm_organizer_schedules')
			->where("departmentID = $this->organizationID")
			->where("termID = $this->termID")
			->where("($diffDate OR $sameDate)")
			->order('creationDate DESC, creationTime DESC');
		$this->_db->setQuery($query);

		if ($result = Helpers\OrganizerHelper::executeQuery('loadAssoc', []))
		{
			return $result['id'];
		}

		return 0;
	}

	/**
	 * Sets entries in table $suffix to 'removed' where the table id is not in $processedIDs but the fk column $column
	 * has an already processed $columnValue.
	 *
	 * @param   string  $suffix        the unique portion of the table name
	 * @param   array   $processedIDs  the ids which have already been processed
	 * @param   string  $column        the name of the fk column
	 * @param   array   $fkIDs         the already processed values for the fk column
	 *
	 * @return void
	 */
	private function remove(string $suffix, array $processedIDs, string $column, array $fkIDs)
	{
		$processedIDs = implode(',', $processedIDs);
		$fkIDs        = implode(',', $fkIDs);
		$query        = $this->_db->getQuery(true);

		/** @noinspection SqlResolve */
		$query->delete("#__thm_organizer_$suffix")
			->where("id NOT IN ($processedIDs)")
			->where("$column IN ($fkIDs)");
		$this->_db->setQuery($query);

		Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Sets table entries related to instancing and instance configurations.
	 *
	 * @return void
	 */
	private function setCalendarReferences()
	{
		$calendar       = $this->current->calendar;
		$configurations = $this->current->configurations;
		$lessons        = $this->current->lessons;

		if ($this->reference)
		{
			$dates             = array_keys((array) $calendar);
			$refCalendar       = $this->reference->calendar;
			$refConfigurations = $this->reference->configurations;
			$refDates          = array_keys((array) $refCalendar);

			// Maintained date indexes
			foreach (array_intersect($dates, $refDates) as $sameDate)
			{
				$times    = array_keys((array) $calendar->$sameDate);
				$refTimes = array_keys((array) $refCalendar->$sameDate);
				$cKeys    = ['schedule_date' => $sameDate];

				// Maintained time indexes
				foreach (array_intersect($times, $refTimes) as $sameTime)
				{
					$instances    = $calendar->$sameDate->$sameTime;
					$lessonIDs    = array_keys((array) $instances);
					$refInstances = $refCalendar->$sameDate->$sameTime;
					$refLessonIDs = array_keys((array) $refInstances);

					list($startTime, $endTime) = explode('-', $sameTime);
					$cKeys['endTime']   = $endTime . '00';
					$cKeys['startTime'] = $startTime . '00';

					// Maintained instance
					foreach (array_intersect($lessonIDs, $refLessonIDs) as $lessonID)
					{
						if (empty($lessons->$lessonID))
						{
							unset($calendar->$sameDate->$sameTime->$lessonID);
							unset($refCalendar->$sameDate->$sameTime->$lessonID);
							continue;
						}

						$unitID            = $lessons->$lessonID;
						$cKeys['lessonID'] = $unitID;

						$c = new Tables\Calendar();
						$c->load($cKeys);
						$c->bind($cKeys);
						$c->delta = '';
						$c->store();

						$this->calendarIDs[$c->id] = $c->id;

						$instance    = $calendar->$sameDate->$sameTime->$lessonID;
						$refInstance = $refCalendar->$sameDate->$sameTime->$lessonID;

						// These usually are arrays, but...
						$instance->configurations    = (array) $instance->configurations;
						$refInstance->configurations = (array) $refInstance->configurations;

						foreach ($instance->configurations as $index => $configurationIndex)
						{
							if (empty($configurations[$configurationIndex]))
							{
								unset($calendar->$sameDate->$sameTime->$lessonID->configurations[$index]);
								continue;
							}

							$configuration = json_decode($configurations[$configurationIndex]);

							$invalidLesson = (empty($configuration->lessonID) or $configuration->lessonID != $lessonID);
							if ($invalidLesson or !$eventID = $configuration->subjectID)
							{
								continue;
							}

							$lsKeys = ['lessonID' => $unitID, 'subjectID' => $eventID];

							$ls = new Tables\LessonSubjects();

							// Lesson => subject pairing was not modeled in the lessons structure => inconsistency
							if (!$ls->load($lsKeys))
							{
								continue;
							}

							unset($configuration->lessonID, $configuration->subjectID);

							$comparisonMade = false;
							$cpIDs          = array_keys((array) $configuration->teachers);
							$crIDs          = array_keys((array) $configuration->rooms);

							// Check for semantic intersection
							foreach ($refInstance->configurations as $refIndex => $refConfigurationIndex)
							{
								if (empty($refConfigurations[$refConfigurationIndex]))
								{
									unset($refInstance->configurations[$index]);
									continue;
								}

								$refConfiguration = json_decode($configurations[$configurationIndex]);

								$invalidLesson =
									(empty($refConfiguration->lessonID) or $refConfiguration->lessonID != $lessonID);

								// No comparison possible
								if ($invalidLesson or $refConfiguration->subjectID != $eventID)
								{
									continue;
								}

								$rrIDs = array_keys((array) $refConfiguration->rooms);

								foreach (array_intersect($crIDs, $rrIDs) as $roomID)
								{
									$configuration->$roomID = '';
								}

								foreach (array_diff($rrIDs, $crIDs) as $roomID)
								{
									$configuration->$roomID = 'removed';
								}

								foreach (array_diff($crIDs, $rrIDs) as $roomID)
								{
									$configuration->$roomID = 'new';
								}

								$rpIDs = array_keys((array) $refConfiguration->teachers);

								foreach (array_intersect($cpIDs, $rpIDs) as $personID)
								{
									$configuration->$personID = '';
								}

								foreach (array_diff($rpIDs, $cpIDs) as $personID)
								{
									$configuration->$personID = 'removed';
								}

								foreach (array_diff($cpIDs, $rpIDs) as $personID)
								{
									$configuration->$personID = 'new';
								}

								$this->setConfigMapping($c->id, $ls->id, $configuration);

								$comparisonMade = true;
								unset($refInstance->configurations[$index]);
							}

							if (!$comparisonMade)
							{
								foreach ($cpIDs as $personID)
								{
									$configuration->$personID = 'new';
								}

								foreach ($crIDs as $roomID)
								{
									$configuration->$roomID = 'new';
								}

								$this->setConfigMapping($c->id, $ls->id, $configuration);
							}

							unset($instance->configurations[$index]);
						}

						unset($calendar->$sameDate->$sameTime->$lessonID);
						unset($refCalendar->$sameDate->$sameTime->$lessonID);
					}

					// Removed instance
					foreach (array_diff($refLessonIDs, $lessonIDs) as $lessonID)
					{
						$cKeys['lessonID'] = $this->reference->lessons->$lessonID;
						$c                 = new Tables\Calendar();

						if ($c->load($cKeys))
						{
							$c->delta = 'removed';
							$c->store();

							$this->calendarIDs[$c->id] = $c->id;
						}

						unset($refCalendar->$sameDate->$sameTime->$lessonID);
					}

					// New instance
					foreach (array_diff($lessonIDs, $refLessonIDs) as $lessonID)
					{
						$this->setNewLessonReference($sameDate, $sameTime, $lessonID);
					}

					unset($calendar->$sameDate->$sameTime, $refCalendar->$sameDate->$sameTime);
				}

				// Removed time indexes
				foreach (array_diff($refTimes, $times) as $time)
				{
					$this->setRemovedTime($sameDate, $time);
				}

				// New time indexes
				foreach (array_diff($times, $refTimes) as $time)
				{
					$this->setNewTimeReference($sameDate, $time);
				}

				unset($calendar->$sameDate, $refCalendar->$sameDate);
			}

			// Removed date index
			foreach (array_diff($refDates, $dates) as $date)
			{
				foreach (array_keys((array) $refCalendar->$date) as $time)
				{
					$this->setRemovedTime($date, $time);
				}

				unset($refCalendar->$date);
			}
		}

		foreach (array_keys((array) $calendar) as $date)
		{
			foreach (array_keys((array) $calendar->$date) as $time)
			{
				$this->setNewTimeReference($date, $time);
			}

			unset($calendar->$date);
		}

		// Ensure no inconsistencies on the edges of the scope
		$this->setRemoved('calendar', $this->calendarIDs, 'lessonID', $this->unitIDs);
		$this->remove('lesson_configurations', $this->lessonConfigurationIDs, 'lessonID', $this->lessonSubjectIDs);
		$this->remove('calendar_configuration_map', $this->ccmIDs, 'calendarID', $this->calendarIDs);
		$this->remove('calendar_configuration_map', $this->ccmIDs, 'configurationID', $this->lessonConfigurationIDs);
	}

	/**
	 * Sets configurations and configuration mapping entries.
	 *
	 * @param   int     $calendarID       the id of the calendar entry
	 * @param   int     $lessonSubjectID  the id of the lesson subject association entry
	 * @param   object  $configuration    the json encoded configuration
	 *
	 * @return void
	 */
	private function setConfigMapping(int $calendarID, int $lessonSubjectID, $configuration)
	{
		$lcKeys = ['lessonID' => $lessonSubjectID, 'configuration' => json_encode($configuration)];

		$lc = new Tables\LessonConfigurations();
		$lc->load($lcKeys);
		$lc->save($lcKeys);

		$this->lessonConfigurationIDs[$lc->id] = $lc->id;

		$ccmKeys = ['calendarID' => $calendarID, 'configurationID' => $lc->id];

		$ccm = new Tables\CalendarConfigurationMap();
		$ccm->load($ccmKeys);
		$ccm->save($ccmKeys);

		$this->ccmIDs[$ccm->id] = $ccm->id;
	}

	/**
	 * Creates or updates a lesson_subjects table entry with the status new and creates new dependent associations.
	 *
	 *
	 * @param   int  $eventID   the id of the event
	 * @param   int  $lessonID  the id of the unit resource in Untis
	 *
	 * @return void
	 */
	private function setNewEventReferences(int $eventID, int $lessonID)
	{
		$lesson = $this->current->lessons->$lessonID;
		$lsKeys = ['lessonID' => $lesson->id, 'subjectID' => $eventID];

		$ls = new Tables\LessonSubjects();

		$ls->load($lsKeys);
		$ls->bind($lsKeys);
		$ls->delta = 'new';
		$ls->store();

		$this->lessonSubjectIDs[$ls->id] = $ls->id;

		foreach (array_keys((array) $lesson->subjects->$eventID->teachers) as $personID)
		{
			$this->setNewPersonReference($ls->id, $personID);
		}
	}

	/**
	 * Sets a new lesson configuration references.
	 *
	 * @param   string  $date      the date being iterated
	 * @param   string  $time      the time being iterated
	 * @param   string  $lessonID  the lesson id being iterated
	 *
	 * @return void
	 */
	private function setNewLessonReference(string $date, string $time, string $lessonID)
	{
		$calendar       = $this->current->calendar;
		$configurations = $this->current->configurations;
		$lessons        = $this->current->lessons;

		if (empty($lessons->$lessonID))
		{
			unset($calendar->$date->$time->$lessonID);

			return;
		}

		$unitID            = $lessons->$lessonID;
		$cKeys['lessonID'] = $lessons->$lessonID;

		$c = new Tables\Calendar();
		$c->load($cKeys);
		$c->bind($cKeys);
		$c->delta = 'new';
		$c->store();

		$this->calendarIDs[$c->id] = $c->id;

		foreach ($calendar->$date->$time->$lessonID->configurations as $index => $configurationIndex)
		{
			if (empty($configurations[$configurationIndex]))
			{
				continue;
			}

			$configuration = json_decode($configurations[$configurationIndex]);

			$invalidLesson = (empty($configuration->lessonID) or $configuration->lessonID !== $lessonID);
			if ($invalidLesson or !$eventID = $configuration->subjectID)
			{
				continue;
			}

			$lsKeys = ['lessonID' => $unitID, 'subjectID' => $eventID];

			$ls = new Tables\LessonSubjects();

			// Lesson => subject pairing was not modeled in the lessons structure => inconsistency
			if (!$ls->load($lsKeys))
			{
				continue;
			}

			unset($configuration->lessonID, $configuration->subjectID);

			foreach (array_keys((array) $configuration->rooms) as $roomID)
			{
				$configuration->$roomID = 'new';
			}

			foreach (array_keys((array) $configuration->teachers) as $personID)
			{
				$configuration->$personID = 'new';
			}

			$this->setConfigMapping($c->id, $ls->id, $configuration);
		}

		unset($calendar->$date->$time->$lessonID);
	}

	/**
	 * Creates or updates a lesson_teachers table entry with the status new.
	 *
	 * @param   int  $lsID      the id of the lesson subject entry referenced
	 * @param   int  $personID  the id of the person referenced
	 *
	 * @return void
	 */
	private function setNewPersonReference(int $lsID, int $personID)
	{
		$ltKeys = ['subjectID' => $lsID, 'teacherID' => $personID];

		$lt = new Tables\LessonTeachers();
		$lt->load($ltKeys);
		$lt->delta     = 'new';
		$lt->subjectID = $lsID;
		$lt->teacherID = $personID;
		$lt->store();

		$this->lessonTeacherIDs[$lt->id] = $lt->id;
	}

	/**
	 * Sets a new calendar reference and the associated configuration references.
	 *
	 * @param   string  $date  the date being iterated
	 * @param   string  $time  the time being iterated
	 *
	 * @return void
	 */
	private function setNewTimeReference(string $date, string $time)
	{
		$calendar = $this->current->calendar;

		list($startTime, $endTime) = explode('-', $time);
		$cKeys['endTime']       = $endTime . '00';
		$cKeys['schedule_date'] = $date;
		$cKeys['startTime']     = $startTime . '00';

		foreach (array_keys((array) $calendar->$date->$time) as $lessonID)
		{
			$this->setNewLessonReference($date, $time, $lessonID);
		}

		unset($calendar->$date->$time);
	}

	/**
	 * Creates the deltas relative to the next most recent schedule in organization/term context.
	 *
	 * @param   int  $scheduleID  the id of the current schedule
	 *
	 * @return void
	 */
	public function setReference(int $scheduleID)
	{
		$current = new Tables\OldSchedules();
		if (!$current->load($scheduleID))
		{
			return;
		}

		$this->current        = json_decode($current->schedule);
		$this->organizationID = $current->departmentID;
		$this->termID         = $current->termID;

		$reference   = new Tables\OldSchedules();
		$referenceID = $this->getReferenceID($current);

		if ($referenceID and $reference->load($referenceID))
		{
			$this->reference = json_decode($reference->schedule);
			$this->cleanCollection($this->reference->lessons);
			$this->cleanConfigurations();
			$this->cleanCalendar($this->reference->calendar);
		}

		$this->setUnitReferences();
		$this->setCalendarReferences();

		$reference->delete();
	}

	/**
	 * Sets entries in table $suffix to 'removed' where the table id is not in $processedIDs but the fk column $column
	 * has an already processed $columnValue.
	 *
	 * @param   string  $suffix        the unique portion of the table name
	 * @param   array   $processedIDs  the ids which have already been processed
	 * @param   string  $column        the name of the fk column
	 * @param   array   $fkIDs         the already processed values for the fk column
	 *
	 * @return void
	 */
	private function setRemoved(string $suffix, array $processedIDs, string $column, array $fkIDs)
	{
		$processedIDs = implode(',', $processedIDs);
		$fkIDs        = implode(',', $fkIDs);
		$query        = $this->_db->getQuery(true);

		/** @noinspection SqlResolve */
		$query->update("#__thm_organizer_$suffix")
			->set("delta = 'removed'")
			->where("id NOT IN ($processedIDs)")
			->where("$column IN ($fkIDs)")
			->where("delta != 'removed'");
		$this->_db->setQuery($query);

		Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Updates calendar references for removed time indexes.
	 *
	 * @param   string  $date  the date index being iterated
	 * @param   string  $time
	 *
	 * @return void
	 */
	private function setRemovedTime(string $date, string $time)
	{
		$instances = $this->reference->calendar->$date->$time;

		list($startTime, $endTime) = explode('-', $time);
		$cKeys = [
			'endTime'       => $endTime . '00',
			'schedule_date' => $date,
			'startTime'     => $startTime . '00'
		];

		foreach (array_keys((array) $instances) as $lessonID)
		{
			if (empty($this->reference->lessons->$lessonID))
			{
				unset($this->reference->calendar->$date->$time->$lessonID);
				continue;
			}

			$cKeys['lessonID'] = $this->reference->lessons->$lessonID;
			$c                 = new Tables\Calendar();

			if ($c->load($cKeys))
			{
				$c->delta = 'removed';
				$c->store();

				$this->calendarIDs[$c->id] = $c->id;
			}
		}

		unset($this->reference->calendar->$date->$time);
	}

	/**
	 * Updates Unit dependant associations in the old structure.
	 *
	 * @return void
	 */
	private function setUnitReferences()
	{
		$lessons = $this->current->lessons;

		if ($this->reference)
		{
			$lessonIDs    = array_keys((array) $this->current->lessons);
			$refLessons   = $this->reference->lessons;
			$refLessonIDs = array_keys((array) $refLessons);

			foreach (array_intersect($lessonIDs, $refLessonIDs) as $lessonID)
			{
				if (empty($lessons->$lessonID->id))
				{
					unset($this->current->lessons->$lessonID, $this->reference->lessons->$lessonID);
					continue;
				}

				$unitID                 = $lessons->$lessonID->id;
				$this->unitIDs[$unitID] = $unitID;

				// Unit statuses are handled using the newer function, nothing to update here.

				$events      = $lessons->$lessonID->subjects;
				$eventIDs    = array_keys((array) $events);
				$refEvents   = $refLessons->$lessonID->subjects;
				$refEventIDs = array_keys((array) $refEvents);

				foreach (array_intersect($refEventIDs, $eventIDs) as $sameEventID)
				{
					$lsKeys = ['lessonID' => $unitID, 'subjectID' => $sameEventID];

					$ls = new Tables\LessonSubjects();
					$ls->load($lsKeys);
					$ls->bind($lsKeys);
					$ls->delta = (empty($ls->id) or $ls->delta === 'removed') ? 'new' : '';
					$ls->store();

					$this->lessonSubjectIDs[$ls->id] = $ls->id;

					$personIDs    = array_keys((array) $events->$sameEventID->teachers);
					$refPersonIDs = array_keys((array) $refEvents->$sameEventID->teachers);

					foreach (array_intersect($personIDs, $refPersonIDs) as $samePersonID)
					{
						$ltKeys = ['subjectID' => $ls->id, 'teacherID' => $samePersonID];

						$lt = new Tables\LessonTeachers();
						$lt->load($ltKeys);
						$lt->bind($ltKeys);
						$lt->delta = (empty($lt->id) or $lt->delta === 'removed') ? 'new' : '';
						$lt->store();

						$this->lessonTeacherIDs[$lt->id] = $lt->id;
					}

					foreach (array_diff($refPersonIDs, $personIDs) as $removedPersonID)
					{
						$ltKeys = ['subjectID' => $ls->id, 'teacherID' => $removedPersonID];
						$lt     = new Tables\LessonTeachers();

						if ($lt->load($ltKeys) and $lt->delta !== 'removed')
						{
							$lt->delta = 'removed';
							$lt->store();
							$this->lessonTeacherIDs[$lt->id] = $lt->id;
						}
					}

					foreach (array_diff($personIDs, $refPersonIDs) as $newPersonID)
					{
						$this->setNewPersonReference($ls->id, $newPersonID);
					}
				}

				foreach (array_diff($refEventIDs, $eventIDs) as $removedEventID)
				{
					$lsKeys = ['lessonID' => $unitID, 'subjectID' => $removedEventID];
					$ls     = new Tables\LessonSubjects();

					if ($ls->load($lsKeys) and $ls->delta !== 'removed')
					{
						$ls->delta = 'removed';
						$ls->store();
						$this->lessonSubjectIDs[$ls->id] = $ls->id;
					}
				}

				foreach (array_diff($eventIDs, $refEventIDs) as $newEventID)
				{
					$this->setNewEventReferences($newEventID, $lessonID);
				}

				$lessons->$lessonID    = $lessons->$lessonID->id;
				$refLessons->$lessonID = $lessons->$lessonID;
			}

			foreach (array_diff($refLessonIDs, $lessonIDs) as $lessonID)
			{
				$refLessons->$lessonID = $refLessons->$lessonID->id;
			}

			$newLessonIDs = array_diff($lessonIDs, $refLessonIDs);
		}

		$lessonIDs = isset($newLessonIDs) ? $newLessonIDs : array_keys((array) $this->current->lessons);

		foreach ($lessonIDs as $lessonID)
		{
			if (empty($lessons->$lessonID->id))
			{
				unset($lessons->$lessonID);
				continue;
			}

			$this->unitIDs[$lessons->$lessonID->id] = $lessons->$lessonID->id;

			foreach (array_keys((array) $lessons->$lessonID->subjects) as $eventID)
			{
				$this->setNewEventReferences($eventID, $lessonID);
			}

			$lessons->$lessonID = $lessons->$lessonID->id;
		}

		// Ensure no inconsistencies on the edges of the scope
		$this->setRemoved('lesson_subjects', $this->lessonSubjectIDs, 'lessonID', $this->unitIDs);
		$this->setRemoved('lesson_teachers', $this->lessonTeacherIDs, 'subjectID', $this->lessonSubjectIDs);
	}
}