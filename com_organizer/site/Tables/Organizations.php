<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Tables;

use Joomla\CMS\Access\Rules;
use Joomla\CMS\Table\Asset;
use Joomla\CMS\Table\Table;
use Organizer\Helpers;

/**
 * Models the organizer_organizations table.
 */
class Organizations extends BaseTable
{
	use Activated, Aliased;

	/**
	 * The resource's German abbreviation.
	 * VARCHAR(25) NOT NULL
	 *
	 * @var string
	 */
	public $abbreviation_de;

	/**
	 * The resource's English abbreviation.
	 * VARCHAR(25) NOT NULL
	 *
	 * @var string
	 */
	public $abbreviation_en;

	/**
	 * The id used by Joomla as a reference to its assets table.
	 * INT(11) NOT NULL
	 *
	 * @var int
	 */
	public $asset_id = null;

	/**
	 * The id of the user entry referenced.
	 * INT(11) DEFAULT NULL
	 *
	 * @var int
	 */
	public $contactID;

	/**
	 * The email address to be used for contacting participants
	 * VARCHAR(100) DEFAULT NULL
	 *
	 * @var string
	 */
	public $contactEmail;

	/**
	 * The resource's German full name.
	 * VARCHAR(200) NOT NULL
	 *
	 * @var string
	 */
	public $fullName_de;

	/**
	 * The resource's English full name.
	 * VARCHAR(200) NOT NULL
	 *
	 * @var string
	 */
	public $fullName_en;

	/**
	 * The resource's German name.
	 * VARCHAR(150) NOT NULL
	 *
	 * @var string
	 */
	public $name_de;

	/**
	 * The resource's English name.
	 * VARCHAR(150) NOT NULL
	 *
	 * @var string
	 */
	public $name_en;

	/**
	 * The resource's German shortened name.
	 * VARCHAR(50) NOT NULL
	 *
	 * @var string
	 */
	public $shortName_de;

	/**
	 * The resource's English shortened name.
	 * VARCHAR(50) NOT NULL
	 *
	 * @var string
	 */
	public $shortName_en;

	/**
	 * The base URL for the organization's homepage.
	 * VARCHAR(50) NOT NULL
	 *
	 * @var string
	 */
	public $URL;

	/**
	 * Declares the associated table.
	 */
	public function __construct()
	{
		parent::__construct('#__organizer_organizations');
	}

	/**
	 * Method to return the title to use for the asset table.  In tracking the assets a title is kept for each asset so
	 * that there is some context available in a unified access manager.
	 *
	 * @return string  The string to use as the title in the asset table.
	 */
	protected function _getAssetTitle()
	{
		return $this->shortName_en;
	}

	/**
	 * Sets the organization asset name
	 *
	 * @return string
	 */
	protected function _getAssetName()
	{
		$key = $this->_tbl_key;

		return 'com_organizer.organization.' . (int) $this->$key;
	}

	/**
	 * Sets the parent as the component root
	 *
	 * @param   Table  $table  the Table object
	 * @param   int    $id     the resource id
	 *
	 * @return int  the asset id of the component root
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	protected function _getAssetParentId(Table $table = null, $id = null)
	{
		$asset = Table::getInstance('Asset');
		$asset->loadByName('com_organizer');

		return $asset->id;
	}

	/**
	 * Overridden bind function
	 *
	 * @param   array  $array   named array
	 * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @return mixed  Null if operation was satisfactory, otherwise returns an error string
	 */
	public function bind($array, $ignore = '')
	{
		if (isset($array['rules']) && is_array($array['rules']))
		{
			$this->cleanRules($array['rules']);
			$rules = new Rules($array['rules']);
			$this->setRules($rules);
		}

		return parent::bind($array, $ignore);
	}

	/**
	 * Set the table column names which are allowed to be null
	 *
	 * @return bool  true
	 */
	public function check()
	{
		if (!$this->alias)
		{
			$this->alias = null;
		}

		if (!$this->contactID)
		{
			$this->contactID = null;
		}

		return true;
	}

	/**
	 * Removes inherited groups before Joomla erroneously sets the value to 0. Joomla must have something similar, but I
	 * don't have time to look for it.
	 *
	 * @param   array &$rules  the rules from the form
	 *
	 * @return void  unsets group indexes with a truly empty value
	 */
	private function cleanRules(&$rules)
	{
		foreach ($rules as $rule => $groups)
		{
			foreach ($groups as $group => $value)
			{
				if (empty($value) and $value !== 0)
				{
					unset($rules[$rule][$group]);
				}
			}
		}
	}

	/**
	 * Method to store a row in the database from the Table instance properties. Completely overwrites the method in
	 * Table because they use the subclass specific update nulls setting for assets which is just stupid.
	 *
	 * @param   bool  $updateNulls  True to update fields even if they are null.
	 *
	 * @return bool  True on success.
	 */
	public function store($updateNulls = true)
	{
		$keys = $this->_tbl_keys;

		// Implement \JObservableInterface: Pre-processing by observers
		$this->_observers->update('onBeforeStore', [$updateNulls, $keys]);

		$currentAssetId = 0;

		if ($this->asset_id)
		{
			$currentAssetId = $this->asset_id;
		}

		unset($this->asset_id);

		// If a primary key exists update the object, otherwise insert it.
		if ($this->hasPrimaryKey())
		{
			$result = $this->_db->updateObject($this->_tbl, $this, $this->_tbl_keys, $updateNulls);
		}
		else
		{
			$result = $this->_db->insertObject($this->_tbl, $this, $this->_tbl_keys[0]);
		}

		$this->_unlock();
		$asset = new Asset($this->getDbo());
		$name  = $this->_getAssetName();
		$asset->loadByName($name);

		if ($error = $asset->getError())
		{
			$this->setError($error);

			return false;
		}

		$this->asset_id = $asset->id;

		$parentId = $this->_getAssetParentId();

		// New asset or new structuring for existing asset
		if (!$this->asset_id or $asset->parent_id != $parentId)
		{
			$asset->setLocation($parentId, 'last-child');
		}

		$asset->name      = $name;
		$asset->parent_id = $parentId;
		$asset->title     = $this->_getAssetTitle();

		if ($this->_rules instanceof Rules)
		{
			$asset->rules = (string) $this->_rules;
		}

		// Try to create/update the asset.
		if (!$asset->check() or !$asset->store())
		{
			$this->setError($asset->getError());

			return false;
		}

		// Create an asset_id or heal one that is corrupted.
		if (!$this->asset_id or ($this->asset_id and $currentAssetId !== $this->asset_id))
		{
			$this->asset_id = $asset->id;

			$query = $this->_db->getQuery(true);
			$query->update('#__organizer_organizations')
				->set("asset_id = {$this->asset_id}")
				->where("id = {$this->id}");
			$this->_db->setQuery($query);

			if (!Helpers\OrganizerHelper::executeQuery('execute'))
			{
				return false;
			}
		}

		// Implement \JObservableInterface: Post-processing by observers
		$this->_observers->update('onAfterStore', [&$result]);

		return $result;
	}
}
