<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Adapters;

use Exception;
use Joomla\CMS\Factory;
use JDatabaseQuery;
use Joomla\Utilities\ArrayHelper;
use Organizer\Helpers;

/**
 * Adapts functions of the document class to avoid exceptions and deprecated warnings.
 */
class Database
{
	/**
	 * Execute the SQL statement.
	 *
	 * @return  bool  True on success, boolean false on failure.
	 */
	public static function execute()
	{
		$dbo = Factory::getDbo();
		try
		{
			return (bool) $dbo->execute();
		}
		catch (Exception $exception)
		{
			Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

			return false;
		}
	}

	/**
	 * Get the current query object or a new JDatabaseQuery object.
	 *
	 * @param   bool  $new  True to return a new JDatabaseQuery object, otherwise false
	 *
	 * @return  JDatabaseQuery  The current query object or a new object extending the JDatabaseQuery class.
	 */
	public static function getQuery($new = true)
	{
		return Factory::getDbo()->getQuery($new);
	}

	/**
	 * Inserts a row into a table based on an object's properties.
	 *
	 * @param   string  $table    The name of the database table to insert into.
	 * @param   object  &$object  A reference to an object whose public properties match the table fields.
	 * @param   string  $key      The name of the primary key. If provided the object property is updated.
	 *
	 * @return  bool    True on success.
	 */
	public static function insertObject(string $table, object &$object, $key = 'id')
	{
		$dbo = Factory::getDbo();

		try
		{
			return $dbo->insertObject($table, $object, $key);
		}
		catch (Exception $exc)
		{
			Helpers\OrganizerHelper::message($exc->getMessage(), 'error');

			return false;
		}
	}

	/**
	 * Method to get the first row of the result set from the database query as an associative array
	 * of ['field_name' => 'row_value'].
	 *
	 * @return  array  The return value or an empty array if the query failed.
	 */
	public static function loadAssoc()
	{
		$dbo = Factory::getDbo();
		try
		{
			$result = $dbo->loadAssoc();

			return $result ? $result : [];
		}
		catch (Exception $exception)
		{
			Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

			return [];
		}
	}

	/**
	 * Method to get an array of the result set rows from the database query where each row is an associative array
	 * of ['field_name' => 'row_value'].  The array of rows can optionally be keyed by a field name, but defaults to
	 * a sequential numeric array.
	 *
	 * NOTE: Chosing to key the result array by a non-unique field name can result in unwanted
	 * behavior and should be avoided.
	 *
	 * @param   string  $key     The name of a field on which to key the result array.
	 * @param   string  $column  An optional column name. Instead of the whole row, only this column value will be in
	 *                           the result array.
	 *
	 * @return  array   The return value or an empty array if the query failed.
	 */
	public static function loadAssocList($key = '', $column = '')
	{
		$dbo = Factory::getDbo();
		try
		{
			$result = $dbo->loadAssocList($key, $column);

			return $result ? $result : [];
		}
		catch (Exception $exception)
		{
			Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

			return [];
		}
	}

	/**
	 * Method to get the first field of the first row of the result set from the database query and return it as an int
	 * value.
	 *
	 * @param   bool  $default  the default value
	 *
	 * @return  int  The return value if successful, otherwise the default value
	 */
	public static function loadBool($default = false)
	{
		$result = self::loadResult();

		return $result !== null ? (bool) $result : $default;
	}

	/**
	 * Method to get an array of values from the <var>$offset</var> field in each row of the result set from
	 * the database query.
	 *
	 * @param   int  $offset  The row offset to use to build the result array.
	 *
	 * @return  array    The return value or null if the query failed.
	 */
	public static function loadColumn($offset = 0)
	{
		$dbo = Factory::getDbo();
		try
		{
			$result = $dbo->loadColumn($offset);

			return $result ? $result : [];
		}
		catch (Exception $exception)
		{
			Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

			return [];
		}
	}

	/**
	 * Method to get the first field of the first row of the result set from the database query and return it as an int
	 * value.
	 *
	 * @param   int  $default  the default value
	 *
	 * @return  int  The return value if successful, otherwise the default value
	 */
	public static function loadInt($default = 0)
	{
		$result = self::loadResult();

		return $result !== null ? (int) $result : $default;
	}

	/**
	 * Method to get an array of values from the <var>$offset</var> field in each row of the result set from
	 * the database query.
	 *
	 * @param   int  $offset  The row offset to use to build the result array.
	 *
	 * @return  array    The return value or null if the query failed.
	 */
	public static function loadIntColumn($offset = 0)
	{
		if ($result = self::loadColumn($offset))
		{
			return ArrayHelper::toInteger($result);
		}

		return $result;
	}

	/**
	 * Method to get the first row of the result set from the database query as an object.
	 *
	 * @param   string  $class  The class name to use for the returned row object.
	 *
	 * @return  array  The return value or an empty array if the query failed.
	 */
	public static function loadObject($class = 'stdClass')
	{
		$dbo = Factory::getDbo();
		try
		{
			$result = $dbo->loadObject($class);

			return $result ? $result : [];
		}
		catch (Exception $exception)
		{
			Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

			return [];
		}
	}

	/**
	 * Method to get an array of the result set rows from the database query where each row is an object.  The array
	 * of objects can optionally be keyed by a field name, but defaults to a sequential numeric array.
	 *
	 * NOTE: Choosing to key the result array by a non-unique field name can result in unwanted
	 * behavior and should be avoided.
	 *
	 * @param   string  $key    The name of a field on which to key the result array.
	 * @param   string  $class  The class name to use for the returned row objects.
	 *
	 * @return  array   The return value or an empty array if the query failed.
	 */
	public static function loadObjectList($key = '', $class = 'stdClass')
	{
		$dbo = Factory::getDbo();
		try
		{
			$result = $dbo->loadObjectList($key, $class);

			return $result ? $result : [];
		}
		catch (Exception $exception)
		{
			Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

			return [];
		}
	}

	/**
	 * Method to get the first field of the first row of the result set from the database query.
	 *
	 * @param   mixed  $default  the default return value
	 *
	 * @return  mixed  The return value if successful, otherwise the default value
	 */
	public static function loadResult($default = null)
	{
		$dbo = Factory::getDbo();
		try
		{
			$result = $dbo->loadResult();

			return $result ? $result : $default;
		}
		catch (Exception $exception)
		{
			Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

			return $default;
		}
	}

	/**
	 * Method to get the first field of the first row of the result set from the database query and return it as an int
	 * value.
	 *
	 * @param   string  $default  the default return value
	 *
	 * @return  int  The return value if successful, otherwise the default value
	 */
	public static function loadString($default = '')
	{
		$result = self::loadResult();

		return $result ? (string) self::loadResult() : $default;
	}

	/**
	 * Sets the SQL statement string for later execution.
	 *
	 * @param   JDatabaseQuery|string  $query   The SQL statement to set either as a JDatabaseQuery object or a string.
	 * @param   int                    $offset  The affected row offset to set.
	 * @param   int                    $limit   The maximum affected rows to set.
	 *
	 * @return  void
	 */
	public static function setQuery($query, $offset = 0, $limit = 0)
	{
		Factory::getDbo()->setQuery($query, $offset, $limit);
	}
}
