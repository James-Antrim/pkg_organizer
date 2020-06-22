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
 * Class loads the instance form into display context.
 */
class InstanceEdit extends EditView
{
	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		if ($this->item->id)
		{
			$cancel = 'ORGANIZER_CLOSE';
			$save   = 'ORGANIZER_SAVE';
			$title  = "ORGANIZER_INSTANCE_EDIT";
		}
		else
		{
			$cancel = 'ORGANIZER_CANCEL';
			$save   = 'ORGANIZER_CREATE';
			$title  = "ORGANIZER_INSTANCE_NEW";
		}

		Helpers\HTML::setTitle(Helpers\Languages::_($title), 'contract-2');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'save', Helpers\Languages::_($save), 'instances.save', false);
		$toolbar->appendButton('Standard', 'cancel', Helpers\Languages::_($cancel), 'instances.cancel', false);
	}

	/**
	 * Adds styles and scripts to the document
	 *
	 * @return void  modifies the document
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		Factory::getDocument()->addScript(Uri::root() . 'components/com_organizer/js/instances.js');
	}
}