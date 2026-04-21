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

use Joomla\Database\ParameterType;
use THM\Organizer\Adapters\{Application, Database as DB, Input, Text};
use THM\Organizer\Helpers;
use THM\Organizer\Tables\{Curricula, Programs};

/** @inheritDoc */
class ImportPrograms extends FormController
{
    use Ranges;

    private bool $existing = false;
    private int $new = 0;
    private int $aSupplements = 0;
    private int $cSupplements = 0;

    /** @inheritDoc */
    protected function authorize(): void
    {
        if (!Helpers\Can::administrate()) {
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

            $this->setRedirect("$this->baseURL&view=programs");
            return;
        }

        $file = Input::instance()->files->get('file');

        if (empty($file['type']) or !in_array($file['type'], ['text/csv', 'text/plain'])) {
            Application::message('FILE_TYPE_INVALID', Application::ERROR);
            $this->setRedirect("$this->baseURL&view=importprograms");
            return;
        }

        if (mb_detect_encoding($file['tmp_name'], 'UTF-8', true) !== 'UTF-8') {
            Application::message('FILE_ENCODING_INVALID', Application::ERROR);
            $this->setRedirect("$this->baseURL&view=importprograms");
            return;
        }

        $file = fopen($file['tmp_name'], 'r');

        $imported = 0;
        $preface  = true;
        $rows     = 0;
        while (($row = fgets($file)) !== false) {

            if ($preface) {
                $preface = false;
                continue;
            }

            $invalid = false;
            $rows++;
            $row = str_replace(chr(13) . chr(10), '', $row);
            if (!preg_match('/([^|]+\|){10}(;[^;]+){11}/', $row)) {
                Application::message("Malformed row: $row.", Application::ERROR);
                continue;
            }

            $identifiers = explode("|", $row);
            unset($identifiers[4], $identifiers[10]);
            [$degreeCode, $nomenCode, $minorCode, $focusCode, $accredited, $campusCode, $atCode, $formCode, $typeCode] = array_values($identifiers);

            if (!$degreeID = Helpers\Degrees::code($degreeCode)) {
                Application::message(Text::sprintf('DEGREE_MISSING', $degreeCode), Application::WARNING);
            }
            if (!$nomenID = Helpers\Nomina::code($nomenCode)) {
                Application::message(Text::sprintf('NOMEN_MISSING', $nomenCode), Application::WARNING);
                $invalid = true;
            }
            if (!$minorID = Helpers\Minors::code($minorCode)) {
                if ($minorCode !== '-') {
                    Application::message(Text::sprintf('MINOR_MISSING', $minorCode), Application::WARNING);
                    $invalid = true;
                }
                $minorID = null;
            }
            if (!$focusID = Helpers\Foci::code($focusCode)) {
                if ($focusCode !== '-') {
                    Application::message(Text::sprintf('FOCUS_MISSING', $focusCode), Application::WARNING);
                    $invalid = true;
                }
                $focusID = null;
            }
            if (!preg_match('/\d{4}/', $accredited)) {
                $original   = $accredited;
                $accredited = intval('19' . $accredited);
                if ($accredited !== Helpers\Programs::UNVERSIONED) {
                    Application::message(Text::sprintf('YEAR_INVALID', $original), Application::WARNING);
                    $invalid = true;
                }
            }
            if (!$campusID = Helpers\Campuses::code($campusCode)) {
                Application::message(Text::sprintf('CAMPUS_MISSING', $campusCode), Application::WARNING);
                $invalid = true;
            }
            if (!$aTypeID = Helpers\AttendanceTypes::code($atCode)) {
                Application::message(Text::sprintf('ATTENDANCE_TYPE_MISSING', $atCode), Application::WARNING);
                $invalid = true;
            }
            if (!$formID = Helpers\ProgramForms::code($formCode)) {
                Application::message(Text::sprintf('PROGRAM_FORM_MISSING', $formCode), Application::WARNING);
                $invalid = true;
            }
            if (!$typeID = Helpers\ProgramTypes::code($typeCode)) {
                Application::message(Text::sprintf('PROGRAM_TYPE_MISSING', $typeCode), Application::WARNING);
                $invalid = true;
            }

            if ($invalid) {
                continue;
            }

            $keys = ['accredited' => $accredited, 'aTypeID' => $aTypeID, 'campusID' => $campusID, 'degreeID' => $degreeID, 'formID' => $formID, 'nomenID' => $nomenID, 'typeID' => $typeID];
            if ($focusID) {
                $keys['focusID'] = $focusID;

            }
            if ($minorID) {
                $keys['minorID'] = $minorID;

            }

            if ($this->importProgram($keys)) {
                $imported++;
            }
        }

        if ($imported) {
            $type = $imported === $rows ? Application::MESSAGE : Application::NOTICE;
        }
        else {
            $type = Application::WARNING;
        }

        Application::message(Text::sprintf('PROGRAMS_IMPORTED', $imported), $type);

        if ($this->existing) {
            if ($this->aSupplements) {
                Application::message(Text::sprintf('PROGRAM_ASSOCIATIONS_SUPPLEMENTED', $this->aSupplements), Application::NOTICE);
            }
            if ($this->aSupplements) {
                Application::message(Text::sprintf('PROGRAM_CURRICULA_SUPPLEMENTED', $this->cSupplements), Application::NOTICE);
            }
        }

        fclose($file);

        $this->setRedirect("$this->baseURL&view=programs");
    }


    /**
     * Imports a program based on the keys provided.
     *
     * @param array $data the column values for the iterated program
     * @return bool
     */
    private function importProgram(array $data): bool
    {
        $table = new Programs();

        $existing = false;
        $new      = false;

        if ($table->load($data)) {
            $this->existing = true;
            $existing       = true;
        }
        elseif (!$table->save($data)) {
            $this->new++;
            $new = true;
        }
        else {
            return false;
        }

        $organizationIDs = [];
        $programID       = $table->id;
        $query           = DB::query();
        $query->select(DB::qn('organizationID'))
            ->from(DB::qn('#__organizer_programs', 'p'))
            ->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.programID', 'p.id'));

        if ($existing) {
            $query->where(DB::qc('p.id', $programID));
            DB::set($query);
            $organizationIDs = array_unique(DB::integers());
        }

        if ($new or !$organizationIDs) {
            $query->clear('where');
            $query->where(DB::qcs([['p.nomenID', $data['nomenID']], ['p.campusID', $data['campusID']]]));
            DB::set($query);
            $organizationIDs = array_unique(DB::integers());

            $query = DB::query();
            $query->insert(DB::qn('#__organizer_associations'))
                ->columns(['organizationID', 'programID'])
                ->values(':organizationID, :programID');

            foreach ($organizationIDs as $organizationID) {
                $query->bind(':organizationID', $organizationID, ParameterType::INTEGER)
                    ->bind(':programID', $programID, ParameterType::INTEGER);
                DB::set($query);
                DB::execute();
            }

            if ($existing) {
                $this->aSupplements++;
            }
        }

        $curriculumExists = false;
        if ($existing) {
            $table            = new Curricula();
            $curriculumExists = $table->load(['programID' => $programID]);
        }

        if (!$curriculumExists) {
            $range = ['parentID' => null, 'programID' => $programID, 'curriculum' => [], 'ordering' => 0];
            $this->addRange($range);

            if ($existing) {
                $this->cSupplements++;
            }
        }

        return true;
    }
}