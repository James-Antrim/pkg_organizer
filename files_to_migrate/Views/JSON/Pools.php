<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\JSON;

use Organizer\Helpers\Input;
use Organizer\Helpers\Pools as PoolsHelper;

/**
 * Class answers dynamic subject pool related queries
 */
class Pools extends BaseView
{
	/**
	 * loads model data into view context
	 *
	 * @return void
	 */
	public function display()
	{
		$function = Input::getTask();
		if (method_exists('Organizer\\Helpers\\Pools', $function))
		{
			echo json_encode(PoolsHelper::$function(), JSON_UNESCAPED_UNICODE);
		}
		else
		{
			echo false;
		}
	}
}
