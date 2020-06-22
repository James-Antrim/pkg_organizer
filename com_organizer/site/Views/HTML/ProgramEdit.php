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

use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers;

/**
 * Class loads the (degree) program form into display context.
 */
class ProgramEdit extends EditView
{
	protected $_layout = 'tabs';

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		if ($this->item->id)
		{
			$apply  = 'ORGANIZER_APPLY';
			$cancel = 'ORGANIZER_CLOSE';
			$save   = 'ORGANIZER_SAVE';
			$title  = "ORGANIZER_PROGRAM_EDIT";
		}
		else
		{
			$apply  = 'ORGANIZER_CREATE';
			$cancel = 'ORGANIZER_CANCEL';
			$save   = 'ORGANIZER_CREATE';
			$title  = "ORGANIZER_PROGRAM_NEW";
		}

		Helpers\HTML::setTitle(Helpers\Languages::_($title), 'list');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'apply', Helpers\Languages::_($apply), 'programs.apply', false);
		$toolbar->appendButton('Standard', 'save', Helpers\Languages::_($save), 'programs.save', false);

		if ($this->item->id)
		{
			$poolLink = 'index.php?option=com_organizer&tmpl=component';
			$poolLink .= "&type=program&id={$this->item->id}&view=pool_selection";
			$toolbar->appendButton('Popup', 'list', Helpers\Languages::_('ORGANIZER_ADD_POOL'), $poolLink);
		}

		$toolbar->appendButton('Standard', 'cancel', Helpers\Languages::_($cancel), 'programs.cancel', false);
	}
}
