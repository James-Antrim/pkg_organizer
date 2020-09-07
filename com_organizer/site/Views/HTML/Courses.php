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

use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers;
use Organizer\Helpers\Languages;

// Exception for frequency of use

/**
 * Class which loads data into the view output context
 */
class Courses extends ListView
{
	private $manages = false;

	/**
	 * Constructor
	 *
	 * @param   array  $config  A named configuration array for object construction.
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$structure = ['name' => 'link', 'campus' => 'value', 'dates' => 'value', 'status' => 'value'];

		if (Helpers\Can::scheduleTheseOrganizations() or Helpers\Can::edit('courses'))
		{
			$this->manages = true;
			$structure     = ['checkbox' => ''] + $structure;
		}

		$this->rowStructure = $structure;
	}

	/**
	 * Adds supplemental information to the display output.
	 *
	 * @return void modifies the object property supplement
	 */
	protected function addSupplement()
	{
		if (empty(Factory::getUser()->id))
		{
			$this->supplement = '<div class="tbox-yellow">' . Languages::_('ORGANIZER_COURSE_LOGIN_WARNING') . '</div>';
		}
	}

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		$frontend     = $this->clientContext !== self::BACKEND;
		$resourceName = '';
		if ($frontend)
		{
			if (Helpers\Input::getBool('onlyPrepCourses', false))
			{
				$resourceName .= Languages::_('ORGANIZER_PREP_COURSES');
				if ($campusID = $this->state->get('filter.campusID', 0))
				{
					$resourceName .= ' ' . Helpers\Campuses::getName($campusID);
				}
			}
		}

		Helpers\HTML::setMenuTitle('ORGANIZER_COURSES', $resourceName, 'contract-2');

		if (Factory::getUser()->id)
		{
			$toolbar = Toolbar::getInstance();
			/*if ($frontend)
			{
				if (Helpers\Participants::exists())
				{
					$toolbar->appendButton(
						'Standard',
						'vcard',
						Languages::_('ORGANIZER_PROFILE_EDIT'),
						'participants.edit',
						false
					);
					$toolbar->appendButton(
						'Standard',
						'vcard',
						Languages::_('ORGANIZER_REGISTER'),
						'courses.register',
						false
					);
					$toolbar->appendButton(
						'Standard',
						'vcard',
						Languages::_('ORGANIZER_DEREGISTER'),
						'courses.register',
						false
					);
				}
				else
				{
					$toolbar->appendButton(
						'Standard',
						'vcard',
						Languages::_('ORGANIZER_PROFILE_NEW'),
						'participants.edit',
						false
					);
				}
			}*/

			if ($this->manages)
			{
				$toolbar->appendButton('Standard', 'edit', Languages::_('ORGANIZER_EDIT'), 'courses.edit',
					true);
			}

			if (Helpers\Can::administrate())
			{
				//$toolbar->appendButton('Standard', 'new', Languages::_('ORGANIZER_ADD'), 'courses.add', false);
				$toolbar->appendButton(
					'Standard',
					'vcard',
					Languages::_('ORGANIZER_PROFILE_EDIT'),
					'participants.edit',
					false
				);
				$toolbar->appendButton(
					'Standard',
					'vcard',
					Languages::_('ORGANIZER_REGISTER'),
					'courses.register',
					false
				);
				$toolbar->appendButton(
					'Standard',
					'vcard',
					Languages::_('ORGANIZER_DEREGISTER'),
					'courses.register',
					false
				);
				$toolbar->appendButton(
					'Standard',
					'last',
					Languages::_('ORGANIZER_MANAGE_PARTICIPANTS'),
					'courses.manageParticipants',
					true
				);
				$toolbar->appendButton(
					'Confirm',
					Languages::_('ORGANIZER_DELETE_CONFIRM'),
					'delete',
					Languages::_('ORGANIZER_DELETE'),
					'courses.delete',
					true
				);
			}
		}
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return bool true if the user may access the view, otherwise false
	 */
	protected function allowAccess()
	{
		if ($this->clientContext)
		{
			return (bool) Helpers\Can::scheduleTheseOrganizations();
		}

		return true;
	}

	/**
	 * Function to set the object's headers property
	 *
	 * @return void sets the object headers property
	 */
	public function setHeaders()
	{
		$headers = [
			'name'   => Languages::_('ORGANIZER_NAME'),
			'campus' => Languages::_('ORGANIZER_CAMPUS'),
			'dates'  => Languages::_('ORGANIZER_DATES'),
			'status' => Languages::_('ORGANIZER_COURSE_STATUS')
		];

		if ($this->manages)
		{
			$headers = ['checkbox' => ''] + $headers;
		}

		$this->headers = $headers;
	}

	/**
	 * Processes the items in a manner specific to the view, so that a generalized  output in the layout can occur.
	 *
	 * @return void processes the class items property
	 */
	protected function structureItems()
	{
		$URL = Uri::base() . '?option=com_organizer';
		$URL .= $this->clientContext ? '&view=course_edit&id=' : '&view=course_item&id=';

		$structuredItems = [];

		foreach ($this->items as $course)
		{
			$campus = Helpers\Campuses::getName($course->campusID);
			$campus .= $this->clientContext ? '' : ' ' . Helpers\Campuses::getPin($course->campusID);

			$course->campus = $campus;
			$course->dates  = Helpers\Courses::getDateDisplay($course->id);
			$index          = "{$course->name}{$course->dates}{$course->id}";
			$course->status = Helpers\Courses::getStatusText($course->id);

			$structuredItems[$index] = $this->structureItem($index, $course, $URL . $course->id);
		}

		ksort($structuredItems);

		$this->items = $structuredItems;
	}
}
