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
 * Provides general functions for course access checks, data retrieval and display.
 */
class Events extends Helpers\ResourceHelper implements UntisXMLValidator
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
		$event = $model->events->$code;
		$table = new Tables\Events();

		if ($table->load(['organizationID' => $event->organizationID, 'code' => $code]))
		{
			$altered = false;
			foreach ($event as $key => $value)
			{
				if (property_exists($table, $key))
				{
					// Protect manual name adjustment done in Organizer.
					if (in_array($key, ['name_de', 'name_en']) and !empty($table->$key))
					{
						continue;
					}
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
			$table->save($event);
		}

		$event->id = $table->id;
	}

	/**
	 * Creates a warning for missing subject no attributes.
	 *
	 * @param   Schedule  $model  the model for the schedule being validated
	 *
	 * @return void modifies &$model
	 */
	public static function setWarnings(Schedule $model)
	{
		if (!empty($model->warnings['SUNO']))
		{
			$warningCount = $model->warnings['SUNO'];
			unset($model->warnings['SUNO']);
			$model->warnings[] = sprintf(Helpers\Languages::_('ORGANIZER_EVENT_SUBJECTNOS_MISSING'), $warningCount);
		}
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
		$code = str_replace('SU_', '', trim((string) $node[0]['id']));
		$name = trim((string) $node->longname);

		if (empty($name))
		{
			$model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_EVENT_NAME_MISSING'), $code);

			return;
		}

		$subjectNo = trim((string) $node->text);

		if (empty($subjectNo))
		{
			$model->warnings['SUNO'] = empty($model->warnings['SUNO']) ? 1 : $model->warnings['SUNO'] + 1;
			$subjectNo               = '';
		}

		$event                 = new stdClass();
		$event->organizationID = $model->organizationID;
		$event->code           = $code;
		$event->name_de        = $name;
		$event->name_en        = $name;
		$event->subjectNo      = $subjectNo;

		$model->events->$code = $event;
		self::setID($model, $code);
	}
}
