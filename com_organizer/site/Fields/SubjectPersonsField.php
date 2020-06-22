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
 * Class creates a select box for the association of persons with subject documentation.
 */
class SubjectPersonsField extends OptionsField
{
	protected $type = 'SubjectPersons';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
		$subjectIDs = Helpers\Input::getSelectedIDs();
		$role       = $this->getAttribute('role');
		$invalid    = (empty($subjectIDs) or empty($subjectIDs[0]) or empty($role));

		if ($invalid)
		{
			return [];
		}

		$existingPersons = Helpers\Subjects::getPersons($subjectIDs[0], $role);
		$this->value     = [];
		foreach ($existingPersons as $person)
		{
			$this->value[$person['id']] = $person['id'];
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('p.id, p.surname, p.forename')
			->from('#__organizer_persons AS p')
			->order('surname, forename');

		$organizationID = $this->form->getValue('organizationID');
		if (!empty($organizationID))
		{
			if (empty($this->value))
			{
				$query->innerJoin('#__organizer_associations AS a ON a.personID = p.id');
				$query->where("organizationID = $organizationID");
			}
			else
			{
				$query->leftJoin('#__organizer_associations AS a ON a.personID = p.id');
				$personIDs  = implode(',', $this->value);
				$extPersons = "(organizationID != $organizationID AND personID IN ($personIDs))";
				$query->where("(organizationID = $organizationID OR $extPersons)");
			}
		}

		$dbo->setQuery($query);

		$options = parent::getOptions();
		if (!$persons = Helpers\OrganizerHelper::executeQuery('loadAssocList', [], 'id'))
		{
			return $options;
		}

		foreach ($persons as $person)
		{
			$text      = empty($person['forename']) ?
				$person['surname'] : "{$person['surname']}, {$person['forename']}";
			$options[] = Helpers\HTML::_('select.option', $person['id'], $text);
		}

		return $options;
	}
}
