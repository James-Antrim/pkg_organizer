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

use THM\Organizer\Adapters\Input;

/**
 * @inheritDoc
 */
class Run extends FormController
{
    protected string $list = 'Runs';

    /**
     * @inheritDoc
     */
    protected function prepareData(): array
    {
        $data    = Input::getFormItems();
        $endDate = '';

        foreach ($data['run'] as $section) {
            $endDate = max($endDate, $section['endDate']);
        }

        $data['endDate'] = $endDate;
        $data['run']     = json_encode(['runs' => $data['run']], JSON_UNESCAPED_UNICODE);

        return $data;
    }

}
