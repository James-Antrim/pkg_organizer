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

use THM\Organizer\Adapters\{Application, Input, Text};
use THM\Organizer\Helpers\{Can, HISinOne, Programs as Helper};

/** @inheritDoc */
class Programs extends CurriculumResources
{
    use Activated;

    /** @inheritDoc */
    public function import(): void
    {
        $this->checkToken();

        $authorized  = false;
        $selectedIDs = Input::selectedIDs();
        if (Can::administrate()) {
            $authorized = true;
        }
        elseif ($selectedIDs) {
            $authorizedIDs = Helper::documentableIDs();
            if (!array_diff($selectedIDs, $authorizedIDs)) {
                $authorized = true;
            }
        }

        if (!$authorized) {
            Application::message('403', Application::ERROR);
        }

        $client   = new HISinOne();
        $imported = 0;

        if ($selected = count($selectedIDs)) {
            if ($selected > 4) {
                Application::message('PROGRAMS_TOO_MANY_TO_IMPORT', Application::WARNING);
                $this->farewell($selected);
            }

            foreach ($selectedIDs as $programID) {
                if (!$HISinOneID = Helper::HISinOneID($programID)) {
                    Application::message('HIO_DATA_MISSING', Application::WARNING);
                    continue;
                }

                if (!$program = $client->program($HISinOneID)) {
                    Application::message(Text::sprintf('HIO_DATA_INCONSISTENT', $HISinOneID, $programID), Application::WARNING);
                    continue;
                }

                if (!$program = Helper::filterPrograms($program)) {
                    Application::message('HIO_STRUCTURE_INVALID', Application::ERROR);
                    continue;
                }

                if (Helper::importSingle($program)) {
                    $imported++;
                }
            }

            $this->farewell($selected, $imported);
        }
        elseif ($programs = $client->program() and $programs = Helper::filterPrograms($programs)) {
            $selected = count($programs);
            foreach ($programs as $program) {
                if (Helper::importSingle($program)) {
                    $imported++;
                }
            }
        }
        else {
            Application::message('HIO_STRUCTURE_INVALID', Application::ERROR);
        }

        $this->farewell($selected, $imported);
    }

    /**
     * Finds the curriculum entry ids for subject entries subordinate to a particular resource.
     *
     * @param int $programID the id of the program
     *
     * @return int[] the associated programs
     */
    private function subjectIDs(int $programID): array
    {
        $ranges = Helper::subjects($programID);

        $ids = [];
        foreach ($ranges as $range) {
            if ($range['subjectID']) {
                $ids[] = $range['subjectID'];
            }
        }

        return $ids;
    }

    /**
     * Makes call to the model's update batch function, and redirects to the manager view.
     * @return void
     */
    public function update(): void
    {
        $this->checkToken();
        $this->authorize();

        if (!$selectedIDs = Input::selectedIDs()) {
            Application::message('NO_SELECTION', Application::WARNING);

            $this->farewell();
        }

        $subject = new Subject();
        $updated = 0;

        foreach ($selectedIDs as $programID) {
            if (!Helper::documentable($programID)) {
                Application::message('403', Application::ERROR);
                break;
            }

            foreach ($this->subjectIDs($programID) as $subjectID) {
                if ($subject->import($subjectID)) {
                    $updated++;
                }
            }
        }

        $this->farewell(0, $updated);
    }
}
