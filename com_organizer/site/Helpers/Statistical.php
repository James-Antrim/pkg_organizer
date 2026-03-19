<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use THM\Organizer\Adapters\HTML;
use THM\Organizer\Adapters\Text;

/**
 * Ensures that documentable helpers implement functions facilitating documentation access checks.
 */
trait Statistical
{
    /**
     * Creates a list of alphabetized statistic code options.
     *
     * @return array
     */
    public static function statisticOptions(): array
    {
        $options = [];
        foreach (self::STATISTIC_CODES as $value => $key) {
            $key           = Text::_($key);
            $options[$key] = HTML::option($value, $key);
        }
        ksort($options);
        return $options;
    }
}
