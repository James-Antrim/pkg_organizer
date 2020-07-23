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

use Joomla\CMS\Factory;

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Groups extends Associated implements Selectable
{
	use Filtered;

	static protected $resource = 'group';

	/**
	 * Retrieves the events associated with a group.
	 *
	 * @param $groupID
	 *
	 * @return array
	 */
	public static function getEvents($groupID)
	{
		$dbo   = Factory::getDbo();
		$tag   = Languages::getTag();
		$query = $dbo->getQuery(true);
		$query->select("DISTINCT e.id, e.code, e.name_$tag AS name, e.description_$tag AS description")
			->from('#__organizer_events AS e')
			->innerJoin('#__organizer_instances AS i ON i.eventID = e.id')
			->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
			->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ip.id')
			->where("groupID = $groupID");
		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}

	/**
	 * Retrieves the selectable options for the resource.
	 *
	 * @param   string  $access  any access restriction which should be performed
	 *
	 * @return array the available options
	 */
	public static function getOptions($access = '')
	{
		$categoryID  = Input::getInt('categoryID');
		$categoryIDs = $categoryID ? [$categoryID] : Input::getFilterIDs('category');
		$tag         = Languages::getTag();
		$name        = count($categoryIDs) === 1 ? "name_$tag" : "fullName_$tag";
		$options     = [];

		foreach (self::getResources() as $group)
		{
			if ($group['active'])
			{
				$options[] = HTML::_('select.option', $group['id'], $group[$name]);
			}
		}

		uasort($options, function ($optionOne, $optionTwo) {
			return $optionOne->text > $optionTwo->text;
		});

		// Any out of sequence indexes cause JSON to treat this as an object
		return array_values($options);
	}

	/**
	 * Retrieves the resource items.
	 *
	 * @param   string  $access  any access restriction which should be performed
	 *
	 * @return array the available resources
	 */
	public static function getResources($access = '')
	{
		$dbo = Factory::getDbo();

		$query = $dbo->getQuery(true);
		$query->select('g.*');
		$query->from('#__organizer_groups AS g');

		if (!empty($access))
		{
			self::addAccessFilter($query, $access, 'group', 'g');
		}

		self::addOrganizationFilter($query, 'group', 'g');
		self::addResourceFilter($query, 'category', 'cat', 'g');

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}

	/**
	 * Retrieves a list of subjects associated with a group
	 *
	 * @return array the subjects associated with the group
	 */
	public static function getSubjects()
	{
		$groupIDs = Input::getFilterIDs('group');
		if (empty($groupIDs))
		{
			return $groupIDs;
		}

		$groupIDs = implode(',', $groupIDs);

		$date = Input::getCMD('date');
		if (!Dates::isStandardized($date))
		{
			$date = date('Y-m-d');
		}

		$interval = Input::getCMD('interval');
		if (!in_array($interval, ['day', 'month', 'term', 'week']))
		{
			$interval = 'term';
		}

		$dbo = Factory::getDbo();

		$query = $dbo->getQuery(true);
		$query->select('DISTINCT lc.courseID')
			->from('#__organizer_lesson_courses AS lc')
			->innerJoin('#__organizer_lessons AS l ON l.id = lc.lessonID')
			->innerJoin('#__organizer_lesson_groups AS lg ON lg.lessonCourseID = lc.id')
			->where("lg.groupID IN ($groupIDs)")
			->where("l.delta != 'removed'")
			->where("lg.delta != 'removed'")
			->where("lc.delta != 'removed'");

		$dateTime = strtotime($date);
		switch ($interval)
		{
			case 'term':
				$query->innerJoin('#__organizer_terms AS term ON term.id = l.termID')
					->where("'$date' BETWEEN term.startDate AND term.endDate");
				break;
			case 'month':
				$monthStart = date('Y-m-d', strtotime('first day of this month', $dateTime));
				$startDate  = date('Y-m-d', strtotime('Monday this week', strtotime($monthStart)));
				$monthEnd   = date('Y-m-d', strtotime('last day of this month', $dateTime));
				$endDate    = date('Y-m-d', strtotime('Sunday this week', strtotime($monthEnd)));
				$query->innerJoin('#__organizer_calendar AS c ON c.lessonID = l.id')
					->where("c.schedule_date BETWEEN '$startDate' AND '$endDate'");
				break;
			case 'week':
				$startDate = date('Y-m-d', strtotime('Monday this week', $dateTime));
				$endDate   = date('Y-m-d', strtotime('Sunday this week', $dateTime));
				$query->innerJoin('#__organizer_calendar AS c ON c.lessonID = l.id')
					->where("c.schedule_date BETWEEN '$startDate' AND '$endDate'");
				break;
			case 'day':
				$query->innerJoin('#__organizer_calendar AS c ON c.lessonID = l.id')
					->where("c.schedule_date = '$date'");
				break;
		}

		$dbo->setQuery($query);
		$courseIDs = OrganizerHelper::executeQuery('loadColumn', []);

		if (empty($courseIDs))
		{
			return [];
		}

		$subjects = [];
		foreach ($courseIDs as $courseID)
		{
			$name            = Courses::getName($courseID, true);
			$subjects[$name] = $courseID;
		}

		ksort($subjects);

		return $subjects;
	}
}
