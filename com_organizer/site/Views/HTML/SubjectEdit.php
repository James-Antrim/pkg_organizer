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
 * Class loads persistent information about a subject into the display context.
 */
class SubjectEdit extends EditView
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
			$title  = "ORGANIZER_SUBJECT_EDIT";
		}
		else
		{
			$apply  = 'ORGANIZER_CREATE';
			$cancel = 'ORGANIZER_CANCEL';
			$save   = 'ORGANIZER_CREATE';
			$title  = "ORGANIZER_SUBJECT_NEW";
		}

		Helpers\HTML::setTitle(Helpers\Languages::_($title), 'book');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'apply', Helpers\Languages::_($apply), 'subjects.apply', false);
		$toolbar->appendButton('Standard', 'save', Helpers\Languages::_($save), "subjects.save", false);
		$toolbar->appendButton('Standard', 'cancel', Helpers\Languages::_($cancel), "subjects.cancel", false);
	}

	/**
	 * Adds styles and scripts to the document
	 *
	 * @return void  modifies the document
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		Factory::getDocument()->addStyleSheet(Uri::root() . 'components/com_organizer/css/curriculum_settings.css');
	}
}
