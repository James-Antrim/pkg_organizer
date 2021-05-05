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
class Link extends ToolbarButton
{
	/**
	 * Button type
	 * @var    string
	 */
	protected $_name = 'Link';

	/**
	 * Fetch the HTML for the button
	 *
	 * @param   string  $type    unused
	 * @param   string  $icon    the unique icon suffix
	 * @param   string  $text    displayed text
	 * @param   string  $url     url
	 * @param   bool    $newTab  whether or not to open the link in a new tab
	 *
	 * @return  string  HTML string for the button
	 */
	public function fetchButton($type = 'Link', $icon = 'back', $text = '', $url = null, bool $newTab = false): string
	{
		$attribs = ['class="btn btn-small"', 'rel="help"', "href=\"$url\""];

		if ($newTab)
		{
			$attribs[] = 'target="_blank"';
		}

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
	 */
	public function fetchId($type = 'Link', $name = '')
	{
		return $this->_parent->getName() . '-' . $name;
	}
}
