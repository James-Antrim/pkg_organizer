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

use Joomla\CMS\Language\Text;
use THM\Organizer\Adapters\Text as Next;

/**
 * Provides general functions for language data retrieval and display.
 */
class Languages extends Text
{
    /**
     * Converts a double colon separated string or 2 separate strings to a string ready for bootstrap tooltips
     *
     * @param string $title   The title of the tooltip (or combined '::' separated string).
     * @param string $content The content to tooltip.
     * @param bool   $escape  If true will pass texts through htmlspecialchars.
     *
     * @return  string  The tooltip string
     */
    public static function tooltip(string $title = '', string $content = '', bool $escape = true): string
    {
        // Initialise return value.
        $result = '';

        // Don't process empty strings
        if ($content !== '' or $title !== '') {
            $title   = Next::_($title);
            $content = Next::_($content);

            if ($title === '') {
                $result = $content;
            } elseif ($title === $content) {
                $result = '<strong>' . $title . '</strong>';
            } elseif ($content !== '') {
                $result = '<strong>' . $title . '</strong><br />' . $content;
            } else {
                $result = $title;
            }

            // Escape everything, if required.
            if ($escape) {
                $result = htmlspecialchars($result);
            }
        }

        return $result;
    }
}
