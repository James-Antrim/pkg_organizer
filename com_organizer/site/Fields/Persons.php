<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use THM\Organizer\Helpers\Persons as Helper;

/**
 * Class creates a form field for room type selection
 */
class Persons extends Options
{
    use Dependent;

    /* @inheritDoc */
    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $persons = Helper::options();

        return array_merge($options, $persons);
    }
}
