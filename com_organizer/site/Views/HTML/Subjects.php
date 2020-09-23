<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Toolbar\Toolbar;
use Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of subjects into the display context.
 */
class Subjects extends ListView
{
	const ALL = 0, COORDINATES = 1, TEACHES = 2;

	private $documentAccess = false;

	private $params = null;

	/**
	 * Constructor
	 *
	 * @param   array  $config  A named configuration array for object construction.
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->params = Helpers\Input::getParams();
	}

	/**
	 * Sets Joomla view title and action buttons
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		$resourceName = '';
		if (!$this->adminContext)
		{
			if ($personID = $this->state->get('calledPersonID', 0))
			{
				$resourceName = Helpers\Persons::getDefaultName($personID);
				$resourceName .= ": " . Helpers\Languages::_('ORGANIZER_SUBJECTS');
			}
			else
			{
				if ($programID = Helpers\Input::getInt('programID'))
				{
					$resourceName = Helpers\Programs::getName($programID);
				}
				if ($poolID = $this->state->get('calledPoolID', 0))
				{
					$poolName     = Helpers\Pools::getFullName($poolID);
					$resourceName .= empty($resourceName) ? $poolName : ", $poolName";
				}
			}
		}

		Helpers\HTML::setMenuTitle('ORGANIZER_SUBJECTS', $resourceName, 'book');
		$toolbar = Toolbar::getInstance();
		if ($this->documentAccess)
		{
			$toolbar->appendButton('Standard', 'new', Helpers\Languages::_('ORGANIZER_ADD'), 'subjects.add', false);
			$toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), 'subjects.edit', true);
			$toolbar->appendButton(
				'Standard',
				'upload',
				Helpers\Languages::_('ORGANIZER_IMPORT_LSF'),
				'subjects.import',
				true
			);
			$toolbar->appendButton(
				'Confirm',
				Helpers\Languages::_('ORGANIZER_DELETE_CONFIRM'),
				'delete',
				Helpers\Languages::_('ORGANIZER_DELETE'),
				'subjects.delete',
				true
			);
		}
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		if (!$this->adminContext)
		{
			return;
		}

		if (!$this->documentAccess = Helpers\Can::documentTheseOrganizations())
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Function to set the object's headers property
	 *
	 * @return void sets the object headers property
	 */
	public function setHeaders()
	{
		$direction = $this->state->get('list.direction');
		$ordering  = $this->state->get('list.ordering');
		$headers   = [];

		if ($this->adminContext or $this->documentAccess)
		{
			$headers['checkbox'] = ($this->adminContext and $this->documentAccess) ?
				Helpers\HTML::_('grid.checkall') : '';
		}

		$headers['name'] = Helpers\HTML::sort('NAME', 'name', $direction, $ordering);
		$headers['code'] = Helpers\HTML::sort('MODULE_CODE', 'code', $direction, $ordering);

		if (!$calledPersonID = (int) $this->state->get('calledPersonID', 0))
		{
			if ($role = (int) Helpers\Input::getParams()->get('role') and $role === self::COORDINATES)
			{
				$personsText = Helpers\Languages::_('ORGANIZER_COORDINATORS');
			}
			else
			{
				$personsText = Helpers\Languages::_('ORGANIZER_TEACHERS');
			}
			$headers['persons'] = $personsText;
		}

		$headers['creditpoints'] = Helpers\Languages::_('ORGANIZER_CREDIT_POINTS');

		$this->headers = $headers;
	}

	/**
	 * Retrieves the person texts and formats them according to their roles for the subject being iterated
	 *
	 * @param   object  $subject  the subject being iterated
	 *
	 * @return string
	 */
	private function getPersonDisplay($subject)
	{
		$names = [];
		$role  = (int) Helpers\Input::getParams()->get('role');

		if (count($subject->persons) > 3)
		{
			return $role === self::COORDINATES ?
				Helpers\Languages::_('ORGANIZER_COORDINATORS_PLACEHOLDER') :
				Helpers\Languages::_('ORGANIZER_TEACHERS_PLACEHOLDER');
		}

		foreach ($subject->persons as $personID => $person)
		{
			$name = $this->getPersonText($person);

			if ($role === self::ALL)
			{
				$roles = [];
				if (isset($person['role'][self::COORDINATES]))
				{
					$roles[] = Helpers\Languages::_('ORGANIZER_SUBJECT_COORDINATOR_ABBR');
				}
				if (isset($person['role'][self::TEACHES]))
				{
					$roles[] = Helpers\Languages::_('ORGANIZER_TEACHER_ABBR');
				}

				$name .= ' (' . implode(', ', $roles) . ')';
			}

			$names[] = $name;
		}

		return implode('<br>', $names);
	}

	/**
	 * Generates the person text (surname(, forename)?( title)?) for the given person
	 *
	 * @param   array  $person  the subject person
	 *
	 * @return string
	 */
	public function getPersonText($person)
	{
		$showTitle = (bool) $this->params->get('showTitle');

		$text = $person['surname'];

		if (!empty($person['forename']))
		{
			$text .= ", {$person['forename']}";
		}

		if ($showTitle and !empty($person['title']))
		{
			$text .= " {$person['title']}";
		}

		return $text;
	}

	/**
	 * Processes the items in a manner specific to the view, so that a generalized  output in the layout can occur.
	 *
	 * @return void processes the class items property
	 */
	protected function structureItems()
	{
		$editLink        = 'index.php?option=com_organizer&view=subject_edit&id=';
		$index           = 0;
		$itemLink        = 'index.php?option=com_organizer&view=subject_item&id=';
		$structuredItems = [];

		$attributes = [];
		if (!$this->adminContext)
		{
			$attributes['target'] = '_blank';
		}

		$calledPersonID = (int) $this->state->get('calledPersonID', 0);

		foreach ($this->items as $subject)
		{
			$access   = Helpers\Can::document('subject', (int) $subject->id);
			$checkbox = $access ? Helpers\HTML::_('grid.id', $index, $subject->id) : '';
			$thisLink = ($this->adminContext and $access) ? $editLink . $subject->id : $itemLink . $subject->id;

			$structuredItems[$index] = [];

			if ($this->adminContext or $this->documentAccess)
			{
				$structuredItems[$index]['checkbox'] = $checkbox;
			}

			$structuredItems[$index]['name'] = Helpers\HTML::_('link', $thisLink, $subject->name, $attributes);
			$structuredItems[$index]['code'] = Helpers\HTML::_('link', $thisLink, $subject->code, $attributes);

			if (!$calledPersonID)
			{
				$structuredItems[$index]['persons'] = $this->getPersonDisplay($subject);
			}

			$structuredItems[$index]['creditpoints'] = empty($subject->creditpoints) ? '' : $subject->creditpoints;

			$index++;
		}

		$this->items = $structuredItems;
	}
}
