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

/**
 * Renders a link button
 */
class Script extends ToolbarButton
{
	/**
	 * Button type
	 * @var    string
	 */
	protected $_name = 'Script';

	/**
	 * Fetch the HTML for the button
	 *
	 * @param   string  $type  unused
	 * @param   string  $icon  the unique icon suffix
	 * @param   string  $text  displayed text
	 * @param   string  $script  the script to use
	 *
	 * @return  string  HTML string for the button
	 */
	public function fetchButton(
		string $type = 'Link',
		string $icon = 'back',
		string $text = '',
		string $event = '',
		string $script = ''
	): string {
		$attribs = ['class="btn btn-small"', "$event=\"$script\""];

		$attribs = implode(' ', $attribs);
		$icon    = "<span class=\"icon-$icon\" aria-hidden=\"true\"></span>";

		return "<a $attribs>$icon$text</a>";
	}

	/**
	 * Get the button CSS id
	 *
	 * @param   string  $type  The button type.
	 * @param   string  $name  The name of the button.
	 *
	 * @return  string  Button CSS Id
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function fetchId(string $type = 'Script', string $name = ''): string
	{
		return $this->_parent->getName() . '-' . $name;
	}
}
