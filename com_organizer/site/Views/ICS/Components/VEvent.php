<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\ICS\Components;

/**
 * Provide a grouping of component properties that describe an event.
 *
 * A "VEVENT" calendar component is a grouping of component properties, possibly including "VALARM" calendar components,
 * that represents a scheduled amount of time on a calendar. For example, it can be an activity; such as a one-hour
 * long, department meeting from 8:00 AM to 9:00 AM, tomorrow. Generally, an event will take up time on an individual
 * calendar. Hence, the event will appear as an opaque interval in a search for busy time.
 *
 * The "VEVENT" is also the calendar component used to specify an anniversary or daily reminder within a calendar. These
 * events have a DATE value type for the "DTSTART" property instead of the default value type of DATE-TIME. If such a
 * "VEVENT" has a "DTEND" property, it MUST be specified as a DATE value also. The anniversary type of "VEVENT" can span
 * more than one date (i.e., "DTEND" property value is set to a calendar date after the "DTSTART" property value). If
 * such a "VEVENT" has a "DURATION" property, it MUST be specified as a "dur-day" or "dur-week" value.
 *
 * The "DTSTART" property for a "VEVENT" specifies the inclusive start of the event. For recurring events, it also
 * specifies the very first instance in the recurrence set. The "DTEND" property for a "VEVENT" calendar component
 * specifies the non-inclusive end of the event. For cases where a "VEVENT" calendar component specifies a "DTSTART"
 * property with a DATE value type but no "DTEND" nor "DURATION" property, the event's duration is taken to be one day.
 * For cases where a "VEVENT" calendar component specifies a "DTSTART" property with a DATE-TIME value type but no
 * "DTEND" property, the event ends on the same calendar date and time of day specified by the "DTSTART" property.
 *
 * The "VEVENT" calendar component cannot be nested within another calendar component. However, "VEVENT" calendar
 * components can be related to each other or to a "VTODO" or to a "VJOURNAL" calendar component with the "RELATED-TO"
 * property.
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.6.1
 */
class VEvent extends VComponent
{
	/**
	 * The following are REQUIRED, but MUST NOT occur more than once.
	 *
	 * @var string
	 */
	private $dtStamp = 'THEDTSTAMP';
	private $uid = 'SOMEUNIQUEID';

	/**
	 * The following is REQUIRED if the component appears in an iCalendar (Instances View) object that doesn't specify
	 * the "METHOD" property; otherwise, it is OPTIONAL; in any case, it MUST NOT occur more than once.
	 */

	/**
	 * @var string
	 */
	private $dtStart = 'THEDTSTART';

	/* The following are OPTIONAL, but MUST NOT occur more than once. *************************************************/

	private $class;
	private $created;
	private $description;
	private $geo;
	private $lastModified;// last-mod
	private $location;
	private $organizer;
	private $priority;
	private $sequence; //seq
	private $status;
	private $summary;

	/**
	 * Whether or not the VEvent is blocking: OPAQUE = blocking, TRANSPARENT = non-blocking
	 * @var string
	 */
	private $transparency = 'OPAQUE';
	private $url;
	private $recurid; // ?

	/* The following are OPTIONAL, and MAY occur more than once. ******************************************************/
	private $attach;
	private $attendee;
	private $categories;
	private $comment;
	private $contact;
	private $exdate;
	private $rstatus;
	private $related;
	private $resources;
	private $rdate;
	private $xProps; // x-prop
	private $ianaProps; // iana-prop

	/* The following is OPTIONAL, but SHOULD NOT occur more than once. ************************************************/
	private $rRule;

	/**
	 * Either 'dtend' or 'duration' MAY appear in a 'eventprop', but 'dtend' and 'duration' MUST NOT occur in the same
	 * 'eventprop'.
	 */
	private $dtEnd;
	private $duration;

	public function __construct(object $instance)
	{
		//echo "<pre>" . print_r($instance, true) . "</pre><br>";
		//die;
	}

	/**
	 * @inheritDoc
	 */
	public function fill(array &$ics)
	{
		$ics[] = "BEGIN:VEVENT";
		//UID:20210621T165049CEST-9826UwJDEe@Stundenplan_bai-4_20210621
		//DTSTAMP:20210621T145049Z
		//DESCRIPTION:https://moodle.thm.de/course/view.php?id=4221
		//DTSTART:20210621T080000
		//DTEND:20210621T092900
		//LOCATION:ONLINE
		//ORGANIZER:MAILTO:james.antrim@nm.thm.de
		//PRIORITY:5
		//SEQUENCE:0
		//SUMMARY:Leit- und Sicherungstechnik - VRL gehalten von Riesbeck\, T..
		$ics[] = "TRANSP:$this->transparency";
		$ics[] = "END:VEVENT";
	}
}