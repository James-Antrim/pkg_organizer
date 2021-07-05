<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace Organizer\Views\ICS;

use Exception;
use Organizer\Helpers;
use Organizer\Models;
use Organizer\Views\ICS\Components\VCalendar;
use SimpleXMLElement;

/**
 * Base class for a Joomla View
 *
 * Class holding methods for displaying presentation data.
 */
class Instances
{
	/**
	 * The instances data.
	 *
	 * @var array
	 */
	private $instances;

	private $language;

	/**
	 * This property specifies the date and time (UTC) that the instance of the iCalendar object was created.
	 * @var string
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.7.2
	 */
	private $stamp;

	/**
	 * This property specifies the text value that uniquely identifies the "VTIMEZONE" calendar component in the scope
	 * of an iCalendar object.
	 *
	 * @var string
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.3.1
	 */
	private $tzID;

	/**
	 * The component version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * This property defines the persistent, globally unique identifier for the calendar component.
	 * @var string
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.4.7
	 */
	private $uniqueID;

	/**
	 * Performs initial construction of the TCPDF Object.
	 */
	public function __construct()
	{
		$model = new Models\Instances();

		if (!$this->instances = $model->getItems())
		{
			Helpers\OrganizerHelper::error(404);
		}

		$this->language = strtoupper(Helpers\Languages::getTag());
		$this->tzID     = date_default_timezone_get();

		$this->setStamp();
		$this->setVersion();
		$this->setUID();
	}

	/**
	 * Provide a grouping of component properties that describe an event.
	 *
	 * @param   array   &$ics       the output container
	 * @param   object   $instance  the instance being iterated
	 *
	 * @return void modifies $ics
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.6.1
	 */
	private function addEvent(array &$ics, object $instance)
	{
		$date      = $instance->date;
		$endTime   = $instance->endTime;
		$startTime = $instance->startTime;

		$ics[] = "BEGIN:VEVENT";
		$ics[] = $this->stamp;
		$ics[] = $this->uniqueID;
		$ics[] = 'DTSTART' . $this->getDateTime("$date $startTime");
		$ics[] = 'DTEND' . $this->getDateTime("$date $endTime");
		$ics[] = $this->getLastModified($instance);

		/**
		 * The following are OPTIONAL but MUST NOT occur more than once:
		 *
		 * created
		 *
		 *
		 * description (TEXT):
		 * This property provides a more complete description of the calendar component than that provided by the "SUMMARY" property.
		 * https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.1.5
		 *
		 * geo (CS-FLOAT):
		 * This property specifies information related to the global position for the activity specified by a calendar component.
		 * The value MUST be two SEMICOLON-separated FLOAT values.
		 * https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.1.6
		 *
		 * location
		 *
		 *
		 * organizer
		 *
		 *
		 * priority
		 *
		 *
		 * seq
		 *
		 *
		 * status
		 *
		 *
		 * summary
		 *
		 *
		 * url
		 *
		 *
		 * recurid
		 */

		/**
		 * The following are OPTIONAL, and MAY occur more than once:
		 *
		 * attach
		 * attendee
		 * categories
		 * comment
		 * contact
		 * exdate
		 * rstatus
		 * related
		 * resources
		 * rdate
		 * x-prop
		 * iana-prop
		 */

		//DESCRIPTION:https://moodle.thm.de/course/view.php?id=4221
		//LOCATION:ONLINE
		//ORGANIZER:MAILTO:james.antrim@nm.thm.de
		//PRIORITY:5
		//SEQUENCE:0
		//SUMMARY:Leit- und Sicherungstechnik - VRL gehalten von Riesbeck\, T..

		// @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.2.7
		$ics[] = "TRANSP:OPAQUE";

		$ics[] = "END:VEVENT";
	}

	/**
	 * Chunks the lines into segments no greater than 75 Bytes for standards conformity.
	 *
	 * @param   string  $output  the output string to be chunked
	 *
	 * @return string
	 */
	private function chunk(string $output): string
	{
		// chunk it
		// add "\r\n "
		// implode it

		return 'CHUNKED OUTPUT';
	}

	/**
	 * Method to generate output. Overwriting functions should place class specific code before the parent call.
	 *
	 * @return void
	 */
	public function display()
	{
		$ics   = [];
		$ics[] = "BEGIN:VCALENDAR";
		$ics[] = "PRODID:-//TH Mittelhessen//NONSGML Organizer $this->version//$this->language";
		$ics[] = 'VERSION:' . $this->version;
		$ics[] = "METHOD:PUBLISH";

		//XPROPS
		//TIMEZONE
		echo "<pre>" . print_r(reset($this->instances), true) . "</pre><br>";

		foreach ($this->instances as $instance)
		{
			$this->addEvent($ics, $instance);
		}

		$ics[] = "END:VCALENDAR";
		echo "<pre>" . print_r($ics, true) . "</pre><br>";
		die;
		//$this->Output($this->filename, $destination);
		//ob_flush();
	}

	/**
	 * Formats a given date time string (Y-m-d H:i) to a timezone qualified DATE-TIME.
	 *
	 * @param   string  $dateTime
	 *
	 * @return string
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.3.5
	 */
	private function getDateTime(string $dateTime = '')
	{
		$stamp = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dateTime) === false ? time() : strtotime($dateTime);
		$value = date('Ymd', $stamp) . 'T' . date('His', $stamp);

		return ";$this->tzID:$value";
	}

	/**
	 * This property specifies the date and time that the information associated with the calendar component was last
	 * revised in the calendar store.
	 *
	 * @param   object  $instance  the instance
	 *
	 * @return string the last modified property
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.7.3
	 */
	private function getLastModified(object $instance): string
	{
		$lastModified = $instance->instanceStatusDate;
		$lastModified = $lastModified > $instance->unitStatusDate ? $lastModified : $instance->unitStatusDate;

		if (!empty($instance->resources))
		{
			foreach ($instance->resources as $person)
			{
				$lastModified = $lastModified > $person['statusDate'] ? $lastModified : $person['statusDate'];

				if (!empty($person['groups']))
				{
					foreach ($person['groups'] as $group)
					{
						$lastModified = $lastModified > $group['statusDate'] ? $lastModified : $group['statusDate'];
					}
				}

				if (!empty($person['rooms']))
				{
					foreach ($person['rooms'] as $room)
					{
						$lastModified = $lastModified > $room['statusDate'] ? $lastModified : $room['statusDate'];
					}
				}
			}
		}

		$stamp = strtotime($lastModified);
		$date  = gmdate('Ymd', $stamp);
		$time  = gmdate('His', $stamp);

		return "LAST-MODIFIED:{$date}T{$time}Z";
	}

	/**
	 * Sets the stamp (UTC generation time) to be used for all components.
	 *
	 * @return void
	 */
	private function setStamp()
	{
		$date = gmdate('Ymd');
		$time = gmdate('His');

		$this->stamp = "DTSTAMP:{$date}T{$time}Z";
	}

	/**
	 * Sets the class $version property from the component manifest.
	 *
	 * @return void
	 */
	private function setVersion()
	{
		$manifest = JPATH_ADMINISTRATOR . '/components/com_organizer/com_organizer.xml';

		try
		{
			$manifest      = new SimpleXMLElement(file_get_contents($manifest));
			$this->version = (string) $manifest->version;
		}
		catch (Exception $exception)
		{
			$this->version = "X.X.X";
		}
	}

	private function setUID()
	{
		$this->uniqueID = "UID:THEUNIQUEID";
	}
}
