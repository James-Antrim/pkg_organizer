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

use Joomla\Database\ParameterType;
use SimpleXMLElement;
use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Tables\{Pools, Subjects};
use THM\Organizer\Helpers\Documentable;

/**
 * @inheritDoc
 */
abstract class CurriculumResource extends FormController
{
    use Associated;
    use Ranges;

    protected const NONE = -1, POOL = 'K', SUBJECT = 'M';

    /**
     * Creates a new resource, imports external data, and redirects to the same view of the same resource.
     * @return void
     */
    public function applyImport(): void
    {
        if (Application::getClass(get_called_class()) === 'Pool') {
            Application::error(501);
        }

        $id = $this->process();
        $this->import($id);
        $this->setRedirect("$this->baseURL&view=$this->name&id=$id");
    }

    /**
     * Default authorization check. Level component administrator. Override for nuance.
     * @return void
     */
    protected function authorize(): void
    {
        /** @var Documentable $helper */
        $helper = "THM\\Organizer\\Helpers\\" . $this->list;
        $id     = Input::getID();

        if ($id ? !$helper::documentable($id) : !$helper::documentableIDs()) {
            Application::error(403);
        }
    }

    /**
     * Method to delete data associated with an individual curriculum resource. Authorized in the list view delete, import and
     * update functions. Authorized in the form views in the apply- & saveImport functions.
     *
     * @param   int  $resourceID  the resource id
     *
     * @return bool
     */
    public function delete(int $resourceID): bool
    {
        if (!$this->deleteRanges($resourceID)) {
            return false;
        }

        $table = $this->getTable();

        return $table->delete($resourceID);
    }

    /**
     * Method to import data associated with an individual curriculum resource. Authorization performed by calling function.
     *
     * @param   int  $resourceID  the id of the program to be imported
     *
     * @return bool
     */
    abstract public function import(int $resourceID): bool;

    /**
     * Retrieves the existing ordering of a pool to its parent item, or next highest value in the series
     *
     * @param   int  $parentID    the id of the parent range
     * @param   int  $resourceID  the id of the resource
     *
     * @return int  the value of the highest existing ordering or 1 if none exist
     */
    protected function ordering(int $parentID, int $resourceID): int
    {
        $column = strtolower(Application::getClass($this)) . 'ID';
        $query  = DB::getQuery();
        $query->select(DB::qn('ordering'))
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('parentID') . ' = :parentID')->bind(':parentID', $parentID, ParameterType::INTEGER)
            ->where(DB::qn($column) . ' = :resourceID')->bind(':resourceID', $resourceID, ParameterType::INTEGER);
        DB::setQuery($query);

        if ($existingOrdering = DB::loadInt()) {
            return $existingOrdering;
        }

        $query = DB::getQuery();
        $query->select('MAX(' . DB::qn('ordering') . ')')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('parentID') . ' = :parentID')->bind(':parentID', $parentID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::loadInt() + 1;
    }

    /**
     * Saves the resource, imports external data and redirects to the list view.
     * @return void
     */
    public function saveImport(): void
    {
        if (Application::getClass(get_called_class()) === 'Pool') {
            Application::error(501);
        }

        $id = $this->process();
        $this->import($id);
        $this->setRedirect("$this->baseURL&view=$this->list");
    }

    /**
     * Set name attributes common to pools and subjects.
     *
     * @param   Pools|Subjects    $table      the table to modify
     * @param   SimpleXMLElement  $XMLObject  the data source
     *
     * @return void
     */
    protected function setNames(Pools|Subjects $table, SimpleXMLElement $XMLObject): void
    {
        $table->setColumn('abbreviation_de', (string) $XMLObject->kuerzel, '');
        $table->setColumn('abbreviation_en', (string) $XMLObject->kuerzelen, $table->abbreviation_de);

        $table->fullName_de = (string) $XMLObject->titelde;
        $table->fullName_en = (string) $XMLObject->titelen ?: $table->fullName_de;
    }

    /**
     * Ensures that a title is set and does not contain 'dummy'. This function favors the German title.
     *
     * @param   SimpleXMLElement  $resource  the resource being checked
     *
     * @return bool
     */
    protected function validTitle(SimpleXMLElement $resource): bool
    {
        $titleDE = trim((string) $resource->titelde);
        $titleEN = trim((string) $resource->titelen);
        $title   = $titleDE ?: $titleEN;

        if (empty($title)) {
            return false;
        }

        $dummyPos = stripos($title, 'dummy');

        return $dummyPos === false;
    }
}