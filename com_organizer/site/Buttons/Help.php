<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Organizer\Buttons;

use Joomla\CMS\Toolbar\Button\StandardButton;
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers\HTML;
use Organizer\Helpers\Languages;

/**
 * Renders a help popup window button
 */
class Help extends StandardButton
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
	 * @param   string  $type  unused
	 * @param   string  $name  the help topic.
	 * @param   bool    $text  whether to differentiate the help text
	 * @param   string  $task  unused
	 * @param   bool    $list  unused
	 *
	 * @return  string
	 */
	public function fetchButton($type = 'Help', $name = '', $text = true, $task = '', $list = true): string
	{
		$attribs  = ['class="btn btn-small"', 'rel="help"', 'onclick="' . $this->getCommand($name) . '"'];
		$attribs  = implode(' ', $attribs);
		$constant = ($name and $text) ? 'ORGANIZER_HELP_' . strtoupper($name) : 'ORGANIZER_HELP';
		$icon     = '<span class="icon-question-sign" aria-hidden="true"></span>';
		$text     = Languages::_($constant);

		HTML::_('behavior.core');

		return "<button $attribs>$icon$text</button>";
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
