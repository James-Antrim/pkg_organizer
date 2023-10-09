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
 * Class contains functions useful in debugging.
 */
class Debug
{
    /**
     * Tears down a UTF-8 string into it's bytes and displays them as numeric values and displayed characters. Useful
     * for determining problems with LSF entries which are never translated into valid HTML.
     *
     * @param string $string the string to be examined
     *
     * @return void
     * @noinspection PhpUnused
     */
    public static function examineString(string $string)
    {
        echo "<pre>string: " . print_r($string, true) . "</pre><br>";
        $bytes = unpack('C*', $string);
        foreach ($bytes as $byte) {
            echo "---------------------<br>";
            echo "<pre>$byte</pre><br>";
            $char = chr($byte);
            echo "<pre>character: " . print_r($char, true) . "</pre><br>";

            echo "<br>";
        }
    }
}