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
 * Class models experimental calendar components.
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.6
 */
class IComponent extends VComponent
{
	use Tokenized;

	/**
	 * XComponent constructor.
	 *
	 * @param   string  $token
	 */
	public function __construct(string $token)
	{
		$this->token = strtoupper($token);
	}

	/**
	 * @inheritDoc
	 */
	public function getProps(array &$output)
	{
		$output[] = "BEGIN:$this->token";
		$this->getIANAProps($output);
		$output[] = "END:$this->token";
	}
}