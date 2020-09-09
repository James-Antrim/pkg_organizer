<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Exception;
use Joomla\CMS\Factory;
use Organizer\Helpers;
use Organizer\Helpers\Languages;

// Exception for frequency of use
use Organizer\Tables;

/**
 * Class which retrieves subject information for a detailed display of subject attributes.
 */
class CourseItem extends ItemModel
{
	/**
	 * Provides a strict access check which can be overwritten by extending classes.
	 *
	 * @return bool  true if the user can access the view, otherwise false
	 */
	protected function allow()
	{
		return true;
	}

	/**
	 * Loads subject information from the database
	 *
	 * @return array  subject data on success, otherwise empty
	 * @throws Exception
	 */
	public function getItem()
	{
		$courseID = Helpers\Input::getID();
		if (empty($courseID))
		{
			return [];
		}

		$courseTable = new Tables\Courses;
		if (!$courseTable->load($courseID))
		{
			return [];
		}

		$course = $this->getStructure();

		$query = $this->_db->getQuery(true);
		$query->select("MIN(startDate) AS startDate, MAX(endDate) AS endDate")
			->from('#__organizer_units')
			->where("courseID = $courseID");
		$this->_db->setQuery($query);

		if ($dates = Helpers\OrganizerHelper::executeQuery('loadAssoc', []))
		{
			$course['endDate']   = $dates['endDate'];
			$course['startDate'] = $dates['startDate'];
		}

		$campusID = $courseTable->campusID;
		$tag      = Languages::getTag();

		$course['campus']['value']      = Helpers\Campuses::getPin($campusID) . ' ' . Helpers\Campuses::getName($campusID);
		$course['campusID']             = $campusID;
		$course['deadline']             = $courseTable->deadline;
		$course['description']['value'] = $courseTable->{"description_$tag"} ? $courseTable->{"description_$tag"} : '';
		$course['fee']['value']         = $courseTable->fee ? $courseTable->fee . ' €' : '';
		$course['groups']               = $courseTable->groups;
		$course['id']                   = $courseID;
		$course['maxParticipants']      = $courseTable->maxParticipants;
		$course['name']['value']        = $courseTable->{"name_$tag"};
		$course['participants']         = count(Helpers\Courses::getParticipantIDs($courseID));
		$course['registrationType']     = $courseTable->registrationType;
		$course['termID']               = $courseTable->termID;

		$this->setRegistrationTexts($course);
		$this->setEvents($course);

		return $course;
	}

	/**
	 * Creates a template for course attributes
	 *
	 * @return array the course template
	 */
	private function getStructure()
	{
		$option   = 'ORGANIZER_';
		$template = [
			'id'                 => 0,
			'name'               => ['label' => Languages::_($option . 'NAME'), 'type' => 'text', 'value' => ''],
			'fee'                => ['label' => Languages::_($option . 'FEE'), 'type' => 'text', 'value' => ''],
			'campusID'           => 0,
			'campus'             => ['label' => Languages::_($option . 'CAMPUS'), 'type' => 'text', 'value' => ''],
			'speakers'           => ['label' => Languages::_($option . 'SPEAKERS'), 'type' => 'list', 'value' => []],
			'teachers'           => ['label' => Languages::_($option . 'TEACHERS'), 'type' => 'list', 'value' => []],
			'tutors'             => ['label' => Languages::_($option . 'TUTORS'), 'type' => 'list', 'value' => []],
			'description'        => [
				'label' => Languages::_($option . 'SHORT_DESCRIPTION'),
				'type'  => 'text',
				'value' => ''
			],
			'content'            => ['label' => Languages::_($option . 'CONTENT'), 'type' => 'text', 'value' => ''],
			'organization'       => [
				'label' => Languages::_($option . 'COURSE_ORGANIZATION'),
				'type'  => 'text',
				'value' => ''
			],
			'registration'       => [
				'label' => Languages::_($option . 'REGISTRATION'),
				'type'  => 'text',
				'value' => ''
			],
			'pretests'           => ['label' => Languages::_($option . 'PRETESTS'), 'type' => 'text', 'value' => ''],
			'courseContact'      => [
				'label' => Languages::_($option . 'COURSE_CONTACTS'),
				'type'  => 'text',
				'value' => ''
			],
			'contact'            => ['label' => Languages::_($option . 'CONTACTS'), 'type' => 'text', 'value' => ''],
			'courseStatus'       => null,
			'courseText'         => null,
			'deadline'           => null,
			'events'             => [],
			'maxParticipants'    => 0,
			'participants'       => 0,
			'preparatory'        => false,
			'registrationStatus' => null,
			'termID'             => null,
		];

		return $template;
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Courses A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Courses;
	}

	/**
	 * Sets event information for the course.
	 *
	 * @param   array &$course  the course to be modified
	 *
	 * @return void modifies the course
	 */
	private function setEvents(&$course)
	{
		// If the course has its own name, do not create it dynamically
		$setName = empty($course['name']['value']);

		$events = Helpers\Courses::getEvents($course['id']);
		foreach ($events as $key => $attributes)
		{
			$course['preparatory'] = ($course['preparatory'] or $attributes['preparatory']);
			foreach ($attributes as $name => $value)
			{
				if ($name == 'id')
				{
					continue;
				}

				if ($name == 'name')
				{
					if (!$setName)
					{
						continue;
					}

					if ($course['name']['value'] and strpos($course['name']['value'], $value) === false)
					{
						$course['name']['value'] .= " / $value";
					}
					elseif (empty($course['name']['value']))
					{
						$course['name']['value'] .= $value;
					}
					continue;
				}

				if ($name == 'fee')
				{
					if (!$course['fee']['value'] and strpos($course['name']['value'], $value) === false)
					{
						$course['fee']['value'] .= $value . '€';
					}
					continue;
				}

				if ($name == 'preparatory')
				{
					unset($events[$key][$name]);
					continue;
				}

				if ($course[$name]['value'] === $value)
				{
					continue;
				}
				elseif (is_string($value) and $course[$name]['value'] === '')
				{
					$course[$name]['value'] = $value;
					continue;
				}
				elseif (is_array($value) and $course[$name]['value'] === [])
				{
					$course[$name]['value'] = $value;
					continue;
				}
				else
				{
					$course[$name]['value'] = null;
					continue;
				}
			}
		}

		foreach ($events as $attributes)
		{
			foreach ($attributes as $name => $value)
			{
				if ($name === 'id')
				{
					continue;
				}

				if ($name === 'name' and $course[$name]['value'] !== $value)
				{
					continue;
				}

				if ($course[$name]['value'] or empty($value))
				{
					unset($attributes[$name]);
					continue;
				}
				else
				{
					unset($course[$name]);
				}
			}

			$event = $this->getStructure();
			foreach (array_keys($event) as $attribute)
			{

				// Course relevant attribute, attribute with the same attribute for all events, attribute with no value
				if (empty($attributes[$attribute]))
				{
					unset($event[$attribute]);
					continue;
				}


				if (is_array($event[$attribute]))
				{
					$event[$attribute]['value'] = $attributes[$attribute];
					continue;
				}
				else
				{
					$event[$attribute] = $attributes[$attribute];
					continue;
				}
			}
			$course['events'][] = $attributes;
		}

		// If there is only one event there will be no event display and only one register/deregister button.
		if (count($course['events']) === 1)
		{
			$course['events'] = [];
		}
	}

	/**
	 * Sets texts pertaining to the registration process.
	 *
	 * @param $course
	 */
	private function setRegistrationTexts(&$course)
	{
		$course['registration']['value'] = $course['registrationType'] ?
			Languages::_('ORGANIZER_REGISTRATION_MANUAL')
			: Languages::_('ORGANIZER_REGISTRATION_FIFO');
		$today                           = Helpers\Dates::standardizeDate();

		$expired = $course['endDate'] < $today;
		$ongoing = ($course['startDate'] <= $today and $expired);

		if ($course['deadline'])
		{
			$deadline = date('Y-m-d', strtotime("-{$course['deadline']} Days", strtotime($course['startDate'])));
		}
		else
		{
			$deadline = $course['startDate'];
		}

		$closed   = (!$expired and !$ongoing and $deadline <= $today);
		$deadline = Helpers\Dates::formatDate($deadline);

		$full   = $course['participants'] >= $course['maxParticipants'];
		$ninety = (!$full and ($course['participants'] / (int) $course['maxParticipants']) >= .9);

		if ($expired)
		{
			$course['courseStatus'] = 'grey';
			$course['courseText']   = Languages::_('ORGANIZER_COURSE_EXPIRED');

			return;
		}

		$texts = [];
		if ($ongoing or $full)
		{
			$course['courseStatus'] = 'red';
			if ($ongoing)
			{
				$texts['course'] = Languages::_('ORGANIZER_COURSE_ONGOING');
			}
			if ($full)
			{
				$texts['cRegistration'] = Languages::_('ORGANIZER_COURSE_FULL');
			}
		}
		elseif ($closed or $ninety)
		{
			$course['courseStatus'] = 'yellow';
			if ($closed)
			{
				$texts['cRegistration'] = Languages::_('ORGANIZER_DEADLINE_EXPIRED');
			}
			elseif ($ninety)
			{
				$texts['cRegistration'] = Languages::_('ORGANIZER_COURSE_LIMITED');
			}
		}

		$deadlineText = sprintf(Languages::_('ORGANIZER_DEADLINE_TEXT'), $deadline);

		if ($userID = Helpers\Users::getID())
		{
			$course['registrationStatus'] = Helpers\CourseParticipants::getState($course['id'], $userID);
			if ($course['registrationStatus'] === null)
			{
				$texts['pRegistration'] = Languages::_('ORGANIZER_COURSE_UNREGISTERED');

				if (!Helpers\Participants::exists())
				{
					$texts['profile'] = Languages::_('ORGANIZER_COURSE_PROFILE_REQUIRED');
				}

				$texts['deadline'] = $deadlineText;
			}
			else
			{
				unset($texts['course'], $texts['cRegistration']);
				if ($course['registrationStatus'])
				{
					$course['courseStatus'] = 'green';
					$texts['pRegistration'] = Languages::_('ORGANIZER_COURSE_ACCEPTED');
				}
				else
				{
					$course['courseStatus'] = 'blue';
					$texts['pRegistration'] = Languages::_('ORGANIZER_COURSE_WAITLIST');
				}
			}
		}
		else
		{
			$course['registrationStatus'] = null;
			$texts['pRegistration']       = Languages::_('ORGANIZER_COURSE_LOGIN_WARNING');
			$texts['deadline']            = $deadlineText;
		}

		$course['courseText'] = implode('<br>', $texts);
	}
}
