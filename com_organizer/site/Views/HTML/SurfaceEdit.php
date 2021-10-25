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
 * Class loads the din nrf form into display context.
 */
class SurfaceEdit extends EditView
{
	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		if ($this->form->getValue('id'))
		{
			$cancel = 'ORGANIZER_CLOSE';
			$save   = 'ORGANIZER_SAVE_CLOSE';
			$title  = "ORGANIZER_SURFACE_EDIT";
		}
		else
		{
			$cancel = 'ORGANIZER_CANCEL';
			$save   = 'ORGANIZER_CREATE_CLOSE';
			$title  = "ORGANIZER_SURFACE_NEW";
		}

		Helpers\HTML::setTitle(Helpers\Languages::_($title), 'cog');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'save', Helpers\Languages::_($save), "surfaces.save", false);
		$toolbar->appendButton('Standard', 'cancel', Helpers\Languages::_($cancel), "surfaces.cancel", false);
	}
}
