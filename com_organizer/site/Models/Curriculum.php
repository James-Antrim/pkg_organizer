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
use Organizer\Helpers\Input;
use Organizer\Helpers\Languages;
use Organizer\Helpers\Mappings;
use Organizer\Helpers\Pools;
use Organizer\Helpers\Programs;

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
			throw new Exception(Languages::_('ORGANIZER_401'), 401);
		}

		$curriculum = [];
		if ($poolID = Input::getFilterID('pool'))
		{
			$ranges             = Pools::getRanges($poolID);
			$curriculum['name'] = Pools::getName($poolID);
			$curriculum['type'] = 'pool';
			$curriculum         += array_pop($ranges);
			Pools::getCurriculum($curriculum);
		}
		elseif ($programID = Input::getFilterID('program'))
		{
			$ranges             = Programs::getRanges($programID);
			$curriculum['name'] = Programs::getName($programID);
			$curriculum['type'] = 'program';
			$curriculum         += array_pop($ranges);
			Programs::getCurriculum($curriculum);
		}

		return $curriculum;
	}
}
