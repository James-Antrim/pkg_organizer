<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\XLS;

/**
 * Class creates a XLS file for the display of the filtered schedule information.
 */
class Workload extends BaseView
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();

        $this->model->setUp();
    }
}
