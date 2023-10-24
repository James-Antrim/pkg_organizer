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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Utilities\ArrayHelper;

class HTML extends HTMLHelper
{
    /**
     * Converts an array $property => $value to a string for use in HTML tags.
     *
     * @param array $array the properties and their values
     *
     * @return string
     */
    public static function toProperties(array $array): string
    {
        foreach ($array as $property => $value) {
            $array[$property] = "$property=\"$value\"";
        }

        return $array ? implode(' ', $array) : '';
    }

    /**
     * Creates an icon with tooltip as appropriate.
     *
     * @param string $class the icon class(es)
     *
     * @return string the HTML for the icon to be displayed
     */
    public static function icon(string $class): string
    {
        return "<i class=\"$class\" aria-hidden=\"true\"></i>";
    }


    /**
     * Returns an action on a grid
     *
     * @param integer $index      the row index
     * @param array   $state      the state configuration
     * @param string  $controller the name of the controller class
     * @param string  $neither    text for columns which cannot be toggled
     *
     * @return  string  the HTML for the toggle item
     */
    public static function toggle(int $index, array $state, string $controller = '', string $neither = ''): string
    {
        $ariaID     = "{$state['column']}-$index";
        $attributes = [
            'aria-labelledby' => $ariaID,
            'class' => "tbody-icon"
        ];

        $class  = $state['class'];
        $return = '';

        if ($neither) {
            $iconClass = 'fa fa-minus';
            $task      = '';
            $tip       = $neither;
        } else {
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
        } else {
            $return .= '<span ' . ArrayHelper::toString($attributes) . '>' . $icon . '</span>';
        }

        $return .= "<div role=\"tooltip\" id=\"$ariaID\">$tip</div>";

        return $return;
    }

    /**
     * The content wrapped with a link referencing a tip and the tip.
     *
     * @param string $content the content referenced by the tip
     * @param string $tip     the tip to be displayed
     * @param string $url     the url linked by the tip as applicable
     * @param bool   $newTab  whether the url should open in a new tab
     *
     * @return string the HTML for the content and tip
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
        $tip     = "<div role=\"tooltip\" id=\"$context\">" . $tip . '</div>';

        return $content . $tip;
    }
}