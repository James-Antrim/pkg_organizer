<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

/**
 * Class which manages stored group data.
 */
class Group extends MergeModel
{
    use Associated;

    /**
     * @var string
     * @see Associated
     */
    protected $resource = 'group';

    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        if (!Helpers\Can::edit('groups', $this->selected)) {
            Application::error(403);
        }
    }

    /**
     * Performs batch processing of groups, specifically their publication per period and their associated grids.
     * @return bool true on success, otherwise false
     */
    public function batch(): bool
    {
        if (!$this->selected = Input::getSelectedIDs()) {
            return false;
        }

        $this->authorize();

        if (!$this->savePublishing()) {
            return false;
        }

        if ($gridID = Input::getBatchItems()['gridID']) {
            foreach ($this->selected as $groupID) {
                $table = new Tables\Groups();

                if (!$table->load($groupID)) {
                    return false;
                }

                $table->gridID = $gridID;

                if (!$table->store()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function save(array $data = [])
    {
        $this->selected = Input::getSelectedIDs();
        $this->authorize();

        $data = empty($data) ? Input::getFormItems()->toArray() : $data;

        $table = new Tables\Groups();

        if (!$table->save($data)) {
            return false;
        }

        if (empty($this->savePublishing())) {
            return false;
        }

        $data['id'] = $table->id;

        if (!empty($data['organizationIDs']) and !$this->updateAssociations($data['id'], $data['organizationIDs'])) {
            return false;
        }

        return $table->id;
    }

    /**
     * @inheritDoc
     */
    protected function updateReferences(): bool
    {
        if (!$this->updateAssociationsReferences()) {
            return false;
        }

        if (!$this->updateReferencingTable('pools')) {
            return false;
        }

        return $this->updateIPReferences();
    }
}
