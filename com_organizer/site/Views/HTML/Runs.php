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
 * Class loads persistent information a filtered set of runs into the display context.
 */
class Runs extends ListView
{
	protected $rowStructure = ['checkbox' => '', 'name' => 'link', 'startDate' => 'link', 'endDate' => 'link'];

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
			'checkbox'  => '',
			'name'      => Helpers\HTML::sort('NAME', 'name', $direction, $ordering),
			'startDate' => Helpers\Languages::_('ORGANIZER_START_DATE'),
			'endDate'   => Helpers\Languages::_('ORGANIZER_END_DATE')
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
			$item->name = "$item->name - $item->term";
			$run        = json_decode($item->run, true);

			if (empty($run) or empty($run['runs']))
			{
				$item->startDate = '';
				$item->endDate   = '';
			}
			else
			{
				$item->startDate = Helpers\Dates::formatDate(reset($run['runs'])['startDate']);
				$item->endDate   = Helpers\Dates::formatDate(end($run['runs'])['endDate']);
			}

			$structuredItems[$index] = $this->structureItem($index, $item, $item->link);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
