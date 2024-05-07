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
use THM\Organizer\Helpers\{Groups as Helper, Organizations, Terms};
use THM\Organizer\Tables\{Groups as Group, GroupPublishing as Publishing, Terms as Term};

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Groups extends ListController
{
    use Activated;

    protected string $item = 'Group';

    /**
     * Authorization check multiple curriculum resources. Individual resource authorization is later checked as appropriate.
     * @return void
     */
    protected function authorize(): void
    {
        if (!Organizations::schedulableIDs()) {
            Application::error(403);
        }
    }

    /**
     * Makes call to the model's batch function, and redirects to the manager view.
     * @return void
     */
    public function batch(): void
    {
//        $model = new Models\Group();
//
//        if ($model->batch()) {
//            Application::message('ORGANIZER_SAVE_SUCCESS');
//        }
//        else {
//            Application::message('ORGANIZER_SAVE_FAIL', Application::ERROR);
//        }
//
//        $url = Helpers\Routing::getRedirectBase() . '&view=' . Application::getClass($this);
//        $this->setRedirect($url);
    }

    /**
     * Activates a group's appointments' publication during the current term.
     * @return void
     */
    public function publishCurrent(): void
    {
        $this->setPublished(Terms::getCurrentID(), Helper::PUBLISHED);
    }

    /**
     * Activates a group's appointments' publication during the next term.
     * @return void
     */
    public function publishNext(): void
    {
        $this->setPublished(Terms::getNextID(), Helper::PUBLISHED);
    }

    /**
     * Common code for setting group publication values.
     *
     * @param   int  $termID     the publication term context
     * @param   int  $published  the publication value
     *
     * @return void
     */
    private function setPublished(int $termID, int $published): void
    {
        $this->checkToken();
        $this->authorize();

        if (!$groupIDs = Input::getSelectedIDs()) {
            Application::message('NO_SELECTION', Application::WARNING);

            return;
        }

        $selected = count($groupIDs);
        $updated  = 0;

        foreach ($groupIDs as $groupID) {
            $group      = new Group();
            $publishing = new Publishing();
            $term       = new Term();

            if (Helper::schedulable($groupID)) {
                if ($group->load($groupID) and $term->load($termID)) {
                    $data = ['groupID' => $groupID, 'termID' => $termID];
                    $publishing->load($data);
                    $data['published'] = $published;
                    if ($publishing->save($data)) {
                        $updated++;
                    }
                }
            }
            else {
                Application::error(403);

            }
        }

        $this->farewell($selected, $updated);
    }

    /**
     * Sets the publication status for any group / complete term pairing to true
     * @return void
     */
    public function publishPast(): void
    {
//        $group = new Models\Group();
//
//        if ($group->publishPast()) {
//            Application::message('ORGANIZER_SAVE_SUCCESS');
//        }
//        else {
//            Application::message('ORGANIZER_SAVE_FAIL', Application::ERROR);
//        }
//
//        $url = Helpers\Routing::getRedirectBase() . '&view=groups';
//        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Activates a group's appointments' publication during the current term.
     * @return void
     */
    public function unpublishCurrent(): void
    {
        $this->setPublished(Terms::getCurrentID(), Helper::UNPUBLISHED);
    }

    /**
     * Activates a group's appointments' publication during the next term.
     * @return void
     */
    public function unpublishNext(): void
    {
        $this->setPublished(Terms::getNextID(), Helper::UNPUBLISHED);
    }
}
