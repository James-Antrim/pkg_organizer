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
use THM\Organizer\Helpers\{HISinOne, Subjects as Helper};
use THM\Organizer\Tables\Subjects as Table;

/** @inheritDoc */
class Subjects extends CurriculumResources
{
    /**
     * Imports subjects from HISinOne.
     * @return void
     */
    public function import(): void
    {
        $this->authorizeImport();

        $client      = new HISinOne();
        $imported    = 0;
        $selectedIDs = Input::selectedIDs();

        if ($selected = count($selectedIDs)) {
            if ($selected > 1000) {
                Application::message('SUBJECTS_TOO_MANY_TO_IMPORT', Application::WARNING);
                $this->farewell($selected);
            }
            foreach ($selectedIDs as $subjectID) {
                if (!$HISinOneID = Helper::HISinOneID($subjectID)) {
                    Application::message('HIO_DATA_MISSING', Application::WARNING);
                    continue;
                }

                if (!$response = $client->subject($HISinOneID)) {
                    Application::message(Text::sprintf('HIO_SUBJECT_DATA_INCONSISTENT', $HISinOneID, $subjectID), Application::WARNING);
                    continue;
                }

                if (!$response = $response->module_out ?? false or !Helper::validateResponse($response)) {
                    Application::message('HIO_STRUCTURE_INVALID', Application::ERROR);
                    continue;
                }

                $subject = new Table();
                $subject->load($subjectID);

                if (Helper::importSingle($subject, $response)) {
                    $imported++;
                }
            }

            $this->farewell($selected, $imported);
            return;
        }

        Application::message('LIST_SELECTION_WARNING', Application::NOTICE);
        $this->farewell();
    }
}
