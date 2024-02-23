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
use THM\Organizer\Tables\{Associations, Curricula, Pools as PoolsTable, Subjects, Table};
use THM\Organizer\Helpers\{Documentable, Pools as PoolsHelper, Programs};

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
        $this->setRedirect("$this->baseURL&view=" . strtolower($this->list) . "&id=$id");
    }

    /**
     * General or specific resource documentation authorization.
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
     * @inheritDoc
     */
    protected function authorizeAJAX(): void
    {
        // Has already been checked in calling function.
        $id = Input::getID();

        /** @var Documentable $helper */
        $helper = "THM\\Organizer\\Helpers\\" . $this->list;

        if (!$helper::documentable($id)) {
            http_response_code(403);
            echo '';
            $this->app->close();
        }
    }

    /**
     * Ensures that the imported resource is mapped in the curricula table.
     *
     * @param   Curricula  $curriculum  the curricula table object
     * @param   int        $parentID    the id of the curriculum entry for the resource superordinate to this one
     * @param   string     $column      the resource reference column name
     * @param   int        $resourceID  the resource id
     *
     * @return void
     */
    protected function checkCurriculum(Curricula $curriculum, int $parentID, string $column, int $resourceID): void
    {
        $keys = ['parentID' => $parentID, $column => $resourceID];
        if (!$curriculum->load($keys)) {
            $range             = $keys;
            $range['ordering'] = $this->ordering($parentID, $resourceID);

            if (!$this->shiftUp($parentID, $range['ordering']) or !$this->addRange($range)) {
                return;
            }

            $curriculum->load($keys);
        }
    }

    /**
     * Ensures that the imported resource is associated with the selected organization.
     *
     * @param   int     $organizationID  the id of the organization
     * @param   string  $column          the resource reference column name
     * @param   int     $resourceID      the resource id
     *
     * @return void
     */
    protected function checkAssociation(int $organizationID, string $column, int $resourceID): void
    {
        $association = new Associations();
        $keys        = ['organizationID' => $organizationID, $column => $resourceID];
        if (!$association->load($keys)) {
            $association->save($keys);
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
     * @inheritDoc
     */
    public function process(): int
    {
        $this->checkToken();
        $this->authorize();

        $id   = Input::getID();
        $data = $this->prepareData();

        // For save to copy, will otherwise be identical.
        $data['id'] = $id;

        /** @var Table $table */
        $table = $this->getTable();

        if (!$id = $this->store($table, $data, $id)) {
            return $id;
        }

        $data['id'] = $id;

        $this->postProcess($data);

        return $id;
    }

    /**
     * The process steps post-store specific to individual resource types.
     *
     * @param   array  $data  the data to process
     *
     * @return void
     */
    abstract protected function postProcess(array $data): void;

    /**
     * Iterates a collection of resources subordinate to the calling resource. Creating structure and data elements as
     * needed.
     *
     * @param   SimpleXMLElement  $collection      the SimpleXML node containing the collection of subordinate elements
     * @param   int               $organizationID  the id of the organization with which the resources are associated
     * @param   int               $parentID        the id of the curriculum entry for the parent element.
     *
     * @return bool
     */
    protected function processCollection(SimpleXMLElement $collection, int $organizationID, int $parentID): bool
    {
        $pool    = new Pool();
        $subject = new Subject();

        foreach ($collection as $subOrdinate) {
            $type = (string) $subOrdinate->pordtyp;

            if ($type === self::POOL) {
                if ($pool->processStub($subOrdinate, $organizationID, $parentID)) {
                    continue;
                }

                return false;
            }

            if ($type === self::SUBJECT) {
                if ($subject->processStub($subOrdinate, $organizationID, $parentID)) {
                    continue;
                }

                return false;
            }
        }

        return true;
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
        $this->setRedirect("$this->baseURL&view=" . strtolower($this->list));
    }

    /**
     * Set name attributes common to pools and subjects.
     *
     * @param   PoolsTable|Subjects  $table      the table to modify
     * @param   SimpleXMLElement     $XMLObject  the data source
     *
     * @return void
     */
    protected function setNames(PoolsTable|Subjects $table, SimpleXMLElement $XMLObject): void
    {
        $table->setColumn('abbreviation_de', (string) $XMLObject->kuerzel, '');
        $table->setColumn('abbreviation_en', (string) $XMLObject->kuerzelen, $table->abbreviation_de);

        $table->fullName_de = (string) $XMLObject->titelde;
        $table->fullName_en = (string) $XMLObject->titelen ?: $table->fullName_de;
    }

    /**
     * Method to save the submitted ordering values for records via AJAX.
     * @return  void
     */
    public function superOrdinates(): void
    {
        $this->checkToken();

        $id   = Input::getID();
        $type = Input::getCMD('type');

        if (!$id or !$type) {
            http_response_code(400);
            echo '';
            $this->app->close();
        }

        $this->authorizeAJAX();

        $options = '';
        $ranges  = Programs::programs(Input::getIntArray('programIDs'));
        $values  = PoolsHelper::superValues($id, $type);

        foreach (PoolsHelper::superOptions($type, $ranges) as $option) {
            $selected = in_array($option->value, $values) ? 'selected' : '';
            $options  .= "<option value=\"$option->value\" $selected $option->disabled>$option->text</option>";
        }

        echo json_encode($options, JSON_UNESCAPED_UNICODE);

        $this->app->close();
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