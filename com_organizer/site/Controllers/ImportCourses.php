<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use Joomla\Utilities\ArrayHelper;
use THM\Organizer\Adapters\{Application, Input, Text};
use THM\Organizer\Helpers\{Courses as Helper, Organizations as oHelper};
use THM\Organizer\Tables\{Courses as Table, Organizations as oTable, Terms, Units};

/** @inheritDoc */
class ImportCourses extends FormController
{
    protected string $list = 'Courses';

    /** @inheritDoc */
    protected function authorize(): void
    {
        if (!oHelper::schedulableIDs()) {
            Application::error(403);
        }
    }

    /**
     * Uses the model's upload function to validate and save the file to the database should validation be successful.
     * @return void
     */
    public function import(): void
    {
        $this->checkToken();
        $this->authorize();

        // Too big for joomla's comprehensive debugging.
        if (JDEBUG) {
            Application::message('ORGANIZER_DEBUG_ON', Application::ERROR);

            $this->setRedirect("$this->baseURL&view=courses");
            return;
        }

        $file = Input::getInput()->files->get('file');

        if (empty($file['type']) or $file['type'] !== 'text/plain') {
            Application::message('FILE_TYPE_INVALID', Application::ERROR);
            $this->setRedirect("$this->baseURL&view=importcourses");
            return;
        }

        if (mb_detect_encoding($file['tmp_name'], 'UTF-8', true) !== 'UTF-8') {
            Application::message('FILE_ENCODING_INVALID', Application::ERROR);
            $this->setRedirect("$this->baseURL&view=importcourses");
            return;
        }

        $organizationID = Input::getInt('organizationID');
        $termID         = Input::getInt('termID');

        if (!$organizationID or !$termID) {
            Application::message('400', Application::ERROR);
            $this->setRedirect("$this->baseURL&view=importcourses");
            return;
        }

        if (!oHelper::schedulable($organizationID)) {
            Application::error(403);
        }

        $organization = new oTable();
        $term         = new Terms();

        if (!$organization->load($organizationID) or !$term->load($termID)) {
            Application::error(500);
        }

        $codes = [];
        $file  = fopen($file['tmp_name'], 'r');

        while (($row = fgets($file)) !== false) {
            $row = str_replace(chr(13) . chr(10), '', $row);

            if (!$row = trim($row)) {
                continue;
            }

            if (!preg_match('/^[\d, ]+$/', $row)) {
                Application::message("Malformed row: $row.", Application::ERROR);
                continue;
            }

            $codes[] = array_filter(ArrayHelper::toInteger(explode(',', $row)));
        }

        fclose($file);

        foreach ($codes as $courseCodes) {
            $this->importCourse($organization, $term, $courseCodes);
        }

        $this->setRedirect("$this->baseURL&view=courses");
    }


    /**
     * Imports a courses based on the information associated with the given units.
     *
     * @param   oTable  $organization  the table for the organizational context of the course to be created
     * @param   Terms   $term          the table for the term context of the course to be created
     * @param   array   $courseCodes   the id of the units which will be associated with the course
     *
     * @return void
     */
    private function importCourse(oTable $organization, Terms $term, array $courseCodes): void
    {
        $course    = new Table();
        $localized = 'name_' . Application::getTag();
        $units     = [];

        foreach ($courseCodes as $code) {
            $unit = new Units();

            if (!$unit->load(['code' => $code, 'organizationID' => $organization->id, 'termID' => $term->id])) {
                Application::message(Text::sprintf('UNIT_ID_INVALID', $code));
                continue;
            }

            if ($unit->courseID) {
                if ($course->id and $course->id !== $unit->courseID) {
                    Application::message(Text::sprintf('UNIT_COURSE_CONFLICT', $code, $course->$localized));

                    return;
                }
                elseif (!$course->id) {
                    $course->load($unit->courseID);
                }
            }

            $units[$unit->id] = $unit;
        }

        $codesCopy = $courseCodes;
        $lastID    = array_pop($codesCopy);
        $nameIDs   = count($codesCopy) ? implode(', ', $codesCopy) . " & $lastID" : $lastID;

        $course->name_de = "$organization->abbreviation_de - $term->name_de - $nameIDs";
        $course->name_en = "$organization->abbreviation_en - $term->name_en - $nameIDs";

        Helper::fromUnits($course, array_keys($units), $term->id, true);

        $course->store();

        foreach ($units as $unit) {
            $unit->courseID = $course->id;
            $unit->store();
        }
    }
}