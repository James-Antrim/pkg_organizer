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
	protected $rowStructure = ['checkbox' => '', 'roomName' => 'link', 'buildingName' => 'link', 'roomType' => 'link'];

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_('ORGANIZER_ROOMS'), 'enter');
		$toolbar = Toolbar::getInstance();
		/*$toolbar->appendButton('Standard', 'new', Helpers\Languages::_('ORGANIZER_ADD'), 'rooms.add', false);*/
		$toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), 'rooms.edit', true);

		if (Helpers\Can::administrate())
		{
			/*$toolbar->appendButton(
				'Standard',
				'attachment',
				Helpers\Languages::_('ORGANIZER_MERGE'),
				'rooms.mergeView',
				true
			);*/
		}
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return bool true if the use may access the view, otherwise false
	 */
	protected function allowAccess()
	{
		return Helpers\Can::manage('facilities');
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
			'roomType'     => Helpers\HTML::sort('TYPE', 'roomType', $direction, $ordering)
		];

		$this->headers = $headers;
	}
}
