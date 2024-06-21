<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use THM\Organizer\Adapters\Application;
use THM\Organizer\Tables\Campuses as Table;

/** @inheritDoc */
class Schedule extends FormController
{
    use Scheduled;

    protected string $list = 'Schedules';

    /** @inheritDoc */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        $this->validate($data, ['name_de', 'name_en']);

        if (!empty($data['parentID'])) {
            /** @var Table $table */
            $table = $this->getTable();

            // Referenced parent doesn't exist or is itself a subordinate campus.
            if (!$table->load($data['parentID']) or !empty($table->parentID)) {
                $data['parentID'] = null;
            }
        }

        return $data;
    }

    /**
     * Uses the model's upload function to validate and save the file to the database should validation be successful.
     * @return void
     */
    public function upload(): void
    {
        $url = Helpers\Routing::getRedirectBase();
        if (JDEBUG) {
            Application::message('DEBUG_ON', Application::ERROR);
            $url .= "&view=schedules";
            $this->setRedirect($url);

            return;
        }

        $form      = $this->input->files->get('jform', [], '[]');
        $file      = $form['file'];
        $url       .= '&view=';
        $validType = (!empty($file['type']) and $file['type'] == 'text/xml');

        if ($validType) {
            if (mb_detect_encoding($file['tmp_name'], 'UTF-8', true) === 'UTF-8') {
                $model = new Models\Schedule();
                $view  = $model->upload() ? 'schedules' : 'importschedule';
            }
            else {
                $view = 'importschedule';
                Application::message('FILE_ENCODING_INVALID', Application::ERROR);
            }
        }
        else {
            $view = 'importschedule';
            Application::message('FILE_TYPE_NOT_ALLOWED', Application::ERROR);
        }

        $this->setRedirect($url . $view);
    }
}