<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use Exception;
use Joomla\Database\ParameterType;
use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers\{Can, LSF, Organizations, Programs as Helper};
use THM\Organizer\Tables\Programs as Table;

/**
 * Class which manages stored (degree) program data.
 */
class Program extends CurriculumResource
{
    use Associated;
    use SuperOrdinate;

    protected string $helper = 'Programs';

    protected string $resource = 'program';

    /**
     * Activates programs by id.
     * @return bool true on success, otherwise false
     */
    public function activate(): bool
    {
        if (!$selected = Input::getSelectedIDs()) {
            return false;
        }

        $this->authorize();

        foreach ($selected as $selectedID) {
            $program = new Table();

            if ($program->load($selectedID)) {
                $program->active = 1;
                $program->store();
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * Deactivates programs by id.
     * @return bool true on success, otherwise false
     */
    public function deactivate(): bool
    {
        if (!$selected = Input::getSelectedIDs()) {
            return false;
        }

        $this->authorize();

        foreach ($selected as $selectedID) {
            $program = new Table();

            if ($program->load($selectedID)) {
                $program->active = 0;
                $program->store();
                continue;
            }

            return false;
        }

        return true;

    }

    /**
     * Retrieves program information relevant for soap queries to the LSF system.
     *
     * @param   int  $programID  the id of the degree program
     *
     * @return array  empty if the program could not be found
     */
    private function getKeys(int $programID): array
    {
        $aliased  = DB::qn(['p.code', 'd.code'], ['program', 'degree']);
        $selected = DB::qn(['p.accredited', 'a.organizationID']);
        $query    = DB::getQuery();
        $query->select(array_merge($aliased, $selected))
            ->from(DB::qn('#__organizer_programs', 'p'))
            ->leftJoin(DB::qn('#__organizer_degrees', 'd'), DB::qc('d.id', 'p.degreeID'))
            ->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.programID', 'p.id'))
            ->where(DB::qn('p.id') . ' = :programID')->bind(':programID', $programID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::loadAssoc();
    }

    /**
     * Finds the curriculum entry ids for subject entries subordinate to a particular resource.
     *
     * @param   int  $resourceID  the id of the resource
     * @param   int  $subjectID   the id of a specific subject resource to find in context
     *
     * @return int[] the associated programs
     */
    private function getSubjectIDs(int $resourceID, int $subjectID = 0): array
    {
        $ranges = Helper::subjects($resourceID, $subjectID);

        $ids = [];
        foreach ($ranges as $range) {
            if ($range['subjectID']) {
                $ids[] = $range['subjectID'];
            }
        }

        return $ids;
    }

    /**
     * @inheritDoc
     * @return Table
     */
    public function getTable($name = '', $prefix = '', $options = []): Table
    {
        return new Table();
    }

    /**
     * @inheritDoc
     */
    public function importSingle(int $resourceID): bool
    {
        if (!$keys = $this->getKeys($resourceID)) {
            Application::message('LSF_DATA_MISSING', Application::ERROR);

            return false;
        }

        try {
            $client = new LSF();
        }
        catch (Exception) {
            Application::message('LSF_CLIENT_FAILED', Application::ERROR);

            return false;
        }

        if (!$program = $client->getModules($keys)) {
            return false;
        }

        // The program has not been completed in LSF.
        if (empty($program->gruppe)) {
            return true;
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
     * @inheritDoc
     */
    public function save(array $data = []): int
    {
        $data = empty($data) ? Input::getFormItems() : $data;

        if (empty($data['id'])) {
            // New program can be saved explicitly by documenters or implicitly by schedulers.
            $documentationAccess = (bool) Organizations::documentableIDs();
            $schedulingAccess    = (bool) Can::scheduleTheseOrganizations();

            if (!($documentationAccess or $schedulingAccess)) {
                Application::error(403);
            }
        }
        elseif (is_numeric($data['id'])) {
            if (!Can::document('program', (int) $data['id'])) {
                Application::error(403);
            }
        }
        else {
            return false;
        }

        $table = new Table();

        if (!$table->save($data)) {
            return false;
        }

        $data['id'] = $table->id;

        if (!empty($data['organizationIDs']) and !$this->updateAssociations($data['id'], $data['organizationIDs'])) {
            return false;
        }

        $range = ['parentID' => null, 'programID' => $table->id, 'curriculum' => $this->subordinates(), 'ordering' => 0];

        if (!$this->addRange($range)) {
            return false;
        }

        return $table->id;
    }

    /**
     * Method to update subject data associated with degree programs from LSF
     * @return bool  true on success, otherwise false
     */
    public function update(): bool
    {
        $programIDs = Input::getSelectedIDs();

        if (empty($programIDs)) {
            return false;
        }

        $subject = new Subject();

        foreach ($programIDs as $programID) {
            if (!Can::document('program', $programID)) {
                Application::error(403);
            }

            if (!$subjectIDs = $this->getSubjectIDs($programID)) {
                continue;
            }

            foreach ($subjectIDs as $subjectID) {
                if (!$subject->importSingle($subjectID)) {
                    return false;
                }
            }
        }

        return true;
    }
}
