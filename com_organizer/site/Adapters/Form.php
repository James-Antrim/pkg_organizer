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

use InvalidArgumentException;
use Joomla\CMS\Form\{Form as Base, FormField, FormHelper};
use Joomla\Database\DatabaseAwareInterface;
use RuntimeException;
use SimpleXMLElement;

/**
 * @inheritDoc
 * Adapts the Form to load properly namespaced fields.
 */
class Form extends Base
{
    /**
     * @inheritDoc
     */
    public function __construct($name, array $options = [])
    {
        parent::__construct($name, $options);

        FormHelper::addFieldPath(JPATH_SITE . '/components/com_groups/Fields');
        FormHelper::addFilterPath(JPATH_SITE . '/components/com_groups/forms');
        FormHelper::addFormPath(JPATH_SITE . '/components/com_groups/forms');
    }

    /**
     * @inheritDoc
     */
    protected function loadField($element, $group = null, $value = null): DatabaseAwareInterface|FormField|bool
    {
        // Make sure there is a valid SimpleXMLElement.
        if (!($element instanceof SimpleXMLElement)) {
            $error = sprintf('%s::%s `xml` is not an instance of SimpleXMLElement', get_class($this), __METHOD__);
            Application::message($error, 'error');

            return false;
        }

        // Get the field type.
        $type = $element['type'] ? (string) $element['type'] : 'text';

        $fields = $this->getFieldClasses();
        if (!in_array($type, $fields)) {
            return parent::loadField($element, $group, $value);
        }

        // Load the FormField object for the field.
        $field = $this->loadFieldClass($type);

        /*
         * Get the value for the form field if not set.
         * Default to the translated version of the 'default' attribute
         * if 'translate_default' attribute if set to 'true' or '1'
         * else the value of the 'default' attribute for the field.
         */
        if ($value === null) {
            $default = (string) ($element['default'] ? $element['default'] : $element->default);

            if (($translate = $element['translate_default']) && ((string) $translate === 'true' || (string) $translate === '1')) {
                $lang = Application::getLanguage();

                if ($lang->hasKey($default)) {
                    $debug   = $lang->setDebug(false);
                    $default = Text::_($default);
                    $lang->setDebug($debug);
                } else {
                    $default = Text::_($default);
                }
            }

            $value = $this->getValue((string) $element['name'], $group, $default);
        }

        $field->setForm($this);

        if ($field->setup($element, $value, $group)) {
            return $field;
        } else {
            return false;
        }
    }

    /**
     * Checks for the available Table classes.
     * @return array
     */
    private function getFieldClasses(): array
    {
        $fields = [];
        foreach (glob(JPATH_SITE . '/components/com_groups/Fields/*') as $field) {
            $field    = str_replace(JPATH_SITE . '/components/com_groups/Fields/', '', $field);
            $fields[] = str_replace('.php', '', $field);
        }

        return $fields;
    }

    /**
     * @inheritDoc
     */
    public static function getInstance($name, $data = null, $options = [], $replace = true, $xpath = false): Base|Form
    {
        // Reference to array with form instances
        $forms = &self::$forms;

        // Only instantiate the form if it does not already exist.
        if (!isset($forms[$name])) {
            $data = trim($data);

            if (empty($data)) {
                throw new InvalidArgumentException(sprintf('%1$s(%2$s, *%3$s*)', __METHOD__, $name, gettype($data)));
            }

            // Instantiate the form.
            $factory = new FormFactory();
            $factory->setDatabase(Application::getDB());
            $forms[$name] = $factory->createForm($name, $options);

            // Load the data.
            if (str_starts_with($data, '<')) {
                if (!$forms[$name]->load($data, $replace, $xpath)) {
                    throw new RuntimeException(sprintf('%s() could not load form', __METHOD__));
                }
            } else {
                if (!$forms[$name]->loadFile($data, $replace, $xpath)) {
                    throw new RuntimeException(sprintf('%s() could not load file', __METHOD__));
                }
            }
        }

        return $forms[$name];
    }

    /**
     * Loads a reasonably namespaced form field.
     *
     * @param string $field the name of the field class to load
     *
     * @return FormField
     */
    private function loadFieldClass(string $field): FormField
    {
        $fqName = 'THM\\Organizer\\Fields\\' . $field;

        $field = new $fqName($this);
        $field->setDatabase($this->getDatabase());

        return $field;
    }
}
