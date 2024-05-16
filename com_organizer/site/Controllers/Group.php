<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2024 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use THM\Organizer\Adapters\Application;
use THM\Organizer\Adapters\Input;
use THM\Organizer\Helpers\Organizations;

/**
 * @inheritDoc
 */
class Group extends FormController
{
    use Associated;
    use Published;

    protected string $list = 'Groups';

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
     * Prepares the data to be saved.
     * @return array
     */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        // External references are not in the table and as such won't be automatically prepared.
        $data['organizationIDs'] = Input::getIntArray('organizationIDs');
        $data['publishing']      = Input::getIntArray('publishing');

        // Because most values are imported this is the only item that is technically required.
        $this->validate($data, ['categoryID', 'fullName_de', 'fullName_en', 'name_de', 'name_en', 'organizationIDs']);
        $this->data = $data;

        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function process(): int
    {
        $id = parent::process();

        if ($id and !$this->updateAssociations('groupID', $id, $this->data['organizationIDs'])) {
            Application::message('UPDATE_ASSOCIATION_FAILED', Application::WARNING);
        }

        if ($id and !$this->savePublishing($id, $this->data['publishing'])) {
            Application::message('UPDATE_PUBLICATION_FAILED', Application::WARNING);
        }

        return $id;
    }
}