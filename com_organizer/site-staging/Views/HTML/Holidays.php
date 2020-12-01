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
use Organizer\Helpers\HTML;

/**
 * Class loads persistent information a filtered set of holidays into the display context.
 */
class Holidays extends ListView
{
	private const OPTIONAL = 1, PARTIAL = 2, BLOCKING = 3;

	/**
	 * @inheritDoc
	 */
	public function setHeaders()
	{
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox'  => '',
			'name'      => HTML::sort('NAME', 'name', $direction, $ordering),
			'startDate' => HTML::sort('DATE', 'startDate', $direction, $ordering),
			'type'      => HTML::sort('TYPE', 'type', $direction, $ordering),
			'status'    => Helpers\Languages::_('ORGANIZER_STATUS')
		];

		$this->headers = $headers;
	}

	/**
	 * @inheritDoc
	 */
	protected function structureItems()
	{
		$index   = 0;
		$link    = 'index.php?option=com_organizer&view=holiday_edit&id=';
		$items   = [];
		$typeMap = [
			self::OPTIONAL => 'ORGANIZER_PLANNING_OPTIONAL',
			self::PARTIAL  => 'ORGANIZER_PLANNING_MANUAL',
			self::BLOCKING => 'ORGANIZER_PLANNING_BLOCKED'
		];

		foreach ($this->items as $item)
		{

			$dateString = Helpers\Dates::getDisplay($item->startDate, $item->endDate);
			$today      = Helpers\Dates::formatDate();
			$startDate  = Helpers\Dates::formatDate($item->startDate);
			$endDate    = Helpers\Dates::formatDate($item->endDate);
			$year       = date('Y', strtotime($item->startDate));

			if ($endDate < $today)
			{
				$status = Helpers\Languages::_('ORGANIZER_EXPIRED');
			}
			elseif ($startDate > $today)
			{
				$status = Helpers\Languages::_('ORGANIZER_PENDING');
			}
			else
			{
				$status = Helpers\Languages::_('ORGANIZER_CURRENT');
			}

			$name     = $item->name . "($year)";
			$constant = $typeMap[$item->type];

			$thisLink                   = $link . $item->id;
			$items[$index]              = [];
			$items[$index]['checkbox']  = HTML::_('grid.id', $index, $item->id);
			$items[$index]['name']      = HTML::_('link', $thisLink, $name);
			$items[$index]['startDate'] = HTML::_('link', $thisLink, $dateString);
			$items[$index]['type']      = HTML::_('link', $thisLink, Helpers\Languages::_($constant));
			$items[$index]['status']    = HTML::_('link', $thisLink, $status);

			$index++;
		}

		$this->items = $items;
	}
}