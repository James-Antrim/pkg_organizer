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

use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers\Programs as Helper;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Programs extends CurriculumResources
{
    use Activated;

    protected string $item = 'Program';

    /**
     * Finds the curriculum entry ids for subject entries subordinate to a particular resource.
     *
     * @param   int  $programID  the id of the program
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

        if (!$selectedIDs = Input::getSelectedIDs()) {
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
