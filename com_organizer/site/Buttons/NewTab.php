<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Buttons;

use Joomla\CMS\Toolbar\Button\StandardButton;
use Joomla\CMS\Uri\Uri;
use Organizer\Adapters\Document;
use Organizer\Helpers;

/**
 * Renders a button whose contents open in a new tab.
 */
class NewTab extends StandardButton
{
	/**
	 * Button type
	 *
	 * @var    string
	 */
	protected $_name = 'NewTab';

	/**
	 * @inheritDoc
	 */
	public function fetchButton($type = 'NewTab', $name = '', $text = '', $task = '', $list = true): string
	{
		// Store all data to the options array for use with JLayout
		$aria        = 'aria-hidden="true"';
		$buttonClass = "class=\"btn btn-small button-$name\"";
		$target      = 'formtarget="_blank" type="submit"';
		$iconClass   = 'class="' . $this->fetchIconClass($name) . '"';
		$task        = 'onclick="' . $this->_getCommand($text, $task, $list) . '"';
		$text        = Helpers\Languages::_($text);

		Document::addScript(Uri::root() . 'components/com_organizer/js/newTab.js');

		return "<button $target $task $buttonClass><span $iconClass $aria></span>$text</button>";
	}

	/**
	 * Get the JavaScript command for the button
	 *
	 * @param   string  $name  The task name as seen by the user
	 * @param   string  $task  The task used by the application
	 * @param   bool    $list  True is requires a list confirmation.
	 *
	 * @return  string   JavaScript command string
	 */
	protected function _getCommand($name, $task, $list)
	{
		$cmd = "newTab('" . $task . "');";

		if ($list)
		{
			$alert = "alert(Joomla.JText._('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));";
			$cmd   = "if (document.adminForm.boxchecked.value == 0) { " . $alert . " } else { " . $cmd . " }";
		}

		return $cmd;
	}
}
