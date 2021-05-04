<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use Joomla\CMS\Form\FormField;
use Organizer\Helpers;
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
     *
     * @var    array
     */
    public $options = [];

    /**
     * Method to get the field input markup for a generic list.
     *
     * @return  string  The field input markup.
     */
    protected function getInput()
    {
        $this->adminContext = Helpers\OrganizerHelper::getApplication()->isClient('administrator');

        $attr = '';

        // Check for previous initialization using the dependent trait. Set before other attributes to allow options to
        // influence them.
        if (empty($this->options)) {
            $this->options = (array)$this->getOptions();
        }

        // Initialize some field attributes.
        $attr .= !empty($this->class) ? ' class="' . $this->class . '"' : '';
        $attr .= $this->required ? ' required aria-required="true"' : '';
        $attr .= $this->autofocus ? ' autofocus' : '';

        $count = count($this->options);
        if ($this->multiple) {
            if ($count >= 2) {
                $attr .= $this->multiple ? ' multiple' : '';

                if ($count >= 3 and !empty($this->size)) {
                    $attr .= " size=\"$this->size\"";
                }
            }
        } else {
            $attr .= !empty($this->size) ? ' size="' . $this->size . '"' : '';
        }


        // To avoid user's confusion, readonly="true" should imply disabled="true".
        if ((bool)$this->readonly == '1' || (bool)$this->disabled) {
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
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     */
    protected function getOptions()
    {
        $fieldName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);
        $options   = [];

        foreach ($this->element->xpath('option') as $optionTag) {

            $option        = new stdClass();
            $option->value = (string)$optionTag['value'];

            $text         = trim((string)$optionTag) != '' ? trim((string)$optionTag) : $option->value;
            $option->text = Helpers\Languages::alt('ORGANIZER_' . $text, $fieldName);

            $option->class = (string)$optionTag['class'];

            $disabled        = (string)$optionTag['disabled'];
            $disabled        = ($disabled == 'true' or $disabled == 'disabled' or $disabled == '1');
            $option->disable = ($disabled or ($this->readonly && $option->value != $this->value));

            $checked = (string)$optionTag['checked'];
            $checked = ($checked == 'true' or $checked == 'checked' or $checked == '1');

            $selected = (string)$optionTag['selected'];
            $selected = ($selected == 'true' or $selected == 'selected' or $selected == '1');

            $option->selected = ($checked or $selected);
            $option->checked  = ($checked or $selected);

            // Set some event handler attributes. But really, should be using unobtrusive js.
            $option->onclick  = (string)$optionTag['onclick'];
            $option->onchange = (string)$optionTag['onchange'];

            // Add the option object to the result set.
            $options[] = $option;
        }

        reset($options);

        return $options;
    }

    /**
     * Method to add an option to the list field.
     *
     * @param   string  $text        Text/Language variable of the option.
     * @param   array   $attributes  Array of attributes ('name' => 'value' format)
     *
     * @return  OptionsField  For chaining.
     */
    public function addOption($text, $attributes = [])
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
     * Method to get certain otherwise inaccessible properties from the form field object.
     *
     * @param   string  $name  The property name for which to get the value.
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
}