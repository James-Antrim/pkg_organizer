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

use Organizer\Models\SubjectItem as Model;

/**
 * Class loads the subject into the display context.
 */
class SubjectItem extends BaseView
{
	/**
	 * loads model data into view context
	 *
	 * @return void
	 */
	public function display()
	{
		$model = new Model;
		echo json_encode($model->get('Item'), JSON_UNESCAPED_UNICODE);
	}
}
