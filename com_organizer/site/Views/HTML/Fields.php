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
 * Class loads persistent information a filtered set of fields (of expertise) into the display context.
 */
class Fields extends ListView
{
	protected $rowStructure = ['checkbox' => '', 'name' => 'link', 'code' => 'link', 'colors' => 'value'];

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_('ORGANIZER_FIELDS'), 'lamp');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'new', Helpers\Languages::_('ORGANIZER_ADD'), 'fields.add', false);
		$toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), 'fields.edit', true);
		$toolbar->appendButton(
			'Confirm',
			Helpers\Languages::_('ORGANIZER_DELETE_CONFIRM'),
			'delete',
			Helpers\Languages::_('ORGANIZER_DELETE'),
			'fields.delete',
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
		return Helpers\Can::administrate();
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
			'checkbox' => '',
			'field'    => Helpers\HTML::sort('NAME', 'field', $direction, $ordering),
			'code'     => Helpers\HTML::sort('CODE', 'code', $direction, $ordering),
			'colors'   => Helpers\Languages::_('ORGANIZER_COLORS')
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
		$link            = 'index.php?option=com_organizer&view=field_edit&id=';
		$structuredItems = [];
		$organizationID  = $this->state->get('filter.organizationID', 0);

		foreach ($this->items as $item)
		{
			$item->colors = Helpers\Fields::getListDisplay($item->id, $organizationID);

			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
