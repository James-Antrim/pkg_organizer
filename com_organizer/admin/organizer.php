<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Admin;

require_once JPATH_COMPONENT_SITE . '/autoloader.php';

use Exception;
use Joomla\CMS\Factory;
use Organizer\Helpers;

if (!Factory::getUser()->authorise('core.manage', 'com_organizer'))
{
	throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
}

Helpers\OrganizerHelper::setUp();
