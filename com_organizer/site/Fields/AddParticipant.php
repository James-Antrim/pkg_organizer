<?php
/**
 * @package     Organizer\Fields
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use Joomla\CMS\Form\FormField;
use THM\Organizer\Adapters\Text;

/**
 * Class provides a field by which to add a participant to a given event related resource.
 */
class AddParticipant extends FormField
{
    use Translated;

    /**
     * Method to get the field input markup for a generic list.
     * @return  string  The field input markup.
     */
    protected function getInput(): string
    {
        $label = "<label for=\"$this->name\" class=\"element-invisible\">XTEXTX</label>";
        $label = str_replace('XTEXTX', Text::_('ADD_PARTICIPANT'), $label);

        $container = '<div class="btn-wrapper input-append">XINPUTXXBUTTONX</div>';

        $attributes = [
            "id=\"$this->id\"",
            "name=\"$this->name\"",
            $this->class ? "class=\"$this->class\"" : '',
            'maxlength="20"',
            'placeholder="' . Text::_('ADD') . '"',
            'type="text"'
        ];
        $input      = '<input ' . implode(' ', $attributes) . '/>';
        $container  = str_replace('XINPUTX', $input, $container);

        $attributes = [
            'aria-label="' . Text::_('ADD_PARTICIPANT') . '"',
            'class="btn hasTooltip"',
            'onclick="Joomla.submitbutton(\'bookings.addParticipant\');"',
            'title="' . Text::_('ADD_PARTICIPANT') . '"',
            'type="submit"'
        ];
        $icon       = '<span class="icon-user-plus" aria-hidden="true"></span>';
        $button     = '<button ' . implode(' ', $attributes) . '>' . $icon . '</button>';
        $container  = str_replace('XBUTTONX', $button, $container);

        // Add a button
        return $label . $container;
    }
}