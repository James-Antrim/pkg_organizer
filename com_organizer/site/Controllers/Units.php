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

use THM\Organizer\Adapters\{Application, Database as DB, Input, Text};
use THM\Organizer\Helpers\{Courses as cHelper, Organizations};
use THM\Organizer\Tables\{Courses as cTable, Units as Table};

/** @inheritDoc */
class Units extends ListController
{
    protected string $item = 'Unit';
    private array $schedulableIDs = [];

    /** @inheritDoc */
    protected function authorize(): void
    {
        if (!$this->schedulableIDs = Organizations::schedulableIDs()) {
            Application::error(403);
        }
    }

    /**
     * Creates a course entry based on the data associated with a unit.
     * @return void
     */
    public function addCourse(): void
    {
        $this->checkToken();
        $this->authorize();

        if (!$unitIDs = Input::getSelectedIDs()) {
            Application::message('LIST_SELECTION_WARNING', Application::WARNING);
            $this->setRedirect("$this->baseURL&view=units");
            return;
        }

        $query = DB::query();
        $query->select(DB::qn(['id', 'courseID', 'organizationID', 'termID']))
            ->from(DB::qn('#__organizer_units'))
            ->whereIn(DB::qn('id'), $unitIDs);
        DB::set($query);

        // Some number of the ids from the request are inconsistent with existing unit ids.
        if (!$pulledIDs = DB::integers() or count($pulledIDs) !== count($unitIDs)) {
            Application::message(Text::sprintf('UNIT_X_INCONSISTENT', Text::_('UNITS')), Application::WARNING);
            $this->setRedirect("$this->baseURL&view=units");
            return;
        }

        // Ensure all units are schedulable for the user.
        $organizationIDs = DB::integers(2);
        if (count($organizationIDs) !== count(array_intersect($organizationIDs, $this->schedulableIDs))) {
            Application::error(403);
        }

        $courseIDs = DB::integers(1);
        $termIDs   = DB::integers(3);

        // Referenced resources would create an inconsistent context for the course.
        if (!$this->consistent($courseIDs, 'COURSES') or !$this->consistent($termIDs, 'TERMS')) {
            $this->setRedirect("$this->baseURL&view=units");
            return;
        }

        // Already checked for consistency, so if there is one they all have it
        if ($courseID = reset($courseIDs)) {
            $this->setRedirect("$this->baseURL&view=course&layout=edit&id=$courseID");
            return;
        }

        $termID = reset($termIDs);

        $course = new cTable();
        cHelper::fromUnits($course, $unitIDs, $termID);

        if (!$course->store()) {
            Application::message('ORGANIZER_SAVE_FAIL', Application::ERROR);
            $this->setRedirect("$this->baseURL&view=units");
            return;
        }

        foreach ($unitIDs as $unitID) {
            $unit = new Table();
            $unit->load($unitID);
            $unit->courseID = $course->id;
            $unit->store();
        }

        $count = count($unitIDs);
        $this->setRedirect("$this->baseURL&view=course&layout=edit&id=$course->id");
        $this->farewell($count, $count);
    }

    /**
     * Checks whether resources referenced by the units from which the course is being generated are identical.
     *
     * @param   array   $resourceIDs  the ids to check for internal consistency
     * @param   string  $resource     the resource referenced by the ids
     *
     * @return bool
     */
    private function consistent(array $resourceIDs, string $resource): bool
    {
        $base = reset($resourceIDs);

        foreach ($resourceIDs as $resourceID) {
            if ($resourceID !== $base) {
                Application::message(Text::sprintf('UNIT_X_INCONSISTENT', Text::_($resource)), Application::WARNING);
                return false;
            }
        }

        return true;
    }
}
