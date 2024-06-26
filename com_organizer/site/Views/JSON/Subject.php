<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\JSON;

use THM\Organizer\Models\Subject as Model;

/**
 * Class loads the subject into the display context.
 */
class Subject extends BaseView
{
    /**
     * loads model data into view context
     * @return void
     */
    public function display(): void
    {
        $model = new Model();
        echo json_encode($model->getItem(), JSON_UNESCAPED_UNICODE);
    }
}
