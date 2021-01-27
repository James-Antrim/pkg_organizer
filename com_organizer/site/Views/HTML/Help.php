<?php
/**
 * @package     Organizer\Views\HTML
 * @extension   Organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Organizer\Helpers;
use Organizer\Helpers\Languages;

class Help extends BaseView
{

	/**
	 * Adds a toolbar and title to the view.
	 *
	 * @return void  adds toolbar items to the view
	 */
	protected function addToolBar()
	{
		$layout = strtoupper(Helpers\Input::getCMD('layout'));
		$title  = Languages::_('ORGANIZER_HELP') . ' - ' . Languages::_("ORGANIZER_$layout");

		Helpers\HTML::setTitle($title);
	}

	/**
	 * @inheritDoc
	 */
	public function display($tpl = null)
	{
		if (!Helpers\Input::getCMD('layout'))
		{
			//Helpers\OrganizerHelper::error(400);
		}

		$this->_layout = 'help-wrapper';

		parent::display($tpl);
	}
}