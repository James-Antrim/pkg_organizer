<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Admin;

require_once JPATH_COMPONENT_SITE . '/autoloader.php';

use Organizer\Helpers;

if (!Helpers\Users::getUser()->authorise('core.manage', 'com_organizer')) {
    Helpers\OrganizerHelper::error(403);
}

Helpers\OrganizerHelper::setUp();
