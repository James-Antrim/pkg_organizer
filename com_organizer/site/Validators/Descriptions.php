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

/**
 * Provides functions for XML description validation and modeling.
 */
class Descriptions implements UntisXMLValidator
{
	/**
	 * Retrieves the resource id using the Untis ID. Creates the resource id if unavailable.
	 *
	 * @param   object  $model     the model for the schedule being validated
	 * @param   string  $code      the id of the resource in Untis
	 * @param   string  $typeFlag  the flag identifying the categorization resource
	 *
	 * @return void modifies the model, setting the id property of the resource
	 */
	public static function setID($model, string $code, $typeFlag = '')
	{
		$error    = 'ORGANIZER_';
		$resource = '';
		switch ($typeFlag)
		{
			case 'r':
				$error    .= 'ROOMTYPE_INVALID';
				$resource = 'Roomtypes';
				$table    = new Tables\Roomtypes();

				break;
			case 'u':
				$error    .= 'METHOD_INVALID';
				$resource = 'Methods';
				$table    = new Tables\Methods();

				break;
		}

		if (empty($table))
		{
			return;
		}

		// These are set by the administrator, so there is no case for saving a new resource on upload.
		if ($table->load(['code' => $code]))
		{
			$property                = strtolower($resource);
			$model->$property->$code = $table->id;
		}
		else
		{
			$model->errors[] = sprintf(Helpers\Languages::_($error), $code);
		}
	}

	/**
	 * Checks whether XML node has the expected structure and required
	 * information
	 *
	 * @param   object            $model  the model for the schedule being validated
	 * @param   SimpleXMLElement  $node   the node being validated
	 *
	 * @return void
	 */
	public static function validate($model, SimpleXMLElement $node)
	{
		$untisID = str_replace('DS_', '', trim((string) $node[0]['id']));
		$name    = trim((string) $node->longname);

		if (empty($name))
		{
			$model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_DESCRIPTION_NAME_MISSING'), $untisID);

			return;
		}

		$typeFlag   = strtolower(trim((string) $node->flags));
		$validFlags = ['f', 'r', 'u'];

		if (empty($typeFlag))
		{
			$model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_DESCRIPTION_TYPE_MISSING'), $name, $untisID);

			return;
		}

		if (!in_array($typeFlag, $validFlags))
		{
			$model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_DESCRIPTION_TYPE_INVALID'), $name, $untisID);

			return;
		}

		self::setID($model, $untisID, $typeFlag);
	}
}
