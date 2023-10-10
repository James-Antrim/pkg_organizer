<?php
/**
 * @package     Organizer\Models
 * @subpackage
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */


namespace THM\Organizer\Models;

use THM\Organizer\Adapters\{Application, Database, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\OrganizerHelper;
use THM\Organizer\Tables;
use SimpleXMLElement;

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
     * @param array &$range an array containing data about a curriculum item and potentially its children
     *
     * @return int the id of the curriculum row on success, otherwise 0
     */
    protected function addRange(array &$range): int
    {
        $curricula = new Tables\Curricula();

        if (empty($range['programID'])) {
            // Subordinates must have a parent
            if (empty($range['parentID']) or !$parent = Helpers\Curricula::getRange($range['parentID'])) {
                return 0;
            }

            // No resource
            if (empty($range['poolID']) and empty($range['subjectID'])) {
                return 0;
            }

            $conditions = ['parentID' => $range['parentID']];

            if (empty($range['subjectID'])) {
                $conditions['poolID'] = $range['poolID'];
            } else {
                $conditions['subjectID'] = $range['subjectID'];
            }
        } else {
            $conditions = ['programID' => $range['programID']];
            $parent     = null;
        }

        if ($curricula->load($conditions)) {
            $curricula->ordering = $range['ordering'];
            if (!$curricula->store()) {
                return 0;
            }
        } else {
            if (!empty($range['programID'])) {
                $range['parentID'] = null;
            }

            $range['lft'] = $this->getLeft($range['parentID'], $range['ordering']);

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

            if ($subRangeIDs = implode(',', $subRangeIDs)) {
                $query = Database::getQuery();
                $query->select('id')
                    ->from('#__organizer_curricula')
                    ->where("id NOT IN ($subRangeIDs)")
                    ->where("parentID = $curricula->id");
                Database::setQuery($query);

                if ($zombieIDs = Database::loadIntColumn()) {
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
    protected function authorize()
    {
        if (($id = Input::getID() and !Helpers\Can::document($this->resource, $id))
            or !Helpers\Can::documentTheseOrganizations()) {
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
                if (!Helpers\Can::document($this->resource, $resourceID)) {
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
     * @param int $rangeID the id value of the range to be deleted
     *
     * @return bool  true on success, otherwise false
     */
    protected function deleteRange(int $rangeID): bool
    {
        if (!$range = Helpers\Curricula::getRange($rangeID)) {
            return false;
        }

        // Deletes the range
        $curricula = new Tables\Curricula();

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
     * @param int $resourceID the id of the resource
     *
     * @return bool true on success, otherwise false
     */
    protected function deleteRanges(int $resourceID): bool
    {
        $helper = "Organizer\\Helpers\\" . $this->helper;

        /** @noinspection PhpUndefinedMethodInspection */
        if ($rangeIDs = $helper::getRangeIDs($resourceID)) {
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
     * @param int $resourceID the resource id
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
     * Gets the curriculum for a pool selected as a subordinate resource
     *
     * @param int $poolID the resource id
     *
     * @return array[]  empty if no child data exists
     */
    protected function getExistingCurriculum(int $poolID): array
    {
        // Subordinate structures are the same for every superordinate resource
        $query = Database::getQuery();
        $query->select('id')->from('#__organizer_curricula')->where("poolID = $poolID");
        Database::setQuery($query);

        if (!$firstID = Database::loadInt()) {
            return [];
        }

        $query = Database::getQuery();
        $query->select('*')->from('#__organizer_curricula')->where("parentID = $firstID")->order('lft');
        Database::setQuery($query);

        if (!$subOrdinates = Database::loadAssocList()) {
            return $subOrdinates;
        }

        foreach ($subOrdinates as $key => $subOrdinate) {
            if ($subOrdinate['poolID']) {
                $subOrdinates[$key]['curriculum'] = $this->getExistingCurriculum($subOrdinate['poolID']);
            }
        }

        return $subOrdinates;
    }

    /**
     * Returns the resource's existing ordering in the context of its parent.
     *
     * @param int $parentID   the parent id (curricula)
     * @param int $resourceID the resource id (resource table)
     *
     * @return int int if the resource has an existing ordering, otherwise null
     */
    protected function getExistingOrdering(int $parentID, int $resourceID): int
    {
        $column = $this->resource . 'ID';
        $query  = Database::getQuery();
        $query->select('ordering')
            ->from('#__organizer_curricula')
            ->where("parentID = $parentID")
            ->where("$column = $resourceID");
        Database::setQuery($query);

        return Database::loadInt();
    }

    /**
     * Attempt to determine the left value for the range to be created
     *
     * @param null|int $parentID the parent of the item to be inserted
     * @param mixed    $ordering the targeted ordering on completion
     *
     * @return int  int the left value for the range to be created, or 0 on error
     */
    protected function getLeft(?int $parentID, $ordering): int
    {
        if (!$parentID) {
            $query = Database::getQuery();
            $query->select('MAX(rgt) + 1')->from('#__organizer_curricula');
            Database::setQuery($query);

            return Database::loadInt();
        }

        // Right value of the next lowest sibling
        $rgtQuery = Database::getQuery();
        $rgtQuery->select('MAX(rgt)')
            ->from('#__organizer_curricula')
            ->where("parentID = $parentID")
            ->where("ordering < $ordering");
        Database::setQuery($rgtQuery);

        if ($rgt = Database::loadInt()) {
            return $rgt + 1;
        }

        // No siblings => use parent left for reference
        $lftQuery = Database::getQuery();
        $lftQuery->select('lft')
            ->from('#__organizer_curricula')
            ->where("id = $parentID");
        Database::setQuery($lftQuery);
        $lft = Database::loadInt();

        return $lft ? $lft + 1 : 0;
    }

    /**
     * Retrieves the existing ordering of a pool to its parent item, or next highest value in the series
     *
     * @param int $parentID   the id of the parent range
     * @param int $resourceID the id of the resource
     *
     * @return int  the value of the highest existing ordering or 1 if none exist
     */
    protected function getOrdering(int $parentID, int $resourceID): int
    {
        if ($existingOrdering = $this->getExistingOrdering($parentID, $resourceID)) {
            return $existingOrdering;
        }

        $query = Database::getQuery();
        $query->select('MAX(ordering)')->from('#__organizer_curricula')->where("parentID = $parentID");
        Database::setQuery($query);

        return Database::loadInt() + 1;
    }

    /**
     * Gets the mapped curricula ranges for the given resource
     *
     * @param int $resourceID the resource id
     *
     * @return array[] the resource ranges
     */
    protected function getRanges(int $resourceID): array
    {
        $helper = "Organizer\\Helpers\\" . $this->helper;

        /** @noinspection PhpUndefinedMethodInspection */
        return $helper::getRanges($resourceID);
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
     * @param int $resourceID the id of the program to be imported
     *
     * @return bool  true on success, otherwise false
     */
    abstract public function importSingle(int $resourceID): bool;

    /**
     * Iterates a collection of resources subordinate to the calling resource. Creating structure and data elements as
     * needed.
     *
     * @param SimpleXMLElement $collection     the SimpleXML node containing the collection of subordinate elements
     * @param int              $organizationID the id of the organization with which the resources are associated
     * @param int              $parentID       the id of the curriculum entry for the parent element.
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
     * Set name attributes common to pools and subjects
     *
     * @param Tables\Pools|Tables\Subjects $table     the table to modify
     * @param SimpleXMLElement             $XMLObject the data source
     *
     * @return void modifies the table object
     */
    protected function setNameAttributes($table, SimpleXMLElement $XMLObject)
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
     * @param int $parentID the id of the parent
     * @param int $ordering the ordering of the item to be inserted
     *
     * @return bool  true on success, otherwise false
     */
    protected function shiftDown(int $parentID, int $ordering): bool
    {
        $query = Database::getQuery();
        $query->update('#__organizer_curricula')
            ->set('ordering = ordering - 1')
            ->where("ordering > $ordering")
            ->where("parentID = $parentID");
        Database::setQuery($query);

        return Database::execute();
    }

    /**
     * Shifts left and right values to allow for the values to be inserted
     *
     * @param int $left  the int value above which left and right values need to be shifted
     * @param int $width the width of the item being deleted
     *
     * @return bool  true on success, otherwise false
     */
    protected function shiftLeft(int $left, int $width): bool
    {
        $lftQuery = Database::getQuery();
        $lftQuery->update('#__organizer_curricula')->set("lft = lft - $width")->where("lft > $left");
        Database::setQuery($lftQuery);

        if (!Database::execute()) {
            return false;
        }

        $rgtQuery = Database::getQuery();
        $rgtQuery->update('#__organizer_curricula')->set("rgt = rgt - $width")->where("rgt > $left");
        Database::setQuery($rgtQuery);

        return Database::execute();
    }

    /**
     * Shifts left and right values to allow for the values to be inserted
     *
     * @param int $left      the int value above which left and right values
     *                       need to be shifted
     *
     * @return bool  true on success, otherwise false
     */
    protected function shiftRight(int $left): bool
    {
        $lftQuery = Database::getQuery();
        $lftQuery->update('#__organizer_curricula')->set('lft = lft + 2')->where("lft >= $left");
        Database::setQuery($lftQuery);

        if (!Database::execute()) {
            return false;
        }

        $rgtQuery = Database::getQuery();
        $rgtQuery->update('#__organizer_curricula')->set('rgt = rgt + 2')->where("rgt >= $left");
        Database::setQuery($rgtQuery);

        return Database::execute();
    }

    /**
     * Shifts the ordering for existing siblings who have an ordering at or above the ordering to be inserted.
     *
     * @param int $parentID the id of the parent
     * @param int $ordering the ordering of the item to be inserted
     *
     * @return bool  true on success, otherwise false
     */
    protected function shiftUp(int $parentID, int $ordering): bool
    {
        $query = Database::getQuery();
        $query->update('#__organizer_curricula')
            ->set('ordering = ordering + 1')
            ->where("ordering >= $ordering")
            ->where("parentID = $parentID");
        Database::setQuery($query);

        return Database::execute();
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param string $name    The table name. Optional.
     * @param string $prefix  The class prefix. Optional.
     * @param array  $options Configuration array for model. Optional.
     *
     * @return Tables\BaseTable A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingReturnTypeInspection polymorphic return value
     */
    public function getTable($name = '', $prefix = '', $options = [])
    {
        $table = "Organizer\\Tables\\" . $this->helper;

        return new $table();
    }

    /**
     * Ensures that the title(s) are set and do not contain 'dummy'. This function favors the German title.
     *
     * @param SimpleXMLElement $resource the resource being checked
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