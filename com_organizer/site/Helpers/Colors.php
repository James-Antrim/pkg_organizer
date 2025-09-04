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

use THM\Organizer\Adapters\Input;
use THM\Organizer\Tables;

/**
 * Class provides generalized functions regarding dates and times.
 */
class Colors extends ResourceHelper
{
    /**
     * Returns the color value for a given colorID.
     *
     * @param   int  $colorID  the id of the color
     *
     * @return string
     */
    public static function color(int $colorID): string
    {
        $table = new Tables\Colors();

        return $table->load($colorID) ? $table->color : '';
    }

    /**
     * Creates a container with the given text and background color. Text color is calculated dynamically for contrast.
     *
     * @param   string  $text     the text to display
     * @param   int     $colorID  the id of the background color
     *
     * @return string
     */
    public static function swatch(string $text, int $colorID): string
    {
        $styles = ['text-align:center;'];
        if (!empty($colorID)) {
            $bgColor   = self::color($colorID);
            $styles[]  = "background-color:$bgColor;";
            $textColor = self::textColor($bgColor);
            $styles[]  = "color:$textColor;";
        }

        return '<div style="' . implode($styles) . '">' . $text . '</div>';
    }

    /**
     * Gets an appropriate value for contrasting text color for a given background color.
     *
     * @param   string  $bgColor  the background color with which do
     *
     * @return string
     */
    public static function textColor(string $bgColor): string
    {
        $color = substr($bgColor, 1);
        $red   = hexdec(substr($color, 0, 2));
        $green = hexdec(substr($color, 2, 2));
        $blue  = hexdec(substr($color, 4, 2));

        $params             = Input::parameters();
        $relativeBrightness = ($red * 299) + ($green * 587) + ($blue * 114);
        $brightness         = $relativeBrightness / 1000;
        if ($brightness >= 128) {
            return $params->get('darkTextColor', '#4a5c66');
        }
        else {
            return $params->get('lightTextColor', '#ffffff');
        }
    }
}
