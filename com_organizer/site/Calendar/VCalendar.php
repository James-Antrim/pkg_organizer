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
 * Description:
 *
 * The body of the iCalendar object consists of a sequence of calendar properties and one or more calendar components.
 * The calendar properties are attributes that apply to the calendar object as a whole.  The calendar components are
 * collections of properties that express a particular calendar semantic. For example, the calendar component can
 * specify an event, a to-do, a journal entry, time zone information, free/busy time information, or an alarm.
 *
 * An iCalendar object MUST include the "PRODID" and "VERSION" calendar properties.  In addition, it MUST include at
 * least one calendar component. Special forms of iCalendar objects are possible to publish just busy time (i.e., only
 * a "VFREEBUSY" calendar component) or time zone (i.e., only a "VTIMEZONE" calendar component) information.  In
 * addition, a complex iCalendar object that is used to capture a complete snapshot of the contents of a calendar is
 * possible (e.g., composite of many different calendar components). More commonly, an iCalendar object will consist of
 * just a single "VEVENT", "VTODO", or "VJOURNAL" calendar component.  Applications MUST ignore x-comp and iana-comp
 * values they don't recognize.  Applications that support importing iCalendar objects SHOULD support all of the
 * component types defined in this document, and SHOULD NOT silently drop any components as that can lead to user data
 * loss.
 *
 * Format Definition:
 *
 * icalobject = "BEGIN" ":" "VCALENDAR" CRLF
 *              icalbody
 *              "END" ":" "VCALENDAR" CRLF
 *
 * icalbody = calprops component
 *
 * calprops = *(
 *   prodid / version - required, can only once
 *   calscale / method - optional, can only once
 *   iana-prop / x-prop - optional, may more than once
 * )
 *
 * component = 1*(eventc / todoc / journalc / freebusyc / timezonec / iana-comp / x-comp)
 *
 * iana-comp = "BEGIN" ":" iana-token CRLF
 *              1*contentline
 *              "END" ":" iana-token CRLF
 *
 * x-comp = "BEGIN" ":" x-name CRLF
 *          1*contentline
 *          "END" ":" x-name CRLF
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.4
 */
class VCalendar extends VComponent
{

}