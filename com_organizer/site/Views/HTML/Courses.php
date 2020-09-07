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

		$structure = [
			'checkbox'           => '',
			'name'               => 'link',
			'campus'             => 'value',
			'dates'              => 'value',
			'courseStatus'       => 'value',
			'registrationStatus' => 'value'
		];

		if (Helpers\Can::scheduleTheseOrganizations() or Helpers\Can::edit('courses'))
		{
			$this->manages = true;
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
			if ($frontend)
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
						'enter',
						Languages::_('ORGANIZER_REGISTER'),
						'courses.register',
						true
					);
					$toolbar->appendButton(
						'Standard',
						'exit',
						Languages::_('ORGANIZER_DEREGISTER'),
						'courses.register',
						true
					);
				}
				else
				{
					$toolbar->appendButton(
						'Standard',
						'user-plus',
						Languages::_('ORGANIZER_PROFILE_NEW'),
						'participants.edit',
						false
					);
				}
			}

			if ($this->manages)
			{
				$toolbar->appendButton('Standard', 'edit', Languages::_('ORGANIZER_EDIT'), 'courses.edit', true);
			}

			if (Helpers\Can::administrate())
			{
				//$toolbar->appendButton('Standard', 'new', Languages::_('ORGANIZER_ADD'), 'courses.add', false);
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
			'checkbox'           => '',
			'name'               => Languages::_('ORGANIZER_NAME'),
			'campus'             => Languages::_('ORGANIZER_CAMPUS'),
			'dates'              => Languages::_('ORGANIZER_DATES'),
			'courseStatus'       => [
				'attributes' => ['class' => 'center'],
				'value'      => Languages::_('ORGANIZER_COURSE_STATUS')
			],
			'registrationStatus' => [
				'attributes' => ['class' => 'center'],
				'value'      => Languages::_('ORGANIZER_REGISTRATION_STATUS')
			]
		];

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

		$today  = Helpers\Dates::standardizeDate();
		$userID = Helpers\Users::getID();

		foreach ($this->items as $course)
		{
			$campus         = Helpers\Campuses::getName($course->campusID);
			$campus         .= $this->clientContext ? '' : ' ' . Helpers\Campuses::getPin($course->campusID);
			$course->campus = $campus;

			$course->dates = Helpers\Courses::getDateDisplay($course->id);

			$expired = $course->endDate < $today;
			$ongoing = ($course->startDate <= $today and $expired);

			if ($course->deadline)
			{
				$deadline = date('Y-m-d', strtotime("-{$course->deadline} Days", strtotime($course->startDate)));
			}
			else
			{
				$deadline = $course->startDate;
			}

			$closed = (!$expired and !$ongoing and $deadline <= $today);

			$full   = $course->participants >= $course->maxParticipants;
			$ninety = (!$full and ($course->participants / (int) $course->maxParticipants) >= .9);


			if ($expired)
			{
				$attributes = ['class' => 'status-display center grey'];

				$course->courseStatus       = [
					'attributes' => $attributes,
					'value'      => Languages::_('ORGANIZER_EXPIRED')
				];
				$course->registrationStatus = [
					'attributes' => $attributes,
					'value'      => Languages::_('ORGANIZER_REGISTRATION_CLOSED')
				];
			}
			else
			{
				$course->courseStatus = [];
				$capacityText         = Languages::_('ORGANIZER_PARTICIPANTS');
				$capacityText         .= ": $course->participants / $course->maxParticipants<br>";

				if ($ongoing or $full)
				{
					$courseAttributes = ['class' => 'status-display center red'];
				}
				elseif ($closed or $ninety)
				{
					$courseAttributes = ['class' => 'status-display center yellow'];
				}
				else
				{
					$courseAttributes = ['class' => 'status-display center green'];
				}

				$course->courseStatus['attributes'] = $courseAttributes;

				if ($ongoing or $closed)
				{
					$courseText = Languages::_('ORGANIZER_REGISTRATION_CLOSED');
				}
				else
				{
					$courseText = sprintf(Languages::_('ORGANIZER_REGISTRATION_CLOSES_ON'), $deadline);
				}

				$course->courseStatus['value'] = $capacityText . $courseText;

				if ($userID)
				{
					if ($course->registered)
					{
						$course->registrationStatus = [
							'attributes' => ['class' => 'status-display center green'],
							'value'      => Languages::_('ORGANIZER_REGISTERED')
						];
					}
					else
					{
						$color                      = ($ongoing or $closed) ? 'red' : 'yellow';
						$course->registrationStatus = [
							'attributes' => ['class' => "status-display center $color"],
							'value'      => Languages::_('ORGANIZER_NOT_REGISTERED')
						];
					}
				}
				else
				{
					$course->registrationStatus = [
						'attributes' => ['class' => 'status-display center grey'],
						'value'      => Languages::_('ORGANIZER_NOT_LOGGED_IN')
					];
				}
			}

			$index = "{$course->name}{$course->dates}{$course->id}";

			$structuredItems[$index] = $this->structureItem($index, $course, $URL . $course->id);
		}

		ksort($structuredItems);

		$this->items = $structuredItems;
	}
}
