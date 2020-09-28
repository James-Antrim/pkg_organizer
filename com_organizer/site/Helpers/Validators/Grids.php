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
 * Class provides general functions for retrieving building data.
 */
class Grids extends Helpers\ResourceHelper implements UntisXMLValidator
{
	/**
	 * Retrieves the table id if existent.
	 *
	 * @param   string  $code  the grid name in untis
	 *
	 * @return mixed int id on success, otherwise null
	 */
	public static function getID($code)
	{
		$table = new Tables\Grids();

		return $table->load(['code' => $code]) ? $table->id : null;
	}

	/**
	 * Retrieves the grid id using the grid name. Creates the grid id if unavailable.
	 *
	 * @param   object  $model     the model for the schedule being validated
	 * @param   string  $gridName  the name of the grid
	 *
	 * @return void modifies the model, setting the id property of the resource
	 */
	public static function setID($model, $gridName)
	{
		if (empty($model->grids->$gridName))
		{
			return;
		}

		$grid       = $model->grids->$gridName;
		$grid->grid = json_encode($grid, JSON_UNESCAPED_UNICODE);
		$table      = new Tables\Grids();

		// No overwrites for global resources
		if (!$table->load(['code' => $gridName]))
		{
			$model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_GRID_INVALID'), $gridName);

			return;
		}

		$grid->id = $table->id;

		return;
	}

	/**
	 * Sets IDs for the grids collection.
	 *
	 * @param   object  $model  the model for the schedule being validated
	 *
	 * @return void modifies &$model
	 */
	public static function setIDs($model)
	{
		foreach (array_keys((array) $model->grids) as $gridName)
		{
			self::setID($model, $gridName);
		}
	}

	/**
	 * Checks whether pool nodes have the expected structure and required
	 * information
	 *
	 * @param   object            $model  the model for the schedule being validated
	 * @param   SimpleXMLElement  $node   the node being validated
	 *
	 * @return void
	 */
	public static function validate($model, $node)
	{
		// Not actually referenced but evinces data inconsistencies in Untis
		$exportKey = trim((string) $node[0]['id']);
		$gridName  = (string) $node->timegrid;
		$day       = (int) $node->day;
		$periodNo  = (int) $node->period;
		$startTime = trim((string) $node->starttime);
		$endTime   = trim((string) $node->endtime);

		$invalidKeys   = (empty($exportKey) or empty($gridName) or empty($periodNo));
		$invalidTimes  = (empty($day) or empty($startTime) or empty($endTime));
		$invalidPeriod = ($invalidKeys or $invalidTimes);

		if ($invalidPeriod)
		{
			if (!in_array(Helpers\Languages::_('ORGANIZER_PERIODS_INCONSISTENT'), $model->errors))
			{
				$model->errors[] = Helpers\Languages::_('ORGANIZER_PERIODS_INCONSISTENT');
			}

			return;
		}

		// Set the grid if not already existent
		if (empty($model->grids->$gridName))
		{
			$model->grids->$gridName          = new stdClass();
			$model->grids->$gridName->periods = new stdClass();
		}

		$grid = $model->grids->$gridName;

		if (!isset($grid->startDay) or $grid->startDay > $day)
		{
			$grid->startDay = $day;
		}

		if (!isset($grid->endDay) or $grid->endDay < $day)
		{
			$grid->endDay = $day;
		}

		$periods = $grid->periods;

		$periods->$periodNo            = new stdClass();
		$periods->$periodNo->startTime = $startTime;
		$periods->$periodNo->endTime   = $endTime;

		$label = (string) $node->label;
		if ($label and preg_match("/[a-zA-ZäÄöÖüÜß]+/", $label))
		{
			$periods->$periodNo->label_de = $label;
			$periods->$periodNo->label_en = $label;

			// This is an assumption, which can later be rectified as necessary.
			$periods->$periodNo->type = 'break';
		}
	}
}
