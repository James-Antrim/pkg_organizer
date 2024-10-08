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

use SimpleXMLElement;
use THM\Organizer\Controllers\ImportSchedule as Schedule;

/**
 * Ensures that Helpers which validate Schedule XML Export files have standardized functions.
 */
interface UntisXMLValidator
{
    /**
     * Retrieves the resource id using the Untis ID. Creates the resource id if unavailable.
     *
     * @param   Schedule  $controller  the validating schedule model
     * @param   string    $code        the id of the resource in Untis
     *
     * @return void modifies the model, setting the id property of the resource
     */
    public static function setID(Schedule $controller, string $code): void;

    /**
     * Checks whether XML node has the expected structure and required
     * information
     *
     * @param   Schedule          $controller  the model for the schedule being validated
     * @param   SimpleXMLElement  $node        the node being validated
     *
     * @return void
     */
    public static function validate(Schedule $controller, SimpleXMLElement $node): void;
}
