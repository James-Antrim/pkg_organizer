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
use Organizer\Adapters\Queries\QueryMySQLi;
use Organizer\Helpers;

/**
 * Class creates a generalized select box for selection of a single column value among those already selected.
 */
class MergeEmailField extends MergeValuesField
{
	use Mergeable;

	/**
	 * @var  string
	 */
	protected $type = 'MergeEmail';

	/**
	 * Gets the saved values for the selected resource IDs.
	 *
	 * @return array
	 */
	protected function getValues(): array
	{
		$domain = Helpers\Input::getParams()->get('emailFilter');

		if (!$domain)
		{
			return [];
		}

		$domain = Database::quote("%$domain%");
		$email  = Database::quoteName('email');

		/* @var QueryMySQLi $query */
		$query = Database::getQuery();
		$query->selectX(['DISTINCT email AS value'], '#__users', 'id', $this->selectedIDs)
			->where("$email LIKE $domain")
			->order('value ASC');
		Database::setQuery($query);

		return Database::loadColumn();
	}
}
