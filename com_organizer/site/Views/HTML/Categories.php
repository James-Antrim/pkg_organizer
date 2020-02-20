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
 * Class loads persistent information a filtered set of event categories into the display context.
 */
class Categories extends ListView
{
	protected $rowStructure = ['checkbox' => '', 'code' => 'link', 'name' => 'link', 'program' => 'link'];

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_('ORGANIZER_CATEGORIES'), 'list-2');
		$toolbar = Toolbar::getInstance();

		if ($admin = Helpers\Can::administrate())
		{
			$toolbar->appendButton('Standard', 'new', Helpers\Languages::_('ORGANIZER_ADD'), 'categories.add', false);
		}

		$toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), 'categories.edit', true);


		if ($admin)
		{
			$toolbar->appendButton(
				'Standard',
				'attachment',
				Helpers\Languages::_('ORGANIZER_MERGE'),
				'categories.mergeView',
				true
			);

			$toolbar->appendButton(
				'Confirm',
				Helpers\Languages::_('ORGANIZER_DELETE_CONFIRM'),
				'delete',
				Helpers\Languages::_('ORGANIZER_DELETE'),
				'categories.delete',
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
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox' => '',
			'code'     => Helpers\HTML::sort('UNTIS_ID', 'ppr.code', $direction, $ordering),
			'name'     => Helpers\HTML::sort('DISPLAY_NAME', 'ppr.name', $direction, $ordering),
			'program'  => Helpers\Languages::_('ORGANIZER_PROGRAM')
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
		$link            = 'index.php?option=com_organizer&view=category_edit&id=';
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$item->program           = Helpers\Categories::getName($item->id);
			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
