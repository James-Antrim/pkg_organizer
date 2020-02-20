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
 * Class loads persistent information a filtered set of room types into the display context.
 */
class Roomtypes extends ListView
{
	protected $rowStructure = [
		'checkbox'    => '',
		'code'        => 'link',
		'name'        => 'link',
		'minCapacity' => 'value',
		'maxCapacity' => 'value',
		'roomCount'   => 'value'
	];

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_('ORGANIZER_ROOMTYPES'), 'cog');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'new', Helpers\Languages::_('ORGANIZER_ADD'), 'roomtypes.add', false);
		$toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), 'roomtypes.edit', true);
		$toolbar->appendButton(
			'Confirm',
			Helpers\Languages::_('ORGANIZER_DELETE_CONFIRM'),
			'delete',
			Helpers\Languages::_('ORGANIZER_DELETE'),
			'roomtypes.delete',
			true
		);
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
			'checkbox'    => '',
			'code'        => Helpers\HTML::sort('UNTIS_ID', 'code', $direction, $ordering),
			'name'        => Helpers\HTML::sort('NAME', 'name', $direction, $ordering),
			'minCapacity' => Helpers\HTML::sort('MIN_CAPACITY', 'minCapacity', $direction, $ordering),
			'maxCapacity' => Helpers\HTML::sort('MAX_CAPACITY', 'maxCapacity', $direction, $ordering),
			'roomCount'   => Helpers\HTML::sort('ROOM_COUNT', 'roomCount', $direction, $ordering)
		];

		$this->headers = $headers;
	}
}
