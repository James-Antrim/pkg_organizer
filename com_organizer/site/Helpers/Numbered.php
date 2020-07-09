<?php
/**
 * @category    Joomla component
 * @package     THM_Organizer
 * @subpackage  com_organizer.helpers
 * @name        Numbered
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace Organizer\Helpers;


trait Numbered
{
	/**
	 * Gets the resource ids.
	 *
	 * @return array the ids of the resource.
	 */
	public static function getIDs()
	{
		$ids = [];

		foreach (self::getResources() as $resource)
		{
			$ids[] = $resource['id'];
		}

		return $ids;
	}
}