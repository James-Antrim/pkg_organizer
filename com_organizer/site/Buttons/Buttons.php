<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Buttons;


use Joomla\CMS\Toolbar\ToolbarButton;
use Organizer\Helpers\Languages;

/**
 * Class provides a button acting as a dropdown toggle for other buttons.
 */
class Buttons extends ToolbarButton
{
    protected $_name = 'Buttons';

    /**
     * Creates the HTML for the buttons 'button'.
     *
     * @param string   $type        implicitly required by Joomla, not used here
     * @param string   $name        implicitly required by Joomla, not used here
     * @param string   $text        the text to display in the button
     * @param string[] $buttons     an array of pre-rendered toolbar buttons (html strings) to displayed on demand,
     *                              prerendered because each button has highly individual arguments used in their
     *                              rendering
     *
     * @return string the html for the 'button'
     */
    public function fetchButton(string $type = 'Buttons', string $name = 'buttons-button', string $text = '', array $buttons = [], string $icon = 'list-3'): string
    {
        $text = $text ?: Languages::_('ORGANIZER_SELECTION');

        $html = '<button class="dropdown-toggle btn" data-toggle="dropdown">';
        $html .= "<span class=\"icon-$icon\"></span>" . $text . '<span class="icon-arrow-down-3"></span>';
        $html .= '</button><ul class="dropdown-menu">';

        foreach ($buttons as $button) {
            $html .= '<li>' . $button . '</li>';
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Function stub for what should be an abstract method. This has no value for this element.
     * @return  string  empty string to avoid a warning in a callback in ToobarButton::render
     * @see ToolbarButton::render()
     */
    public function fetchid(): string
    {
        return '';
    }
}