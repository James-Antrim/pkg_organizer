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
	 * Fetch the HTML for the button
	 *
	 * @param   string  $type  Unused string.
	 * @param   string  $name  The name of the button icon class.
	 * @param   string  $text  Button text.
	 * @param   string  $task  Task associated with the button.
	 * @param   bool    $list  True to allow lists
	 *
	 * @return  string  HTML string for the button
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

		Helpers\HTML::_('behavior.core');

		return "<button $target $task $buttonClass><span $iconClass $aria></span>$text</button>";
	}
}
