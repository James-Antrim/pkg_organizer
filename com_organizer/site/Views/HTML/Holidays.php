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
use Organizer\Helpers\Languages;

/**
 * Class loads persistent information a filtered set of holidays into the display context.
 */
class Holidays extends ListView
{
	private const META = 2, OFFICIAL = 3, SOFT = 1;

	/**
	 * @inheritDoc
	 */
	public function setHeaders()
	{
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox' => '',
			'name'     => HTML::sort('NAME', 'name', $direction, $ordering),
			'dates'    => HTML::sort('DATE', 'startDate', $direction, $ordering),
			'type'     => Languages::_('ORGANIZER_TYPE'),
			'status'   => Languages::_('ORGANIZER_STATUS')
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
			self::SOFT     => 'ORGANIZER_HOLIDAY_SOFT',
			self::META     => 'ORGANIZER_HOLIDAY_META',
			self::OFFICIAL => 'ORGANIZER_HOLIDAY_OFFICIAL'
		];

		foreach ($this->items as $item)
		{
			$today = date('Y-m-d');
			$year  = date('Y', strtotime($item->startDate));

			$dateString = Helpers\Dates::getDisplay($item->startDate, $item->endDate);
			$name       = "$item->name ($year)";
			$status     = $item->endDate < $today ?
				Languages::_('ORGANIZER_EXPIRED') : Languages::_('ORGANIZER_CURRENT');
			$type       = $typeMap[$item->type];

			$thisLink      = $link . $item->id;
			$items[$index] = [
				'checkbox' => HTML::_('grid.id', $index, $item->id),
				'name'     => HTML::_('link', $thisLink, $name),
				'dates'    => HTML::_('link', $thisLink, $dateString),
				'type'     => HTML::_('link', $thisLink, Languages::_($type)),
				'status'   => HTML::_('link', $thisLink, $status)
			];

			$index++;
		}

		$this->items = $items;
	}
}