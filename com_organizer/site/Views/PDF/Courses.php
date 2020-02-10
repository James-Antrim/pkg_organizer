<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\PDF;

use Exception;
use Organizer\Helpers\Can;
use Organizer\Views\BaseView;
use OrganizerTemplateBadges;
use OrganizerTemplateDepartment_Participants;
use OrganizerTemplateParticipants;

/**
 * Class loads persistent information about a course into the display context.
 */
class Courses extends BaseView
{
	const BADGES = 2;
	const ORGANIZATION_PARTICIPANTS = 1;
	const PARTICIPANTS = 0;

	/**
	 * Method to get display
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return void
	 * @throws Exception => invalid request / unauthorized access
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function display($tpl = null)
	{
		$input = OrganizerHelper::getInput();

		$courseID   = $input->get('lessonID', 0);
		$type       = $input->get('type', 0);
		$validTypes = [self::BADGES, self::ORGANIZATION_PARTICIPANTS, self::PARTICIPANTS];

		if (empty($courseID) or !in_array($type, $validTypes))
		{
			throw new Exception(Languages::_('ORGANIZER_400'), 400);
		}

		if (!Can::manage('course', $courseID))
		{
			throw new Exception(Languages::_('ORGANIZER_401'), 401);
		}

		switch ($type)
		{
			case self::BADGES:
				require_once __DIR__ . '/tmpl/badges.php';
				new OrganizerTemplateBadges($courseID);
				break;
			case self::ORGANIZATION_PARTICIPANTS:
				require_once __DIR__ . '/tmpl/department_participants.php';
				new OrganizerTemplateDepartment_Participants($courseID);
				break;
			case self::PARTICIPANTS:
				require_once __DIR__ . '/tmpl/participants.php';
				new OrganizerTemplateParticipants($courseID);
				break;
			default:
				throw new Exception(Languages::_('ORGANIZER_400'), 400);
		}
	}
}
