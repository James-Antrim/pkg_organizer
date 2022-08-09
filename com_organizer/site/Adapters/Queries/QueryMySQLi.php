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

use JDatabaseDriver;
use JDatabaseQuery;
use JDatabaseQueryElement;
use JDatabaseQueryMysqli;
use Joomla\CMS\Factory;
use Organizer\Adapters\Database;
use Organizer\Helpers\OrganizerHelper;

class QueryMySQLi extends JDatabaseQueryMysqli
{
	/**
	 * @inheritDoc
	 */
	public function __construct(JDatabaseDriver $db = null)
	{
		$db = !($db instanceof JDatabaseDriver) ? Factory::getDbo() : $db;
		parent::__construct($db);
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function andWhere($conditions, $glue = 'OR'): QueryMySQLi
	{
		return $this->extendWhere('AND', $conditions, $glue);
	}

	/**
	 * Wraps the JDatabaseQuery function to ensure return type compatibility.
	 *
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function call($columns): QueryMySQLi
	{
		return parent::call($columns);
	}

	/**
	 * Wraps the the clear function in its own function to minimize the display of "<statement> or DELIMITER expected" errors.
	 *
	 * @inheritDoc
	 *
	 * @return  QueryMySQLi  returns this object to allow chaining
	 */
	public function clear($clause = null): QueryMySQLi
	{
		return parent::clear($clause);
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function columns($columns): QueryMySQLi
	{
		$columns = $this->formatColumns($columns);

		return parent::columns($columns);
	}

	/**
	 * Add a table name to the DELETE clause of the query.
	 *
	 * @param   string  $table  the unique part of an organizer table name, or a complete table name
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function delete($table = null): QueryMySQLi
	{
		$alias = null;

		if ($table)
		{
			[$table, $alias] = $this->parseTable($table);
		}

		$alias = !$alias ? $alias : $this->quoteName($alias);

		$this->type   = 'delete';
		$this->delete = new JDatabaseQueryElement('DELETE', $alias);

		if ($table)
		{
			$table = $this->quoteName($table, $alias);
			$this->from($table);
		}

		return $this;
	}

	/**
	 * Adds the table to delete from in a query, optionally with a value filter.
	 *
	 * @param   string          $table   the unique part of an organizer table name, or a complete table name, optionally with alias
	 * @param   string          $where   the optional column to filter against
	 * @param   int[]|string[]  $in      the optional values to filter against
	 * @param   bool            $negate  if the predicate should be negated
	 * @param   bool            $quote   if the values should be quoted
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function deleteX(
		string $table,
		string $where = '',
		array $in = [],
		bool $negate = false,
		bool $quote = false
	): QueryMySQLi {
		$this->delete($table);

		if ($where and $in)
		{
			$this->wherein($where, $in, $negate, $quote);
		}

		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function extendWhere($outerGlue, $conditions, $innerGlue = 'AND'): QueryMySQLi
	{
		if ($this->where)
		{
			return parent::extendWhere($outerGlue, $conditions, $innerGlue);
		}

		if ($innerGlue === 'OR')
		{
			$conditions = '(' . implode(' OR ', $conditions) . ')';

			return $this->where($conditions);
		}

		return $this->where($conditions);
	}

	/**
	 * Explicitly resolves the passed column to query appropriate quoting.
	 *
	 * @param   string  $column  the 'column' to be resolved
	 *
	 * @return string
	 */
	private function formatColumn(string $column): string
	{
		// Ignore function and recursive calls.
		if (strpos($column, '(') !== false or strpos($column, '`') === 0)
		{
			return $column;
		}

		$column = trim($column);

		$distinct = stripos($column, 'DISTINCT ');
		if ($distinct !== false)
		{
			$column = preg_replace("/DISTINCT /i", '', $column);
		}

		$columnAlias = null;
		if (stripos($column, ' AS '))
		{
			[$column, $columnAlias] = preg_split("/ AS /i", $column);
		}

		$thingOne = $column;
		$thingTwo = '';
		if (strpos($column, '.'))
		{
			[$thingOne, $thingTwo] = explode('.', $column);
		}

		if ($thingTwo)
		{
			$column     = $thingTwo;
			$tableAlias = $thingOne;
		}
		else
		{
			$column     = $thingOne;
			$tableAlias = '';
		}

		if (strpos($column, '*') !== false)
		{
			// A column alias for a star select makes no
			return $tableAlias ? $this->quoteName($tableAlias) . '.*' : $column;
		}

		$column = $tableAlias ? "$tableAlias.$column" : $column;
		$column = $this->quoteName($column, $columnAlias);

		return $distinct === false ? $column : 'DISTINCT ' . $column;
	}

	/**
	 * Explicitly resolves the passed columns to query appropriate quoting.
	 *
	 * @param   array|string  $columns  the unique part of an organizer table name, or a complete table name
	 *
	 * @return string[]
	 */
	private function formatColumns($columns): array
	{
		$return = [];
		if (is_array($columns))
		{
			foreach ($columns as $column)
			{
				$return[] = $this->formatColumn($column);
			}
		}
		elseif (is_string($columns))
		{
			$columns = explode(',', $columns);

			foreach ($columns as $column)
			{
				$return[] = $this->formatColumn($column);
			}
		}
		else
		{
			OrganizerHelper::error(400);
		}

		return $return;
	}

	/**
	 * Supplements the component internal common portions of a table name. Already complete table names are not changed.
	 *
	 * @param   string  $table  the unique part of an organizer table name, or a complete table name
	 *
	 * @return string
	 */
	private function formatTable(string $table): string
	{
		[$table, $alias] = $this->parseTable($table);

		return $this->quoteName($table, $alias);
	}

	/**
	 * Supplements the component internal common portions of a table name. Already complete table names are not changed.
	 *
	 * @param   array|string  $tables  the unique part of an organizer table name, or a complete table name
	 *
	 * @return string[]
	 */
	private function formatTables($tables): array
	{
		$return = [];
		if (is_array($tables))
		{
			foreach ($tables as $table)
			{
				$return[] = $this->formatTable($table);
			}
		}
		elseif (is_string($tables))
		{
			$return = [$this->formatTable($tables)];
		}
		else
		{
			OrganizerHelper::error(400);
		}

		return $return;
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function from($tables, $subQueryAlias = null): QueryMySQLi
	{
		if ($tables instanceof JDatabaseQuery)
		{
			return parent::from($tables, $subQueryAlias);
		}

		$tables = $this->formatTables($tables);

		return parent::from($tables);
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function group($columns): QueryMySQLi
	{
		$columns = $this->formatColumns($columns);

		return parent::group($columns);
	}

	//todo: having?

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function innerJoin($condition): QueryMySQLi
	{
		return $this->join('INNER', $condition);
	}

	/**
	 * Shortcut to explicit inner joins with standardized condition aggregation.
	 *
	 * @param   string  $table       the table being joined
	 * @param   array   $conditions  the conditions for the join
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function innerJoinX(string $table, array $conditions): QueryMySQLi
	{
		return $this->joinX('INNER', $table, $conditions);
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function insert($table, $incrementField = false): QueryMySQLi
	{
		$table = $this->formatTable($table);

		return parent::insert($table, $incrementField);
	}

	/**
	 * Add a JOIN clause to the query.
	 *
	 * @param   string  $type        the type of join, prepended to the JOIN keyword
	 * @param   string  $conditions  the join conditions
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function join($type, $conditions): QueryMySQLi
	{
		[$table, $conditions] = preg_split("/ ON /i", $conditions);
		$conditions = preg_split("/ AND /i", $conditions);

		// Subquery results used as a table; subquery was hopefully independently formatted.
		$join = stripos($table, 'SELECT') !== false ? $table : $this->formatTable($table);
		$join .= $this->joinConditions($conditions);

		return parent::join($type, $join);
	}

	/**
	 * Formats and aggregates join conditions.
	 *
	 * @param   array  $conditions
	 *
	 * @return string the aggregated conditions
	 */
	private function joinConditions(array $conditions): string
	{
		$join    = '';
		$keyWord = 'ON';

		foreach ($conditions as $condition)
		{
			[$left, $right] = explode(" = ", $condition);
			$left  = $this->formatColumn($left);
			$right = $this->formatColumn($right);
			$join  .= " $keyWord $left = $right";

			if ($keyWord === 'ON')
			{
				$keyWord = 'AND';
			}
		}

		return $join;
	}

	/**
	 * Add a JOIN clause to the query.
	 *
	 * @param   string  $type        the type of join, prepended to the JOIN keyword
	 * @param   string  $table       the table being joined
	 * @param   array   $conditions  the join conditions
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function joinX(string $type, string $table, array $conditions): QueryMySQLi
	{
		// Subquery results used as a table; subquery was hopefully independently formatted.
		$join = stripos($table, 'SELECT') !== false ? $table : $this->formatTable($table);
		$join .= $this->joinConditions($conditions);

		return parent::join($type, $join);
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function leftJoin($condition): QueryMySQLi
	{
		return $this->join('LEFT', $condition);
	}

	/**
	 * Shortcut to explicit inner joins with standardized condition aggregation.
	 *
	 * @param   string  $table       the table being joined
	 * @param   array   $conditions  the conditions for the join
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function leftJoinX(string $table, array $conditions): QueryMySQLi
	{
		return $this->joinX('LEFT', $table, $conditions);
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function order($columns): QueryMySQLi
	{
		$this->formatColumns($columns);

		return parent::order($columns);
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function orWhere($conditions, $glue = 'AND'): QueryMySQLi
	{
		return $this->extendWhere('OR', $conditions, $glue);
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function outerJoin($condition): QueryMySQLi
	{
		return $this->join('OUTER', $condition);
	}

	/**
	 * Supplements the component internal common portions of a table name. Already complete table names are not changed.
	 *
	 * @param   string  $table  the unique part of an organizer table name, or a complete table name
	 *
	 * @return array
	 */
	private function parseTable(string $table): array
	{
		$table = str_replace('`', '', $table);

		// If this is an empty string Joomla will seriously use it as an alias, also given here as a null parameter in delete.
		$alias = null;
		if (stripos($table, ' AS '))
		{
			[$table, $alias] = preg_split("/ AS /i", $table);
		}

		$table = strpos($table, '#_') === 0 ? $table : "#__organizer_$table";

		return [$table, $alias];
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function rightJoin($condition): QueryMySQLi
	{
		return $this->join('RIGHT', $condition);
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function select($columns): QueryMySQLi
	{
		return parent::select($columns);
	}

	/**
	 * Provides a shortcut signature for a simple select query.
	 *
	 * @param   array|string    $select  the 'columns' to select
	 * @param   array|string    $from    the unique part of an organizer table name, or a complete table name
	 * @param   string          $where   the optional column to filter against
	 * @param   int[]|string[]  $in      the optional values to filter against
	 * @param   bool            $negate  if the predicate should be negated
	 * @param   bool            $quote   if the values should be quoted
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function selectX(
		$select,
		$from,
		string $where = '',
		array $in = [],
		bool $negate = false,
		bool $quote = false
	): QueryMySQLi {

		$select = $this->formatColumns($select);

		$this->select($select)->from($from);

		if ($where and $in)
		{
			$this->wherein($where, $in, $negate, $quote);
		}

		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function set($conditions, $glue = ','): QueryMySQLi
	{
		return parent::set($conditions, $glue);
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function update($table): QueryMySQLi
	{
		$table = $this->formatTable($table);

		return parent::update($table);
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function values($values): QueryMySQLi
	{
		return parent::values($values);
	}

	/**
	 * @inheritDoc
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function where($conditions, $glue = 'AND'): QueryMySQLi
	{
		return parent::where($conditions, $glue);
	}

	/**
	 * Adds value filter conditions to a query (WHERE <column> <NOT> IN <value set>).
	 *
	 * @param   string          $where   the column to filter against
	 * @param   int[]|string[]  $in      the values to filter against
	 * @param   bool            $negate  if the predicate should be negated
	 * @param   bool            $quote   if the values should be quoted
	 *
	 * @return QueryMySQLi returns this object to allow chaining
	 */
	public function wherein(string $where, array $in, bool $negate = false, bool $quote = false): QueryMySQLi
	{
		return $this->where($this->quoteName($where) . Database::makeSet($in, $negate, $quote));
	}
}