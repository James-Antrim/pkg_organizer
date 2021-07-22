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

use Organizer\Tables;

/**
 * Abstract static class with functions returning name like resource attributes.
 */
abstract class ResourceHelper
{
	/**
	 * Attempts to retrieve the code of the resource.
	 *
	 * @param   int  $resourceID  the id of the resource
	 *
	 * @return string
	 */
	public static function getAbbreviation(int $resourceID): string
	{
		return self::getNameAttribute('abbreviation', $resourceID);
	}

	/**
	 * Attempts to retrieve the code of the resource.
	 *
	 * @param   int  $resourceID  the id of the resource
	 *
	 * @return string
	 */
	public static function getCode(int $resourceID): string
	{
		return self::getNameAttribute('code', $resourceID);
	}

	/**
	 * Attempts to retrieve the name of the resource.
	 *
	 * @param   int  $resourceID  the id of the resource
	 *
	 * @return string
	 */
	public static function getFullName(int $resourceID): string
	{
		return self::getNameAttribute('fullName', $resourceID);
	}

	/**
	 * Attempts to retrieve the name of the resource.
	 *
	 * @param   string  $columnName  the substatiative part of the column name to search for
	 * @param   int     $resourceID  the id of the resource
	 *
	 * @return string
	 */
	public static function getNameAttribute(string $columnName, int $resourceID): string
	{
		$table = self::getTable();
		if (!$table->load($resourceID))
		{
			return '';
		}

		$tableFields = $table->getFields();
		if (array_key_exists($columnName, $tableFields))
		{
			// Some name columns may contain a null value
			return (string) $table->$columnName;
		}

		$localizedName = "{$columnName}_" . Languages::getTag();
		if (array_key_exists($localizedName, $tableFields))
		{
			// Some name columns may contain a null value
			return (string) $table->$localizedName;
		}

		return '';
	}

	/**
	 * Attempts to retrieve the name of the resource.
	 *
	 * @param   int  $resourceID  the id of the resource
	 *
	 * @return string
	 */
	public static function getName(int $resourceID): string
	{
		return self::getNameAttribute('name', $resourceID);
	}

	/**
	 * Attempts to retrieve the plural of the resource.
	 *
	 * @param   int  $resourceID  the id of the resource
	 *
	 * @return string
	 */
	public static function getPlural(int $resourceID): string
	{
		return self::getNameAttribute('plural', $resourceID);
	}

	/**
	 * Attempts to retrieve the name of the resource.
	 *
	 * @param   int  $resourceID  the id of the resource
	 *
	 * @return string
	 */
	public static function getShortName(int $resourceID): string
	{
		return self::getNameAttribute('shortName', $resourceID);
	}

	/**
	 * Returns a table based on the called class.
	 *
	 * @return Tables\BaseTable
	 */
	public static function getTable(): Tables\BaseTable
	{
		$tableClass = OrganizerHelper::getClass(get_called_class());
		$fqn        = "\\Organizer\\Tables\\$tableClass";

		return new $fqn();
	}
}
