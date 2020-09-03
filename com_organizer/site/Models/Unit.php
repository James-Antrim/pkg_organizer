<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Exception;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored unit data.
 */
class Unit extends BaseModel
{
	/**
	 * Creates a course based on the information associated with the given unit.
	 *
	 * @return int the id of the newly created course
	 * @throws Exception
	 */
	public function addCourse()
	{
		if (!$unitID = Helpers\Input::getSelectedID())
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_400'), 400);
		}

		$unit = new Tables\Units();
		if (!$unit->load($unitID))
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_412'), 412);
		}
		elseif ($unit->courseID)
		{
			return $unit->courseID;
		}

		$authorized = Helpers\Can::scheduleTheseOrganizations();
		if (!in_array($unit->organizationID, $authorized))
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_401'), 401);
		}

		$course = new Tables\Courses();
		$course->organizationID = $unit->organizationID;

		echo "<pre>" . print_r($unitID, true) . "</pre>";
		echo "<pre>here</pre>";
		die;
#insert INTO v7ocf_organizer_courses (campusID, name_de, name_en, termID, deadline, description_de, description_en, fee, maxParticipants, registrationType)
#SELECT DISTINCT e.campusID, e.name_de, e.name_en, u.termID, e.deadline, e.description_de, e.description_en, e.fee, e.maxParticipants, e.registrationType
#FROM v7ocf_organizer_events AS e
#INNER JOIN v7ocf_organizer_instances AS i ON i.`eventID` = e.`id`
#INNER JOIN v7ocf_organizer_instance_persons AS ip ON ip.instanceID = i.id
#INNER JOIN v7ocf_organizer_instance_rooms AS ir ON ir.`assocID` = ip.`id`
#INNER JOIN v7ocf_organizer_rooms AS r ON r.id = ir.`roomID`
#INNER JOIN v7ocf_organizer_buildings AS b ON b.id = r.`buildingID`
#INNER JOIN v7ocf_organizer_campuses AS c ON c.id = b.`campusID`
#INNER JOIN v7ocf_organizer_units AS u ON u.id = i.`unitID`
#INNER JOIN v7ocf_thm_organizer_user_lessons AS ul ON ul.`lessonID` = u.id
#WHERE e.`preparatory` = 1 AND ul.`status` = 1
		return 0;
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Units A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Units;
	}
}
