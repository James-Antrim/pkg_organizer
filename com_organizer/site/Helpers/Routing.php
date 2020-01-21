<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use Joomla\CMS\Uri\Uri;

/**
 * Class provides generalized functions useful for several component files.
 */
class Routing
{
	/**
	 * Builds a the base url for redirection
	 *
	 * @return string the root url to redirect to
	 */
	public static function getRedirectBase()
	{
		$url = Uri::base();
		if ($menuID = Input::getItemid())
		{
			$url .= OrganizerHelper::getApplication()->getMenu()->getItem($menuID)->route . '?';
		}
		else
		{
			$url .= '?option=com_organizer';
		}

		if ($tag = Input::getCMD('languageTag'))
		{
			$url .= "&languageTag=$tag";
		}

		return $url;
	}
}
