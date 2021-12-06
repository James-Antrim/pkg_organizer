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

/**
 * Class loads persistent information a filtered set of event categories into the display context.
 */
class Categories extends ListView
{
	protected $rowStructure = [
		'checkbox' => '',
		'name'     => 'link',
		'active'   => 'value',
		'program'  => 'link',
		'code'     => 'link'
	];

	/**
	 * @inheritdoc
	 */
	protected function addToolBar(bool $delete = true)
	{
		Helpers\HTML::setTitle(Helpers\Languages::_('ORGANIZER_CATEGORIES'), 'list-2');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), 'categories.edit', true);
		$toolbar->appendButton(
			'Standard',
			'eye-open',
			Helpers\Languages::_('ORGANIZER_ACTIVATE'),
			'Categories.activate',
			false
		);
		$toolbar->appendButton(
			'Standard',
			'eye-close',
			Helpers\Languages::_('ORGANIZER_DEACTIVATE'),
			'Categories.deactivate',
			false
		);

		if (Helpers\Can::administrate())
		{
			$toolbar->appendButton(
				'Standard',
				'delete',
				Helpers\Languages::_('ORGANIZER_DELETE'),
				'Categories.delete',
				true
			);
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function authorize()
	{
		if (!Helpers\Can::scheduleTheseOrganizations())
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
			'checkbox' => '',
			'name'     => Helpers\HTML::sort('DISPLAY_NAME', 'name', $direction, $ordering),
			'active'   => Helpers\Languages::_('ORGANIZER_ACTIVE'),
			'program'  => Helpers\Languages::_('ORGANIZER_PROGRAM'),
			'code'     => Helpers\HTML::sort('UNTIS_ID', 'code', $direction, $ordering)
		];

		$this->headers = $headers;
	}

	/**
	 * @inheritdoc
	 */
	protected function structureItems()
	{
		$index           = 0;
		$link            = 'index.php?option=com_organizer&view=CategoryEdit&id=';
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$tip          = $item->active ? 'ORGANIZER_CLICK_TO_DEACTIVATE' : 'ORGANIZER_CLICK_TO_ACTIVATE';
			$item->active = $this->getToggle('categories', $item->id, $item->active, $tip, 'active');

			$item->program           = Helpers\Categories::getName($item->id);
			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
