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
 * Class loads persistent information a filtered set of organizations into the display context.
 */
class Organizations extends ListView
{
	protected $rowStructure = ['checkbox' => '', 'shortName' => 'link', 'name' => 'link'];

	/**
	 * @inheritdoc
	 */
	protected function addToolBar(bool $delete = true)
	{
		$this->setTitle('ORGANIZER_ORGANIZATIONS');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'new', Helpers\Languages::_('ORGANIZER_ADD'), 'organizations.add', false);
		$toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), 'organizations.edit', true);
		$toolbar->appendButton(
			'Confirm',
			Helpers\Languages::_('ORGANIZER_DELETE_CONFIRM'),
			'delete',
			Helpers\Languages::_('ORGANIZER_DELETE'),
			'organizations.delete',
			true
		);
	}

	/**
	 * @inheritdoc
	 */
	public function setHeaders()
	{
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox'  => '',
			'shortName' => Helpers\HTML::sort('SHORT_NAME', 'shortName', $direction, $ordering),
			'name'      => Helpers\HTML::sort('NAME', 'name', $direction, $ordering)
		];

		$this->headers = $headers;
	}
}
