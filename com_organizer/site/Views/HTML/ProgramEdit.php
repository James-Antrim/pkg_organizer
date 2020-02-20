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
use Organizer\Helpers\Languages; // Exception for frequency of use

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
		$new   = empty($this->item->id);
		$title = $new ?
			Languages::_('ORGANIZER_PROGRAM_NEW') : Languages::_('ORGANIZER_PROGRAM_EDIT');
		Helpers\HTML::setTitle($title, 'list');
		$toolbar   = Toolbar::getInstance();
		$applyText = $new ? Languages::_('ORGANIZER_CREATE') : Languages::_('ORGANIZER_APPLY');
		$toolbar->appendButton('Standard', 'apply', $applyText, 'programs.apply', false);
		$toolbar->appendButton('Standard', 'save', Languages::_('ORGANIZER_SAVE'), 'programs.save', false);
		$toolbar->appendButton(
			'Standard',
			'save-new',
			Languages::_('ORGANIZER_SAVE2NEW'),
			'programs.save2new',
			false
		);
		if (!$new)
		{
			$toolbar->appendButton(
				'Standard',
				'save-copy',
				Languages::_('ORGANIZER_SAVE2COPY'),
				'programs.save2copy',
				false
			);

			$poolLink = 'index.php?option=com_organizer&view=pool_selection&tmpl=component';
			$toolbar->appendButton('Popup', 'list', Languages::_('ORGANIZER_ADD_POOL'), $poolLink);
		}
		$cancelText = $new ? Languages::_('ORGANIZER_CANCEL') : Languages::_('ORGANIZER_CLOSE');
		$toolbar->appendButton('Standard', 'cancel', $cancelText, 'programs.cancel', false);
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
