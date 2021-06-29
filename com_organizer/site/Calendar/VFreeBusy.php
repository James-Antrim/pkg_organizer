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
 * Provide a grouping of component properties that describe either a request for free/busy time, describe a response to
 * a request for free/busy time, or describe a published set of busy time.
 *
 * Description:
 *
 * A "VFREEBUSY" calendar component is a grouping of component properties that represents either a request for free or
 * busy time information, a reply to a request for free or busy time information, or a published set of busy time
 * information.
 *
 * When used to request free/busy time information, the "ATTENDEE" property specifies the calendar users whose free/busy
 * time is being requested; the "ORGANIZER" property specifies the calendar user who is requesting the free/busy time;
 * the "DTSTART" and "DTEND" properties specify the window of time for which the free/busy time is being requested; the
 * "UID" and "DTSTAMP" properties are specified to assist in proper sequencing of multiple free/busy time requests.
 *
 * When used to reply to a request for free/busy time, the "ATTENDEE" property specifies the calendar user responding to
 * the free/busy time request; the "ORGANIZER" property specifies the calendar user that originally requested the
 * free/busy time; the "FREEBUSY" property specifies the free/busy time information (if it exists); and the "UID" and
 * "DTSTAMP" properties are specified to assist in proper sequencing of multiple free/busy time replies.
 *
 * When used to publish busy time, the "ORGANIZER" property specifies the calendar user associated with the published
 * busy time; the "DTSTART" and "DTEND" properties specify an inclusive time window that surrounds the busy time
 * information; the "FREEBUSY" property specifies the published busy time information; and the "DTSTAMP" property
 * specifies the DATE-TIME that iCalendar object was created.
 *
 * The "VFREEBUSY" calendar component cannot be nested within another calendar component.  Multiple "VFREEBUSY" calendar
 * components can be specified within an iCalendar object. This permits the grouping of free/busy information into
 * logical collections, such as monthly groups of busy time information.
 *
 * The "VFREEBUSY" calendar component is intended for use in iCalendar object methods involving requests for free time,
 * requests for busy time, requests for both free and busy, and the associated replies.
 *
 * Free/Busy information is represented with the "FREEBUSY" property. This property provides a terse representation of
 * time periods. One or more "FREEBUSY" properties can be specified in the "VFREEBUSY" calendar component.
 *
 * When present in a "VFREEBUSY" calendar component, the "DTSTART" and "DTEND" properties SHOULD be specified prior to
 * any "FREEBUSY" properties.
 *
 * * The recurrence properties ("RRULE", "RDATE", "EXDATE") are not permitted within a "VFREEBUSY" calendar component.
 * Any recurring events are resolved into their individual busy time periods using the "FREEBUSY" property.
 *
 * Format Definition:
 *
 * freebusyc = "BEGIN" ":" "VFREEBUSY" CRLF
 *             fbprop
 *             "END" ":" "VFREEBUSY" CRLF
 *
 * fbprop     = *(
 *   dtstamp / uid - required, can only once
 *   contact / dtstart / dtend / organizer / url - optional, can only once
 *   attendee / comment / freebusy / iana-prop / rstatus / x-prop - optional, may more than once
* )
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.6.4
 */
class VFreeBusy extends VComponent
{

}