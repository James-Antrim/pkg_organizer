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
use THM\Organizer\Helpers\Can;

/**
 * @inheritDoc
 */
class Person extends FormController
{
    use Associated;

    private array $data;

    protected string $list = 'Persons';

    /**
     * @inheritDoc
     */
    protected function authorize(): void
    {
        if (!Can::manage('persons')) {
            Application::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    protected function prepareData(): array
    {
        $data                    = parent::prepareData();
        $data['organizationIDs'] = Input::getIntArray('organizationIDs');

        $this->validate($data);
        $this->data = $data;

        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function process(): int
    {
        if ($id = parent::process() and !$this->updateAssociations('personID', $id, $this->data['organizationIDs'])) {
            Application::message('UPDATE_ASSOCIATION_FAILED', Application::WARNING);
        }

        return $id;
    }

    /**
     * @inheritDoc
     */
    protected function validate(array &$data, array $required = []): void
    {
        $required = ['code', 'surname'];
        parent::validate($data, $required);

        $namePattern = '/^[A-ZÀ-ÖØ-Þa-zß-ÿ \-\']+$/';
        if (!preg_match($namePattern, $data['surname'])) {
            Application::error(400);
        }

        if ($data['forename'] and !preg_match($namePattern, $data['forename'])) {
            Application::error(400);
        }

        if (!preg_match('/^[A-ZÀ-ÖØ-Þa-zß-ÿ]+$/', $data['code'])) {
            Application::error(400);
        }

        if ($data['title'] and !preg_match('/^(([A-Z]\.)?[A-Z][a-z]+\.?([A-Z][a-z].?)? ?)+$/', $data['title'])) {
            Application::error(400);
        }

        // Joomla's check is an inverted check for forbidden characters.
        if ($data['username'] and preg_match('/[<|>"\'%;()&]/i', $data['username'])) {
            Application::error(400);
        }

        /**
         * active, public and suppress are adequately validated during prepareData
         * organizationIDs is handled by the updateAssociations function later and needs no validation
         */
    }
}