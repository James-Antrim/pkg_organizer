<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use THM\Organizer\Adapters\{Application, Input};
use Joomla\Utilities\ArrayHelper;

/**
 * @inheritDoc
 */
class MergeGroups extends MergeController
{
    use Published;

    protected string $list = 'Groups';
    protected string $mergeContext = 'group';

    /**
     * @inheritDoc
     */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        $data['active']   = $this->boolAggregate('active', 'groups', false);
        $data['suppress'] = $this->boolAggregate('suppress', 'groups', false);

        // Use of Input::getIntArray removes unpublished indexes.
        $data['publishing'] = ArrayHelper::toInteger(Input::getArray('publishing'));

        $this->data = $data;

        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function updateReferences(): bool
    {
        $this->savePublishing($this->mergeID, $this->data['publishing']);

        if (!$this->updateAssignments()) {
            // Localize
            Application::message('MERGE_FAILED_ASSIGNMENTS', Application::ERROR);

            return false;
        }

        if (!$this->updateAssociations()) {
            // Localize
            Application::message('MERGE_FAILED_ASSOCIATIONS', Application::ERROR);
            return false;
        }

        if (!$this->updateTable('pools')) {
            // Localize
            Application::message('MERGE_FAILED_COORDINATORS', Application::ERROR);

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function validate(array &$data, array $required = []): void
    {
        parent::validate($data, ['categoryID', 'fullName_de', 'fullName_en', 'name_de', 'name_en']);
    }
}