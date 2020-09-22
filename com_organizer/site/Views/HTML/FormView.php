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
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers;

/**
 * Class loads a non-item based resource form (merge) into the display context. Specific resource determined by
 * extending class.
 */
abstract class FormView extends BaseHTMLView
{
	protected $_layout = 'form';

	public $params = null;

	public $form = null;

	/**
	 * Method to get display
	 *
	 * @param   Object  $tpl  template  (default: null)
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
		$this->form = $this->get('Form');

		// Allows for view specific toolbar handling
		$this->addToolBar();

		if (empty($this->adminContext))
		{
			if (method_exists($this, 'setSubtitle'))
			{
				$this->setSubtitle();
			}
			if (method_exists($this, 'addSupplement'))
			{
				$this->addSupplement();
			}
		}

		$this->modifyDocument();
		parent::display($tpl);
	}

	/**
	 * Adds a toolbar and title to the view.
	 *
	 * @return void  adds toolbar items to the view
	 */
	abstract protected function addToolBar();

	/**
	 * Adds styles and scripts to the document
	 *
	 * @return void  modifies the document
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		Helpers\HTML::_('behavior.formvalidator');

		$document = Factory::getDocument();
		$document->addScript(Uri::root() . 'components/com_organizer/js/multiple.js');
		$document->addScript(Uri::root() . 'components/com_organizer/js/submitButton.js');
		$document->addScript(Uri::root() . 'components/com_organizer/js/validators.js');
		$document->addStyleSheet(Uri::root() . 'components/com_organizer/css/form.css');
	}
}
