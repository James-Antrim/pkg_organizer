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

use JDatabaseQuery;
use Joomla\CMS\Form\Form;
use Organizer\Adapters\Database;
use Organizer\Adapters\Queries\QueryMySQLi;
use Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of rooms.
 */
class Rooms extends ListModel
{
	use Activated;

	protected $defaultOrdering = 'r.name';

	protected $filter_fields = ['buildingID', 'campusID', 'cleaningID', 'keyID', 'roomtypeID', 'virtual'];

	/**
	 * @inheritDoc
	 */
	protected function filterFilterForm(Form $form)
	{
		if (Helpers\Input::getParams()->get('campusID'))
		{
			$form->removeField('campusID', 'filter');

			// No virtual rooms in a physical area
			$form->removeField('virtual', 'filter');
			unset($this->filter_fields['campusID'], $this->filter_fields['virtual']);
		}

		if (!$this->adminContext)
		{
			$form->removeField('active', 'filter');
		}
	}

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery(): JDatabaseQuery
	{
		$tag = Helpers\Languages::getTag();
		/* @var QueryMySQLi $query */
		$query = Database::getQuery();

		$query->select('r.id, r.code, r.name AS roomName, r.active, r.effCapacity')
			->select("t.id AS roomtypeID, t.name_$tag AS roomType")
			->select('b.id AS buildingID, b.address, b.name AS buildingName, b.location, b.propertyType')
			->select("c1.name_$tag AS campus, c2.name_$tag AS parent")
			->from('#__organizer_rooms AS r')
			->leftJoin('#__organizer_roomtypes AS t ON t.id = r.roomtypeID')
			->leftJoin('#__organizer_use_codes AS uc ON uc.id = t.usecode')
			->leftJoin('#__organizer_roomkeys AS rk ON rk.id = uc.keyID');

		$campusID = (int) $this->state->get('filter.campusID');
		if ($campusID and $campusID !== self::NONE)
		{
			$query->innerJoin('#__organizer_buildings AS b ON b.id = r.buildingID')
				->innerJoin('#__organizer_campuses AS c1 ON c1.id = b.campusID')
				->where("(c1.id = $campusID OR c1.parentID = $campusID)");
		}
		else
		{
			$query->leftJoin('#__organizer_buildings AS b ON b.id = r.buildingID')
				->leftJoin('#__organizer_campuses AS c1 ON c1.id = b.campusID');

			if ($campusID and $campusID === self::NONE)
			{
				$query->where('r.buildingID IS NULL');
			}
		}

		$query->leftJoin('#__organizer_campuses AS c2 ON c2.id = c1.parentID');

		$this->setActiveFilter($query, 'r');
		$this->setIDFilter($query, 'rk.id', 'filter.keyID');
		$this->setIDFilter($query, 'rk.cleaningID', 'filter.cleaningID');
		$this->setSearchFilter($query, ['r.name', 'b.name', 't.name_de', 't.name_en', 'uc.code']);
		$this->setValueFilters($query, ['buildingID', 'roomtypeID', 'virtual']);

		$this->setOrdering($query);

		return $query;
	}

	/**
	 * @inheritDoc
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState($ordering, $direction);

		if ($format = Helpers\Input::getCMD('format') and in_array($format, ['pdf', 'xls']))
		{
			$this->setState('list.limit', 0);
		}

		if ($campusID = Helpers\Input::getInt('campusID'))
		{
			$this->setState('filter.campusID', $campusID);
		}
	}
}
