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

use Joomla\CMS\Toolbar\ToolbarButton;
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers\HTML;
use Organizer\Helpers\Languages;

/**
 * Renders a help popup window button
 */
class Help extends ToolbarButton
{
	/**
	 * Button type
	 *
	 * @var    string
	 */
	protected $_name = 'Help';

	/**
	 * Fetch the HTML for the button
	 *
	 * @param   string  $type         unused
	 * @param   string  $topic        the help topic.
	 * @param   bool    $showContext  whether to differentiate the help text
	 *
	 * @return  string
	 */
	public function fetchButton($type = 'Help', $topic = '', $showContext = true): string
	{
		$attribs     = ['class="btn btn-small"', 'rel="help"', 'onclick="' . $this->getCommand($topic) . '"'];
		$attribs     = implode(' ', $attribs);
		$constant    = ($topic and $showContext) ? 'ORGANIZER_HELP_' . strtoupper($topic) : 'ORGANIZER_HELP';
		$icon        = '<span class="icon-question-sign" aria-hidden="true"></span>';
		$showContext = Languages::_($constant);

		HTML::_('behavior.core');

		return "<button $attribs>$icon$showContext</button>";
	}

	/**
	 * Get the JavaScript command for the button
	 *
	 * @param   string  $topic  the help topic
	 *
	 * @return  string   JavaScript command string
	 */
	private function getCommand(string $topic): string
	{
		// Get Help URL
		$url = Uri::base() . "?option=com_organizer&view=help&topic=$topic&tmpl=component";
		$url = json_encode(htmlspecialchars($url, ENT_QUOTES), JSON_HEX_APOS);
		$url = substr($url, 1, -1);

		return "Joomla.popupWindow('$url', '" . Languages::_('ORGANIZER_HELP', true) . "', 700, 500, 1)";
	}

}
