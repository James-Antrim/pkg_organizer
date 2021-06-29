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
 * Adds a token property used to name IANA / experimintal components.
 */
trait Tokenized
{
	/**
	 * The name of the dynamic component.
	 *
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.6
	 * @var string
	 */
	private $token;
}