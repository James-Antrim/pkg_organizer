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

use Joomla\CMS\Form\FormField;
use THM\Organizer\Helpers;

/**
 * Class creates text input.
 */
class Date extends FormField
{
    use Translated;

    /**
     * Method to get the field input markup.
     * @return  string  The field input markup.
     */
    protected function getInput(): string
    {
        $empty    = $this->getAttribute('empty', 'true');
        $onchange = $this->getAttribute('onchange', '');

        if ($this->value) {
            $value = Helpers\Dates::standardizeDate($this->value);
        }
        else {
            $value = $empty === 'false' ? Helpers\Dates::standardizeDate() : '';
        }

        $attributes = [
            $this->autofocus ? 'autofocus' : '',
            $this->class ? "class=\"$this->class\"" : '',
            $this->disabled ? 'disabled' : '',
            "id=\"$this->id\"",
            "name=\"$this->name\"",
            $onchange ? "onChange=\"$onchange\"" : '',
            $this->readonly ? 'readonly' : '',
            $this->required ? 'required aria-required="true"' : '',
            'type="date"',
            'value="' . $value . '"'
        ];

        return '<input ' . implode(' ', $attributes) . '/>';
    }
}
