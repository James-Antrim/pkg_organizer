<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Exception;
use Organizer\Helpers;

/**
 * Class provides methods for merging resources. Resource specific tasks are implemented in the extending classes.
 */
abstract class InterimMergeModel extends BaseModel
{
	/**
	 * @var string the column name in the organization resources table
	 */
	protected $association;

	/**
	 * @var array the preprocessed form data
	 */
	protected $data = [];

	/**
	 * Get the ids of the resources associated over an association table.
	 *
	 * @param   string  $assocColumn  the name of the column which has the associated ids
	 * @param   string  $assocTable   the unique part of the association table name
	 *
	 * @return array the associated ids
	 */
	protected function getAssociatedResourceIDs($assocColumn, $assocTable)
	{
		$mergeIDs = implode(', ', $this->selected);
		$query    = $this->_db->getQuery(true);
		$query->select("DISTINCT $assocColumn")
			->from("#__organizer_$assocTable")
			->where("$this->fkColumn IN ($mergeIDs)");
		$this->_db->setQuery($query);

		return Helpers\OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return bool true on success, otherwise false
	 */
	public function save($data = [])
	{
		$this->authorize();

		$this->data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

		try
		{
			$table = $this->getTable();
		}
		catch (Exception $exception)
		{
			Helpers\OrganizerHelper::message($exception->getMessage());

			return false;
		}

		// Merge associations and external data first

		// Set id for new rewrite for existing.
		/*$this->data['id'] = $table->id;

		if (!empty($this->association) and !$this->updateOrganizations())
		{
			return false;
		}*/

		return $table->save($this->data) ? $table->id : false;
	}


	/**
	 * Updates the associated organizations for a resource
	 *
	 * @return bool true on success, otherwise false
	 */
	/*private function updateOrganizations()
	{
		$existingQuery = $this->_db->getQuery(true);
		$existingQuery->select('DISTINCT organizationID');
		$existingQuery->from('#__organizer_associations');
		$existingQuery->where("{$this->association} = {$this->data['id']}");
		$this->_db->setQuery($existingQuery);
		$existing = Helpers\OrganizerHelper::executeQuery('loadColumn', []);

		if ($deprecated = array_diff($existing, $this->data['organizationID']))
		{
			$deletionQuery = $this->_db->getQuery(true);
			$deletionQuery->delete('#__organizer_associations');
			$deletionQuery->where("{$this->association} = {$this->data['id']}");
			$deletionQuery->where("organizationID IN ('" . implode("','", $deprecated) . "')");
			$this->_db->setQuery($deletionQuery);

			if (!Helpers\OrganizerHelper::executeQuery('execute', false))
			{
				return false;
			}
		}

		$new = array_diff($this->data['organizationID'], $existing);

		if (!empty($new))
		{
			$insertQuery = $this->_db->getQuery(true);
			$insertQuery->insert('#__organizer_associations');
			$insertQuery->columns("organizationID, {$this->association}");

			foreach ($new as $newID)
			{
				$insertQuery->values("'$newID', '{$this->data['id']}'");
				$this->_db->setQuery($insertQuery);

				if (!Helpers\OrganizerHelper::executeQuery('execute', false))
				{
					return false;
				}
			}
		}

		return true;
	}*/

	/**
	 * Updates organization resource associations
	 *
	 * @return boolean  true on success, otherwise false
	 */
	/*protected function updateDRAssociation()
	{
		$relevantIDs = "'" . implode("', '", $this->selected) . "'";

		$selectQuery = $this->_db->getQuery(true);
		$selectQuery->select('DISTINCT organizationID');
		$selectQuery->from('#__organizer_associations');
		$selectQuery->where("{$this->association} IN ( $relevantIDs )");
		$this->_db->setQuery($selectQuery);
		$organizationIDs = Helpers\OrganizerHelper::executeQuery('loadColumn', []);

		if (empty($organizationIDs))
		{
			return true;
		}

		$deleteQuery = $this->_db->getQuery(true);
		$deleteQuery->delete('#__organizer_associations')
			->where("{$this->fkColumn} IN ( $relevantIDs )");
		$this->_db->setQuery($deleteQuery);

		if (!Helpers\OrganizerHelper::executeQuery('execute', false))
		{
			return false;
		}

		$mergeID     = reset($this->selected);
		$insertQuery = $this->_db->getQuery(true);
		$insertQuery->insert('#__organizer_associations');
		$insertQuery->columns("organizationID, {$this->fkColumn}");

		foreach ($organizationIDs as $organizationID)
		{
			$insertQuery->values("'$organizationID', $mergeID");
		}

		$this->_db->setQuery($insertQuery);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute', false);
	}*/
}
