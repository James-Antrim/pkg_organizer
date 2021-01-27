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
	protected $_layout = 'help-wrapper';

	/**
	 * Adds a toolbar and title to the view.
	 *
	 * @return void  adds toolbar items to the view
	 */
	protected function addToolBar()
	{
		//https://www.thm.de/dev/organizer/?option=com_organizer&view=help
		$layout = strtoupper(Helpers\Input::getCMD('layout', 'toc'));
		$title  = Languages::_('ORGANIZER_HELP_TOPICS') . ' - ' . Languages::_("ORGANIZER_$layout");

		Helpers\HTML::setTitle($title, 'info');
	}

	/**
	 * @inheritDoc
	 */
	public function display($tpl = null)
	{
		$this->addToolBar();
		$this->modifyDocument();
		parent::display($tpl);
	}
}