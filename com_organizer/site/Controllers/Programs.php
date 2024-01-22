<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use Exception;
use Joomla\Database\ParameterType;
use THM\Organizer\Adapters\Application;
use THM\Organizer\Adapters\Database as DB;
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\{LSF, Programs as Helper};
use THM\Organizer\Models;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Programs extends CurriculumResources
{
    use Activated;

    protected string $item = 'Program';

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
     * Makes call to the model's update batch function, and redirects to the manager view.
     * @return void
     */
    public function update(): void
    {
        $model = new Models\Program();

        if ($model->update()) {
            Application::message('ORGANIZER_UPDATE_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_UPDATE_FAIL', Application::ERROR);
        }

        $url = Helpers\Routing::getRedirectBase() . '&view=' . Application::getClass($this);
        $this->setRedirect($url);
    }

    /**
     * @inheritDoc
     */
    public function importSingle(int $resourceID): bool
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
}
