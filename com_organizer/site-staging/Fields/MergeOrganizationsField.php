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

use Joomla\CMS\Factory;
use Organizer\Helpers;

/**
 * Class creates a generalized select box for selection of a single id column value among those already selected.
 */
class MergeOrganizationsField extends OptionsField
{
	/**
	 * @var  string
	 */
	protected $type = 'MergeOrganizations';

	/**
	 * Returns a select box where resource attributes can be selected
	 *
	 * @return array the options for the select box
	 */
	protected function getOptions()
	{
		$selectedIDs    = Helpers\Input::getSelectedIDs();
		$resource       = str_replace('_merge', '', Helpers\Input::getView());
		$validResources = ['category', 'person'];
		$invalid        = (empty($selectedIDs) or empty($resource) or !in_array($resource, $validResources));
		if ($invalid)
		{
			return [];
		}

		$textColumn = 'shortName_' . Helpers\Languages::getTag();
		$table      = $resource === 'category' ? 'categories' : 'persons';

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select("DISTINCT o.id AS value, o.$textColumn AS text")
			->from("#__organizer_organizations AS o")
			->innerJoin("#__organizer_associations AS a ON a.organizationID = o.id")
			->innerJoin("#__organizer_$table AS res ON res.id = a.{$resource}ID")
			->where("res.id IN ( '" . implode("', '", $selectedIDs) . "' )")
			->order('text ASC');
		$dbo->setQuery($query);

		$valuePairs = Helpers\OrganizerHelper::executeQuery('loadAssocList');
		if (empty($valuePairs))
		{
			return [];
		}

		$options = [];
		$values  = [];
		foreach ($valuePairs as $valuePair)
		{
			$options[]                   = Helpers\HTML::_('select.option', $valuePair['value'], $valuePair['text']);
			$values[$valuePair['value']] = $valuePair['value'];
		}

		$this->value = $values;

		return empty($options) ? [] : $options;
	}
}
