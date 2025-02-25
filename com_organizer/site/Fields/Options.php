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
use Joomla\CMS\Form\FormHelper;
use SimpleXMLElement;
use stdClass;
use THM\Organizer\Adapters\Text;

/** @inheritDoc */
class Options extends ListField
{
    /**
     * Cached array of the category items.
     * @var    array
     */
    public array $options = [];

    /** @inheritDoc */
    public function addOption($text, $attributes = []): Options
    {
        if ($text && $this->element instanceof SimpleXMLElement) {
            $child = $this->element->addChild('option', $text);

            foreach ($attributes as $name => $value) {
                $value = $name === 'text' ? Text::_($value) : $value;
                $child->addAttribute($name, $value);
            }
        }

        return $this;
    }

    /** @inheritDoc */
    protected function getOptions(): array
    {
        // Prevents rewrites from Joomla
        if ($this->options) {
            return $this->options;
        }

        foreach ($this->element->xpath('option') as $optionTag) {

            $option        = new stdClass();
            $option->value = (string) $optionTag['value'];

            $text         = trim((string) $optionTag) ?: $option->value;
            $option->text = Text::_($text);

            $disabled        = (string) $optionTag['disabled'];
            $disabled        = ($disabled === 'true' or $disabled === 'disabled' or $disabled === '1');
            $option->disable = ($disabled or ($this->readonly and $option->value != $this->value));

            $checked = (string) $optionTag['checked'];
            $checked = ($checked === 'true' or $checked === 'checked' or $checked === '1');

            $selected = (string) $optionTag['selected'];
            $selected = ($selected === 'true' or $selected === 'selected' or $selected === '1');

            $option->class    = (string) $optionTag['class'];
            $option->selected = ($checked || $selected);
            $option->checked  = ($checked || $selected);

            // Set some event handler attributes. But really, should be using unobtrusive js.
            $option->onclick  = (string) $optionTag['onclick'];
            $option->onchange = (string) $optionTag['onchange'];

            if ((string) $optionTag['showon']) {
                $encodedConditions = json_encode(
                    FormHelper::parseShowOnConditions((string) $optionTag['showon'], $this->formControl, $this->group)
                );

                $option->optionattr = " data-showon='" . $encodedConditions . "'";
            }

            // Add the option object to the result set.
            $this->options[] = $option;
        }

        return $this->options;
    }

    /**
     * Gets the options defined in the form manifest.
     * @return stdClass[]
     */
    protected function manifestOptions(): array
    {
        return self::getOptions();
    }

    /**
     * Method to set the field options.
     *
     * @param   object[]  $options  the options to display
     *
     * @return void
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
}