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
use Organizer\Helpers;

/**
 * Class retrieves the data regarding a filtered set of courses.
 */
class Courses extends ListModel
{
	use Helpers\Filtered;

	protected $defaultOrdering = 'name';

	protected $filter_fields = ['campusID', 'status', 'termID'];

	/**
	 * Filters out form inputs which should not be displayed due to menu settings.
	 *
	 * @param   Form  $form  the form to be filtered
	 *
	 * @return void modifies $form
	 */
	protected function filterFilterForm(&$form)
	{
		parent::filterFilterForm($form);

		if ($this->clientContext)
		{
			return;
		}

		$form->removeField('termID', 'filter');

		$params = Helpers\Input::getParams();

		if ($params->get('campusID'))
		{
			$form->removeField('campusID', 'filter');
		}

		if ($params->get('onlyPrepCourses'))
		{
			$form->removeField('search', 'filter');
		}
	}

	/**
	 * Wrapper method for Joomla\CMS\MVC\Model\ListModel which has a mixed return type.
	 *
	 * @return  array  An array of data items on success.
	 */
	public function getItems()
	{
		if (!$items = parent::getItems())
		{
			return [];
		}

		$userID = Helpers\Users::getID();

		foreach ($items as $item)
		{
			$item->participants = count(Helpers\Courses::getParticipantIDs($item->id));
			$item->registered   = Helpers\CourseParticipants::getState($item->id, $userID);
		}

		return $items ? $items : [];
	}

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$tag   = Helpers\Languages::getTag();
		$query = $this->_db->getQuery(true);
		$query->select("c.*, c.name_$tag AS name, MIN(u.startDate) AS startDate, MAX(u.endDate) AS endDate")
			->from('#__organizer_courses AS c')
			->innerJoin('#__organizer_units AS u ON u.courseID = c.id')
			->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
			->innerJoin('#__organizer_events AS e ON e.id = i.eventID')
			->group('c.id');

		$this->setSearchFilter($query, ['c.name_de', 'c.name_en', 'e.name_de', 'e.name_en']);

		if (!$this->clientContext and Helpers\Input::getParams()->get('onlyPrepCourses'))
		{
			$query->where('e.preparatory = 1');
		}
		else
		{
			$this->setValueFilters($query, ['c.termID']);
		}

		if (empty($this->state->get('filter.status')))
		{
			$today = date('Y-m-d');
			$query->where("endDate >= '$today'");
		}

		$this->addCampusFilter($query, 'c');

		return $query;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return void populates state properties
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState($ordering, $direction);

		if ($this->clientContext === self::FRONTEND)
		{
			$params = Helpers\Input::getParams();

			if ($campusID = $params->get('campusID'))
			{
				$this->state->set('filter.campusID', $campusID);
			}
		}
	}
}
