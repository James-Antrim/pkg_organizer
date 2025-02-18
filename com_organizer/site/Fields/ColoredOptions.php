<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use Joomla\CMS\Form\Field\ListField;

/** @inheritDoc */
abstract class ColoredOptions extends ListField
{
    /** @inheritDoc */
    public function getInput(): string
    {
        $onChange = empty($this->getAttribute('onchange')) ?
            '' : ' onchange="' . $this->getAttribute('onchange') . '"';
        $html     = '<select name="' . $this->name . '"' . $onChange . ' class="form-select">';
        $options  = $this->getOptions();
        foreach ($options as $option) {
            $style    = isset($option->style) ? ' style="' . $option->style . '"' : '';
            $selected = $this->value == $option->value ? ' selected="selected"' : '';
            $html     .= '<option value="' . $option->value . '"' . $selected . $style . '>';
            $html     .= $option->text . '</option>';
        }
        $html .= '</select>';

        return $html;
    }
}
