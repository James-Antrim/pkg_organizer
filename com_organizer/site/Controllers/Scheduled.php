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

use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers\Schedulable;

/**
 * Standardizes maintenance of associations entries across resources.
 */
trait Scheduled
{
    /**
     * Authorization check multiple curriculum resources. Individual resource authorization is later checked as appropriate.
     * @return void
     */
    protected function authorize(): void
    {
        /** @var Schedulable $helper */
        $helper = "THM\\Organizer\\Helpers\\" . $this->list;
        $id     = Input::getID();

        if ($id ? !$helper::schedulable($id) : !$helper::schedulableIDs()) {
            Application::error(403);
        }
    }
}
