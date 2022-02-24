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
 * Class loads a filtered set of campuses into the display context.
 */
class Campuses extends ListView
{
	protected $rowStructure = [
		'checkbox' => '',
		'name'     => 'link',
		'address'  => 'link',
		'location' => 'value',
		'gridID'   => 'link'
	];

	/**
	 * @inheritdoc
	 */
	protected function authorize()
	{
		if (!Helpers\Can::manage('facilities'))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function setHeaders()
	{
		$headers = [
			'checkbox' => '',
			'name'     => Helpers\Languages::_('ORGANIZER_NAME'),
			'address'  => Helpers\Languages::_('ORGANIZER_STREET'),
			'location' => Helpers\Languages::_('ORGANIZER_LOCATION'),
			'gridID'   => Helpers\Languages::_('ORGANIZER_GRID')
		];

		$this->headers = $headers;
	}

	/**
	 * @inheritdoc
	 */
	protected function structureItems()
	{
		$link            = 'index.php?option=com_organizer&view=campus_edit&id=';
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			if (empty($item->parentID))
			{
				$index = $item->name;
			}
			else
			{
				$index      = "{$item->parentName}-{$item->name}";
				$item->name = "|&nbsp;&nbsp;-&nbsp;{$item->name}";
			}

			$address    = '';
			$ownAddress = (!empty($item->address) or !empty($item->city) or !empty($item->zipCode));

			if ($ownAddress)
			{
				$addressParts   = [];
				$addressParts[] = empty($item->address) ? empty($item->parentAddress) ?
					'' : $item->parentAddress : $item->address;
				$addressParts[] = empty($item->city) ? empty($item->parentCity) ? '' : $item->parentCity : $item->city;
				$addressParts[] = empty($item->zipCode) ? empty($item->parentZIPCode) ?
					'' : $item->parentZIPCode : $item->zipCode;
				$address        = implode(' ', $addressParts);
			}

			$item->address  = $address;
			$item->location = Helpers\Campuses::getPin($item->location);

			if (!empty($item->gridName))
			{
				$gridName = $item->gridName;
			}
			elseif (!empty($item->parentGridName))
			{
				$gridName = $item->parentGridName;
			}
			else
			{
				$gridName = Helpers\Languages::_('ORGANIZER_NONE_GIVEN');
			}
			$item->gridID = $gridName;

			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
		}

		asort($structuredItems);

		$this->items = $structuredItems;
	}
}
