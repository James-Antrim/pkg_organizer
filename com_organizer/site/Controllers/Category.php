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

use THM\Organizer\Adapters\{Application, Input};

/**
 * @inheritDoc
 */
class Category extends FormController
{
    use Activated;
    use Associated;
    use Scheduled;
    use Suppressed;

    /** @inheritDoc */
    protected string $list = 'Categories';

    /**
     * Prepares the data to be saved.
     * @return array
     */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        // External references are not in the table and as such won't be automatically prepared.
        $data['organizationIDs'] = Input::getIntArray('organizationIDs');

        // Because most values are imported this is the only item that is technically required.
        $this->validate($data, ['name_de', 'name_en']);

        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function process(): int
    {
        $id = parent::process();

        if ($id and !$this->updateAssociations()) {
            Application::message('UPDATE_ASSOCIATION_FAILED', Application::WARNING);
        }

        return $id;
    }
}