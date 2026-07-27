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
use THM\Organizer\Helpers\{HISinOne, Programs as Helper, Subjects as SHelper};
use THM\Organizer\Tables\Subjects as STable;

/** @inheritDoc */
class Programs extends CurriculumResources
{
    use Activated;

    /**
     * Imports programs from HISinOne.
     * @return void
     */
    public function import(): void
    {
        $this->authorizeImport();

        $client      = new HISinOne();
        $imported    = 0;
        $selectedIDs = Input::selectedIDs();

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
                    Application::message(Text::sprintf('HIO_PROGRAM_DATA_INCONSISTENT', $HISinOneID, $programID), Application::WARNING);
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
            return;
        }

        if ($programs = $client->program() and $programs = Helper::filterPrograms($programs)) {
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
        $this->authorizeImport();

        $client   = new HISinOne();
        $imported = 0;

        $selectedIDs = Input::selectedIDs();
        if ($selected = count($selectedIDs)) {
            if ($selected > 4) {
                Application::message('PROGRAMS_TOO_MANY_TO_IMPORT', Application::WARNING);
                $this->farewell($selected);
            }

            $selected = 0;
            foreach ($selectedIDs as $programID) {
                foreach ($this->subjectIDs($programID) as $subjectID) {
                    $selected++;
                    if (!$HISinOneID = SHelper::HISinOneID($subjectID)) {
                        Application::message('HIO_DATA_MISSING', Application::WARNING);
                        continue;
                    }

                    if (!$response = $client->subject($HISinOneID)) {
                        Application::message(Text::sprintf('HIO_SUBJECT_DATA_INCONSISTENT', $HISinOneID, $subjectID), Application::WARNING);
                        continue;
                    }

                    if (!$response = $response->module_out ?? false or !SHelper::validateResponse($response)) {
                        Application::message('HIO_STRUCTURE_INVALID', Application::ERROR);
                        continue;
                    }

                    $subject = new STable();
                    $subject->load($subjectID);

                    if (SHelper::importSingle($subject, $response)) {
                        $imported++;
                    }
                }
            }

            $this->farewell($selected, $imported);
            return;
        }

        Application::message('LIST_SELECTION_WARNING', Application::NOTICE);
        $this->farewell();

    }
}
