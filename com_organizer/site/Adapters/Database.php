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

use Exception;
use JDatabaseQuery;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Utilities\ArrayHelper;
use THM\Organizer\Helpers;
use stdClass;

/**
 * Adapts functions of the document class to avoid exceptions and deprecated warnings.
 */
class Database
{
    /**
     * Execute the SQL statement.
     * @return  bool  True on success, bool false on failure.
     */
    public static function execute(): bool
    {
        $dbo = Factory::getDbo();
        try {
            return (bool) $dbo->execute();
        } catch (Exception $exception) {
            self::logException($exception);
            Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

            return false;
        }
    }

    /**
     * Retrieves the null date (time) specific to the driver.
     * @return  string  the driver specific null date (time).
     */
    public static function getNullDate(): string
    {
        $dbo = Factory::getDbo();

        return $dbo->getNullDate();
    }

    /**
     * Get the current query object or a new JDatabaseQuery object.
     *
     * @param bool $new True to return a new JDatabaseQuery object, otherwise false
     *
     * @return  JDatabaseQuery|string  The current query object or a new object extending the JDatabaseQuery class.
     */
    public static function getQuery(bool $new = true)
    {
        $dbo = Factory::getDbo();

        if (strtolower($dbo->getName()) !== 'mysqli') {
            Application::error(501);
        }

        return $new ? new Queries\QueryMySQLi() : $dbo->getQuery();
    }

    /**
     * Inserts a row into a table based on an object's properties.
     *
     * @param string   $table  The name of the database table to insert into.
     * @param object  &$object A reference to an object whose public properties match the table fields.
     * @param string   $key    The name of the primary key. If provided the object property is updated.
     *
     * @return  bool    True on success.
     */
    public static function insertObject(string $table, object &$object, string $key = 'id'): bool
    {
        $dbo = Factory::getDbo();

        try {
            return $dbo->insertObject($table, $object, $key);
        } catch (Exception $exception) {
            self::logException($exception);
            Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

            return false;
        }
    }

    /**
     * Method to get the first row of the result set from the database query as an associative array
     * of ['field_name' => 'row_value'].
     * @return  array  The return value or an empty array if the query failed.
     */
    public static function loadAssoc(): array
    {
        $dbo = Factory::getDbo();
        try {
            $result = $dbo->loadAssoc();

            return $result ?: [];
        } catch (Exception $exception) {
            self::logException($exception);
            Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

            return [];
        }
    }

    /**
     * Method to get an array of the result set rows from the database query where each row is an associative array
     * of ['field_name' => 'row_value'].  The array of rows can optionally be keyed by a field name, but defaults to
     * a sequential numeric array.
     * NOTE: Choosing to key the result array by a non-unique field name can result in unwanted
     * behavior and should be avoided.
     *
     * @param string $key        The name of a field on which to key the result array.
     * @param string $column     An optional column name. Instead of the whole row, only this column value will be in
     *                           the result array.
     *
     * @return  array[]   The return value or an empty array if the query failed.
     */
    public static function loadAssocList(string $key = '', string $column = ''): array
    {
        $dbo = Factory::getDbo();
        try {
            $result = $dbo->loadAssocList($key, $column);

            return $result ?: [];
        } catch (Exception $exception) {
            self::logException($exception);
            Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

            return [];
        }
    }

    /**
     * Method to get the first field of the first row of the result set from the database query and return it as an int
     * value.
     *
     * @param bool $default the default value
     *
     * @return  bool  The return value if successful, otherwise the default value
     */
    public static function loadBool(bool $default = false): bool
    {
        $result = self::loadResult();

        return $result !== null ? (bool) $result : $default;
    }

    /**
     * Method to get an array of values from the <var>$offset</var> field in each row of the result set from
     * the database query.
     *
     * @param int $offset The row offset to use to build the result array.
     *
     * @return  array    The return value or null if the query failed.
     */
    public static function loadColumn(int $offset = 0): array
    {
        $dbo = Factory::getDbo();
        try {
            $result = $dbo->loadColumn($offset);

            return $result ?: [];
        } catch (Exception $exception) {
            self::logException($exception);
            Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

            return [];
        }
    }

    /**
     * Method to get the first field of the first row of the result set from the database query and return it as an int
     * value.
     *
     * @param int $default the default value
     *
     * @return  int  The return value if successful, otherwise the default value
     */
    public static function loadInt(int $default = 0): int
    {
        $result = self::loadResult();

        return $result !== null ? (int) $result : $default;
    }

    /**
     * Method to get an array of values from the <var>$offset</var> field in each row of the result set from
     * the database query.
     *
     * @param int $offset The row offset to use to build the result array.
     *
     * @return  int[]    The return value or null if the query failed.
     */
    public static function loadIntColumn(int $offset = 0): array
    {
        if ($result = self::loadColumn($offset)) {
            return ArrayHelper::toInteger($result);
        }

        return $result;
    }

    /**
     * Method to get the first row of the result set from the database query as an object.
     *
     * @param string $class The class name to use for the returned row object.
     *
     * @return  object  The return value or an empty array if the query failed.
     */
    public static function loadObject(string $class = 'stdClass')
    {
        $dbo = Factory::getDbo();
        try {
            $result = $dbo->loadObject($class);

            return $result ?: new stdClass();
        } catch (Exception $exception) {
            self::logException($exception);
            Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

            return new stdClass();
        }
    }

    /**
     * Method to get an array of the result set rows from the database query where each row is an object.  The array
     * of objects can optionally be keyed by a field name, but defaults to a sequential numeric array.
     * NOTE: Choosing to key the result array by a non-unique field name can result in unwanted
     * behavior and should be avoided.
     *
     * @param string $key   The name of a field on which to key the result array.
     * @param string $class The class name to use for the returned row objects.
     *
     * @return  array   The return value or an empty array if the query failed.
     */
    public static function loadObjectList(string $key = '', string $class = 'stdClass'): array
    {
        $dbo = Factory::getDbo();
        try {
            $result = $dbo->loadObjectList($key, $class);

            return $result ?: [];
        } catch (Exception $exception) {
            self::logException($exception);
            Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

            return [];
        }
    }

    /**
     * Method to get the first field of the first row of the result set from the database query.
     *
     * @param mixed $default the default return value
     *
     * @return  mixed  The return value if successful, otherwise the default value
     */
    public static function loadResult($default = null)
    {
        $dbo = Factory::getDbo();
        try {
            $result = $dbo->loadResult();

            return $result ?: $default;
        } catch (Exception $exception) {
            self::logException($exception);
            Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

            return $default;
        }
    }

    /**
     * Method to get the first field of the first row of the result set from the database query and return it as an int
     * value.
     *
     * @param string $default the default return value
     *
     * @return  string  The return value if successful, otherwise the default value
     */
    public static function loadString(string $default = ''): string
    {
        $result = self::loadResult();

        return $result ? (string) self::loadResult() : $default;
    }

    /**
     * Logs the exception.
     *
     * @param Exception $exception
     *
     * @return void
     */
    private static function logException(Exception $exception)
    {
        $options = ['text_file' => 'organizer_db_errors.php', 'text_entry_format' => '{DATETIME}:{MESSAGE}'];
        Log::addLogger($options, Log::ALL, ['com_organizer.dbErrors']);
        $message = "\n\nError Message:\n--------------\n";
        $message .= print_r($exception->getMessage(), true);
        $message .= "\n\nQuery:\n------\n";
        $message .= print_r((string) self::getQuery(false), true);
        $message .= "\n\nCall Stack:\n-----------\n";
        $message .= print_r($exception->getTraceAsString(), true);
        $message .= "\n\n--------------------------------------------------------------------------------------------";
        $message .= "--------------------------------------";
        Log::add($message, Log::DEBUG, 'com_organizer.dbErrors');
    }

    /**
     * Formats the values to form a set used in the predicate of a query restriction. <NOT> IN <value set>
     *
     * @param array $values the values to aggregate
     * @param bool  $negate whether the set should be negated
     * @param false $quote  whether to quote the values
     *
     * @return string the comma separated values surrounded by braces
     */
    public static function makeSet(array $values, bool $negate = false, bool $quote = false): string
    {
        $values = $quote ? self::quote($values) : ArrayHelper::toInteger($values);
        $values = implode(',', $values);

        return $negate ? " NOT IN ($values)" : " IN ($values)";
    }

    /**
     * Wraps the database quote function for use outside a query class without PhpStorm complaining about resolution.
     *
     * @param string|string[] $term   the term or terms to quote
     * @param bool            $escape whether to escape the name provided
     *
     * @return string|string[] an accurate representation of what is actually returned from the dbo quoteName function
     */
    public static function quote($term, bool $escape = true)
    {
        return Factory::getDbo()->quote($term, $escape);
    }

    /**
     * Wraps the database quote name function for use outside a query class without PhpStorm complaining about
     * resolution.
     *
     * @param string|string[]   $name  the column name or names
     * @param array|null|string $alias the column alias or aliases, if arrays and incongruent sizes => empty array
     *                                 return value
     *
     * @return string|string[] an accurate representation of what is actually returned from the dbo quoteName function
     */
    public static function quoteName($name, $alias = null)
    {
        return Factory::getDbo()->quoteName($name, $alias);
    }

    /**
     * Sets the SQL statement string for later execution.
     *
     * @param JDatabaseQuery|string $query  The SQL statement to set either as a JDatabaseQuery object or a string.
     * @param int                   $offset The affected row offset to set.
     * @param int                   $limit  The maximum affected rows to set.
     *
     * @return  void
     */
    public static function setQuery($query, int $offset = 0, int $limit = 0)
    {
        Factory::getDbo()->setQuery($query, $offset, $limit);
    }
}
