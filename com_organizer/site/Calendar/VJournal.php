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
 * Provide a grouping of component properties that describe a journal entry.
 *
 * Description:
 *
 * A "VJOURNAL" calendar component is a grouping of component properties that represent one or more descriptive text
 * notes associated with a particular calendar date.  The "DTSTART" property is used to specify the calendar date with
 * which the journal entry is associated.  Generally, it will have a DATE value data type, but it can also be used to
 * specify a DATE-TIME value data type.  Examples of a journal entry include a daily record of a legislative body or a
 * journal entry of individual telephone contacts for the day or an ordered list of accomplishments for the day. The
 * "VJOURNAL" calendar component can also be used to associate a document with a calendar date.
 *
 * The "VJOURNAL" calendar component does not take up time on a calendar.  Hence, it does not play a role in free or
 * busy time searches -- it is as though it has a time transparency value of TRANSPARENT.  It is transparent to any such
 * searches.
 *
 * The "VJOURNAL" calendar component cannot be nested within another calendar component.  However, "VJOURNAL" calendar
 * components can be related to each other or to a "VEVENT" or to a "VTODO" calendar component, with the "RELATED-TO"
 * property.
 *
 * Format Definition:
 *
 * journalc = "BEGIN" ":" "VJOURNAL" CRLF
 *            jourprop
 *            "END" ":" "VJOURNAL" CRLF
 *
 * jourprop   = *(
 *   dtstamp / uid - required, can only once
 *   class / created / dtstart / last-mod / organizer / recurid / seq / status / summary / url - optional, can only once
 *   rrule - optional, should only once
 *   attach / attendee / categories / comment / contact / description / exdate / iana-prop✓ / rdate / related / rstatus
 *   / x-prop✓ - optional, may more than once
 * )
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.6.3
 */
class VJournal extends VComponent
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