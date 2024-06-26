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
use THM\Organizer\Tables\Terms as Table;

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Terms implements UntisXMLValidator
{
    /**
     * @inheritDoc
     */
    public static function setID(Schedule $controller, string $code): void
    {
        $loadCriteria = [
            ['code' => $code],
            ['endDate' => $controller->term->endDate, 'startDate' => $controller->term->startDate]
        ];

        $table = new Table();

        foreach ($loadCriteria as $criterion) {
            if ($exists = $table->load($criterion)) {
                break;
            }
        }

        if (!$exists) {
            $term         = (array) $controller->term;
            $term['code'] = $code;
            $shortEndYear = date('y', $term['endDate']);
            $startYear    = date('Y', $term['startDate']);
            $endYear      = date('Y', $term['endDate']);
            $shortYear    = $endYear !== $startYear ? "$startYear/$shortEndYear" : $startYear;

            switch ($term['name']) {
                case 'SS':
                    $term['name_de']     = "SS $shortYear";
                    $term['name_en']     = "Spring $startYear";
                    $term['fullName_de'] = "Sommersemester $shortYear";
                    $term['fullName_en'] = "Spring Term $startYear";
                    break;
                case 'WS':
                    $term['name_de']     = "WS $shortYear";
                    $term['name_en']     = "Fall $startYear";
                    $term['fullName_de'] = "Wintersemester $shortYear";
                    $term['fullName_en'] = "Fall Term $startYear";
                    break;
                default:
                    $term['name_de']     = "{$term['name']} $shortYear";
                    $term['name_en']     = "{$term['name']} $startYear";
                    $term['fullName_de'] = "{$term['name']} $shortYear";
                    $term['fullName_en'] = "{$term['name']} $startYear";
                    break;
            }
            $table->save($term);
        }

        $controller->termID = $table->id;
    }

    /**
     * @inheritDoc
     */
    public static function validate(Schedule $controller, SimpleXMLElement $node): void
    {
        $controller->schoolYear            = new stdClass();
        $controller->schoolYear->endDate   = trim((string) $node->schoolyearenddate);
        $controller->schoolYear->startDate = trim((string) $node->schoolyearbegindate);

        $validSYED = $controller->validateDate($controller->schoolYear->endDate, 'SCHOOL_YEAR_END_DATE');
        $validSYSD = $controller->validateDate($controller->schoolYear->startDate, 'SCHOOL_YEAR_START_DATE');
        $valid     = ($validSYED and $validSYSD);

        $term            = new stdClass();
        $term->endDate   = trim((string) $node->termenddate);
        $validTED        = $controller->validateDate($term->endDate, 'TERM_END_DATE');
        $term->code      = trim((string) $node->footer);
        $validTN         = $controller->validateText($term->code, 'TERM_NAME', '/[\#\;]/');
        $term->startDate = trim((string) $node->termbegindate);
        $validTSD        = $controller->validateDate($term->startDate, 'TERM_START_DATE');
        $valid           = ($valid and $validTED and $validTN and $validTSD);

        // Data type / value checks failed.
        if (!$valid) {
            $controller->errors[] = Text::_('TERM_INVALID');

            return;
        }

        $endTimeStamp = strtotime($term->endDate);

        if ($endTimeStamp < strtotime(date('Y-m-d'))) {
            $controller->errors[] = Text::_('TERM_EXPIRED');

            return;
        }

        $invalidEnd = $endTimeStamp > strtotime($controller->schoolYear->endDate);

        $startTimeStamp = strtotime($term->startDate);
        $invalidStart   = $startTimeStamp < strtotime($controller->schoolYear->startDate);

        $invalidPeriod = $startTimeStamp >= $endTimeStamp;
        $invalid       = ($invalidStart or $invalidEnd or $invalidPeriod);

        // Consistency among the dates failed.
        if ($invalid) {
            $controller->errors[] = Text::_('TERM_INVALID');

            return;
        }

        $controller->term = $term;
        $code             = date('y', strtotime($term->endDate)) . $term->code;

        self::setID($controller, $code);
    }
}
