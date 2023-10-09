<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use JInput;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Filter\InputFilter;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Class provides generalized functions useful for several component files.
 */
class Input
{
    /**
     * @var Registry
     */
    private static $batchItems;

    /**
     * @var InputFilter
     */
    private static $filter;

    /**
     * @var Registry
     */
    private static $filterItems;

    /**
     * @var Registry
     */
    private static $formItems;

    /**
     * @var JInput
     */
    private static $input;

    /**
     * @var Registry
     */
    private static $listItems;

    /**
     * @var Registry
     */
    private static $params;

    /**
     * @var Registry
     */
    private static $supplementalItems;

    /**
     * Filters the given source data according to the type parameter.
     *
     * @param mixed  $source the data to be filtered
     * @param string $type   the type against which to filter the source data
     *
     * @return mixed
     */
    public static function filter($source, string $type = 'string')
    {
        if (empty(self::$filter)) {
            self::$filter = new InputFilter();
        }

        return self::$filter->clean($source, $type);
    }

    /**
     * Retrieves the specified parameter.
     *
     * @param string $property Name of the property to get.
     *
     * @return array|int|string the value found, or false if the property could not be found
     */
    private static function find(string $property)
    {
        if ($value = self::getInput()->get($property, null, 'raw')) {
            return $value;
        }

        if ($value = self::getFormItems()->get($property)) {
            return $value;
        }

        if ($value = self::getParams()->get($property)) {
            return $value;
        }

        return null;
    }

    /**
     * Provides a shortcut to retrieve an array from the request.
     *
     * @param string $name    the name of the array item
     * @param array  $default the default array
     *
     * @return array
     */
    public static function getArray(string $name = 'jform', array $default = []): array
    {
        return self::getInput()->get($name, $default, 'array');
    }

    /**
     * Retrieves the batch items from the request and creates a registry with the data.
     * @return Registry
     */
    public static function getBatchItems(): Registry
    {
        return new Registry(self::getArray('batch'));
    }

    /**
     * Retrieves the specified parameter.
     *
     * @param string $property Name of the property to get.
     * @param mixed  $default  Default value to return if variable does not exist.
     *
     * @return bool
     */
    public static function getBool(string $property, $default = false): bool
    {
        $value = self::find($property);

        if ($value === null) {
            return self::filter($default, 'bool');
        }

        return self::filter($value, 'bool');
    }

    /**
     * Retrieves the specified parameter.
     *
     * @param string $property Name of the property to get.
     * @param mixed  $default  Default value to return if variable does not exist.
     *
     * @return string
     */
    public static function getCMD(string $property, $default = ''): string
    {
        if ($value = self::find($property)) {
            return self::filter($value, 'cmd');
        }

        return self::filter($default, 'cmd');
    }

    /**
     * Returns the application's input object.
     *
     * @param string $resource the name of the resource upon which the ids being sought reference
     * @param int    $default  the default value
     *
     * @return int the filter id
     */
    public static function getFilterID(string $resource, int $default = 0): int
    {
        $filterIDs = self::getFilterIDs($resource);

        return empty($filterIDs) ? $default : $filterIDs[0];
    }

    /**
     * Returns the application's input object.
     *
     * @param string $resource the name of the resource upon which the ids being sought reference
     *
     * @return int[] the filter ids
     */
    public static function getFilterIDs(string $resource): array
    {
        $filterItems = self::getFilterItems();
        $listItems   = self::getListItems();

        $pluralIndex = "{$resource}IDs";

        if ($values = $filterItems->get($pluralIndex, false)) {
            return self::formatIDValues($values);
        }

        if ($values = $listItems->get($pluralIndex, false)) {
            return self::formatIDValues($values);
        }

        $singularIndex = "{$resource}ID";

        if ($value = $filterItems->get($singularIndex, false)) {
            $values = [$value];

            return self::formatIDValues($values);
        }

        if ($value = $listItems->get($singularIndex, false)) {
            $values = [$value];

            return self::formatIDValues($values);
        }

        return [];
    }

    /**
     * Retrieves the filter items from the request and creates a registry with the data.
     * @return Registry
     */
    public static function getFilterItems(): Registry
    {
        if (empty(self::$filterItems)) {
            $view     = self::getView();
            $previous = Factory::getSession()->get('registry')->get("com_organizer.$view.filter", []);

            self::$filterItems = new Registry(self::getArray('filter', $previous));
        }

        return self::$filterItems;
    }

    /**
     * Retrieves the request form.
     * @return Registry with the request data if available
     */
    public static function getFormItems(): Registry
    {
        return new Registry(self::getArray());
    }

    /**
     * Retrieves the id parameter.
     * @return int
     */
    public static function getID(): int
    {
        return self::getInt('id');
    }

    /**
     * Retrieves the id parameter.
     *
     * @param string $name the input field name at which the value should be found
     *
     * @return int[] the ids
     */
    public static function getIntCollection(string $name): array
    {
        $collection = self::getArray($name);

        return self::formatIDValues($collection);
    }

    /**
     * Retrieves the specified parameter.
     *
     * @param string $property Name of the property to get.
     * @param mixed  $default  Default value to return if variable does not exist.
     *
     * @return int
     */
    public static function getInt(string $property, $default = 0): int
    {
        if ($value = self::find($property)) {
            return self::filter($value, 'int');
        }

        return self::filter($default, 'int');
    }

    /**
     * Retrieves the id of the requested menu item / menu item configuration.
     * @return int
     */
    public static function getItemid(): int
    {
        $app     = OrganizerHelper::getApplication();
        $default = (empty($app->getMenu()) or empty($app->getMenu()->getActive())) ?
            0 : $app->getMenu()->getActive()->id;

        return self::getInt('Itemid', $default);
    }

    /**
     * Returns the application's input object.
     * @return JInput
     */
    public static function getInput(): JInput
    {
        if (empty(self::$input)) {
            self::$input = OrganizerHelper::getApplication()->input;
        }

        return self::$input;
    }

    /**
     * Retrieves the list items from the request and creates a registry with the data.
     * @return Registry
     */
    public static function getListItems(): Registry
    {
        if (empty(self::$listItems)) {
            $view            = self::getView();
            $previous        = Factory::getSession()->get('registry')->get("com_organizer.$view.list", []);
            self::$listItems = new Registry(self::getArray('list', $previous));
        }

        return self::$listItems;
    }

    /**
     * Consolidates the application, component and menu parameters to a single registry with one call.
     * @return Registry
     */
    public static function getParams(): Registry
    {
        if (empty(self::$params)) {
            $app          = OrganizerHelper::getApplication();
            self::$params = method_exists($app, 'getParams') ?
                $app->getParams() : ComponentHelper::getParams('com_organizer');
        }

        return self::$params;
    }

    /**
     * Returns the selected resource id.
     *
     * @param int $default the default value
     *
     * @return int the selected id
     */
    public static function getSelectedID(int $default = 0): int
    {
        $selectedIDs = self::getSelectedIDs();

        return empty($selectedIDs) ? $default : $selectedIDs[0];
    }

    /**
     * Returns the selected resource ids.
     * @return int[] the selected ids
     */
    public static function getSelectedIDs(): array
    {
        // List Views
        $selectedIDs = self::getIntCollection('cid');

        if (!empty($selectedIDs)) {
            return $selectedIDs;
        }

        // Forms
        $formItems = self::getFormItems();
        if ($formItems->count()) {
            // Merge Views
            if ($selectedIDs = $formItems->get('ids')) {
                $formattedValues = self::formatIDValues($selectedIDs);
                if (count($formattedValues)) {
                    asort($formattedValues);

                    return $formattedValues;
                }
            }

            // Edit Views
            if ($id = $formItems->get('id')) {
                $selectedIDs = [$id];

                return self::formatIDValues($selectedIDs);
            }
        }

        // Default: explicit GET/POST parameter
        $selectedID = self::getID();

        return empty($selectedID) ? [] : [$selectedID];
    }

    /**
     * Retrieves the specified parameter.
     *
     * @param string $property Name of the property to get.
     * @param mixed  $default  Default value to return if variable does not exist.
     *
     * @return string
     */
    public static function getString(string $property, $default = ''): string
    {
        if ($value = self::find($property)) {
            return self::filter($value);
        }

        return self::filter($default);
    }

    /**
     * Retrieves the batch items from the request and creates a registry with the data.
     * @return Registry
     */
    public static function getSupplementalItems(): Registry
    {
        return new Registry(self::getArray('supplement'));
    }

    /**
     * Retrieves the task parameter.
     * @return string
     */
    public static function getTask(): string
    {
        return self::getCMD('task');
    }

    /**
     * Retrieves the view parameter.
     * @return string
     */
    public static function getView(): string
    {
        return self::getCMD('view');
    }

    /**
     * Resolves a comma separated list of id values to an array of id values.
     *
     * @param mixed $idValues the id values as an array or string
     *
     * @return int[] the id values, empty if the values were invalid or the input was not an array or a string
     */
    public static function formatIDValues(&$idValues): array
    {
        if (is_string($idValues)) {
            $idValues = explode(',', $idValues);
        } elseif (is_array($idValues) and count($idValues) === 1 and preg_match('/^(\d+,)*\d+$/', $idValues[0]) !== -1) {
            $idValues = explode(',', $idValues[0]);
        } elseif (!is_array($idValues)) {
            $idValues = [];
        }

        $idValues = ArrayHelper::toInteger($idValues);

        return array_filter($idValues);
    }

    /**
     * Sets an input property with a value.
     *
     * @param string $property the name of the property to set
     * @param mixed  $value    the value to set to the property
     * @param string $method   the method group of the property
     *
     * @return void
     */
    public static function set(string $property, $value, string $method = '')
    {
        if ($method) {
            self::getInput()->$method->set($property, $value);

            return;
        }

        self::getInput()->set($property, $value);
    }
}
