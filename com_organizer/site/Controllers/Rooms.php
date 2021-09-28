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
use Organizer\Helpers\OrganizerHelper;
use Organizer\Models\Room;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Rooms extends Controller
{
	use Activated;

	protected $listView = 'rooms';

	protected $resource = 'room';

	/**
	 * Makes call to the model's import function, and redirects to the manager view if the file .
	 *
	 * @return void
	 */
	public function import()
	{
		$url  = Helpers\Routing::getRedirectBase();
		$view = 'Rooms';

		if (JDEBUG)
		{
			OrganizerHelper::message('ORGANIZER_DEBUG_ON', 'error');
			$url .= "&view=$view";
			$this->setRedirect($url);

			return;
		}

		$form  = $this->input->files->get('jform', [], '[]');
		$file  = $form['file'];
		$types = ['application/vnd.ms-excel', 'text/csv'];

		if (!empty($file['type']) and in_array($file['type'], $types))
		{
			if (mb_detect_encoding($file['tmp_name'], 'UTF-8', true) === 'UTF-8')
			{
				$model = new Room();
				$view  = $model->import() ? 'Rooms' : 'RoomsImport';
			}
			else
			{
				$view = 'RoomsImport';
				Helpers\OrganizerHelper::message('ORGANIZER_FILE_ENCODING_INVALID', 'error');
			}
		}
		else
		{
			$view = 'RoomsImport';
			Helpers\OrganizerHelper::message('ORGANIZER_FILE_TYPE_NOT_ALLOWED', 'error');
		}

		$url .= "&view=$view";
		$this->setRedirect($url);
	}

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
