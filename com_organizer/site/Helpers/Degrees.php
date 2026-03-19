<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

/**
 * Class provides basic methods to retrieve degree attributes.
 */
class Degrees extends ResourceHelper
{
    use Statistical;

    public const BACHELOR = 84, CERTIFICATE = 94, MASTER = 90, NO_DEGREE = 97, TEST = 17;

    public const STATISTIC_CODES = [
        self::BACHELOR    => 'BACHELOR_DEGREE',
        self::CERTIFICATE => 'CERTIFICATE',
        self::MASTER      => 'MASTER_DEGREE',
        self::NO_DEGREE   => 'NO_DEGREE',
        self::TEST        => 'TEST'
    ];

    /**
     * Gets the academic level of the degree. (Bachelor|Master)
     *
     * @param int $degreeID the id of the degree
     *
     * @return string
     */
    public static function level(int $degreeID): string
    {
        $code = self::getCode($degreeID);

        return str_starts_with($code, 'M') ? 'Master' : 'Bachelor';
    }
}