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

/**
 * @inheritDoc
 */
class MergeCategories extends MergeController
{
    use Published;

    protected string $list = 'Categories';
    protected string $mergeContext = 'category';

    /**
     * @inheritDoc
     */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        $data['active']   = $this->boolAggregate('active', 'categories', false);
        $data['suppress'] = $this->boolAggregate('suppress', 'categories', false);

        $this->data = $data;

        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function updateReferences(): bool
    {
        if (!$this->updateAssociations()) {
            // Localize
            Application::message('MERGE_FAILED_ASSOCIATIONS', Application::ERROR);
            return false;
        }

        if (!$this->updateTable('groups')) {
            // Localize
            Application::message('MERGE_FAILED_ASSOCIATIONS', Application::ERROR);

            return false;
        }

        if (!$this->updateTable('programs')) {
            // Localize
            Application::message('MERGE_FAILED_ASSOCIATIONS', Application::ERROR);

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function validate(array &$data, array $required = []): void
    {
        parent::validate($data, ['code', 'name_de', 'name_en']);
    }
}