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

use Exception;
use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers\{Can, Categories, Documentable, HISinOne, Organizations, Programs as Helper};
use THM\Organizer\Tables\Programs as Table;

/** @inheritDoc */
class Program extends CurriculumResource
{
    use Activated;

    /** @inheritDoc */
    protected function authorize(): void
    {
        if (Can::administrate()) {
            return;
        }

        /** @var Documentable $helper */
        $helper = "THM\\Organizer\\Helpers\\" . $this->list;
        $id     = Input::id();

        // Existing over document access, new explicitly over document access and implicitly over schedule access.
        if ($id ? !$helper::documentable($id) : !(Organizations::documentableIDs() or Organizations::schedulableIDs())) {
            Application::error(403);
        }
    }

    /**
     * Creates a program stub from imported category data
     *
     * @param array $data       key value pairs for old format entries nomen code, degree code, year of accreditation
     * @param int   $categoryID the id of the calling category to associate the program with
     *
     * @return void
     */
    public function fromSchedule(array $data, int $categoryID): void
    {
        if (!Categories::schedulable($categoryID)) {
            Application::error(403);
        }

        $table = $this->getTable();

        if ($table->load($data)) {
            return;
        }

        $data['categoryID']      = $categoryID;
        $data['organizationIDs'] = [Input::integer('organizationID')];
        $data['subordinates']    = [];
        $this->data              = $data;

        /** @var Table $table */
        $table = $this->getTable();

        if (!$this->data['id'] = $this->store($table, $data)) {
            return;
        }

        $this->updateAssociations();
        $this->postProcess();
    }

    /** @inheritDoc */
    public function import(int $resourceID = 0): int
    {
        if (!Helper::documentable($resourceID)) {
            Application::message('403', Application::WARNING);
            return false;
        }

        try {
            $client = new HISinOne();
        } catch (Exception $exception) {
            Application::message($exception->getMessage());
            Application::message('HIO_CLIENT_FAILED', Application::WARNING);

            return false;
        }

        if ($resourceID) {
            if (!$key = $this->key($resourceID)) {
                Application::message('HIO_DATA_MISSING', Application::WARNING);
                return false;
            }

            // Messaging handled by the HIO helper.
            if (!$program = $client->program($key)) {
                return false;
            }

            echo "<pre>" . print_r($program, true) . "</pre>";
            die;

            // Invalid structure
            /*if (empty($program->gruppe)) {
                Application::message('HIO_STRUCTURE_INVALID', Application::WARNING);
                return false;
            }

            if (!$ranges = $this->ranges($resourceID) or empty($ranges[0])) {
                $range = ['parentID' => null, 'programID' => $resourceID, 'ordering' => 0];

                return $this->addRange($range);
            }
            else {
                $curriculumID = $ranges[0]['id'];
            }

            // Curriculum entry doesn't exist and could not be created.
            if (empty($curriculumID)) {
                return false;
            }

            return $this->processCollection($program->gruppe, $keys['organizationID'], $curriculumID);*/
        }

        if (!$programs = $client->program()) {
            return false;
        }

        echo "<pre>" . print_r($programs, true) . "</pre>";
        die;
    }

    /**
     * Retrieves program information relevant for soap queries to the HIO system.
     *
     * @param int $programID the id of the degree program
     *
     * @return string
     */
    private function key(int $programID): string
    {
        $h     = ["'H' as `h`"];
        $part1 = DB::qn(['d.code', 'n.code', 'm.code', 'f.code'], ['degree', 'program', 'minor', 'focus']);
        $part2 = DB::qn(
            ['p.accredited', 'c.code', 'at.code', 'pf.code', 'pt.code'],
            ['year', 'campus', 'attendance', 'form', 'type']);
        $query = DB::query();
        $query->select(array_merge($part1, $h, $part2))
            ->from(DB::qn('#__organizer_programs', 'p'))
            ->innerJoin(DB::qn('#__organizer_attendance_types', 'at'), DB::qc('at.id', 'p.aTypeID'))
            ->innerJoin(DB::qn('#__organizer_degrees', 'd'), DB::qc('d.id', 'p.degreeID'))
            ->innerJoin(DB::qn('#__organizer_nomina', 'n'), DB::qc('n.id', 'p.nomenID'))
            ->leftJoin(DB::qn('#__organizer_campuses', 'c'), DB::qc('c.id', 'p.campusID'))
            ->leftJoin(DB::qn('#__organizer_foci', 'f'), DB::qc('f.id', 'p.focusID'))
            ->leftJoin(DB::qn('#__organizer_minors', 'm'), DB::qc('m.id', 'p.minorID'))
            ->leftJoin(DB::qn('#__organizer_program_forms', 'pf'), DB::qc('pf.id', 'p.formID'))
            ->leftJoin(DB::qn('#__organizer_program_types', 'pt'), DB::qc('pt.id', 'p.typeID'))
            ->where(DB::qc('p.id', $programID));
        DB::set($query);

        if (!$identifiers = DB::array()) {
            return '';
        }

        if (empty($identifiers['attendance'])) {
            $identifiers['attendance'] = Helper::DEFAULT_ATTENDANCE;
        }

        if (empty($identifiers['focus'])) {
            $identifiers['focus'] = Helper::DEFAULT_FOCUS;
        }

        if (empty($identifiers['form'])) {
            $identifiers['form'] = Helper::DEFAULT_FORM;
        }

        if (empty($identifiers['minor'])) {
            $identifiers['minor'] = Helper::DEFAULT_MINOR;
        }

        return implode('|', $identifiers) . '|';
    }

    /** @inheritDoc */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        /**
         * External references are not in the table and as such won't be automatically prepared. Subordinates are picked up
         * individually during further processing.
         * @see Ranges::addSubordinate(), Ranges::subordinates()
         */
        $data['organizationIDs'] = Input::resourceIDs('organizationIDs');
        $data['subordinates']    = $this->subordinates();

        $this->validate($data, ['accredited', 'aTypeID', 'campusID', 'degreeID', 'formID', 'nomenID', 'organizationIDs', 'typeID']);

        return $data;
    }

    /** @inheritDoc */
    public function postProcess(): void
    {
        $range = [
            'parentID'   => null,
            'programID'  => $this->data['id'],
            'curriculum' => $this->data['subordinates'],
            'ordering'   => 0
        ];

        if (!$this->addRange($range)) {
            Application::message('UPDATE_CURRICULUM_FAILED', Application::WARNING);
        }
    }
}