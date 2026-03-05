<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers\Validators;

use THM\Organizer\Helpers\Methods as Helper;
use stdClass;

/**
 * Provides functions for XML description validation and modeling.
 */
class Methods
{
    /**
     * Sets the available methods for later validation.
     *
     * @param stdClass $methods
     * @return void
     */
    public static function methods(stdClass $methods): void
    {
        foreach (Helper::resources() as $method) {
            $methods->{$method['code']} = $method['id'];
        }
    }
}
