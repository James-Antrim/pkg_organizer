<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use Organizer\Tables;

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
	 * @return string the hex value of the color
	 */
	public static function getColor(int $colorID): string
	{
		$table = new Tables\Colors();

		return $table->load($colorID) ? $table->color : '';
	}

	/**
	 * Creates a container to output text with a system specific color.
	 *
	 * @param   string  $text     the text to display
	 * @param   int     $colorID  the id of the color
	 *
	 * @return string
	 */
	public static function getListDisplay(string $text, int $colorID): string
	{
		$styles = ['text-align:center;'];
		if (!empty($colorID))
		{
			$bgColor   = self::getColor($colorID);
			$styles[]  = "background-color:$bgColor;";
			$textColor = self::getDynamicTextColor($bgColor);
			$styles[]  = "color:$textColor;";
		}

		return '<div style="' . implode($styles) . '">' . $text . '</div>';
	}

	/**
	 * Gets an appropriate value for contrasting text color for a given background color.
	 *
	 * @param   string  $bgColor  the background color with which do
	 *
	 * @return string  the hexadecimal value for an appropriate text color
	 */
	public static function getDynamicTextColor(string $bgColor): string
	{
		$color              = substr($bgColor, 1);
		$params             = Input::getParams();
		$red                = hexdec(substr($color, 0, 2));
		$green              = hexdec(substr($color, 2, 2));
		$blue               = hexdec(substr($color, 4, 2));
		$relativeBrightness = ($red * 299) + ($green * 587) + ($blue * 114);
		$brightness         = $relativeBrightness / 1000;
		if ($brightness >= 128)
		{
			return $params->get('darkTextColor', '#4a5c66');
		}
		else
		{
			return $params->get('lightTextColor', '#ffffff');
		}
	}
}
