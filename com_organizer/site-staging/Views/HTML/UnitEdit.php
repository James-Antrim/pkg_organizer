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
 * Class loads the grid form into display context.
 */
class UnitEdit extends EditView
{
	public $orientation = 'vertical';

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		$model = $this->getModel();

		$title = $model->my ? 'ORGANIZER_MANAGE_MY_UNIT' : 'ORGANIZER_UNIT_EDIT';

		Helpers\HTML::setTitle(Helpers\Languages::_($title), 'cog');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'save', Helpers\Languages::_('ORGANIZER_SAVE_CLOSE'), "Units.save", false);
		$toolbar->appendButton('Standard', 'cancel', Helpers\Languages::_('ORGANIZER_CLOSE'), "Units.cancel", false);
	}
}
