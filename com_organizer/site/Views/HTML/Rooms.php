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
 * Class loads persistent information a filtered set of rooms into the display context.
 */
class Rooms extends ListView
{
	protected $rowStructure = [
		'checkbox'     => '',
		'roomName'     => 'link',
		'buildingName' => 'link',
		'roomType'     => 'link',
		'active'       => 'value'
	];

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_('ORGANIZER_ROOMS'), 'enter');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), 'rooms.edit', true);
		$toolbar->appendButton(
			'Standard',
			'eye-open',
			Helpers\Languages::_('ORGANIZER_ACTIVATE'),
			'rooms.activate',
			false
		);
		$toolbar->appendButton(
			'Standard',
			'eye-close',
			Helpers\Languages::_('ORGANIZER_DEACTIVATE'),
			'rooms.deactivate',
			false
		);

		/*if (Helpers\Can::administrate())
		{
			$toolbar->appendButton(
				'Standard',
				'attachment',
				Helpers\Languages::_('ORGANIZER_MERGE'),
				'rooms.mergeView',
				true
			);
		}*/
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		if (!Helpers\Can::manage('facilities'))
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
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox'     => '',
			'roomName'     => Helpers\HTML::sort('NAME', 'roomName', $direction, $ordering),
			'buildingName' => Helpers\HTML::sort('BUILDING', 'buildingName', $direction, $ordering),
			'roomType'     => Helpers\HTML::sort('TYPE', 'roomType', $direction, $ordering),
			'active'       => Helpers\Languages::_('ORGANIZER_ACTIVE')
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
		$link            = 'index.php?option=com_organizer&view=room_edit&id=';
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$tip          = $item->active ? 'ORGANIZER_CLICK_TO_DEACTIVATE' : 'ORGANIZER_CLICK_TO_ACTIVATE';
			$item->active = $this->getToggle('rooms', $item->id, $item->active, $tip, 'active');

			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
