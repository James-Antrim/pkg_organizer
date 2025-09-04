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
use Joomla\Database\ParameterType;
use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers\{Can, Categories, Documentable, LSF, Organizations, Programs as Helper};
use THM\Organizer\Tables\Programs as Table;

/** @inheritDoc */
class Program extends CurriculumResource
{
    use Activated;

    protected string $list = 'Programs';

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
     * @param   array   $data
     * @param   string  $name
     * @param   int     $categoryID
     *
     * @return void
     */
    public function fromSchedule(array $data, string $name, int $categoryID): void
    {
        if (!Categories::schedulable($categoryID)) {
            Application::error(403);
        }

        $table = $this->getTable();

        if ($table->load($data)) {
            return;
        }

        $data['categoryID']      = $categoryID;
        $data['name_de']         = $name;
        $data['name_en']         = $name;
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
    public function import(int $resourceID): bool
    {
        if (!Helper::documentable($resourceID)) {
            Application::message('403', Application::WARNING);
            return false;
        }

        if (!$keys = $this->keys($resourceID)) {
            Application::message('LSF_DATA_MISSING', Application::WARNING);

            return false;
        }

        try {
            $client = new LSF();
        }
        catch (Exception) {
            Application::message('LSF_CLIENT_FAILED', Application::WARNING);

            return false;
        }

        // Messaging handled by the LSF helper.
        if (!$program = $client->getModules($keys)) {
            return false;
        }

        // Invalid structure
        if (empty($program->gruppe)) {
            Application::message('LSF_STRUCTURE_INVALID', Application::WARNING);
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

        return $this->processCollection($program->gruppe, $keys['organizationID'], $curriculumID);
    }

    /**
     * Retrieves program information relevant for soap queries to the LSF system.
     *
     * @param   int  $programID  the id of the degree program
     *
     * @return array  empty if the program could not be found
     */
    private function keys(int $programID): array
    {
        $aliased  = DB::qn(['p.code', 'd.code'], ['program', 'degree']);
        $selected = DB::qn(['p.accredited', 'a.organizationID']);
        $query    = DB::query();
        $query->select(array_merge($aliased, $selected))
            ->from(DB::qn('#__organizer_programs', 'p'))
            ->leftJoin(DB::qn('#__organizer_degrees', 'd'), DB::qc('d.id', 'p.degreeID'))
            ->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.programID', 'p.id'))
            ->where(DB::qn('p.id') . ' = :programID')->bind(':programID', $programID, ParameterType::INTEGER);
        DB::set($query);

        return DB::array();
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

        $this->validate($data, ['accredited', 'code', 'degreeID', 'name_de', 'name_en', 'organizationIDs']);

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