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

use THM\Organizer\Adapters\Application;

/** @inheritDoc */
class MergeRooms extends MergeController
{
    protected string $list = 'Rooms';
    protected string $mergeContext = 'room';

    /** @inheritDoc */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        $data['active']  = $this->boolAggregate('active', 'persons', false);
        $data['virtual'] = $this->boolAggregate('virtual', 'persons', true);

        return $data;
    }

    /** @inheritDoc */
    protected function updateReferences(): bool
    {
        if (!$this->updateTable('monitors')) {
            Application::message('MERGE_FAILED_MONITORS', Application::ERROR);

            return false;
        }

        if (!$this->updateAssignments()) {
            Application::message('MERGE_FAILED_ASSIGNMENTS', Application::ERROR);

            return false;
        }

        return true;
    }

    /** @inheritDoc */
    protected function validate(array &$data, array $required = []): void
    {
        parent::validate($data, ['code', 'name', 'roomtypeID']);
    }
}