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
use THM\Organizer\Helpers\{Documentable, LSF, Organizations, Programs as Helper};

/**
 * @inheritDoc
 */
class Program extends CurriculumResource
{
    use Activated;

    protected string $list = 'Programs';

    /**
     * General or specific resource documentation authorization.
     * @return void
     */
    protected function authorize(): void
    {
        /** @var Documentable $helper */
        $helper = "THM\\Organizer\\Helpers\\" . $this->list;
        $id     = Input::getID();

        // Existing over document access, new explicitly over document access and implicitly over schedule access.
        if ($id ? !$helper::documentable($id) : !(Organizations::documentableIDs() or Organizations::schedulableIDs())) {
            Application::error(403);
        }
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
     * @inheritDoc
     */
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
     * Prepares the data to be saved.
     * @return array
     */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        /**
         * External references are not in the table and as such won't be automatically prepared. Subordinates are picked up
         * individually during further processing.
         * @see Ranges::addSubordinate(), Ranges::subordinates()
         */
        $data['organizationIDs'] = Input::getIntArray('organizationIDs');

        $this->validate($data, ['accredited', 'code', 'degreeID', 'name_de', 'name_en', 'organizationIDs']);

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function postProcess(array $data): void
    {
        if (!$this->updateAssociations('programID', $data['id'], $data['organizationIDs'])) {
            Application::message('UPDATE_ASSOCIATION_FAILED', Application::WARNING);
        }

        $range = ['parentID' => null, 'programID' => $data['id'], 'curriculum' => $this->subordinates(), 'ordering' => 0];

        if (!$this->addRange($range)) {
            Application::message('UPDATE_CURRICULUM_FAILED', Application::WARNING);
        }
    }
}