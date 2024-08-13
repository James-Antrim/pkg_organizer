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
use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers\{Organizations, Schedules as Helper};
use THM\Organizer\Tables\Schedules as Table;

/** @inheritDoc */
class Schedules extends ListController
{
    use Scheduled;

    protected string $item = 'ImportSchedule';

    /**
     * Redirects to the form view for the creation of a new resource.
     * @return void
     */
    public function import(): void
    {
        $this->setRedirect("$this->baseURL&view=importschedule");
    }

    /**
     * Rebuilds the delta status of planning resources and relations.
     * @return void
     */
    public function rebuild(): void
    {
        $this->checkToken();
        $this->authorize();

        $organizationID = Input::getFilterID('organizationID');
        $termID         = Input::getFilterID('termID');
        if (!$organizationID or !$termID) {
            Application::error(400);
        }

        if (!Organizations::schedulable($organizationID)) {
            Application::error(403);
        }

        $conditions = DB::qcs([
            ['s1.creationDate', 's2.creationDate'],
            ['s1.creationTime', 's2.creationTime'],
            ['s1.organizationID', 's2.organizationID'],
            ['s1.termID', 's2.termID']
        ]);

        $query = DB::getQuery();
        $query->select(DB::qn('s1.id'))
            ->from(DB::qn('#__organizer_schedules', 's1'))
            ->innerJoin(DB::qn('#__organizer_schedules', 's2'), $conditions)
            ->where(DB::qc('s1.id', 's2.id', '<'));
        DB::setQuery($query);

        foreach (DB::loadIntColumn() as $duplicateID) {
            if (!Helper::schedulable($duplicateID)) {
                Application::error(403);
            }
            $table = new Table();
            $table->delete($duplicateID);
        }

        if ($scheduleIDs = Helper::contextIDs($organizationID, $termID)) {
            $this->resetContext($organizationID, $termID, reset($scheduleIDs));

            $referenceID = 0;
            foreach ($scheduleIDs as $currentID) {
                $this->update($currentID, $referenceID);
                $referenceID = $currentID;
            }
        }

        Application::message('REBUILD_SUCCESS');

        try {
            $this->display();
        }
        catch (Exception $exception) {
            Application::handleException($exception);
        }
    }
}
