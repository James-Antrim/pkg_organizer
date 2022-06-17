<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Validators;

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
	 * @inheritDoc
	 */
	public static function setID(Schedule $model, string $code)
	{
		$room  = $model->rooms->$code;
		$table = new Tables\Rooms();

		if (!$table->load(['code' => $room->code]))
		{
			$model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_ROOM_MISSING_FROM_INVENTORY'), $code);

			return;
		}

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

		$model->rooms->$code->id = $table->id;
	}

	/**
	 * Checks whether nodes have the expected structure and required information
	 *
	 * @param   Schedule  $model  the model for the schedule being validated
	 *
	 * @return void modifies &$model
	 */
	public static function setWarnings(Schedule $model)
	{
		if (!empty($model->warnings['REX']))
		{
			$warningCount = $model->warnings['REX'];
			unset($model->warnings['REX']);
			$model->warnings[] = sprintf(Helpers\Languages::_('ORGANIZER_ROOM_EXTERNAL_IDS_MISSING'), $warningCount);
		}
	}

	/**
	 * @inheritDoc
	 */
	public static function validate(Schedule $model, SimpleXMLElement $node)
	{
		$internalID = strtoupper(str_replace('RM_', '', trim((string) $node[0]['id'])));

		if ($externalID = strtoupper(trim((string) $node->external_name)))
		{
			$code = $externalID;
		}
		else
		{
			$model->warnings['REX'] = empty($model->warnings['REX']) ? 1 : $model->warnings['REX'] + 1;

			$code = strpos($internalID, 'ONLINE') !== false ? 'ONLINE' : $internalID;
		}

		$capacity      = (int) $node->capacity;
		$buildingID    = null;
		$buildingREGEX = Helpers\Input::getParams()->get('buildingRegex');

		if (!empty($buildingREGEX) and preg_match("/$buildingREGEX/", $code, $matches))
		{
			$buildingID = Helpers\Buildings::getID($matches[1]);
		}

		$room              = new stdClass();
		$room->buildingID  = $buildingID;
		$room->effCapacity = $capacity;
		$room->maxCapacity = $capacity;
		$room->name        = $code;
		$room->code        = $code;

		$model->rooms->$internalID = $room;
		self::setID($model, $internalID);
	}
}
