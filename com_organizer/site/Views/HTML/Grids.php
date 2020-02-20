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
 * Class loads persistent information a filtered set of schedule grids into the display context.
 */
class Grids extends ListView
{
	protected $rowStructure = [
		'checkbox'  => '',
		'name'      => 'link',
		'startDay'  => 'value',
		'endDay'    => 'value',
		'startTime' => 'value',
		'endTime'   => 'value',
		'isDefault' => 'value'
	];

	/**
	 * Function to set the object's headers property
	 *
	 * @return void sets the object headers property
	 */
	public function setHeaders()
	{
		$headers = [
			'checkbox'  => '',
			'name'      => Helpers\Languages::_('ORGANIZER_NAME'),
			'startDay'  => Helpers\Languages::_('ORGANIZER_START_DAY'),
			'endDay'    => Helpers\Languages::_('ORGANIZER_END_DAY'),
			'startTime' => Helpers\Languages::_('ORGANIZER_START_TIME'),
			'endTime'   => Helpers\Languages::_('ORGANIZER_END_TIME'),
			'isDefault' => Helpers\Languages::_('ORGANIZER_DEFAULT')
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
			$grid = json_decode($item->grid, true);

			if (!empty($grid['periods']))
			{
				// 'l' (lowercase L) in date function for full textual day of the week.
				$startDayConstant = strtoupper(date('l', strtotime("Sunday + {$grid['startDay']} days")));
				$endDayConstant   = strtoupper(date('l', strtotime("Sunday + {$grid['endDay']} days")));

				$item->startDay  = Helpers\Languages::_($startDayConstant);
				$item->endDay    = Helpers\Languages::_($endDayConstant);
				$item->startTime = Helpers\Dates::formatTime(reset($grid['periods'])['startTime']);
				$item->endTime   = Helpers\Dates::formatTime(end($grid['periods'])['endTime']);
			}
			else
			{
				$item->startDay  = '';
				$item->endDay    = '';
				$item->startTime = '';
				$item->endTime   = '';
			}

			$tip                     = Helpers\Languages::_('ORGANIZER_GRID_DEFAULT_DESC');
			$item->isDefault         = $this->getToggle('grid', $item->id, $item->isDefault, $tip);
			$structuredItems[$index] = $this->structureItem($index, $item, $item->link);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
