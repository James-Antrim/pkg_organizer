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

use THM\Organizer\Helpers;

/**
 * Class creates a form field for room type selection
 */
class Persons extends Options
{
    use Dependent;

    /**
     * @var  string
     */
    protected $type = 'Persons';

    /**
     * Method to get the field options.
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $persons = Helpers\Persons::getOptions();

        return array_merge($options, $persons);
    }
}