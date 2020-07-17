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
 * Class loads persistent information a filtered set of schedules into the display context.
 */
class Schedules extends ListView
{
	protected $rowStructure = [
		'checkbox'         => '',
		'organizationName' => 'value',
		'termName'         => 'value',
		'active'           => 'value',
		'userName'         => 'value',
		'created'          => 'value'
	];

	/**
	 * creates a joomla administrative tool bar
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_('ORGANIZER_SCHEDULES'), 'calendars');
		$toolbar = Toolbar::getInstance();
		if (Helpers\Can::administrate())
		{
			$toolbar->appendButton('Standard', 'arrow-right-2', 'Move', 'schedules.move', false);
			//$toolbar->appendButton('Standard', 'tree-2', 'Restructure', 'schedules.restructure', false);
		}
		/*$toolbar->appendButton('Standard', 'new', Helpers\Languages::_('ORGANIZER_ADD'), 'schedules.add', false);
		$toolbar->appendButton(
			'Standard',
			'default',
			Helpers\Languages::_('ORGANIZER_ACTIVATE'),
			'schedules.activate',
			true
		);
		$toolbar->appendButton(
			'Standard',
			'tree',
			Helpers\Languages::_('ORGANIZER_CALCULATE_DELTA'),
			'schedules.setReference',
			true
		);
		$toolbar->appendButton(
			'Confirm',
			Helpers\Languages::_('ORGANIZER_DELETE_CONFIRM'),
			'delete',
			Helpers\Languages::_('ORGANIZER_DELETE'),
			'schedules.delete',
			true
		);*/
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return bool true if the use may access the view, otherwise false
	 */
	protected function allowAccess()
	{
		return (bool) Helpers\Can::scheduleTheseOrganizations();
	}

	/**
	 * Function to set the object's headers property
	 *
	 * @return void sets the object headers property
	 */
	public function setHeaders()
	{
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox'         => '',
			'organizationName' => Helpers\HTML::sort('ORGANIZATION', 'organizationName', $direction, $ordering),
			'termName'         => Helpers\HTML::sort('TERM', 'termName', $direction, $ordering),
			'active'           => Helpers\HTML::sort('STATUS', 'active', $direction, $ordering),
			'userName'         => Helpers\HTML::sort('USERNAME', 'userName', $direction, $ordering),
			'created'          => Helpers\HTML::sort('CREATION_DATE', 'created', $direction, $ordering)
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
		$index           = 0;
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$item->active =
				$this->getToggle('schedule', $item->id, $item->active, 'ORGANIZER_TOGGLE_ACTIVE');

			$creationDate  = Helpers\Dates::formatDate($item->creationDate);
			$creationTime  = Helpers\Dates::formatTime($item->creationTime);
			$item->created = "$creationDate / $creationTime";

			$structuredItems[$index] = $this->structureItem($index, $item);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
