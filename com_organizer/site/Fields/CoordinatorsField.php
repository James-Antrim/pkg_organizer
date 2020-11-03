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
use Organizer\Tables;

/**
 * Class creates a select box for organizations.
 */
class CoordinatorsField extends OptionsField
{
	/**
	 * @var  string
	 */
	protected $type = 'Coordinators';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		$eventID = Helpers\Input::getID();
		$dbo     = Factory::getDbo();
		$query   = $dbo->getQuery(true);
		$query->select('DISTINCT personID')
			->from('#__organizer_event_coordinators')
			->where("eventID = $eventID");
		$dbo->setQuery($query);

		$this->value = Helpers\OrganizerHelper::executeQuery('loadColumn', []);

		return parent::getInput();
	}

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	public function getOptions()
	{
		$eventID = Helpers\Input::getID();
		$event   = new Tables\Events();
		$options = [];

		if (!$event->load($eventID) or !$organizationID = $event->organizationID)
		{
			return $options;
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('DISTINCT p.id, p.forename, p.surname')
			->from('#__organizer_persons AS p')
			->innerJoin('#__organizer_associations AS a ON a.personID = p.id')
			->where("a.organizationID = $organizationID")
			->order('p.surname, p.forename');
		$dbo->setQuery($query);

		if (!$persons = Helpers\OrganizerHelper::executeQuery('loadAssocList', []))
		{
			return $options;
		}

		foreach ($persons as $person)
		{
			$name      = empty($person['forename']) ? $person['surname'] : "{$person['surname']}, {$person['forename']}";
			$options[] = Helpers\HTML::_('select.option', $person['id'], $name);
		}

		return $options;
	}
}
