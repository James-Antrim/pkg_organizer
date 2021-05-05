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
 * Class loads persistent information a filtered set of fields (of expertise) into the display context.
 */
class FieldColors extends ListView
{
	protected $rowStructure = ['checkbox' => '', 'field' => 'link', 'organization' => 'link', 'color' => 'value'];

	/**
	 * @inheritdoc
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_("ORGANIZER_FIELD_COLORS"), 'list-2');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'link', Helpers\Languages::_('ORGANIZER_ADD'), "field_colors.add", false);
		$toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), "field_colors.edit", true);

		$toolbar->appendButton(
			'Confirm',
			Helpers\Languages::_('ORGANIZER_DELETE_CONFIRM'),
			'delete',
			Helpers\Languages::_('ORGANIZER_DELETE'),
			"field_colors.delete",
			true
		);

		$toolbar->appendButton('Standard', 'lamp', Helpers\Languages::_('ORGANIZER_FIELD_NEW'), 'fields.add', false);
		$toolbar->appendButton('Standard', 'palette', Helpers\Languages::_('ORGANIZER_COLOR_NEW'), 'colors.add', false);
	}

	/**
	 * @inheritdoc
	 */
	protected function authorize()
	{
		if (!Helpers\Can::documentTheseOrganizations())
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
			'checkbox'     => '',
			'field'        => Helpers\HTML::sort('FIELD', 'field', $direction, $ordering),
			'organization' => Helpers\HTML::sort('ORGANIZATION', 'organization', $direction, $ordering),
			'color'        => Helpers\Languages::_('ORGANIZER_COLOR')
		];

		$this->headers = $headers;
	}

	/**
	 * @inheritdoc
	 */
	protected function structureItems()
	{
		$index           = 0;
		$link            = 'index.php?option=com_organizer&view=field_color_edit&id=';
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$item->color = Helpers\Colors::getListDisplay($item->color, $item->colorID);

			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
