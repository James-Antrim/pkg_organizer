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
use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers;
use SimpleXMLElement;
use stdClass;

/**
 * Class creates select input.
 */
class OptionsField extends FormField
{
    use Translated;

    protected $adminContext;

    /**
     * Cached array of the category items.
     * @var    array
     */
    public $options = [];

    /**
     * Method to get certain otherwise inaccessible properties from the form field object.
     *
     * @param string $name The property name for which to get the value.
     *
     * @return  mixed  The property value or null.
     */
    public function __get($name)
    {
        if ($name == 'options') {
            return $this->getOptions();
        }

        return parent::__get($name);
    }

    /**
     * Method to add an option to the list field.
     *
     * @param string $text       Text/Language variable of the option.
     * @param array  $attributes Array of attributes ('name' => 'value' format)
     *
     * @return  OptionsField  For chaining.
     */
    public function addOption(string $text, array $attributes = []): OptionsField
    {
        if ($text && $this->element instanceof SimpleXMLElement) {
            $child = $this->element->addChild('option', $text);

            foreach ($attributes as $name => $value) {
                $child->addAttribute($name, $value);
            }
        }

        return $this;
    }

    /**
     * Allows direct access to the getInput function defined here.
     * @return string
     */
    protected function getBaseInput(): string
    {
        return self::getInput();
    }

    /**
     * Method to get the field input markup for a generic list.
     * @return  string  The field input markup.
     */
    protected function getInput(): string
    {
        $this->adminContext = Application::getApplication()->isClient('administrator');

        $attr = '';

        // Check for previous initialization using the dependent trait. Set before other attributes to allow options to
        // influence them.
        if (!$this->options) {
            $this->options = $this->getOptions();
        }

        // Initialize some field attributes.
        $attr .= !empty($this->class) ? ' class="' . $this->class . '"' : '';
        $attr .= $this->required ? ' required aria-required="true"' : '';
        $attr .= $this->autofocus ? ' autofocus' : '';

        $count = count($this->options);
        if ($this->multiple) {
            if ($count >= 2) {
                $attr .= ' multiple';

                if ($count >= 3 and !empty($this->size)) {
                    $attr .= " size=\"$this->size\"";
                }
            }
        } else {
            $attr .= !empty($this->size) ? ' size="' . $this->size . '"' : '';
        }


        // To avoid user's confusion, readonly="true" should imply disabled="true".
        if ($this->readonly or $this->disabled) {
            $attr .= ' disabled="disabled"';
        }

        // Initialize JavaScript field attributes.
        $attr .= $this->onchange ? ' onchange="' . $this->onchange . '"' : '';
        $attr .= empty($this->getAttribute('onblur')) ?
            '' : ' onblur="' . $this->getAttribute('onblur') . '"';

        return Helpers\HTML::_(
            'select.genericlist',
            $this->options,
            $this->name,
            trim($attr),
            'value',
            'text',
            $this->value,
            $this->id
        );
    }

    /**
     * Gets the options defined in the form manifest.
     * @return stdClass[]
     */
    protected function getDefaultOptions(): array
    {
        return self::getOptions();
    }

    /**
     * Method to get the field options.
     * @return  stdClass[]  The field option objects.
     */
    protected function getOptions(): array
    {
        $fieldName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);
        $options   = [];

        foreach ($this->element->xpath('option') as $optionTag) {

            $option        = new stdClass();
            $option->value = (string) $optionTag['value'];

            $text         = trim((string) $optionTag) != '' ? trim((string) $optionTag) : $option->value;
            $text         = strpos($text, 'ORGANIZER_') === 0 ? $text : "ORGANIZER_$text";
            $option->text = Helpers\Languages::alt($text, $fieldName);

            $option->class = (string) $optionTag['class'];

            $disabled        = (string) $optionTag['disabled'];
            $disabled        = ($disabled == 'true' or $disabled == 'disabled' or $disabled == '1');
            $option->disable = ($disabled or ($this->readonly && $option->value != $this->value));

            $checked = (string) $optionTag['checked'];
            $checked = ($checked == 'true' or $checked == 'checked' or $checked == '1');

            $selected = (string) $optionTag['selected'];
            $selected = ($selected == 'true' or $selected == 'selected' or $selected == '1');

            $option->selected = ($checked or $selected);
            $option->checked  = ($checked or $selected);

            // Set some event handler attributes. But really, should be using unobtrusive js.
            $option->onclick  = (string) $optionTag['onclick'];
            $option->onchange = (string) $optionTag['onchange'];

            // Add the option object to the result set.
            $options[] = $option;
        }

        reset($options);

        return $options;
    }
}