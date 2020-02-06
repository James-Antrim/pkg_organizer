<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Tables;

use InvalidArgumentException;
use JDatabaseDriver;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Organizer\Helpers\OrganizerHelper;
use RuntimeException;
use UnexpectedValueException;

/**
 * Abstract class extending Table by adding a getter and setter method for individual properties and suppresses
 * redundant exceptions from the load function.
 */
abstract class BaseTable extends Table
{
	/**
	 * The primary key.
	 * INT(11) UNSIGNED NOT NULL AUTO_INCREMENT
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Object constructor to set table and key fields.  In most cases this will
	 * be overridden by child classes to explicitly set the table and key fields
	 * for a particular database table.
	 *
	 * @param   string           $table  Name of the table to model.
	 * @param   mixed            $key    Name of the primary key field or array of composite primary field names.
	 * @param   JDatabaseDriver  $db     JDatabaseDriver object.
	 */
	public function __construct($table, $key, $db = null)
	{
		$db = empty($db) ? Factory::getDbo() : $db;
		parent::__construct($table, $key, $db);
	}

	/**
	 * Gets a given property from a table, loading the table as necessary.
	 *
	 * @param   string  $property  the name of the property to retrieve
	 * @param   mixed   $keys      an optional primary key value to load the row by, or an array of fields to match
	 * @param   mixed   $default   the default value to return if the property was empty or non-existent
	 *
	 * @return mixed the property value on success, otherwise null
	 */
	public function getProperty($property, $keys = null, $default = null)
	{
		if (empty($this->id) and !$this->load($keys))
		{
			return $default;
		}

		return $this->$property;
	}

	/**
	 * Method to load a row from the database by primary key and bind the fields to the Table instance properties. Wraps
	 * the parent load function in a try catch clause to avoid redundant exception handling in other classes.
	 *
	 * @param   mixed    $keys   An optional primary key value to load the row by, or an array of fields to match.
	 *                           If not set the instance property value is used.
	 * @param   boolean  $reset  True to reset the default values before loading the new row.
	 *
	 * @return  boolean  True if successful, otherwise false
	 */
	public function load($keys = null, $reset = true)
	{
		try
		{
			return parent::load($keys, $reset);
		}
		catch (InvalidArgumentException $exception)
		{
			OrganizerHelper::message($exception->getMessage(), 'error');

			return false;
		}
		catch (RuntimeException $exception)
		{
			OrganizerHelper::message($exception->getMessage(), 'error');

			return false;
		}
		catch (UnexpectedValueException $exception)
		{
			OrganizerHelper::message($exception->getMessage(), 'error');

			return false;
		}
	}

	/**
	 * Sets a given property from a table, loading the table as necessary.
	 *
	 * @param   string  $property  the name of the property to set
	 * @param   mixed   $value     the value to set the property to
	 * @param   mixed   $keys      an optional primary key value to load the row by, or an array of fields to match
	 *
	 * @return bool true on success, otherwise false
	 */
	public function setProperty($property, $value, $keys = null)
	{
		if (empty($this->id) and !$this->load($keys))
		{
			return false;
		}

		$this->$property = $value;

		return $this->store();
	}

	/**
	 * Allows null values to be set without explicit parameterised calls to the store function.
	 *
	 * @param   bool  $updateNulls  true to update fields even if they are null.
	 *
	 * @return bool  true on success.
	 */
	public function store($updateNulls = true)
	{
		return parent::store($updateNulls);
	}
}
