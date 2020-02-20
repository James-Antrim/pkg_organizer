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

use Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of colors into the display context.
 */
class Colors extends ListView
{
	protected $rowStructure = ['checkbox' => '', 'name' => 'link', 'color' => 'value'];

	/**
	 * Function to set the object's headers property
	 *
	 * @return void sets the object headers property
	 */
	public function setHeaders()
	{
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox' => '',
			'name'     => Helpers\HTML::sort('NAME', 'name', $direction, 'name'),
			'color'    => Helpers\Languages::_('ORGANIZER_COLOR')
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
		$link            = 'index.php?option=com_organizer&view=color_edit&id=';
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$item->color             = Helpers\Colors::getListDisplay($item->color, $item->id);
			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
