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
 * Class modifies the document for the output of a menu like list of resource management views.
 */
class Organizer extends BaseHTMLView
{
	public $menuItems;

	/**
	 * Method to get display
	 *
	 * @param   Object  $tpl  template  (default: null)
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
		$this->addMenu();
		$this->modifyDocument();
		$this->addToolBar();

		parent::display($tpl);
	}

	/**
	 * Creates a toolbar
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_('ORGANIZER_MAIN'), 'organizer');

		if (Helpers\Can::administrate())
		{
			/*$toolbar = Toolbar::getInstance();
			$this->getModel()->showConfigurationMigrationButtons($toolbar);
			$this->getModel()->showScheduleMigrationButton($toolbar);*/
			Helpers\HTML::setPreferencesButton();
		}
	}

	/**
	 * Modifies document variables and adds links to external files
	 *
	 * @return void
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();
		Factory::getDocument()->addStyleSheet(Uri::root() . 'components/com_organizer/css/organizer.css');
	}
}
