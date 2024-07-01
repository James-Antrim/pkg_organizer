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

use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers\{Groups as Helper, Terms};
use THM\Organizer\Tables\{Groups as Group, GroupPublishing as Publishing, Terms as Term};

/** @inheritDoc */
class Groups extends ListController
{
    use Activated;
    use Published;
    use Scheduled;

    protected string $item = 'Group';

    /**
     * Makes call to the model's batch function, and redirects to the manager view.
     * @return void
     */
    public function batch(): void
    {
        $this->checkToken();
        $this->authorize();

        if (!$groupIDs = Input::getSelectedIDs()) {
            Application::message('NO_SELECTION', Application::WARNING);

            return;
        }

        $batch      = Input::getBatchItems();
        $gridID     = (int) $batch->get('gridID');
        $publishing = (array) $batch->get('publishing');
        $selected   = count($groupIDs);

        // Individual resource authorization is checked in the called function, making subsequent checking for grids redundant.
        $updated = $this->savePublishing($groupIDs, $publishing);

        if ($gridID) {
            $gUpdated = 0;
            foreach ($groupIDs as $groupID) {
                $group = new Group();
                $group->load($groupID);
                $group->gridID = $gridID;

                if (!$group->store()) {
                    continue;
                }
                $gUpdated++;
            }

            $updated = min($updated, $gUpdated);
        }

        $this->farewell($selected, $updated);
    }

    /**
     * Activates a group's appointments' publication during the current term.
     * @return void
     */
    public function publishCurrent(): void
    {
        $this->setPublished(Terms::currentID(), Helper::PUBLISHED);
    }

    /**
     * Activates a group's appointments' publication during the next term.
     * @return void
     */
    public function publishNext(): void
    {
        $this->setPublished(Terms::nextID(), Helper::PUBLISHED);
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
            if (!Helper::schedulable($groupID)) {
                Application::error(403);
            }

            $group      = new Group();
            $publishing = new Publishing();
            $term       = new Term();

            if ($group->load($groupID) and $term->load($termID)) {
                $data = ['groupID' => $groupID, 'termID' => $termID];
                $publishing->load($data);
                $data['published'] = $published;
                if ($publishing->save($data)) {
                    $updated++;
                }
            }
        }

        $this->farewell($selected, $updated);
    }

    /**
     * Sets the publication status for any group / complete term pairing to true.
     * @return void
     */
    public function publishPast(): void
    {
        // Authorization isn't super relevant, but this still shouldn't be publicly available.
        $this->checkToken();

        $query = DB::getQuery();
        $terms = Terms::resources();
        $today = date('Y-m-d');
        $query->update(DB::qn('#__organizer_group_publishing'))
            ->set(DB::qc('published', 1))
            ->where(DB::qc('termID', ':termID'))
            ->bind(':termID', $termID);

        $updated = 0;
        foreach ($terms as $term) {
            if ($term['endDate'] >= $today) {
                continue;
            }

            $termID = $term['id'];
            DB::setQuery($query);

            if (!DB::execute()) {
                continue;
            }

            $updated++;
        }

        $this->farewell($updated, $updated);
    }

    /**
     * Activates a group's appointments' publication during the current term.
     * @return void
     */
    public function unpublishCurrent(): void
    {
        $this->setPublished(Terms::currentID(), Helper::UNPUBLISHED);
    }

    /**
     * Activates a group's appointments' publication during the next term.
     * @return void
     */
    public function unpublishNext(): void
    {
        $this->setPublished(Terms::nextID(), Helper::UNPUBLISHED);
    }
}
