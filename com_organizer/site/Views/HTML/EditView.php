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

use Joomla\CMS\Toolbar\Toolbar;
use Organizer\Helpers;

/**
 * Class loads the resource form into display context. Specific resource determined by extending class.
 */
abstract class EditView extends FormView
{
	public $item = null;

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		$resource   = Helpers\OrganizerHelper::classEncode($this->getName());
		$constant   = strtoupper($resource);
		$controller = Helpers\OrganizerHelper::getPlural($resource);

		if ($this->item->id)
		{
			$title      = Helpers\Languages::_("ORGANIZER_{$constant}_EDIT");
			$cancelText = Helpers\Languages::_('ORGANIZER_CLOSE');
			$saveText   = Helpers\Languages::_('ORGANIZER_SAVE');
		}
		else
		{
			$title      = Helpers\Languages::_("ORGANIZER_{$constant}_NEW");
			$cancelText = Helpers\Languages::_('ORGANIZER_CANCEL');
			$saveText   = Helpers\Languages::_('ORGANIZER_CREATE');
		}

		Helpers\HTML::setTitle($title, 'cog');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'save', $saveText, "$controller.save", false);
		$toolbar->appendButton('Standard', 'cancel', $cancelText, "$controller.cancel", false);
	}

	/**
	 * Method to get display
	 *
	 * @param   Object  $tpl  template  (default: null)
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
		$this->item = $this->getModel()->getItem(Helpers\Input::getSelectedID());
		parent::display($tpl);
	}
}
