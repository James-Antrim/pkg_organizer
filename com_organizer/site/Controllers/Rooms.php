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
class Rooms extends Controller
{
	use Activated;

	protected $listView = 'rooms';

	protected $resource = 'room';

	/**
	 * Creates an xls file based on form data.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function uniNow()
	{
		Helpers\Input::set('layout', 'UniNow');
		Helpers\Input::set('format', 'xls');
		$this->display();
	}

    public function FMExport(){
        Helpers\Input::set('layout', 'FMExport');
        Helpers\Input::set('format', 'xls');
        $this->display();
    }

	/**
	 * Creates an xls file based on form data.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function xls()
	{
		Helpers\Input::set('format', 'xls');
		$this->display();
	}
}
