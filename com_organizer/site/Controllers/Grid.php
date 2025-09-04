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

use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers\Grids as Helper;

/** @inheritDoc */
class Grid extends FormController
{
    protected string $list = 'Grids';

    /** @inheritDoc */
    protected function prepareData(): array
    {
        $data = Input::post();

        // Re-key the field row names away
        $periods      = empty($data['grid']) ? [] : array_values($data['grid']);
        $grid         = ['periods' => $periods, 'startDay' => $data['startDay'], 'endDay' => $data['endDay']];
        $data['grid'] = json_encode($grid, JSON_UNESCAPED_UNICODE);

        if ($data['isDefault'] and !Helper::resetDefault()) {
            Application::message('TABLE_DEFAULT_NOT_RESET', Application::ERROR);
            $data['isDefault'] = 0;
        }

        return $data;
    }
}