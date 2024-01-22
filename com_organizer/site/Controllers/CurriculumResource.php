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
abstract class CurriculumResource extends FormController
{
    use Ranges;

    /**
     * Creates a new resource, imports external data, and redirects to the same view of the same resource.
     * @return void
     */
    public function applyImport(): void
    {
        if (Application::getClass($this) === 'Pool') {
            Application::error(501);
        }

        $id = $this->process();
        $this->import($id);
        $this->setRedirect("$this->baseURL&view=$this->name&id=$id");
    }

    abstract protected function import(int $resourceID): void;


    /**
     * Saves the resource, imports external data and redirects to the list view.
     * @return void
     */
    public function saveImport(): void
    {
        if (Application::getClass($this) === 'Pool') {
            Application::error(501);
        }

        $id = $this->process();
        $this->import($id);
        $this->setRedirect("$this->baseURL&view=$this->list");
    }
}