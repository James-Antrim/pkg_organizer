<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Calendar;

/**
 * Purpose:
 *
 * Provide a grouping of component properties that describe an event.
 *
 * Description:
 *
 * A "VEVENT" calendar component is a grouping of component properties, possibly including "VALARM"
 * calendar components, that represents a scheduled amount of time on a calendar.  For example, it can be an activity;
 * such as a one-hour long, department meeting from 8:00 AM to 9:00 AM, tomorrow. Generally, an event will take up time
 * on an individual calendar. Hence, the event will appear as an opaque interval in a search for busy time. Alternately,
 * the event can have its Time Transparency set to "TRANSPARENT" in order to prevent blocking of the event in searches
 * for busy time.
 *
 * The "VEVENT" is also the calendar component used to specify an anniversary or daily reminder within a calendar. These
 * events have a DATE value type for the "DTSTART" property instead of the default value type of DATE-TIME. If such a
 * "VEVENT" has a "DTEND" property, it MUST be specified as a DATE value also. The anniversary type of "VEVENT" can span
 * more than one date (i.e., "DTEND" property value is set to a calendar date after the "DTSTART" property value). If
 * such a "VEVENT" has a "DURATION" property, it MUST be specified as a "dur-day" or "dur-week" value.
 *
 * The "DTSTART" property for a "VEVENT" specifies the inclusive start of the event. For recurring events, it also
 * specifies the very first instance in the recurrence set.  The "DTEND" property for a "VEVENT" calendar component
 * specifies the non-inclusive end of the event.  For cases where a "VEVENT" calendar component specifies a "DTSTART"
 * property with a DATE value type but no "DTEND" nor "DURATION" property, the event's duration is taken to be one day.
 * For cases where a "VEVENT" calendar component specifies a "DTSTART" property with a DATE-TIME value type but no
 * "DTEND" property, the event ends on the same calendar date and time of day specified by the "DTSTART" property.
 *
 * The "VEVENT" calendar component cannot be nested within another calendar component.  However, "VEVENT" calendar
 * components can be related to each other or to a "VTODO" or to a "VJOURNAL" calendar component with the "RELATED-TO"
 * property.
 *
 * Format Definition:
 *
 * eventc = "BEGIN" ":" "VEVENT" CRLF
 *          eventprop *alarmc
 *          "END" ":" "VEVENT" CRLF
 *
 * eventprop  = *(
 *   dtstamp / uid - required, can only once
 *   dtstart - if vcalendar w/o method required else optional, can only once
 *   class / created / description / geo / last-mod / location / organizer / priority / seq / status / summary / transp
 *   / url / recurid - optional, can only once
 *   rrule - optional, should only once
 *   dtend / duration - optional?, can only once?, mutually exclusive
 *   attach / attendee / categories / comment / contact / exdate / iana-prop✓ / rdate / related / resources / rstatus
 *   / x-prop✓ - optional, may more than once
 * )
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.6.1
 */
class VEvent extends VComponent
{
	/**
	 * @inheritDoc
	 */
	public function getProps(&$output)
	{
		$this->getIANAProps($output);
		$this->getXProps($output);
	}
}