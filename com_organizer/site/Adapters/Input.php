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

    public const BINARY = [self::NO, self::YES];

    // (File) Formats
    public const HTML = 'html', ICS = 'ics', JSON = 'json', PDF = 'pdf', VCF = 'vcf', XLS = 'xls', XML = 'xml';

    private static InputFilter $filter;
    private static Registry $filters;
    private static Core $input;
    private static Registry $lists;
    private static Registry $parameters;

    /**
     * Retrieves the specified array from the input context.
     *
     * @param string $name    the name of the array item
     * @param array  $default the default array
     *
     * @return array
     */
    public static function array(string $name, array $default = []): array
    {
        return self::instance()->get($name, $default, 'array');
    }

    /**
     * Retrieves the batch items from the request and creates a registry with the data.
     * @return Registry
     */
    public static function batches(): Registry
    {
        return new Registry(self::array('batch'));
    }

    /**
     * Retrieves the specified boolean from the input context.
     *
     * @param string $property Name of the property to get.
     * @param mixed  $default  Default value to return if variable does not exist.
     *
     * @return bool
     */
    public static function bool(string $property, bool $default = false): bool
    {
        $value = self::find($property);

        if ($value === null) {
            return self::filter($default, 'bool');
        }

        return self::filter($value, 'bool');
    }

    /**
     * Retrieves the specified string value with a CMD filter.
     *
     * @param string $property Name of the property to get.
     * @param mixed  $default  Default value to return if variable does not exist.
     *
     * @return string
     */
    public static function cmd(string $property, string $default = ''): string
    {
        return ($value = self::find($property)) ? self::filter($value, 'cmd') : self::filter($default, 'cmd');
    }

    /**
     * Retrieves the controller parameter with the view parameter as fallback.
     * @return string
     */
    public static function controller(): string
    {
        return (string) self::instance()->get('controller') ?: self::view();
    }

    /**
     * Filters the given source data according to the type parameter.
     *
     * @param mixed  $source the data to be filtered
     * @param string $type   the type against which to filter the source data
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
     * Retrieves the filter items from the request and creates a registry with the data.
     * @return Registry
     */
    public static function filters(): Registry
    {
        if (empty(self::$filters)) {
            $view     = self::view();
            $previous = Application::session()->get('registry')->get("com_organizer.$view.filter", []);

            self::$filters = new Registry(self::array('filter', $previous));
        }

        return self::$filters;
    }

    /**
     * Retrieves the specified parameter.
     *
     * @param string $property Name of the property to get.
     *
     * @return mixed the value found, or false if the property could not be found
     */
    private static function find(string $property): mixed
    {
        if ($value = self::instance()->get($property, null, 'raw')) {
            return $value;
        }

        if ($filters = self::filters() and $value = $filters->get($property)) {
            return $value;
        }

        if ($lists = self::lists() and $value = $lists->get($property)) {
            return $value;
        }

        if ($value = self::parameters()->get($property)) {
            return $value;
        }

        return null;
    }

    /**
     * Retrieves the specified float from the input context.
     *
     * @param string $property Name of the property to get.
     * @param float  $default  Default value to return if variable does not exist.
     *
     * @return float
     */
    public static function float(string $property, float $default = 0.0): float
    {
        $value = self::find($property);

        // Better plausibility test for this type whose value can also be 0 on purpose and otherwise evaluate to false.
        if (is_numeric($value)) {
            return self::filter($value, 'float');
        }

        return self::filter($default, 'float');
    }

    /**
     * Accessor for the format parameter and document type. Joomla handles them redundantly internally leading to format
     * overwrites to html if the document type is not explicitly set.
     *
     * @param string $format
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
     * Retrieves the id parameter.
     * @return int
     */
    public static function id(): int
    {
        return self::integer('id');
    }

    /**
     * Returns the application's input object.
     * @return Core
     */
    public static function instance(): Core
    {
        if (empty(self::$input)) {
            self::$input = Application::instance()->input;
        }

        return self::$input;
    }

    /**
     * Retrieves the specified parameter.
     *
     * @param string $property Name of the property to get
     * @param int    $default  Default value to return if variable does not exist
     * @param string $method   Explicit method where the value should be
     *
     * @return int
     */
    public static function integer(string $property, int $default = 0, string $method = ''): int
    {
        if ($method) {
            $value = self::instance()->$method->get($property, $default, 'raw');
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
    public static function itemID(): int
    {
        $item    = Application::menuItem();
        $default = $item ? $item->id : 0;

        return self::integer('Itemid', $default);
    }

    /**
     * Retrieves the layout parameter.
     * @return string
     */
    public static function layout(): string
    {
        return (string) self::instance()->get('layout');
    }

    /**
     * Retrieves the list items from the request and creates a registry with the data.
     * @return Registry
     */
    public static function lists(): Registry
    {
        if (empty(self::$lists)) {
            $view        = self::view();
            $previous    = Application::session()->get('registry')->get("com_organizer.$view.list", []);
            self::$lists = new Registry(self::array('list', $previous));
        }

        return self::$lists;
    }

    /**
     * Consolidates the application, component and menu parameters to a single registry with one call.
     * @return Registry
     */
    public static function parameters(): Registry
    {
        if (empty(self::$parameters)) {
            $app              = Application::instance();
            self::$parameters = method_exists($app, 'getParams') ?
                $app->getParams() : Application::parameters();
        }

        return self::$parameters;
    }

    /**
     * Retrieves form data / POST.
     * @return array
     */
    public static function post(): array
    {
        return self::instance()->post->getArray();
    }

    /**
     * Gets the URL of the referrer.
     * @return string
     */
    public static function referrer(): string
    {
        // The command filter removes syntax elements from the URL
        return (string) self::instance()->server->get('HTTP_REFERER', null, 'string');
    }

    /**
     * Removes tags without visual output from an HTML markup string.
     *
     * @param string $original
     *
     * @return string
     */
    public static function removeEmptyTags(string $original): string
    {
        $pattern = "/<[^\/>]*>([\s|&nbsp;]?)*<\/[^>]*>/";
        $cleaned = preg_replace($pattern, '', $original);

        // If the text remains unchanged there is no more to be done => bubble up
        if ($original === $cleaned) {
            return $original;
        }

        // There could still be further empty tags which encased the original empties.
        return self::removeEmptyTags($cleaned);
    }

    /**
     * Gets the selected resources as an array.
     *
     * @param string $field
     *
     * @return int[]
     */
    public static function resourceIDs(string $field): array
    {
        $alt   = str_ends_with($field, 's') ? substr($field, -1) : $field . 's';
        $value = self::find($field) ?? self::find($alt);

        // Null (not found) and zero (invalid)
        if (!$value) {
            return [];
        }

        if (is_array($value)) {
            return array_filter(array_map('intval', $value));
        }

        if (is_int($value)) {
            return [$value];
        }

        // Unsupported type
        if (!is_string($value)) {
            return [];
        }

        if (is_numeric($value)) {
            return [(int) $value];
        }

        // Array represented as CS values
        return array_filter(array_map('intval', explode(',', $value)));
    }

    /**
     * Returns the selected resource id.
     *
     * @param int $default the default value
     *
     * @return int
     */
    public static function selectedID(int $default = 0): int
    {
        $selectedIDs = self::selectedIDs();

        return empty($selectedIDs) ? $default : $selectedIDs[0];
    }

    /**
     * Returns the selected resource ids.
     * @return int[]
     */
    public static function selectedIDs(): array
    {
        // List Views
        if ($selectedIDs = self::resourceIDs('cid')) {
            return $selectedIDs;
        }

        // Default: explicit GET/POST parameter
        $selectedID = self::id();

        return $selectedID ? [$selectedID] : [];
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
    public static function set(string $property, mixed $value, string $method = ''): void
    {
        if ($method) {
            self::instance()->$method->set($property, $value);

            return;
        }

        self::instance()->set($property, $value);
    }

    /**
     * Retrieves the specified parameter.
     *
     * @param string $property Name of the property to get.
     * @param string $default  $default  Default value to return if variable does not exist.
     *
     * @return string
     */
    public static function string(string $property, string $default = ''): string
    {
        if ($value = self::find($property)) {
            return self::filter($value);
        }

        return self::filter($default);
    }

    /**
     * Retrieves the task parameter.
     * @return string
     */
    public static function task(): string
    {
        return (string) self::instance()->get('task', 'display');
    }

    /**
     * Ensures the requested value is valid and returns the default value if not.
     *
     * @param string   $field  the field with the cmd value
     * @param string[] $values the values to validate against
     *
     * @return string
     */
    public static function validCMD(string $field, array $values): string
    {
        $value = Input::cmd($field);

        return in_array($value, $values) ? $value : $values['default'];
    }

    /**
     * Ensures the requested value is valid and returns the default value if not.
     *
     * @param string $field  the field with the int value
     * @param int[]  $values the values to validate against
     *
     * @return int
     */
    public static function validInt(string $field, array $values): int
    {
        $value = self::integer($field);

        return in_array($value, $values) ? $value : 0;
    }

    /**
     * Retrieves the view parameter.
     * @return string
     */
    public static function view(): string
    {
        return (string) self::instance()->get('view');
    }
}
