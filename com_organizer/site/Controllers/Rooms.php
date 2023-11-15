<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use Exception;
use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Models\Room;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Rooms extends ListController
{
    use Activated;

    protected string $item = 'Room';

    /**
     * Makes call to the model's import function, and redirects to the manager view if the file .
     * @return void
     */
    public function import(): void
    {
        $url  = Helpers\Routing::getRedirectBase();
        $view = 'Rooms';

        if (JDEBUG) {
            Application::message('ORGANIZER_DEBUG_ON', Application::ERROR);
            $url .= "&view=$view";
            $this->setRedirect($url);

            return;
        }

        $form  = $this->input->files->get('jform', [], '[]');
        $file  = $form['file'];
        $types = ['application/vnd.ms-excel', 'text/csv'];

        if (!empty($file['type']) and in_array($file['type'], $types)) {
            if (mb_detect_encoding($file['tmp_name'], 'UTF-8', true) === 'UTF-8') {
                $model = new Room();
                $view  = $model->import() ? 'Rooms' : 'RoomsImport';
            }
            else {
                $view = 'RoomsImport';
                Application::message('ORGANIZER_FILE_ENCODING_INVALID', Application::ERROR);
            }
        }
        else {
            $view = 'RoomsImport';
            Application::message('ORGANIZER_FILE_TYPE_NOT_ALLOWED', Application::ERROR);
        }

        $url .= "&view=$view";
        $this->setRedirect($url);
    }

    /**
     * Creates an UniNow xls file based on form data.
     * @return void
     * @throws Exception
     */
    public function uniNow(): void
    {
        Input::set('layout', 'UniNow');
        Input::set('format', 'xls');
        $this->display();
    }

    /**
     * Creates a xls file based on form data.
     * @return void
     * @throws Exception
     */
    public function xls(): void
    {
        Input::set('format', 'xls');
        $this->display();
    }
}
