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

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Organizer\Helpers;

/**
 * Abstract class extending Table.
 */
abstract class BaseTable extends Table
{
	/**
	 * The primary key.
	 * INT (UN)SIGNED (11|20) NOT NULL AUTO_INCREMENT
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Object constructor to set table and key fields.
	 *
	 * @param   string  $table  Name of the table to model.
	 */
	public function __construct(string $table)
	{
		$dbo = Factory::getDbo();
		parent::__construct($table, 'id', $dbo);
	}

	/**
	 * Wraps the parent load function in a try catch clause to avoid redundant handling in other classes.
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
		catch (Exception $exception)
		{
			Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

			return false;
		}
	}

	/**
	 * Sets a given property from a table, loading the table as necessary.
	 *
	 * @param   string  $column   the name of the property to set
	 * @param   mixed   $value    the value to set the property to
	 * @param   mixed   $default  the default value to use if the value parameter is empty
	 *
	 * @return void modifies the column property value
	 */
	public function setColumn($column, $value, $default)
	{
		if (property_exists($this, $column))
		{
			$this->$column = empty($value) ? $default : $value;
		}
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
