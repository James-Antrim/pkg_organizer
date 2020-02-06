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

use Organizer\Helpers\Groups;

/**
 * Class answers dynamic event group related queries
 */
class GroupOptions extends BaseView
{
	/**
	 * loads model data into view context
	 *
	 * @return void
	 */
	public function display()
	{
		echo json_encode(Groups::getOptions(), JSON_UNESCAPED_UNICODE);
	}
}
