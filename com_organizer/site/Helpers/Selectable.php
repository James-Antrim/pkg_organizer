<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

/**
 * Ensures that helpers that reference selectable items offer the getOptions function.
 */
interface Selectable
{
	/**
	 * Retrieves the selectable options for the resource.
	 *
	 * @return array the available options
	 */
	public static function getOptions();

	/**
	 * Retrieves resource items.
	 *
	 * @return array the available resources
	 */
	public static function getResources();
}
