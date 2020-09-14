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
		$base = Uri::base();

		if (OrganizerHelper::getApplication()->isClient('administrator'))
		{
			return "$base?option=com_organizer";
		}

		$languageQuery = '';
		if ($tag = Input::getCMD('languageTag'))
		{
			$languageQuery .= "languageTag=$tag";
		}

		// If the menu is plausible redirect
		if ($menuID = Input::getItemid() and !OrganizerHelper::getApplication()->getMenu()->getItem($menuID)->home)
		{
			$url = $base . OrganizerHelper::getApplication()->getMenu()->getItem($menuID)->route . '?';

			return $languageQuery ? $url . $languageQuery : $url;
		}

		$base = "$base?option=com_organizer";

		return $languageQuery ? $base . $languageQuery : $base;
	}
}
