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
use THM\Organizer\Adapters\Text;

/**
 * Class creates text input.
 */
class Blank extends FormField
{
    use Translated;

    /**
     * The allowable maxlength of the field.
     * @var    int
     */
    protected int $maxLength;

    /**
     * Method to get the field input markup.
     * @return  string  The field input markup.
     */
    protected function getInput(): string
    {
        if ($this->hint and $hint = trim($this->hint)) {
            $hint = preg_match('/^[A-Z_]+$/', $hint) ?
                Text::_($hint) : htmlspecialchars($hint, ENT_COMPAT);
        }
        else {
            $hint = '';
        }

        $maxLength = $this->getAttribute('maxlength');
        $password  = $this->getAttribute('password', false);

        $attributes = [
            (!$this->autocomplete or $this->autocomplete !== 'off') ?
                '' : "autocomplete=\"$this->autocomplete\"",
            $this->autofocus ? 'autofocus' : '',
            $this->class ? "class=\"$this->class\"" : '',
            $this->disabled ? 'disabled' : '',
            $hint ? "placeholder=\"$hint\"" : '',
            "id=\"$this->id\"",
            $maxLength ? 'maxlength="' . (int) $maxLength . '"' : '',
            "name=\"$this->name\"",
            !empty($this->onChange) ? "onChange=\"$this->onChange\"" : '',
            $this->pattern ? 'pattern="' . $this->pattern . '"' : '',
            $this->readonly ? 'readonly' : '',
            $this->required ? 'required aria-required="true"' : '',
            $this->spellcheck ? '' : 'spellcheck="false"',
            $password ? 'type="password"' : 'type="text"',
            'value="' . htmlspecialchars($this->value, ENT_COMPAT) . '"'
        ];

        return '<input ' . implode(' ', $attributes) . '/>';
    }
}
