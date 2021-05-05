<?php
/**
 * @category    Joomla component
 * @package     THM_Organizer
 * @subpackage  com_thm_organizer.<subdirectory>
 * @name        <classname>
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Organizer\Helpers\Input;

trait SuperOrdinate
{
	/**
	 * Builds the resource's curriculum using the subordinate resources contained in the form.
	 *
	 * @return array  an array containing the resource's subordinate resources
	 */
	private function getSubOrdinates()
	{
		$index        = 1;
		$subOrdinates = [];

		while (Input::getInt("sub{$index}Order"))
		{
			$ordering      = Input::getInt("sub{$index}Order");
			$aggregateInfo = Input::getCMD("sub{$index}");

			if (!empty($aggregateInfo))
			{
				$resourceID   = substr($aggregateInfo, 0, strlen($aggregateInfo) - 1);
				$resourceType = strpos($aggregateInfo, 'p') ? 'pool' : 'subject';

				if ($resourceType == 'subject')
				{
					$subOrdinates[$ordering]['poolID']    = null;
					$subOrdinates[$ordering]['subjectID'] = $resourceID;
					$subOrdinates[$ordering]['ordering']  = $ordering;
				}

				if ($resourceType == 'pool')
				{
					$subOrdinates[$ordering]['poolID']     = $resourceID;
					$subOrdinates[$ordering]['subjectID']  = null;
					$subOrdinates[$ordering]['ordering']   = $ordering;
					$subOrdinates[$ordering]['curriculum'] = $this->getExistingCurriculum($resourceID);
				}
			}

			$index++;
		}

		return $subOrdinates;
	}
}