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

use Organizer\Adapters\Toolbar;
use Organizer\Helpers;
use Joomla\CMS\Factory;

/**
 * Class loads persistent information a filtered set of rooms into the display context.
 */
class Rooms extends ListView
{
	protected $rowStructure = [
		'checkbox'     => '',
		'roomName'     => 'link',
		'buildingName' => 'link',
		'roomType'     => 'link',
		'active'       => 'value'
	];

	/**
	 * @inheritdoc
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_('ORGANIZER_ROOMS'), 'enter');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'new', Helpers\Languages::_('ORGANIZER_ADD'), 'rooms.add', false);
		$toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), 'rooms.edit', true);
		$toolbar->appendButton(
			'Standard',
			'eye-open',
			Helpers\Languages::_('ORGANIZER_ACTIVATE'),
			'rooms.activate',
			false
		);
		$toolbar->appendButton(
			'Standard',
			'eye-close',
			Helpers\Languages::_('ORGANIZER_DEACTIVATE'),
			'rooms.deactivate',
			false
		);

		if (Helpers\Can::administrate())
		{
			$toolbar->appendButton(
				'Standard',
				'contract',
				Helpers\Languages::_('ORGANIZER_MERGE'),
				'rooms.mergeView',
				true
			);
		}

		$toolbar->appendButton(
			'NewTab',
			'file-xls',
			Helpers\Languages::_('ORGANIZER_UNINOW_EXPORT'),
			'Rooms.UniNow',
			false
		);

        $toolbar->appendButton(
            'NewTab',
            'file-xls',
            Helpers\Languages::_('ORGANIZER_FM_EXPORT'),
            'Rooms.FMExport',
            false
        );
	}

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
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox'     => Helpers\HTML::_('grid.checkall'),
			'roomName'     => Helpers\HTML::sort('NAME', 'roomName', $direction, $ordering),
			'buildingName' => Helpers\HTML::sort('BUILDING', 'buildingName', $direction, $ordering),
			'roomType'     => Helpers\HTML::sort('TYPE', 'roomType', $direction, $ordering),
			'active'       => Helpers\Languages::_('ORGANIZER_ACTIVE')
		];

		$this->headers = $headers;
	}

	/**
	 * @inheritdoc
	 */
	protected function structureItems()
	{
		$index           = 0;
		$link            = 'index.php?option=com_organizer&view=room_edit&id=';
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$tip          = $item->active ? 'ORGANIZER_CLICK_TO_DEACTIVATE' : 'ORGANIZER_CLICK_TO_ACTIVATE';
			$item->active = $this->getToggle('rooms', $item->id, $item->active, $tip, 'active');

			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
