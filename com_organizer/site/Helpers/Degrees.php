<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;


/**
 * Class provides basic methods to retrieve degree attributes.
 */
class Degrees extends ResourceHelper
{
    /**
     * Gets the academic level of the degree. (Bachelor|Master)
     *
     * @param int $degreeID the id of the degree
     *
     * @return string
     */
    public static function getLevel(int $degreeID): string
    {
        $code = self::getCode($degreeID);

        return strpos($code, 'M') === 0 ? 'Master' : 'Bachelor';
    }
}