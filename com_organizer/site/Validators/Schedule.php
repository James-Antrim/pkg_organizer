<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Validators;

use Organizer\Helpers;
use Organizer\Tables;
use stdClass;

/**
 * Provides functions for XML unit validation and persistence.
 */
class Schedule
{
	public $categories = null;

	public $creationDate;

	public $creationTime;

	public $dateTime;

	public $errors = [];

	public $events = null;

	public $grids = null;

	public $groups = null;

	public $instances = [];

	public $methods = null;

	public $organizationID;

	public $persons = null;

	public $rooms = null;

	public $schoolYear = null;

	public $term = null;

	public $termID = null;

	public $units = null;

	public $warnings = [];

	public $xml = null;

	/**
	 * Creates a status report based upon object error and warning messages
	 *
	 * @return void  outputs errors to the application
	 */
	private function printStatusReport()
	{
		if (count($this->errors))
		{
			$errorMessage = Helpers\Languages::_('ORGANIZER_ERROR_HEADER') . '<br />';
			$errorMessage .= implode('<br />', $this->errors);
			Helpers\OrganizerHelper::message($errorMessage, 'error');
		}

		if (count($this->warnings))
		{
			Helpers\OrganizerHelper::message(implode('<br />', $this->warnings), 'warning');
		}
	}

	/**
	 * Checks a given untis schedule xml export for data completeness and consistency. Forms the data into structures
	 * for further processing
	 *
	 * @return bool true on successful validation w/o errors, false if the schedule was invalid or an error occurred
	 */
	public function validate(): bool
	{
		$this->organizationID = Helpers\Input::getInt('organizationID');
		$formFiles            = Helpers\Input::getInput()->files->get('jform', [], 'array');
		$this->xml            = simplexml_load_file($formFiles['file']['tmp_name']);

		// Unused & mostly unfilled nodes
		unset($this->xml->lesson_date_schemes, $this->xml->lesson_tables, $this->xml->reductions);
		unset($this->xml->reduction_reasons, $this->xml->studentgroups, $this->xml->students);

		// Creation Date & Time, school year dates, term attributes
		$this->creationDate = trim((string) $this->xml[0]['date']);
		$validCreationDate  = $this->validateDate($this->creationDate, 'CREATION_DATE');
		$this->creationTime = trim((string) $this->xml[0]['time']);
		$valid              = false;

		if ($valid = ($validCreationDate and $this->validateCreationTime()))
		{
			// Set the cut off to the day before schedule generation to avoid inconsistencies on the creation date
			$this->dateTime = strtotime('-1 day', strtotime("$this->creationDate $this->creationTime"));
		}

		Terms::validate($this, $this->xml->general);

		// If the term is expired or invalid there is no need for further validation.
		if ($this->errors)
		{
			return false;
		}

		$valid = ($valid and !empty($this->term));
		unset($this->xml->general);

		$contextKeys = [
			'creationDate'   => $this->creationDate,
			'creationTime'   => $this->creationTime,
			'organizationID' => $this->organizationID,
			'termID'         => $this->termID
		];

		$schedule = new Tables\Schedules();

		if ($schedule->load($contextKeys))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_SCHEDULE_EXISTS', 'error');

			return false;
		}

		$this->validateResources($valid);

		$this->printStatusReport();

		return !count($this->errors);
	}

	/**
	 * Validates a text attribute. Sets the attribute if valid.
	 *
	 * @return bool true if the creation time is valid, otherwise false
	 */
	public function validateCreationTime(): bool
	{
		if (empty($this->creationTime))
		{
			$this->errors[] = Helpers\Languages::_("ORGANIZER_CREATION_TIME_MISSING");

			return false;
		}

		if (!preg_match('/^[\d]{6}$/', $this->creationTime))
		{
			$this->errors[]     = Helpers\Languages::_("ORGANIZER_CREATION_TIME_INVALID");
			$this->creationTime = '';

			return false;
		}

		$this->creationTime = implode(':', str_split($this->creationTime, 2));

		return true;
	}

	/**
	 * Validates a date attribute.
	 *
	 * @param   string &$value     the attribute value passed by reference because of reformatting to Y-m-d
	 * @param   string  $constant  the unique text constant fragment
	 *
	 * @return bool true on success, otherwise false
	 */
	public function validateDate(string &$value, string $constant): bool
	{
		if (empty($value))
		{
			$this->errors[] = Helpers\Languages::_("ORGANIZER_{$constant}_MISSING");

			return false;
		}

		if ($value = date('Y-m-d', strtotime($value)))
		{
			return true;
		}

		return false;
	}

	/**
	 * Checks a given schedule in gp-untis xml format for data completeness and
	 * consistency and gives it basic structure
	 *
	 * @param   bool  $validTerm  whether or not the term is valid
	 *
	 * @return void true on successful validation w/o errors, false if the schedule was invalid or an error occurred
	 */
	public function validateResources(bool $validTerm)
	{
		$this->categories = new stdClass();
		foreach ($this->xml->departments->children() as $node)
		{
			Categories::validate($this, $node);
		}
		unset($this->xml->departments);

		$this->methods   = new stdClass();
		foreach ($this->xml->descriptions->children() as $node)
		{
			Descriptions::validate($this, $node);
		}
		unset($this->xml->descriptions);

		$this->grids = new stdClass();
		foreach ($this->xml->timeperiods->children() as $node)
		{
			Grids::validate($this, $node);
		}
		Grids::setIDs($this);
		unset($this->xml->timeperiods);

		$this->events = new stdClass();
		foreach ($this->xml->subjects->children() as $node)
		{
			Events::validate($this, $node);
		}
		Events::setWarnings($this);
		unset($this->xml->subjects);

		$this->groups = new stdClass();
		foreach ($this->xml->classes->children() as $node)
		{
			Groups::validate($this, $node);
		}

		// Grids are not unset here because they are still used in lesson/instance processing.
		unset($this->categories, $this->xml->classes);

		$this->persons = new stdClass();
		foreach ($this->xml->teachers->children() as $node)
		{
			Persons::validate($this, $node);
		}
		Persons::setWarnings($this);
		unset($this->xml->teachers);

		$this->rooms = new stdClass();
		foreach ($this->xml->rooms->children() as $node)
		{
			Rooms::validate($this, $node);
		}
		Rooms::setWarnings($this);
		unset($this->xml->rooms);

		if ($validTerm)
		{
			$this->units = new stdClass();

			foreach ($this->xml->lessons->children() as $node)
			{
				Units::validate($this, $node);
			}

			Units::updateDates((array) $this->units);
			Units::setWarnings($this);
		}
		unset($this->events, $this->groups, $this->methods, $this->persons, $this->term, $this->xml);
	}

	/**
	 * Validates a text attribute. Sets the attribute if valid.
	 *
	 * @param   string  $value     the attribute value
	 * @param   string  $constant  the unique text constant fragment
	 * @param   string  $regex     the regex to check the text against
	 *
	 * @return bool false if blocking errors were found, otherwise true
	 */
	public function validateText(string $value, string $constant, string $regex = ''): bool
	{
		if (empty($value))
		{
			$this->errors[] = Helpers\Languages::_("ORGANIZER_{$constant}_MISSING");

			return false;
		}

		if (!empty($regex) and preg_match($regex, $value))
		{
			$this->errors[] = Helpers\Languages::_("ORGANIZER_{$constant}_INVALID");

			return false;
		}

		return true;
	}
}
