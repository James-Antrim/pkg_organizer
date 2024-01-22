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
use THM\Organizer\Adapters\Application;
use THM\Organizer\Adapters\Database as DB;

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
        if (Application::getClass(get_called_class()) === 'Pool') {
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
        if (Application::getClass(get_called_class()) === 'Pool') {
            Application::error(501);
        }

        $id = $this->process();
        $this->import($id);
        $this->setRedirect("$this->baseURL&view=$this->list");
    }

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
}