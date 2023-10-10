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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;

/**
 * Provides general functions for language data retrieval and display.
 */
class Languages extends Text
{
    /**
     * @inheritDoc
     * @noinspection PhpMethodNamingConventionInspection
     */
    public static function _($string, $jsSafe = false, $interpretBackSlashes = true, $script = false): string
    {
        if (is_array($jsSafe)) {
            if (array_key_exists('interpretBackSlashes', $jsSafe)) {
                $interpretBackSlashes = (bool) $jsSafe['interpretBackSlashes'];
            }

            if (array_key_exists('script', $jsSafe)) {
                $script = (bool) $jsSafe['script'];
            }

            $jsSafe = !empty($jsSafe['jsSafe']);
        }

        $language = self::getLanguage();

        if ($script) {
            static::$strings[$string] = $language->_($string, $jsSafe, $interpretBackSlashes);

            return $string;
        }

        return $language->_($string, $jsSafe, $interpretBackSlashes);
    }

    /**
     * Converts an array of values into a list string.
     *
     * @param array $array the array to reformat
     * @param bool  $and   whether the last entry should be separated with ampersand
     *
     * @return string the reformatted
     */
    public static function array2string(array $array, bool $and = true): string
    {
        asort($array);

        if ($and) {
            $last = array_pop($array);

            // Did the array originally have more than one item
            return $array ? implode(', ', $array) . ', & ' . $last : $last;
        }

        return implode(', ', $array);
    }

    /**
     * Returns a language instance based on user input.
     * @return Language
     */
    public static function getLanguage(): Language
    {
        $language = Factory::getLanguage();
        $language->load('com_organizer', JPATH_ADMINISTRATOR . '/components/com_organizer');

        return $language;
    }

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
            $title   = self::_($title);
            $content = self::_($content);

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
