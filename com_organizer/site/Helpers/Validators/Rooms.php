<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers\Validators;

use Organizer\Helpers;
use Organizer\Tables;
use SimpleXMLElement;
use stdClass;

/**
 * Class provides general functions for retrieving room data.
 */
class Rooms extends Helpers\ResourceHelper implements UntisXMLValidator
{
	/**
	 * Retrieves the resource id using the Untis ID. Creates the resource id if unavailable.
	 *
	 * @param   object  $model  the model for the schedule being validated
	 * @param   string  $code   the id of the resource in Untis
	 *
	 * @return void modifies the model, setting the id property of the resource
	 */
	public static function setID($model, $code)
	{
		$room  = $model->rooms->$code;
		$table = new Tables\Rooms;

		if ($table->load(['code' => $room->code]))
		{
			$altered = false;
			foreach ($room as $key => $value)
			{
				if (property_exists($table, $key) and empty($table->$key) and !empty($value))
				{
					$table->set($key, $value);
					$altered = true;
				}
			}

			if ($altered)
			{
				$table->store();
			}
		}
		else
		{
			$table->save($room);
		}
		$model->rooms->$code->id = $table->id;

		return;
	}

	/**
	 * Checks whether nodes have the expected structure and required information
	 *
	 * @param   object  $model  the model for the schedule being validated
	 *
	 * @return void modifies &$model
	 */
	public static function setWarnings($model)
	{
		if (!empty($model->warnings['REX']))
		{
			$warningCount = $model->warnings['REX'];
			unset($model->warnings['REX']);
			$model->warnings[] = sprintf(Helpers\Languages::_('ORGANIZER_ROOM_EXTERNAL_IDS_MISSING'), $warningCount);
		}

		if (!empty($model->warnings['RT']))
		{
			$warningCount = $model->warnings['RT'];
			unset($model->warnings['RT']);
			$model->warnings[] = sprintf(Helpers\Languages::_('ORGANIZER_ROOMTYPES_MISSING'), $warningCount);
		}
	}

	/**
	 * Checks whether room nodes have the expected structure and required
	 * information
	 *
	 * @param   object            $model  the model for the schedule being validated
	 * @param   SimpleXMLElement  $node   the node being validated
	 *
	 * @return void
	 */
	public static function validate($model, $node)
	{
		$internalID = strtoupper(str_replace('RM_', '', trim((string) $node[0]['id'])));

		if ($externalID = strtoupper(trim((string) $node->external_name)))
		{
			$code = $externalID;
		}
		else
		{
			$model->warnings['REX'] = empty($model->warnings['REX']) ? 1 : $model->warnings['REX'] + 1;

			$code = $internalID;
		}

		$roomTypeID  = str_replace('DS_', '', trim((string) $node->room_description[0]['id']));
		$invalidType = (empty($roomTypeID) or empty($model->roomtypes->$roomTypeID));
		if ($invalidType)
		{
			$model->warnings['RT'] = empty($model->warnings['RT']) ? 1 : $model->warnings['RT'] + 1;

			$roomTypeID = null;
		}
		else
		{
			$roomTypeID = $model->roomtypes->$roomTypeID;
		}

		$capacity      = (int) $node->capacity;
		$buildingID    = null;
		$buildingREGEX = Helpers\Input::getParams()->get('buildingRegex');

		if (!empty($buildingREGEX) and preg_match("/$buildingREGEX/", $code, $matches))
		{
			$buildingID = Helpers\Buildings::getID($matches[1]);
		}

		$room             = new stdClass();
		$room->buildingID = $buildingID;
		$room->capacity   = $capacity;
		$room->name       = $code;
		$room->roomtypeID = $roomTypeID;
		$room->code       = $code;

		$model->rooms->$internalID = $room;
		self::setID($model, $internalID);
	}
}
