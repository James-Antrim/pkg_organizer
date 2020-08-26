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
			$toolbar->appendButton('Standard', 'play', 'Move Schedules', 'schedules.moveSchedules', false);
			$toolbar->appendButton('Standard', 'next', 'Migrate Resources', 'schedules.migrateResources', false);
			$toolbar->appendButton('Standard', 'last', 'Migrate Schedules', 'schedules.migrateSchedules', false);

			if ($this->state->get('filter.organizationID') and $this->state->get('filter.termID'))
			{
				$toolbar->appendButton(
					'Standard',
					'loop',
					Helpers\Languages::_('ORGANIZER_REFRESH_HISTORY'),
					'schedules.rebuild',
					false
				);
			}

			/*$toolbar->appendButton(
				'Standard',
				'envelope',
				Helpers\Languages::_('ORGANIZER_NOTIFY_CHANGES'),
				'schedules.notify',
				true
			);*/

			$toolbar->appendButton(
				'Confirm',
				Helpers\Languages::_('ORGANIZER_DELETE_CONFIRM'),
				'delete',
				Helpers\Languages::_('ORGANIZER_DELETE'),
				'schedules.delete',
				true
			);
		}
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
		$headers = [
			'checkbox'         => '',
			'organizationName' => Helpers\Languages::_('ORGANIZER_ORGANIZATION'),
			'termName'         => Helpers\Languages::_('ORGANIZER_TERM'),
			'active'           => Helpers\Languages::_('ORGANIZER_STATUS'),
			'userName'         => Helpers\Languages::_('ORGANIZER_USERNAME'),
			'created'          => Helpers\Languages::_('ORGANIZER_CREATION_DATE')
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
		$icon            = '<span class="icon-XCLASSX"></span>';
		$index           = 0;
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$class        = empty($item->active) ? 'checkbox-unchecked' : 'checkbox-checked';
			$item->active = str_replace('XCLASSX', $class, $icon);

			$creationDate  = Helpers\Dates::formatDate($item->creationDate);
			$creationTime  = Helpers\Dates::formatTime($item->creationTime);
			$item->created = "$creationDate / $creationTime";

			$structuredItems[$index] = $this->structureItem($index, $item);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
