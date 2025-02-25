<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Adapters;

use Joomla\Filter\InputFilter;
use Joomla\Input\Input as Core;
use Joomla\Registry\Registry;

/**
 * Class provides generalized functions useful for several component files.
 */
class Input
{
    public const NO = 0, NONE = -1, YES = 1;

    // (File) Formats
    public const HTML = 'html', ICS = 'ics', JSON = 'json', PDF = 'pdf', XLS = 'xls';// XML = 'xml';

    private static InputFilter $filter;
    private static Registry $filterItems;
    private static Core $input;
    private static Registry $listItems;
    private static Registry $params;

    /**
     * Filters the given source data according to the type parameter.
     *
     * @param   mixed   $source  the data to be filtered
     * @param   string  $type    the type against which to filter the source data
     *
     * @return mixed
     */
    public static function filter(mixed $source, string $type = 'string'): mixed
    {
        if (empty(self::$filter)) {
            self::$filter = new InputFilter();
        }

        return self::$filter->clean($source, $type);
    }

    /**
     * Retrieves the specified parameter.
     *
     * @param   string  $property  Name of the property to get.
     *
     * @return mixed the value found, or false if the property could not be found
     */
    private static function find(string $property): mixed
    {
        if ($value = self::getInput()->get($property, null, 'raw')) {
            return $value;
        }

        if ($filterItems = self::getFilterItems() and $value = $filterItems->get($property)) {
            return $value;
        }

        if ($listItems = self::getListItems() and $value = $listItems->get($property)) {
            return $value;
        }

        if ($value = self::getParams()->get($property)) {
            return $value;
        }

        return null;
    }

    /**
     * Accessor for the format parameter and document type. Joomla handles them redundantly internally leading to format
     * overwrites to html if the document type is not explicitly set.
     *
     * @param   string  $format
     *
     * @return string
     */
    public static function format(string $format = ''): string
    {
        $document  = Application::document();
        $supported = ['html', 'ics', 'json', 'pdf', 'xls', 'xml'];

        if ($format and in_array($format, $supported)) {
            self::set('format', $format);
            return Document::type($format);
        }

        return $document->getType();
    }

    /**
     * Provides a shortcut to retrieve an array from the request.
     *
     * @param   string  $name     the name of the array item
     * @param   array   $default  the default array
     *
     * @return array
     */
    public static function getArray(string $name, array $default = []): array
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
     * @param   string  $property  Name of the property to get.
     * @param   mixed   $default   Default value to return if variable does not exist.
     *
     * @return bool
     */
    public static function getBool(string $property, bool $default = false): bool
    {
        $value = self::find($property);

        if ($value === null) {
            return self::filter($default, 'bool');
        }

        return self::filter($value, 'bool');
    }

    /**
     * Retrieves the controller parameter with the view parameter as fallback.
     * @return string
     */
    public static function getController(): string
    {
        return (string) self::getInput()->get('controller') ?: self::getView();
    }

    /**
     * Retrieves the specified parameter.
     *
     * @param   string  $property  Name of the property to get.
     * @param   mixed   $default   Default value to return if variable does not exist.
     *
     * @return string
     */
    public static function getCMD(string $property, string $default = ''): string
    {
        return ($value = self::find($property)) ? self::filter($value, 'cmd') : self::filter($default, 'cmd');
    }

    /**
     * Retrieves an id value from a filter or list selection box.
     *
     * @param   string  $resource  the name of the resource upon which the ids being sought reference
     * @param   int     $default   the default value
     *
     * @return int the filter id
     */
    public static function getFilterID(string $resource, int $default = 0): int
    {
        if ($value = self::getFilterItems()->get($resource, false)) {
            return (int) $value;
        }

        if ($value = self::getListItems()->get($resource, false)) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * Retrieves the filter items from the request and creates a registry with the data.
     * @return Registry
     */
    public static function getFilterItems(): Registry
    {
        if (empty(self::$filterItems)) {
            $view     = self::getView();
            $previous = Application::session()->get('registry')->get("com_organizer.$view.filter", []);

            self::$filterItems = new Registry(self::getArray('filter', $previous));
        }

        return self::$filterItems;
    }

    /**
     * Retrieves the specified parameter.
     *
     * @param   string  $property  Name of the property to get.
     * @param   float   $default   Default value to return if variable does not exist.
     *
     * @return float
     */
    public static function getFloat(string $property, float $default = 0.0): float
    {
        $value = self::find($property);

        // Better plausibility test for this type whose value can also be 0 on purpose and otherwise evaluate to false.
        if (is_numeric($value)) {
            return self::filter($value, 'float');
        }

        return self::filter($default, 'float');
    }

    /**
     * Retrieves form data / POST.
     * @return array
     */
    public static function getFormItems(): array
    {
        return self::getInput()->post->getArray();
    }

    /**
     * Retrieves the id parameter.
     * @return int
     */
    public static function getID(): int
    {
        return (int) self::getInput()->get('id', 0, 'int');
    }

    /**
     * Retrieves the id parameter.
     *
     * @param   string  $name  the input field name at which the value should be found
     *
     * @return int[] the ids
     */
    public static function getIntArray(string $name, array $default = []): array
    {
        $array = array_filter(self::getArray($name), 'intval');
        return in_array(self::NONE, $array) ? $default : $array;
    }

    /**
     * Resolves a comma separated list of id values to an array of id values.
     *
     * @param   string  $name  the input field name at which the value should be found
     *
     * @return int[]
     */
    public static function getIntCollection(string $name): array
    {
        $collection = self::getString($name);

        // Single number collection
        if (is_numeric($collection)) {
            $value = (int) $collection;
            return $value === self::NONE ? [] : [$value];
        }

        $collection = explode(',', $collection);
        $collection = array_filter($collection, 'intval');

        return in_array(self::NONE, $collection) ? [] : $collection;
    }

    /**
     * Retrieves the specified parameter.
     *
     * @param   string  $property  Name of the property to get
     * @param   int     $default   Default value to return if variable does not exist
     * @param   string  $method    Explicit method where the value should be
     *
     * @return int
     */
    public static function getInt(string $property, int $default = 0, string $method = ''): int
    {
        if ($method) {
            $value = self::getInput()->$method->get($property, $default, 'raw');
        }
        else {
            $value = self::find($property);
        }

        // Better plausibility test for this type whose value can also be 0 on purpose and otherwise evaluate to false.
        if (is_numeric($value)) {
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
        $item    = Application::menuItem();
        $default = $item ? $item->id : 0;

        return (int) self::getInput()->get('Itemid', $default, 'int');
    }

    /**
     * Returns the application's input object.
     * @return Core
     */
    public static function getInput(): Core
    {
        if (empty(self::$input)) {
            self::$input = Application::instance()->input;
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
            $previous        = Application::session()->get('registry')->get("com_organizer.$view.list", []);
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
            $app          = Application::instance();
            self::$params = method_exists($app, 'getParams') ?
                $app->getParams() : Application::parameters();
        }

        return self::$params;
    }

    /**
     * Gets the URL of the referrer.
     * @return string
     */
    public static function getReferrer(): string
    {
        // The command filter removes syntax elements from the URL
        return (string) self::getInput()->server->get('HTTP_REFERER', null, 'string');
    }

    /**
     * Returns the selected resource id.
     *
     * @param   int  $default  the default value
     *
     * @return int
     */
    public static function getSelectedID(int $default = 0): int
    {
        $selectedIDs = self::getSelectedIDs();

        return empty($selectedIDs) ? $default : $selectedIDs[0];
    }

    /**
     * Returns the selected resource ids.
     * @return int[]
     */
    public static function getSelectedIDs(): array
    {
        // List Views
        if ($selectedIDs = self::getIntArray('cid')) {
            return $selectedIDs;
        }

        // Default: explicit GET/POST parameter
        $selectedID = self::getID();

        return $selectedID ? [$selectedID] : [];
    }

    /**
     * Retrieves the specified parameter.
     *
     * @param   string  $property  Name of the property to get.
     * @param   string  $default   $default  Default value to return if variable does not exist.
     *
     * @return string
     */
    public static function getString(string $property, string $default = ''): string
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
        return (string) self::getInput()->get('task', 'display');
    }

    /**
     * Retrieves the view parameter.
     * @return string
     */
    public static function getView(): string
    {
        return (string) self::getInput()->get('view');
    }

    /**
     * Retrieves the layout parameter.
     * @return string
     */
    public static function layout(): string
    {
        return (string) self::getInput()->get('layout');
    }

    /**
     * Sets an input property with a value.
     *
     * @param   string  $property  the name of the property to set
     * @param   mixed   $value     the value to set to the property
     * @param   string  $method    the method group of the property
     *
     * @return void
     */
    public static function set(string $property, mixed $value, string $method = ''): void
    {
        if ($method) {
            self::getInput()->$method->set($property, $value);

            return;
        }

        self::getInput()->set($property, $value);
    }
}
