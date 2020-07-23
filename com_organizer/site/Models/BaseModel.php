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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Organizer\Helpers;

/**
 * Class which manages stored building data.
 */
abstract class BaseModel extends BaseDatabaseModel
{
	use Named;

	protected $selected = [];

	/**
	 * BaseModel constructor.
	 *
	 * @param   array  $config
	 */
	public function __construct($config = array())
	{
		try
		{
			parent::__construct($config);
		}
		catch (Exception $exception)
		{
			Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

			return;
		}

		$this->setContext();
	}

	/**
	 * Authorizes the user
	 */
	protected function allow()
	{
		return Helpers\Can::administrate();
	}

	/**
	 * Removes entries from the database.
	 *
	 * @return boolean true on success, otherwise false
	 * @throws Exception => unauthorized access
	 */
	public function delete()
	{
		if (!$this->allow())
		{
			throw new Exception(Helpers\Languages::_('COM_ORGANIZER_403'), 403);
		}

		$selectedIDs = Helpers\Input::getSelectedIDs();
		$success     = true;
		foreach ($selectedIDs as $selectedID)
		{
			$table   = $this->getTable();
			$success = ($success and $table->delete($selectedID));
		}

		// TODO: create a message with an accurate count of successes.

		return $success;
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return int|bool int id of the resource on success, otherwise boolean false
	 * @throws Exception => unauthorized access
	 */
	public function save($data = [])
	{
		if (!$this->allow())
		{
			throw new Exception(Helpers\Languages::_('COM_ORGANIZER_403'), 403);
		}

		$data  = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;
		$table = $this->getTable();

		return $table->save($data) ? $table->id : false;
	}

	/**
	 * Alters the state of a binary property.
	 *
	 * @return bool true on success, otherwise false
	 * @throws Exception unauthorized access
	 */
	public function toggle()
	{
		$resourceID = Helpers\Input::getID();

		// Necessary for access checks in mergeable resources.
		$this->selected = [$resourceID];

		if (!$this->allow())
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_401'), 401);
		}

		$attribute = Helpers\Input::getCMD('attribute');
		$table     = $this->getTable();

		$tableFields = $table->getFields();
		if (array_key_exists($attribute, $tableFields))
		{
			if (!$table->load($resourceID))
			{
				return false;
			}

			$table->$attribute = !$table->$attribute;

			return $table->store();
		}

		return false;
	}
}
