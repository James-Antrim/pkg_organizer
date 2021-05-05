<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use JDatabaseQuery;
use Organizer\Adapters\Database;
use Organizer\Helpers;

/**
 * Class creates a generalized select box for selection of a single id column value among those already selected.
 */
class MergeAssociationsField extends OptionsField
{
	/**
	 * @var  string
	 */
	protected $type = 'MergeAssociations';

	/**
	 * Returns a select box where resource attributes can be selected
	 *
	 * @return array the options for the select box
	 */
	protected function getOptions()
	{
		$default     = [Helpers\HTML::_('select.option', '', Helpers\Languages::_('ORGANIZER_NONE_GIVEN'))];
		$selectedIDs = Helpers\Input::getSelectedIDs();
		$valueColumn = $this->getAttribute('name');
		if (empty($selectedIDs) or empty($valueColumn))
		{
			return $default;
		}

		$query      = Database::getQuery();
		$textColumn = $this->resolveTextColumn($query);

		if (empty($textColumn))
		{
			return $default;
		}

		$query->select("DISTINCT $valueColumn AS value, $textColumn AS text")->order('text');

		// 1 => table, 2 => alias, 4 => conditions
		$pattern = '/([a-z_]+) AS ([a-z]+)( ON ([a-z]+\.[A-Za-z]+ = [a-z]+\.[A-Za-z]+))?/';
		$from    = $this->getAttribute('from', '');

		$validFrom = preg_match($pattern, $from, $parts);
		if (!$validFrom)
		{
			return $default;
		}

		$external = (bool) $this->getAttribute('external', false);
		$from     = $external ? "#__$from" : "#__organizer_$from";

		$alias = $parts[2];
		$query->from($from)->where("$alias.id IN ( '" . implode("', '", $selectedIDs) . "' )");

		$innerJoins = explode(',', $this->getAttribute('innerJoins', ''));

		foreach ($innerJoins as $innerJoin)
		{
			$validJoin = preg_match($pattern, $innerJoin, $parts);
			if (!$validJoin)
			{
				return $default;
			}

			$query->innerJoin("#__organizer_$innerJoin");
		}

		Database::setQuery($query);

		if (!$valuePairs = Database::loadAssocList())
		{
			return $default;
		}

		$options = [];
		foreach ($valuePairs as $valuePair)
		{
			$options[] = Helpers\HTML::_('select.option', $valuePair['value'], $valuePair['text']);
		}

		if (empty($options))
		{
			$options = $default;
		}
		elseif (count($options) > 1)
		{
			array_unshift(
				$options,
				Helpers\HTML::_('select.option', '', Helpers\Languages::_('ORGANIZER_SELECT_VALUE'))
			);
		}

		return $options;
	}

	/**
	 * Resolves the textColumns for localization and concatenation of column names
	 *
	 * @param   JDatabaseQuery  $query  the query to modify
	 *
	 * @return string  the string to use for text selection
	 */
	private function resolveTextColumn($query)
	{
		$textColumn  = $this->getAttribute('textcolumn', '');
		$textColumns = explode(',', $textColumn);
		$localized   = $this->getAttribute('localized', false);

		if ($localized)
		{
			$textColumns[0] = $textColumns[0] . '_' . Helpers\Languages::getTag();
		}

		$glue = $this->getAttribute('glue');

		if (count($textColumns) === 1 or empty($glue))
		{
			return $textColumns[0];
		}

		return '( ' . $query->concatenate($textColumns, $glue) . ' )';
	}
}
