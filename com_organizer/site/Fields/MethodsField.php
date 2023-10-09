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

use Organizer\Helpers;
use stdClass;

/**
 * Class creates a form field for building selection.
 */
class MethodsField extends OptionsField
{
    /**
     * @var  string
     */
    protected $type = 'Methods';

    /**
     * Returns a select box where stored buildings can be chosen
     * @return stdClass[]  the available buildings
     */
    protected function getOptions(): array
    {
        $defaultOptions = parent::getOptions();
        $options        = Helpers\Methods::getOptions();

        return array_merge($defaultOptions, $options);
    }
}
