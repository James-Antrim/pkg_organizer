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
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Groups extends Helpers\ResourceHelper implements UntisXMLValidator
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
		$group = $model->groups->$code;

		$table  = new Tables\Groups;
		$exists = $table->load(['code' => $group->untisID]);

		if ($exists)
		{
			$altered = false;
			foreach ($group as $key => $value)
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
			$table->save($group);
		}

		$model->groups->$code->id = $table->id;

		return;
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
	public static function validate($model, $node)
	{
		$untisID  = str_replace('CL_', '', trim((string) $node[0]['id']));
		$fullName = trim((string) $node->longname);
		if (empty($fullName))
		{
			$model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_GROUP_FULLNAME_MISSING'), $untisID);

			return;
		}

		$name = trim((string) $node->classlevel);
		if (empty($name))
		{
			$model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_GROUP_NAME_MISSING'), $fullName, $untisID);

			return;
		}

		$categoryID = str_replace('DP_', '', trim((string) $node->class_department[0]['id']));
		if (empty($categoryID))
		{
			$model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_GROUP_CATEGORY_MISSING'), $fullName, $untisID);

			return;
		}
		elseif (empty($model->categories->$categoryID))
		{
			$model->errors[] = sprintf(
				Helpers\Languages::_('ORGANIZER_GROUP_CATEGORY_INCOMPLETE'),
				$fullName,
				$untisID,
				$categoryID
			);

			return;
		}

		$gridName = (string) $node->timegrid;
		if (empty($gridName))
		{
			$model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_GROUP_GRID_MISSING'), $fullName, $untisID);

			return;
		}
		elseif (empty($model->periods->$gridName))
		{
			$model->errors[] = sprintf(
				Helpers\Languages::_('ORGANIZER_GROUP_GRID_INCOMPLETE'),
				$fullName,
				$untisID,
				$gridName
			);

			return;
		}

		$group             = new stdClass;
		$group->categoryID = $categoryID;
		$group->untisID    = $untisID;
		$group->fullName   = $fullName;
		$group->name       = $name;
		$group->gridID     = Grids::getID($gridName);

		$model->groups->$untisID = $group;
		self::setID($model, $untisID);
	}
}
