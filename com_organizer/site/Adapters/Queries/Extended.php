<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Adapters\Queries;

use JDatabaseQuery;
use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;

trait Extended
{
	/**
	 * @param   string          $column  the column to filter against
	 * @param   int[]|string[]  $values  the values to filter with
	 * @param   bool            $not     if the predicate should be negated
	 * @param   bool            $quote   if the values should be quoted
	 *
	 * @return  JDatabaseQuery  Returns this object to allow chaining.
	 */
	public function filter(string $column, array $values, bool $not = false, bool $quote = false): JDatabaseQuery
	{
		$values    = $quote ? implode("','", $values) : implode(',', ArrayHelper::toInteger($values));
		$predicate = $not ? " NOT IN ($values)" : " IN ($values)";

		/* @var JDatabaseQuery $this */
		return $this->where($this->quoteName($column) . $predicate);
	}

	/**
	 * An overwrite for simpler from clause creation.
	 *
	 * @param   string  $table
	 * @param   string  $alias
	 *
	 * @return JDatabaseQuery
	 */
	public function fromX(string $table, string $alias = ''): JDatabaseQuery
	{
		$alias = $alias ?: null;
		$dbo = Factory::getDbo();
		$table = strpos($table, '#_') === 0 ? $table : "#__organizer_$table";

		return $this->from($dbo->quoteName($table, $alias));
	}
}