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
class Surfaces extends ListView
{
	protected $rowStructure = ['checkbox' => '', 'code' => 'link', 'name' => 'link'];

	/**
	 * @inheritDoc
	 */
	protected function addToolBar(bool $delete = false)
	{
		parent::addToolBar($delete);
	}

	/**
	 * @inheritdoc
	 */
	public function setHeaders()
	{
		$headers = [
			'checkbox' => '',
			'code'     => Helpers\Languages::_('ORGANIZER_CODE'),
			'name'     => Helpers\Languages::_('ORGANIZER_DESC')
		];

		$this->headers = $headers;
	}
}
