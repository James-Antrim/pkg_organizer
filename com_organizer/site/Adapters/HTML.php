<?php
/**
 * @package     Groups
 * @extension   com_groups
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Adapters;

use Joomla\CMS\HTML\Helpers\{Grid, SearchTools, Select};
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Utilities\ArrayHelper;
use stdClass;

class HTML extends HTMLHelper
{
    /**
     * Method to check all checkboxes in a resource table.
     *
     * @return  string
     * @see Grid::checkall()
     */
    public static function checkAll(): string
    {
        return Grid::checkall();
    }

    /**
     * Method to create a checkbox for a resource table row.
     *
     * @param   int  $rowNumber
     * @param   int  $rowID
     *
     * @return  string
     *
     * @since   1.5
     */
    public static function checkBox(int $rowNumber, int $rowID): string
    {
        return Grid::id($rowNumber, $rowID);
    }

    /**
     * Creates an icon with tooltip as appropriate.
     *
     * @param   string  $class  the icon class(es)
     *
     * @return string
     */
    public static function icon(string $class): string
    {
        return "<i class=\"$class\" aria-hidden=\"true\"></i>";
    }

    /**
     * Method to create a sorting column header for a resource table. Header text key is automatically prefaced and localized.
     *
     * @param   string  $constant       the text to display in the table header
     * @param   string  $column         the query column that this link sorts by
     * @param   string  $direction      the current sort direction for this query column
     * @param   string  $currentColumn  the current query column that the results are being sorted by
     *
     * @return  string
     * @see SearchTools::sort()
     */
    public static function sort(string $constant, string $column, string $direction, string $currentColumn): string
    {
        return SearchTools::sort(Text::_($constant), $column, $direction, $currentColumn);
    }

    /**
     * @inheritdoc
     * Link text key is automatically prefaced and localized.
     */
    public static function link($url, $text, $attribs = null): string
    {
        return parent::link($url, Text::_($text), $attribs);
    }

    /**
     * Create an object that represents an option in an option list.
     *
     * @param   int|string  $value    the option value
     * @param   string      $text     the option text
     * @param   boolean     $disable  whether the option is disabled
     *
     * @return  stdClass
     */
    public static function option(int|string $value, string $text, bool $disable): stdClass
    {
        return Select::option((string) $value, $text, $disable);
    }

    /**
     * Returns an action on a grid
     *
     * @param   integer  $index       the row index
     * @param   array    $state       the state configuration
     * @param   string   $controller  the name of the controller class
     * @param   string   $neither     text for columns which cannot be toggled
     *
     * @return  string
     */
    public static function toggle(int $index, array $state, string $controller = '', string $neither = ''): string
    {
        $ariaID     = "{$state['column']}-$index";
        $attributes = [
            'aria-labelledby' => $ariaID,
            'class'           => "tbody-icon"
        ];

        $class  = $state['class'];
        $return = '';

        if ($neither) {
            $iconClass = 'fa fa-minus';
            $task      = '';
            $tip       = $neither;
        }
        else {
            $iconClass = $class === 'publish' ? 'fa fa-check' : 'fa fa-times';
            $task      = $state['task'];
            $tip       = Text::_($state['tip']);
        }

        $icon = self::icon($iconClass);

        if ($task and $controller) {
            $attributes['class']   .= $class === 'publish' ? ' active' : '';
            $attributes['href']    = 'javascript:void(0);';
            $attributes['onclick'] = "return Joomla.listItemTask('cb$index','$controller.$task','adminForm')";

            $return .= '<a ' . ArrayHelper::toString($attributes) . '>' . $icon . '</a>';
        }
        else {
            $return .= '<span ' . ArrayHelper::toString($attributes) . '>' . $icon . '</span>';
        }

        $return .= "<div role=\"tooltip\" id=\"$ariaID\">$tip</div>";

        return $return;
    }

    /**
     * The content wrapped with a link referencing a tip and the tip.
     *
     * @param   string  $content  the content referenced by the tip
     * @param   string  $context
     * @param   string  $tip      the tip to be displayed
     * @param   array   $properties
     * @param   string  $url      the url linked by the tip as applicable
     * @param   bool    $newTab   whether the url should open in a new tab
     *
     * @return string
     */
    public static function tip(
        string $content,
        string $context,
        string $tip,
        array $properties = [],
        string $url = '',
        bool $newTab = false
    ): string
    {
        if (empty($tip) and empty($url)) {
            return $content;
        }

        $properties['aria-describedby'] = $context;

        if ($url and $newTab) {
            $properties['target'] = '_blank';
        }

        $url     = $url ?: '#';
        $content = self::link($url, $content, $properties);
        $tip     = "<div role=\"tooltip\" id=\"$context\">" . Text::_($tip) . '</div>';

        return $content . $tip;
    }

    /**
     * Converts an array $property => $value to a string for use in HTML tags.
     *
     * @param   array  $array  the properties and their values
     *
     * @return string
     * @see ArrayHelper::toString()
     */
    public static function toString(array $array): string
    {
        return ArrayHelper::toString($array);
    }
}