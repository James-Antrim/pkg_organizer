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
	 * Prints badges for the selected participants.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function gridA3()
	{
		Helpers\Input::set('format', 'pdf');
		Helpers\Input::set('layout', 'GridA3');
		parent::display();
	}

	/**
	 * Prints badges for the selected participants.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function gridA4()
	{
		Helpers\Input::set('format', 'pdf');
		Helpers\Input::set('layout', 'GridA4');
		parent::display();
	}
}
