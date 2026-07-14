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

use stdClass;
use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Tables\{Pools as PTable, Subjects, Table};
use THM\Organizer\Helpers\{Can, Curricula as Helper, Documentable, Pools as PHelper, Programs};

/** @inheritDoc */
abstract class CurriculumResource extends FormController
{
    use Associated;

    protected const NONE = -1;

    /**
     * Creates a new resource, imports external data, and redirects to the same view of the same resource.
     * @return void
     */
    public function applyImport(): void
    {
        if (Application::uqClass(get_called_class()) === 'Pool') {
            Application::error(501);
        }

        $id = $this->process();
        $this->import($id);
        $this->setRedirect("$this->baseURL&view=" . strtolower($this->name) . "&id=$id");
    }

    /** @inheritDoc */
    protected function authorize(): void
    {
        if (Can::administrate()) {
            return;
        }

        /** @var Documentable $helper */
        $helper = "THM\\Organizer\\Helpers\\" . $this->list;
        $id     = Input::id();

        if ($id ? !$helper::documentable($id) : !$helper::documentableIDs()) {
            Application::error(403);
        }
    }

    /**
     * Method to delete data associated with an individual curriculum resource. Authorized in the list view delete, import and
     * update functions. Authorized in the form views in apply- & saveImport functions.
     *
     * @param int $resourceID the resource id
     *
     * @return bool
     */
    public function delete(int $resourceID): bool
    {
        if (!Helper::deleteRanges($resourceID)) {
            return false;
        }

        $table = $this->getTable();

        return $table->delete($resourceID);
    }

    /**
     * Method to import data associated with an individual curriculum resource. Authorization performed by calling function.
     *
     * @param int $resourceID the id of the program to be imported
     *
     * @return bool|int
     */
    abstract public function import(int $resourceID = 0): bool|int;

    /** @inheritDoc */
    public function process(): int
    {
        $this->checkToken();
        $this->authorize();

        $id         = Input::id();
        $this->data = $this->prepareData();

        // For save to copy, will otherwise be identical.
        $this->data['id'] = $id;

        /** @var Table $table */
        $table = $this->getTable();

        if (!$id = $this->store($table, $this->data, $id)) {
            return $id;
        }

        $this->data['id'] = $id;

        if (!$this->updateAssociations()) {
            Application::message('UPDATE_ASSOCIATION_FAILED', Application::WARNING);
        }

        $this->postProcess();

        return $id;
    }

    /**
     * The process steps post-store specific to individual resource types.
     *
     * @return void
     */
    abstract protected function postProcess(): void;

    /**
     * Saves the resource, imports external data and redirects to the list view.
     * @return void
     */
    public function saveImport(): void
    {
        if (Application::uqClass(get_called_class()) === 'Pool') {
            Application::error(501);
        }

        $id = $this->process();
        $this->import($id);
        $this->setRedirect("$this->baseURL&view=" . strtolower($this->list));
    }

    /**
     * Set name attributes common to pools and subjects.
     *
     * @param PTable|Subjects $table  the table to modify
     * @param stdClass        $object the data source
     *
     * @return void
     */
    protected function setNames(PTable|Subjects $table, stdClass $object): void
    {
        $table->setColumn('abbreviation_de', (string) $object->kuerzel, '');
        $table->setColumn('abbreviation_en', (string) $object->kuerzelen, $table->abbreviation_de);

        $table->fullName_de = (string) $object->titelde;
        $table->fullName_en = (string) $object->titelen ?: $table->fullName_de;
    }

    /**
     * Method to save the submitted ordering values for records via AJAX.
     * @return  void
     */
    public function superOrdinatesAjax(): void
    {
        if (!$this->checkToken('get', false)) {
            http_response_code(403);
            echo '';
            $this->app->close();
        }

        if (!$id = Input::id()) {
            http_response_code(400);
            echo '';
            $this->app->close();
        }

        $type = strtolower(Application::uqClass(get_called_class()));

        if (!in_array($type, ['pool', 'subject'])) {
            http_response_code(501);
            echo '';
            $this->app->close();
        }

        $options = '';
        $ranges  = Programs::programs(Input::resourceIDs('programIDs'));

        foreach (PHelper::superOptions($id, $type, $ranges) as $option) {
            $options .= "<option value='$option->value' $option->selected $option->disable>$option->text</option>";
        }

        echo $options;

        $this->app->close();
    }
}