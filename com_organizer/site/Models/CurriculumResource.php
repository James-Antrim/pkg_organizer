<?php
/**
 * @package     Organizer\Models
 * @subpackage
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace THM\Organizer\Models;

use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use SimpleXMLElement;
use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers\{Can, Curricula as Helper, Organizations};
use THM\Organizer\Tables\{Curricula, Pools, Subjects};

/**
 * Class provides functions to use managing resources in a nested curriculum structure.
 */
abstract class CurriculumResource extends BaseModel
{
    use Associated;

    protected const NONE = -1, POOL = 'K', SUBJECT = 'M';

    protected string $helper;

    protected string $resource;

    /**
     * Adds a curriculum range to a parent curriculum range
     *
     * @param   array &$range  an array containing data about a curriculum item and potentially its children
     *
     * @return int the id of the curriculum row on success, otherwise 0
     */
    protected function addRange(array &$range): int
    {
        $curricula = new Curricula();

        if (empty($range['programID'])) {
            // Subordinates must have a parent
            if (empty($range['parentID']) or !$parent = Helper::row($range['parentID'])) {
                return 0;
            }

            // No resource
            if (empty($range['poolID']) and empty($range['subjectID'])) {
                return 0;
            }

            $conditions = ['parentID' => $range['parentID']];

            if (empty($range['subjectID'])) {
                $conditions['poolID'] = $range['poolID'];
            }
            else {
                $conditions['subjectID'] = $range['subjectID'];
            }
        }
        else {
            $conditions = ['programID' => $range['programID']];
            $parent     = null;
        }

        if ($curricula->load($conditions)) {
            $curricula->ordering = $range['ordering'];
            if (!$curricula->store()) {
                return 0;
            }
        }
        else {
            if (!empty($range['programID'])) {
                $range['parentID'] = null;
            }

            $range['lft'] = $this->left($range['parentID'], $range['ordering']);

            if (!$range['lft'] or !$this->shiftRight($range['lft'])) {
                return 0;
            }

            $range['level'] = $parent ? $parent['level'] + 1 : 0;
            $range['rgt']   = $range['lft'] + 1;

            if (!$curricula->save($range)) {
                return 0;
            }
        }

        if (!empty($range['curriculum'])) {
            $subRangeIDs = [];

            foreach ($range['curriculum'] as $subOrdinate) {
                $subOrdinate['parentID'] = $curricula->id;

                if (!$subRangeID = $this->addRange($subOrdinate)) {
                    return 0;
                }

                $subRangeIDs[$subRangeID] = $subRangeID;
            }

            if ($subRangeIDs) {
                $query = DB::getQuery();
                $query->select(DB::qn('id'))
                    ->from(DB::qn('#__organizer_curricula'))
                    ->whereNotIn(DB::qn('id'), $subRangeIDs)
                    ->where(DB::qn('parentID') . ' = :curriculaID')
                    ->bind(':curriculaID', $curricula->id, ParameterType::INTEGER);
                DB::setQuery($query);

                if ($zombieIDs = DB::loadIntColumn()) {
                    foreach ($zombieIDs as $zombieID) {
                        $this->deleteRange($zombieID);
                    }
                }
            }
        }

        return $curricula->id;
    }

    /**
     * @inheritDoc
     */
    protected function authorize(): void
    {
        if (($id = Input::getID() and !Can::document($this->resource, $id)) or !Organizations::documentableIDs()) {
            Application::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(): bool
    {
        $this->authorize();

        if ($resourceIDs = Input::getSelectedIDs()) {
            foreach ($resourceIDs as $resourceID) {
                if (!Can::document($this->resource, $resourceID)) {
                    Application::error(403);
                }

                if (!$this->deleteSingle($resourceID)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Method to delete a single range from the curricula table
     *
     * @param   int  $rangeID  the id value of the range to be deleted
     *
     * @return bool  true on success, otherwise false
     */
    protected function deleteRange(int $rangeID): bool
    {
        if (!$range = Helper::row($rangeID)) {
            return false;
        }

        // Deletes the range
        $curricula = new Curricula();

        if (!$curricula->delete($rangeID)) {
            return false;
        }

        // Reduces the ordering of siblings with a greater ordering
        if (!empty($range['parentID']) and !$this->shiftDown($range['parentID'], $range['ordering'])) {
            return false;
        }

        $width = $range['rgt'] - $range['lft'] + 1;

        return $this->shiftLeft($range['lft'], $width);
    }

    /**
     * Deletes ranges of a specific curriculum resource.
     *
     * @param   int  $resourceID  the id of the resource
     *
     * @return bool true on success, otherwise false
     */
    protected function deleteRanges(int $resourceID): bool
    {
        /** @var Helper $helper */
        $helper = "THM\\Organizer\\Helpers\\$this->helper";

        if ($rangeIDs = $helper::rowIDs($resourceID)) {
            foreach ($rangeIDs as $rangeID) {
                $success = $this->deleteRange($rangeID);
                if (!$success) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Deletes a single curriculum resource.
     *
     * @param   int  $resourceID  the resource id
     *
     * @return bool  true on success, otherwise false
     */
    protected function deleteSingle(int $resourceID): bool
    {
        if (!$this->deleteRanges($resourceID)) {
            return false;
        }

        $table = $this->getTable();

        return $table->delete($resourceID);
    }

    /**
     * Method to import data associated with resources from LSF
     * @return bool true on success, otherwise false
     */
    public function import(): bool
    {
        foreach (Input::getSelectedIDs() as $resourceID) {
            if (!$this->importSingle($resourceID)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Method to import data associated with a resource from LSF
     *
     * @param   int  $resourceID  the id of the program to be imported
     *
     * @return bool  true on success, otherwise false
     */
    abstract public function importSingle(int $resourceID): bool;

    /**
     * Attempt to determine the left value for the range to be created
     *
     * @param   null|int  $parentID  the parent of the item to be inserted
     * @param   int       $ordering  the targeted ordering on completion
     *
     * @return int  int the left value for the range to be created, or 0 on error
     */
    protected function left(?int $parentID, int $ordering): int
    {
        if (!$parentID) {
            $query = DB::getQuery();
            $query->select('MAX(' . DB::qn('rgt') . ') + 1')->from(DB::qn('#__organizer_curricula'));
            DB::setQuery($query);

            return DB::loadInt();
        }

        // Right value of the next lowest sibling
        $rgtQuery = DB::getQuery();
        $rgtQuery->select('MAX(' . DB::qn('rgt') . ')')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('parentID') . ' = :parentID')->bind(':parentID', $parentID, ParameterType::INTEGER)
            ->where(DB::qn('ordering') . ' < :ordering')->bind(':ordering', $ordering, ParameterType::INTEGER);
        DB::setQuery($rgtQuery);

        if ($rgt = DB::loadInt()) {
            return $rgt + 1;
        }

        // No siblings => use parent left for reference
        $lftQuery = DB::getQuery();
        $lftQuery->select(DB::qn('lft'))
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('id') . ' = :parentID')->bind(':parentID', $parentID, ParameterType::INTEGER);
        DB::setQuery($lftQuery);
        $lft = DB::loadInt();

        return $lft ? $lft + 1 : 0;
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
        $column = $this->resource . 'ID';
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
     * Iterates a collection of resources subordinate to the calling resource. Creating structure and data elements as
     * needed.
     *
     * @param   SimpleXMLElement  $collection      the SimpleXML node containing the collection of subordinate elements
     * @param   int               $organizationID  the id of the organization with which the resources are associated
     * @param   int               $parentID        the id of the curriculum entry for the parent element.
     *
     * @return bool true on success, otherwise false
     */
    protected function processCollection(SimpleXMLElement $collection, int $organizationID, int $parentID): bool
    {
        $pool    = new Pool();
        $subject = new Subject();

        foreach ($collection as $subOrdinate) {
            $type = (string) $subOrdinate->pordtyp;

            if ($type === self::POOL) {
                if ($pool->processResource($subOrdinate, $organizationID, $parentID)) {
                    continue;
                }

                return false;
            }

            if ($type === self::SUBJECT) {
                if ($subject->processResource($subOrdinate, $organizationID, $parentID)) {
                    continue;
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Gets the mapped curricula ranges for the given resource
     *
     * @param   int  $resourceID  the resource id
     *
     * @return array[] the resource ranges
     */
    protected function ranges(int $resourceID): array
    {
        /** @var Helper $helper */
        $helper = "THM\\Organizer\\Helpers\\$this->helper";

        return $helper::rows($resourceID);
    }

    /**
     * Set name attributes common to pools and subjects
     *
     * @param   Pools|Subjects    $table      the table to modify
     * @param   SimpleXMLElement  $XMLObject  the data source
     *
     * @return void
     */
    protected function setNameAttributes(Pools|Subjects $table, SimpleXMLElement $XMLObject): void
    {
        $table->setColumn('abbreviation_de', (string) $XMLObject->kuerzel, '');
        $table->setColumn('abbreviation_en', (string) $XMLObject->kuerzelen, $table->abbreviation_de);

        $deTitle = (string) $XMLObject->titelde;
        if (!$enTitle = (string) $XMLObject->titelen) {
            $enTitle = $deTitle;
        }

        $table->fullName_de = $deTitle;
        $table->fullName_en = $enTitle;
    }

    /**
     * Shifts the ordering for existing siblings who have an ordering at or above the ordering to be inserted.
     *
     * @param   int  $parentID  the id of the parent
     * @param   int  $ordering  the ordering of the item to be inserted
     *
     * @return bool  true on success, otherwise false
     */
    protected function shiftDown(int $parentID, int $ordering): bool
    {
        $column = DB::qn('ordering');
        $query  = DB::getQuery();
        $query->update('#__organizer_curricula')
            ->set('$column = $column - 1')
            ->where("$column > :ordering")->bind(':ordering', $ordering, ParameterType::INTEGER)
            ->where(DB::qn('parentID') . ' = :parentID')->bind(':parentID', $parentID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::execute();
    }

    /**
     * Shifts left and right values to allow for the values to be inserted
     *
     * @param   int  $left   the int value above which left and right values need to be shifted
     * @param   int  $width  the width of the item being deleted
     *
     * @return bool  true on success, otherwise false
     */
    protected function shiftLeft(int $left, int $width): bool
    {
        $column = DB::qn('lft');
        $query  = DB::getQuery();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set("$column = $column - :width")->bind(':width', $width, ParameterType::INTEGER)
            ->where("$column > :left")->bind(':left', $left, ParameterType::INTEGER);
        DB::setQuery($query);

        if (!DB::execute()) {
            return false;
        }

        $column = DB::qn('rgt');
        $query  = DB::getQuery();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set("$column = $column - :width")->bind(':width', $width, ParameterType::INTEGER)
            ->where("$column > :left")->bind(':left', $left, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::execute();
    }

    /**
     * Shifts left and right values to allow for the values to be inserted
     *
     * @param   int  $left   the int value above which left and right values
     *                       need to be shifted
     *
     * @return bool  true on success, otherwise false
     */
    protected function shiftRight(int $left): bool
    {
        $column = DB::qn('lft');
        $query  = DB::getQuery();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set('$column = $column + 2')
            ->where("$column >= :left")->bind(':left', $left, ParameterType::INTEGER);
        DB::setQuery($query);

        if (!DB::execute()) {
            return false;
        }

        $column = DB::qn('rgt');
        $query  = DB::getQuery();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set('$column = $column 2')
            ->where("$column >= :left")->bind(':left', $left, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::execute();
    }

    /**
     * Shifts the ordering for existing siblings who have an ordering at or above the ordering to be inserted.
     *
     * @param   int  $parentID  the id of the parent
     * @param   int  $ordering  the ordering of the item to be inserted
     *
     * @return bool  true on success, otherwise false
     */
    protected function shiftUp(int $parentID, int $ordering): bool
    {
        $column = DB::qn('ordering');
        $query  = DB::getQuery();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set('$column = $column + 1')
            ->where("$column >= :ordering")->bind(':ordering', $ordering, ParameterType::INTEGER)
            ->where(DB::qn('parentID') . ' = :parentID')->bind(':parentID', $parentID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::execute();
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return Table A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingReturnTypeInspection polymorphic return value
     */
    public function getTable($name = '', $prefix = '', $options = []): Table
    {
        $table = "THM\\Organizer\\Tables\\$this->helper";

        return new $table();
    }

    /**
     * Ensures that the title(s) are set and do not contain 'dummy'. This function favors the German title.
     *
     * @param   SimpleXMLElement  $resource  the resource being checked
     *
     * @return bool true if one of the titles has the possibility of being valid, otherwise false
     */
    protected function validTitle(SimpleXMLElement $resource): bool
    {
        $titleDE = trim((string) $resource->titelde);
        $titleEN = trim((string) $resource->titelen);
        $title   = empty($titleDE) ? $titleEN : $titleDE;

        if (empty($title)) {
            return false;
        }

        $dummyPos = stripos($title, 'dummy');

        return $dummyPos === false;
    }
}