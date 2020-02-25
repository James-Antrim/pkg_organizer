<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Exception;
use Organizer\Helpers;

/**
 * Class loads curriculum information into the view context.
 */
class Curriculum extends ItemModel
{
	/**
	 * Provides a strict access check which can be overwritten by extending classes.
	 *
	 * @return bool  true if the user can access the view, otherwise false
	 */
	protected function allowView()
	{
		return true;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return mixed  An array of data items on success, false on failure.
	 * @throws Exception
	 */
	public function getItem()
	{
		$allowView = $this->allowView();
		if (!$allowView)
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_401'), 401);
		}

		$curriculum = [];
		if ($poolID = Helpers\Input::getFilterID('pool'))
		{
			$ranges             = Helpers\Pools::getRanges($poolID);
			$curriculum['name'] = Helpers\Pools::getName($poolID);
			$curriculum['type'] = 'pool';
			$curriculum         += array_pop($ranges);
			Helpers\Pools::getCurriculum($curriculum);
		}
		elseif ($programID = Helpers\Input::getFilterID('program'))
		{
			$ranges             = Helpers\Programs::getRanges($programID);
			$curriculum['name'] = Helpers\Programs::getName($programID);
			$curriculum['type'] = 'program';
			$curriculum         += array_pop($ranges);
			Helpers\Programs::getCurriculum($curriculum);
		}

		return $curriculum;
	}
}
