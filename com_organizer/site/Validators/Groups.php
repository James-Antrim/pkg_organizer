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
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Groups extends Helpers\ResourceHelper implements UntisXMLValidator
{
	/**
	 * Retrieves the resource id using the Untis ID. Creates the resource id if unavailable.
	 *
	 * @param   Schedule  $model  the model for the schedule being validated
	 * @param   string    $code   the id of the resource in Untis
	 *
	 * @return void modifies the model, setting the id property of the resource
	 */
	public static function setID(Schedule $model, string $code)
	{
		$group = $model->groups->$code;

		$table  = new Tables\Groups();
		$exists = $table->load(['code' => $group->code]);

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

		$association = new Tables\Associations();
		if (!$association->load(['groupID' => $table->id, 'organizationID' => $model->organizationID]))
		{
			$association->save(['groupID' => $table->id, 'organizationID' => $model->organizationID]);
		}

		$model->groups->$code->id = $table->id;
	}

	/**
	 * Checks whether XML node has the expected structure and required
	 * information
	 *
	 * @param   Schedule          $model  the model for the schedule being validated
	 * @param   SimpleXMLElement  $node   the node being validated
	 *
	 * @return void
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public static function validate(Schedule $model, SimpleXMLElement $node)
	{
		$code     = str_replace('CL_', '', trim((string) $node[0]['id']));
		$fullName = trim((string) $node->longname);
		if (empty($fullName))
		{
			$model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_GROUP_FULLNAME_MISSING'), $code);

			return;
		}

		$name = trim((string) $node->classlevel);
		if (empty($name))
		{
			$model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_GROUP_NAME_MISSING'), $fullName, $code);

			return;
		}

		if (!$categoryID = str_replace('DP_', '', trim((string) $node->class_department[0]['id'])))
		{
			$model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_GROUP_CATEGORY_MISSING'), $fullName, $code);

			return;
		}
		elseif (!$category = $model->categories->$categoryID)
		{
			$model->errors[] = sprintf(
				Helpers\Languages::_('ORGANIZER_GROUP_CATEGORY_INCOMPLETE'),
				$fullName,
				$code,
				$categoryID
			);

			return;
		}

		if (!$gridName = (string) $node->timegrid)
		{
			$model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_GROUP_GRID_MISSING'), $fullName, $code);

			return;
		}
		elseif (!$grid = $model->grids->$gridName)
		{
			$model->errors[] = sprintf(
				Helpers\Languages::_('ORGANIZER_GROUP_GRID_INCOMPLETE'),
				$fullName,
				$code,
				$gridName
			);

			return;
		}

		$group              = new stdClass();
		$group->categoryID  = $category->id;
		$group->code        = $code;
		$group->fullName_de = $fullName;
		$group->fullName_en = $fullName;
		$group->name_de     = $name;
		$group->name_en     = $name;
		$group->gridID      = $grid->id;

		$model->groups->$code = $group;
		self::setID($model, $code);
	}
}
