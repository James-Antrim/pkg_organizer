<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers\Validators;

use SimpleXMLElement;
use stdClass;
use THM\Organizer\Adapters\Text;
use THM\Organizer\Controllers\Schedule;
use THM\Organizer\Tables\Events as Table;

/**
 * Provides general functions for course access checks, data retrieval and display.
 */
class Events implements UntisXMLValidator
{
    /**
     * @inheritDoc
     */
    public static function setID(Schedule $controller, string $code): void
    {
        $event = $controller->events->$code;
        $table = new Table();

        if ($table->load(['organizationID' => $event->organizationID, 'code' => $code])) {
            $altered = false;
            foreach ($event as $key => $value) {
                if (property_exists($table, $key)) {
                    // Protect manual name adjustment done in Organizer.
                    if (in_array($key, ['name_de', 'name_en']) and !empty($table->$key)) {
                        continue;
                    }
                    $table->set($key, $value);
                    $altered = true;
                }
            }

            if ($altered) {
                $table->store();
            }
        }
        else {
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
    public static function setWarnings(Schedule $model): void
    {
        if (!empty($model->warnings['SubjectNumber'])) {
            $warningCount = $model->warnings['SubjectNumber'];
            unset($model->warnings['SubjectNumber']);
            $model->warnings[] = Text::sprintf('EVENT_SUBJECTNOS_MISSING', $warningCount);
        }
    }

    /**
     * @inheritDoc
     */
    public static function validate(Schedule $controller, SimpleXMLElement $node): void
    {
        $code = str_replace('SU_', '', trim((string) $node[0]['id']));
        $name = trim((string) $node->longname);

        if (empty($name)) {
            $controller->errors[] = Text::sprintf('EVENT_NAME_MISSING', $code);

            return;
        }

        $subjectNo = trim((string) $node->text);

        if (empty($subjectNo)) {
            $controller->warnings['SubjectNumber'] = empty($controller->warnings['SubjectNumber']) ?
                1 : $controller->warnings['SubjectNumber'] + 1;

            $subjectNo = '';
        }

        $event                 = new stdClass();
        $event->organizationID = $controller->organizationID;
        $event->code           = $code;
        $event->name_de        = $name;
        $event->name_en        = $name;
        $event->subjectNo      = $subjectNo;

        $controller->events->$code = $event;
        self::setID($controller, $code);
    }
}
