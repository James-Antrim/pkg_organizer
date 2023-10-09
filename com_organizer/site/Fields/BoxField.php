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
use Organizer\Helpers;

/**
 * Class creates text input.
 */
class BoxField extends FormField
{
    use Translated;

    /**
     * The allowable maxlength of the field.
     * @var    integer
     */
    protected $maxLength;

    /**
     * The form field type.
     * @var    string
     */
    protected $type = 'Box';

    /**
     * Method to get the field input markup.
     * @return  string  The field input markup.
     */
    protected function getInput(): string
    {
        if ($this->hint and $hint = trim($this->hint)) {
            $hint = preg_match('/^[A-Z_]+$/', $hint) ?
                Helpers\Languages::_("ORGANIZER_$hint") : htmlspecialchars($hint, ENT_COMPAT);
        } else {
            $hint = '';
        }

        $attributes = [
            (!$this->autocomplete or $this->autocomplete !== 'off') ? '' : "autocomplete=\"$this->autocomplete\"",
            $this->autofocus ? 'autofocus' : '',
            $this->class ? "class=\"$this->class\"" : '',
            $this->disabled ? 'disabled' : '',
            $hint ? "placeholder=\"$hint\"" : '',
            "id=\"$this->id\"",
            $this->maxLength ? "maxlength=\"$this->maxLength\"" : '',
            "name=\"$this->name\"",
            !empty($this->onChange) ? "onChange=\"$this->onChange\"" : '',
            $this->pattern ? 'pattern="' . $this->pattern . '"' : '',
            $this->readonly ? 'readonly' : '',
            $this->required ? 'required aria-required="true"' : '',
            $this->spellcheck ? '' : 'spellcheck="false"'
        ];

        $open  = '<textarea ' . implode(' ', $attributes) . '>';
        $value = htmlspecialchars($this->value, ENT_COMPAT);

        return $open . $value . '</textarea>';
    }
}
