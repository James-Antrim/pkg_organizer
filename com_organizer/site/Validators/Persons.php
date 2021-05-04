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
 * Provides general functions for person access checks, data retrieval and display.
 */
class Persons extends Helpers\ResourceHelper implements UntisXMLValidator
{
    /**
     * @inheritDoc
     */
    public static function setID(Schedule $model, string $code)
    {
        $person       = $model->persons->$code;
        $table        = new Tables\Persons();
        $loadCriteria = [];

        if (!empty($person->username)) {
            $loadCriteria[] = ['username' => $person->username];
        }
        if (!empty($person->forename)) {
            $loadCriteria[] = ['surname' => $person->surname, 'forename' => $person->forename];
        }
        $loadCriteria[] = ['code' => $person->code];

        $extPattern = "/^[v]?[A-ZÀ-ÖØ-Þ][a-zß-ÿ]{1,3}([A-ZÀ-ÖØ-Þ][A-ZÀ-ÖØ-Þa-zß-ÿ]*)$/";
        foreach ($loadCriteria as $criteria) {
            if ($exists = $table->load($criteria)) {
                $altered = false;
                foreach ($person as $key => $value) {

                    // This gets special handling
                    if ($key === 'code') {
                        continue;
                    }

                    if (property_exists($table, $key) and empty($table->$key) and !empty($value)) {
                        $table->set($key, $value);
                        $altered = true;
                    }
                }

                $replaceable    = !preg_match($extPattern, $table->code);
                $valid          = preg_match($extPattern, $code);
                $overwriteUntis = ($table->code != $code and $replaceable and $valid);
                if ($overwriteUntis) {
                    $table->code = $code;
                    $altered     = true;
                }

                if ($altered) {
                    $table->store();
                }

                break;
            }
        }

        // Entry not found
        if (!$exists) {
            $table->save($person);
        }

        $association = new Tables\Associations();
        if (!$association->load(['organizationID' => $model->organizationID, 'personID' => $table->id])) {
            $association->save(['organizationID' => $model->organizationID, 'personID' => $table->id]);
        }

        $model->persons->$code->id = $table->id;
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
        if (!empty($model->warnings['PEX'])) {
            $warningCount = $model->warnings['PEX'];
            unset($model->warnings['PEX']);
            $model->warnings[] = sprintf(Helpers\Languages::_('ORGANIZER_PERSON_EXTERNAL_IDS_MISSING'), $warningCount);
        }

        if (!empty($model->warnings['PFN'])) {
            $warningCount = $model->warnings['PFN'];
            unset($model->warnings['PFN']);
            $model->warnings[] = sprintf(Helpers\Languages::_('ORGANIZER_PERSON_FORENAMES_MISSING'), $warningCount);
        }
    }

    /**
     * @inheritDoc
     */
    public static function validate(Schedule $model, SimpleXMLElement $node)
    {
        $internalID = str_replace('TR_', '', trim((string)$node[0]['id']));

        if ($externalID = trim((string)$node->external_name)) {
            $untisID = $externalID;
        } else {
            $model->warnings['PEX'] = empty($model->warnings['PEX']) ? 1 : $model->warnings['PEX'] + 1;
            $untisID                = $internalID;
        }

        $surname = trim((string)$node->surname);
        if (empty($surname)) {
            $model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_PERSON_SURNAME_MISSING'), $internalID);

            return;
        }

        $person           = new stdClass();
        $person->surname  = $surname;
        $person->code     = $untisID;
        $person->username = trim((string)$node->payrollnumber);
        $person->title    = trim((string)$node->title);
        $person->forename = trim((string)$node->forename);

        if (empty($person->forename)) {
            $model->warnings['PFN'] = empty($model->warnings['PFN']) ? 1 : $model->warnings['PFN'] + 1;
        }

        $model->persons->$internalID = $person;

        self::setID($model, $internalID);
        Helpers\Organizations::setResource($person->id, 'personID');
    }
}
