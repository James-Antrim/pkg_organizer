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
use Joomla\CMS\Uri\Uri;
use Organizer\Adapters;
use Organizer\Helpers;

/**
 * Class modifies the document for the output of a menu like list of resource management views.
 */
class Organizer extends BaseHTMLView
{
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
			$uri    = (string) Uri::getInstance();
			$return = urlencode(base64_encode($uri));
			$link   = "index.php?option=com_config&view=component&component=com_organizer&return=$return";

			$toolbar = Toolbar::getInstance('toolbar');
			$toolbar->appendButton('Link', 'options', Helpers\Languages::_('ORGANIZER_SETTINGS'), $link);
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/organizer.css');
	}
}
