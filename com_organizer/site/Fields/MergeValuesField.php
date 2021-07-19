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

use Organizer\Adapters\Database;
use Organizer\Helpers;

/**
 * Class creates a generalized select box for selection of a single column value among those already selected.
 */
class MergeValuesField extends OptionsField
{
	/**
	 * @var  string
	 */
	protected $type = 'MergeValues';

	/**
	 * Returns a select box where resource attributes can be selected
	 *
	 * @return array the options for the select box
	 */
	protected function getOptions(): array
	{
		$selectedIDs    = Helpers\Input::getSelectedIDs();
		$resource       = str_replace('_merge', '', Helpers\Input::getView());
		$validResources = ['category', 'field', 'group', 'method', 'room', 'roomtype', 'participant', 'person'];
		$invalid        = (empty($selectedIDs) or empty($resource) or !in_array($resource, $validResources));
		if ($invalid)
		{
			return [];
		}

		$column = $this->getAttribute('name');
		$query  = Database::getQuery(true);
		$table  = $resource === 'category' ? 'categories' : "{$resource}s";
		$query->select("DISTINCT $column AS value")
			->from("#__organizer_$table")
			->where("id IN ( '" . implode("', '", $selectedIDs) . "' )")
			->order('value ASC');
		Database::setQuery($query);

		if (!$values = Database::loadColumn())
		{
			return [Helpers\HTML::_('select.option', '', Helpers\Languages::_('ORGANIZER_NONE_GIVEN'))];
		}

		$options = [];
		foreach ($values as $value)
		{
			if (empty($value))
			{
				continue;
			}
			$options[] = Helpers\HTML::_('select.option', $value, $value);
		}

		if (empty($options))
		{
			$options[] = Helpers\HTML::_('select.option', '', Helpers\Languages::_('ORGANIZER_NONE_GIVEN'));
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
}
