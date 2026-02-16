<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Adapters;

use Joomla\CMS\HTML\Helpers\{Form as FH, Grid, Number, SearchTools, Select};
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Utility\Utility;
use Joomla\Utilities\ArrayHelper;
use stdClass;

/**
 * Class integrates HTMLHelper and several further helper classes, wrapping unhandled exceptions and creating simplified
 * and documented access to functions otherwise called by magic methods.
 */
class HTML extends HTMLHelper
{
    /**
     * addTab wrapper: only the last three parameters had value.
     * @param stdClass|string $tab         the tab (fieldset) object created from the form manifest or the name of tab
     * @param string          $label       implicit localization
     * @param string          $description implicit localization
     * @return string
     */
    public static function addTab(stdClass|string $tab, string $label = '', string $description = ''): string
    {
        if (is_object($tab)) {
            return self::_('uitab.addTab', 'myTab', $tab->name, Text::_($tab->label), Text::_($tab->description));
        }
        return self::_('uitab.addTab', 'myTab', $tab, Text::_($label), Text::_($description));
    }

    /**
     * Method to check all checkboxes in a resource table.
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
     * @param int $rowNumber the row number in the HTML output
     * @param int $rowID     the id of the resource row in the database
     *
     * @return  string
     */
    public static function checkBox(int $rowNumber, int $rowID): string
    {
        return Grid::id($rowNumber, $rowID);
    }

    /**
     * endTab wrapper: parameterless and
     * @return string
     */
    public static function endTab(): string
    {
        return self::_('uitab.endTab');
    }

    /**
     * endTabSet wrapper: parameterless...
     * @return string
     */
    public static function endTabs(): string
    {
        return self::_('uitab.endTabSet');
    }

    /**
     * Creates an icon with tooltip as appropriate.
     *
     * @param string $class the icon class(es)
     *
     * @return string
     */
    public static function icon(string $class): string
    {
        return "<i class=\"$class\" aria-hidden=\"true\"></i>";
    }

    /**
     * Method to create a sorting column header for a resource table. Header text key is automatically prefaced and
     * localized.
     *
     * @param string $constant      the text to display in the table header
     * @param string $column        the query column that this link sorts by
     * @param string $direction     the current sort direction for this query column
     * @param string $currentColumn the current query column that the results are being sorted by
     *
     * @return  string
     * @see SearchTools::sort()
     */
    public static function sort(string $constant, string $column, string $direction, string $currentColumn): string
    {
        return SearchTools::sort(Text::_($constant), $column, $direction, $currentColumn);
    }

    /**
     * @inheritDoc
     * Link text key is automatically prefaced and localized.
     */
    public static function link($url, $text, $attribs = null): string
    {
        return parent::link($url, $text, $attribs);
    }

    /**
     * Method to return the maximum upload size defined in the site configured in the ini.
     * @return  string
     * @see Number::bytes(), Utility::getMaxUploadSize()
     */
    public static function maxUploadSize(): string
    {
        return Number::bytes(Utility::getMaxUploadSize());
    }

    /**
     * Create an object that represents an option in an option list.
     *
     * @param int|string $value   the option value
     * @param string     $text    the option text
     * @param bool       $disable whether the option is disabled
     *
     * @return  stdClass
     */
    public static function option(int|string $value, string $text, bool $disable = false): stdClass
    {
        return Select::option((string) $value, $text, 'value', 'text', $disable);
    }

    /**
     * Method to create a column header for activating the sort column when multiple list columns can be sorted.
     *
     * @param string $currentColumn the current query column that the results are being sorted by
     *
     * @return  string
     * @see SearchTools::sort()
     */
    public static function orderingSort(string $currentColumn): string
    {
        return SearchTools::sort(
            '',
            'ordering',
            'asc',
            $currentColumn,
            null,
            'asc',
            '',
            HTML::icon('fa fa-arrows-alt-v')
        );
    }

    /**
     * Generates a string containing property information for an HTML element to be output
     *
     * @param mixed &$element the element being processed
     *
     * @return string the HTML attribute output for the item
     */
    public static function properties(array &$element): string
    {
        $return = '';

        if (!empty($element['properties']) and is_array($element['properties'])) {
            $return = ArrayHelper::toString($element['properties']);
        }
        unset($element['properties']);

        return $return;
    }

    /**
     * Generates an HTML selection list.
     *
     * @param string           $name       the field name.
     * @param stdClass[]       $options    the field options
     * @param array|int|string $selected   the selected resource designators; called function accepts array|string
     * @param array            $properties additional HTML properties for the select tag
     * @param string           $textKey    name of the name column when working directly with table rows
     * @param string           $valueKey   name of the value column when working directly with table rows
     * @param bool|string      $id         the optional id for the select box
     *
     * @return  string
     */
    public static function selectBox(
        string           $name,
        array            $options,
        array|int|string $selected = [],
        array            $properties = [],
        string           $textKey = 'text',
        string           $valueKey = 'value',
        bool|string      $id = false
    ): string
    {
        /**
         * Called function will most likely eventually be typed to array|string, most of our single selection values
         * will be integers for resource ids.
         */
        $selected = gettype($selected) === 'integer' ? (string) $selected : $selected;

        return Select::genericlist($options, $name, $properties, $valueKey, $textKey, $selected, $id, true);
    }

    /**
     * startTabSet wrapper: it was only being used with the same parameters every time anyway.
     * @param string $active     the name of the tab to use as the active tab set on rendering
     * @param int    $breakPoint the width of the tab
     * @return string
     */
    public static function startTabs(string $active = 'details', int $breakPoint = 768): string
    {
        return self::_('uitab.startTabSet', 'myTab', ['active' => $active, 'recall' => true, $breakPoint => 768]);
    }

    /**
     * Displays a hidden token field to reduce the risk of CSRF exploits.
     * @return  string  A hidden input field with a token
     * @see     FH::token(), Session::checkToken()
     */
    public static function token(): string
    {
        return FH::token();
    }

    /**
     * Returns an action on a grid. Deviates from groups, because of groups offering a disabled toggle as a third option.
     *
     * @param int    $index      the row index
     * @param array  $state      the state configuration
     * @param string $controller the name of the controller class
     * @param string $context    supplemental context for the task
     *
     * @return  string
     */
    public static function toggle(int $index, array $state, string $controller, string $context = ''): string
    {
        $ariaID = "{$state['column']}-$index";
        $ariaID .= $context ? "-$context" : '';

        $attributes = [
            'aria-labelledby' => $ariaID,
            'class'           => "tbody-icon"
        ];

        $class     = $state['class'];
        $iconClass = $class === 'publish' ? 'fa fa-check' : 'fa fa-times';
        $return    = '';
        $task      = $state['task'];
        $tip       = Text::_($state['tip']);

        $attributes['class']   .= $class === 'publish' ? ' active' : '';
        $attributes['href']    = 'javascript:void(0);';
        $attributes['onclick'] = $task ? "return Joomla.listItemTask('cb$index','$controller.$task$context','adminForm')" : '#';

        $return .= '<a ' . ArrayHelper::toString($attributes) . '>' . self::icon($iconClass) . '</a>';

        $return .= "<div role=\"tooltip\" id=\"$ariaID\">$tip</div>";

        return $return;
    }

    /**
     * The content wrapped with a link referencing a tip and the tip.
     *
     * @param string $content the content referenced by the tip
     * @param string $context
     * @param string $tip     the tip to be displayed
     * @param array  $properties
     * @param string $url     the url linked by the tip as applicable
     * @param bool   $newTab  whether the url should open in a new tab
     *
     * @return string
     */
    public static function tip(
        string $content,
        string $context,
        string $tip,
        array  $properties = [],
        string $url = '',
        bool   $newTab = false
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
}