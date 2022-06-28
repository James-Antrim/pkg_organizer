<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use JDatabaseQuery;
use Joomla\Utilities\ArrayHelper;

class Query
{
	/**
	 * @param   string          $column  the column to filter against
	 * @param   int[]|string[]  $values  the values to filter with
	 * @param   bool            $not     if the predicate should be negated
	 * @param   bool            $quote   if the values should be quoted
	 *
	 * @return void
	 */
	public static function filter(JDatabaseQuery $query, string $column, array $values, bool $not = false, bool $quote = false)
	{
		$values    = $quote ? implode("','", $values) : implode(',', ArrayHelper::toInteger($values));
		$predicate = $not ? " NOT IN ($values)" : " IN ($values)";
		$query->where($query->quoteName($column) . $predicate);
	}
}