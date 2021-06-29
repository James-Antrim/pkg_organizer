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
 * Provide a grouping of component properties that defines a time zone.
 *
 * Description:
 * A time zone is unambiguously defined by the set of time measurement rules determined by the governing body for a
 * given geographic area.  These rules describe, at a minimum, the base offset from UTC for the time zone, often
 * referred to as the Standard Time offset.  Many locations adjust their Standard Time forward or backward by one hour,
 * in order to accommodate seasonal changes in number of daylight hours, often referred to as Daylight Saving Time. Some
 * locations adjust their time by a fraction of an hour.  Standard Time is also known as Winter Time.  Daylight Saving
 * Time is also known as Advanced Time, Summer Time, or Legal Time in certain countries.
 *
 * Interoperability between two calendaring and scheduling applications, especially for recurring events, to-dos or
 * journal entries, is dependent on the ability to capture and convey date and time information in an unambiguous
 * format.  The specification of current time zone information is integral to this behavior.
 *
 * If present, the "VTIMEZONE" calendar component defines the set of Standard Time and Daylight Saving Time observances
 * (or rules) for a particular time zone for a given interval of time. The "VTIMEZONE" calendar component cannot be
 * nested within other calendar components. Multiple "VTIMEZONE" calendar components can exist in an iCalendar object.
 * In this situation, each "VTIMEZONE" MUST represent a unique time zone definition. This is necessary for some classes
 * of events, such as airline flights, that start in one time zone and end in another.
 *
 * The "VTIMEZONE" calendar component MUST include the "TZID" property and at least one definition of a "STANDARD" or
 * "DAYLIGHT" sub-component. The "STANDARD" or "DAYLIGHT" sub-component MUST include the "DTSTART", "TZOFFSETFROM", and
 * "TZOFFSETTO" properties.
 *
 * An individual "VTIMEZONE" calendar component MUST be specified for each unique "TZID" parameter value specified in
 * the iCalendar object. In addition, a "VTIMEZONE" calendar component, referred to by a recurring calendar component,
 * MUST provide valid time zone information for all recurrence instances.
 *
 * Each "VTIMEZONE" calendar component consists of a collection of one or more sub-components that describe the rule for
 * a particular observance (either a Standard Time or a Daylight Saving Time observance). The "STANDARD" sub-component
 * consists of a collection of properties that describe Standard Time. The "DAYLIGHT" sub-component consists of a
 * collection of properties that describe Daylight Saving Time. In general, this collection of properties consists of:
 *
 * - the first onset DATE-TIME for the observance;
 * - the last onset DATE-TIME for the observance, if a last onset is known;
 * - the offset to be applied for the observance;
 * - a rule that describes the day and time when the observance takes effect;
 * - an optional name for the observance.
 *
 * For a given time zone, there may be multiple unique definitions of the observances over a period of time. Each
 * observance is described using either a "STANDARD" or "DAYLIGHT" sub-component. The collection of these sub-components
 * is used to describe the time zone for a given period of time. The offset to apply at any given time is found by
 * locating the observance that has the last onset date and time before the time in question, and using the offset value
 * from that observance.
 *
 * The top-level properties in a "VTIMEZONE" calendar component are:
 *
 * - The mandatory "TZID" property is a text value that uniquely identifies the "VTIMEZONE" calendar component within
 *   the scope of an iCalendar object.
 * - The optional "LAST-MODIFIED" property is a UTC value that specifies the date and time that this time zone
 *   definition was last updated.
 * - The optional "TZURL" property is a url value that points to a published "VTIMEZONE" definition. "TZURL" SHOULD
 *   refer to a resource that is accessible by anyone who might need to interpret the object. This SHOULD NOT normally
 *   be a "file" URL or other URL that is not widely accessible.
 *
 * Format Definition:
 *
 * timezonec = "BEGIN" ":" "VTIMEZONE" CRLF
 *             *(
 *               tzid - required, can only once
 *               last-mod / tzurl - optional, can only once
 *               SubTimeZone - required, at least once
 *               iana-prop✓ / x-prop✓ - optional, may more than once✓
 *             )
 *             "END" ":" "VTIMEZONE" CRLF
 *
 * @see SubTimeZone
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.6.5
 */
class VTimeZone extends VComponent
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