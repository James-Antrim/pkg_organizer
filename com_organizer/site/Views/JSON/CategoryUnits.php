<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\JSON;

use Organizer\Helpers;

/**
 * Class answers dynamic (degree) program related queries
 */
class CategoryUnits extends BaseView
{
	/**
	 * loads model data into view context
	 *
	 * @return void
	 */
	public function display()
	{
		$date   = Helpers\Input::getString('date');
		$date   = strtotime($date) ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
		$groups = [];

		foreach (Helpers\Categories::getGroups(Helpers\Input::getInt('categoryID')) as $group)
		{
			$group         = (object) $group;
			$group->events = [];

			foreach (Helpers\Groups::getEvents($group->id) as $event)
			{
				$event        = (object) $event;
				$event->units = [];

				foreach (Helpers\Events::getUnits($event->id, $date) as $unit)
				{
					$unit           = (object) $unit;
					$event->units[] = $unit;
				}

				if (count($event->units))
				{
					$group->events[] = $event;
				}
			}

			if (count($group->events))
			{
				$groups[] = $group;
			}
		}

		echo json_encode($groups, JSON_UNESCAPED_UNICODE);
	}
}
