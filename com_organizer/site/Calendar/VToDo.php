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
 * Provide a grouping of calendar properties that describe a to-do.
 *
 * Description:
 *
 * A "VTODO" calendar component is a grouping of component properties and possibly "VALARM" calendar components that
 * represent an action-item or assignment. For example, it can be used to represent an item of work assigned to an
 * individual; such as "turn in travel expense today".
 *
 * The "VTODO" calendar component cannot be nested within another calendar component.  However, "VTODO" calendar
 * components can be related to each other or to a "VEVENT" or to a "VJOURNAL" calendar component with the "RELATED-TO"
 * property.
 *
 * A "VTODO" calendar component without the "DTSTART" and "DUE" (or "DURATION") properties specifies a to-do that will
 * be associated with each successive calendar date, until it is completed.
 *
 * Format Definition:
 *
 * todoc = "BEGIN" ":" "VTODO" CRLF
 *         todoprop *alarmc
 *         "END" ":" "VTODO" CRLF
 *
 * todoprop = *(
 *   dtstamp / uid - required, can only once
 *   class / completed / created / description / dtstart / geo / last-mod / location / organizer / percent / priority
 *   / recurid / seq / status / summary / url - optional, can only once
 *   rrule - optional, should only once
 *   due / duration - optional?, can only once?, mutually exclusive, duration requires dtstart
 *   attendee / categories / comment / contact / exdate / rdate / related / resources / rstatus - optional, may more than once
 * )
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.6.2
 */
class VToDo extends VComponent
{
	/**
	 * @inheritDoc
	 */
	public function getProps(&$output)
	{
		$this->getAttachments($output);
		$this->getIANAProps($output);
		$this->getXProps($output);
	}
}