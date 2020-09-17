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

use Organizer\Helpers;

if (!Helpers\Users::getUser()->authorise('core.manage', 'com_organizer'))
{
	$referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');
	Helpers\OrganizerHelper::message(Helpers\Languages::_('ORGANIZER_403'), 'error');
	Helpers\OrganizerHelper::getApplication()->redirect($referrer, 403);
}

Helpers\OrganizerHelper::setUp();
