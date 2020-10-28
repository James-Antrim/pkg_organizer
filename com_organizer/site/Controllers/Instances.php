<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Controllers;

use Exception;
use Organizer\Controller;
use Organizer\Helpers;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Instances extends Controller
{
	protected $listView = 'instances';

	protected $resource = 'instance';

	/**
	 * Opens the course participants view for the selected course.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function participants()
	{
		// Reliance on POST requires a different method of redirection
		Helpers\Input::set('id', Helpers\Input::getSelectedIDs()[0]);
		Helpers\Input::set('view', 'instance_participants');
		parent::display();
	}
}
