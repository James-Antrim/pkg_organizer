<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Controllers;

use Exception;
use Organizer\Controller;
use Organizer\Helpers;
use Organizer\Models;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Organizer extends Controller
{
	/**
	 * Makes call to migrate the data
	 *
	 * @return void
	 * @throws Exception
	 */
	public function migrateConfigurations()
	{
		$model = new Models\Organizer;

		if ($model->migrateConfigurations())
		{
			Helpers\OrganizerHelper::message('Configurations have been migrated');
		}
		else
		{
			Helpers\OrganizerHelper::message('Configurations have not been migrated', 'error');
		}

		$url = Helpers\Routing::getRedirectBase() . "&view=organizer";
		$this->setRedirect($url);
	}

	/**
	 * Makes call to migrate the data
	 *
	 * @return void
	 * @throws Exception
	 */
	public function migrateSchedules()
	{
		$model = new Models\Organizer;

		if ($model->migrateSchedules())
		{
			Helpers\OrganizerHelper::message('Schedules have been migrated');
		}
		else
		{
			Helpers\OrganizerHelper::message('Schedules have not been migrated', 'error');
		}

		$url = Helpers\Routing::getRedirectBase() . "&view=organizer";
		$this->setRedirect($url);
	}

	/**
	 * Makes call to migrate the data
	 *
	 * @return void
	 * @throws Exception
	 */
	public function migrateUserLessons()
	{
		$model = new Models\Organizer;

		if ($model->migrateUserLessons())
		{
			Helpers\OrganizerHelper::message('User lessons have been migrated');
		}
		else
		{
			Helpers\OrganizerHelper::message('User lessons have not been migrated', 'error');
		}

		$url = Helpers\Routing::getRedirectBase() . "&view=organizer";
		$this->setRedirect($url);
	}
}
