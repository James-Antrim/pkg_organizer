<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use THM\Organizer\Adapters\{Application, Database, HTML, Input};
use THM\Organizer\Tables;

/**
 * Provides general functions for (subject) pool access checks, data retrieval and display.
 */
class Pools extends Curricula implements Selectable
{
    use Filtered;

    protected static $resource = 'pool';

    /**
     * Creates a text for the required pool credit points
     *
     * @param   array  $pool  the pool
     *
     * @return string  the required amount of credit points
     */
    public static function getCrPText(array $pool): string
    {
        $minCrPExists = !empty($pool['minCrP']);
        $maxCrPExists = !empty($pool['maxCrP']);
        if ($maxCrPExists and $minCrPExists) {
            return $pool['maxCrP'] === $pool['minCrP'] ?
                "{$pool['maxCrP']} CrP" : "{$pool['minCrP']} - {$pool['maxCrP']} CrP";
        }
        elseif ($maxCrPExists) {
            return "max. {$pool['maxCrP']} CrP";
        }
        elseif ($minCrPExists) {
            return "min. {$pool['minCrP']} CrP";
        }

        return '';
    }

    /**
     * Gets an HTML option based upon a pool curriculum association
     *
     * @param   array  $range      the curriculum range entry
     * @param   array  $parentIDs  the selected parents
     *
     * @return string  HTML option
     */
    public static function getCurricularOption(array $range, array $parentIDs): string
    {
        $poolsTable = new Tables\Pools();

        if (!$poolsTable->load($range['poolID'])) {
            return '';
        }

        $nameColumn   = 'fullName_' . Application::getTag();
        $indentedName = Pools::getIndentedName($poolsTable->$nameColumn, $range['level']);

        $selected = in_array($range['id'], $parentIDs) ? 'selected' : '';

        return "<option value='{$range['id']}' $selected>$indentedName</option>";
    }

    /**
     * Gets the mapped curricula ranges for the given pool
     *
     * @param   array|int  $identifiers  int poolID | array ranges of subordinate resources
     *
     * @return array[] the pool ranges
     */
    public static function getFilteredRanges(array|int $identifiers): array
    {
        if (!$ranges = self::getRanges($identifiers)) {
            return [];
        }

        $filteredBoundaries = [];
        foreach ($ranges as $range) {
            $filteredBoundaries = self::removeExclusions($range);
        }

        return $filteredBoundaries;
    }

    /**
     * Creates a name for use in a list of options implicitly displaying the pool hierarchy.
     *
     * @param   string  $name   the name of the pool
     * @param   int     $level  the structural depth
     *
     * @return string the pool name indented according to the curricular hierarchy
     */
    public static function getIndentedName(string $name, int $level): string
    {
        $iteration = 0;
        $indent    = '';
        while ($iteration < $level) {
            $indent .= '&nbsp;&nbsp;&nbsp;';
            $iteration++;
        }

        return $indent . '|_' . $name;
    }

    /**
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function getOptions(string $access = ''): array
    {
        $options = [];
        foreach (self::getResources($access) as $pool) {
            $options[] = HTML::option($pool['id'], $pool['name']);
        }

        return $options;
    }

    /**
     * Retrieves pool options for a given curriculum element
     * @return string
     */
    public static function getParentOptions(): string
    {
        $resourceID   = Input::getID();
        $resourceType = Input::getCMD('type');

        // Pending program ranges are dependent on selected programs.
        $programIDs    = Input::getFilterIDs('program');
        $programRanges = Programs::getPrograms($programIDs);

        $options = self::getSuperOptions($resourceID, $resourceType, $programRanges);

        return implode('', $options);
    }

    /**
     * @inheritDoc
     */
    public static function getRanges(array|int $identifiers): array
    {
        if (empty($identifiers)) {
            return [];
        }

        $query = Database::getQuery();
        $query->select('DISTINCT *')
            ->from('#__organizer_curricula')
            ->where('poolID IS NOT NULL ')
            ->order('lft');

        if (is_array($identifiers)) {
            self::filterSuperOrdinate($query, $identifiers);
        }
        else {
            $poolID = $identifiers;
            if ($identifiers != self::NONE) {
                $query->where("poolID = $poolID");
            }
        }

        Database::setQuery($query);

        return Database::loadAssocList();
    }

    /**
     * Gets an array modeling the attributes of the resource.
     *
     * @param $poolID
     *
     * @return array
     */
    public static function getPool($poolID): array
    {
        $table = new Tables\Pools();

        if (!$table->load($poolID)) {
            return [];
        }

        $fieldID         = $table->fieldID ?: 0;
        $organizationIDs = self::getOrganizationIDs($table->id);
        $organizationID  = $organizationIDs ? $organizationIDs[0] : 0;
        $tag             = Application::getTag();

        return [
            'abbreviation' => $table->{"abbreviation_$tag"},
            'bgColor'      => Fields::getColor($fieldID, $organizationID),
            'description'  => $table->{"description_$tag"},
            'field'        => $fieldID ? Fields::getName($fieldID) : '',
            'fieldID'      => $table->fieldID,
            'id'           => $table->id,
            'maxCrP'       => $table->maxCrP,
            'minCrP'       => $table->minCrP,
            'name'         => $table->{"fullName_$tag"}
        ];
    }

    /**
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function getResources(string $access = ''): array
    {
        $programID = Input::getFilterID('program') ? Input::getFilterID('program') : Input::getInt('programID');
        $poolID    = Input::getInt('poolID');
        if (!$programID and !$poolID) {
            return [];
        }

        $ranges = $poolID ? self::getRanges($poolID) : Programs::getRanges($programID);
        if (empty($ranges)) {
            return [];
        }

        $query = Database::getQuery();
        $tag   = Application::getTag();
        $query->select("DISTINCT p.*, p.fullName_$tag AS name")
            ->from('#__organizer_pools AS p')
            ->innerJoin('#__organizer_curricula AS c ON c.poolID = p.id')
            ->where("lft > {$ranges[0]['lft']}")
            ->where("rgt < {$ranges[0]['rgt']}")
            ->order('name ASC');

        if (!empty($access)) {
            self::filterAccess($query, $access, 'pool', 'p');
        }

        Database::setQuery($query);

        return Database::loadAssocList('id');
    }

    /**
     * Retrieves the range of the selected resource exclusive subordinate pools.
     *
     * @param   array  $range  the original range of a pool
     *
     * @return array[]  boundary values
     */
    private static function removeExclusions(array $range): array
    {
        $query = Database::getQuery();
        $query->select('*')->from('#__organizer_curricula')
            ->where('poolID IS NOT NULL')
            ->where("lft > '{$range['lft']}' AND rgt < '{$range['rgt']}'")
            ->order('lft');
        Database::setQuery($query);

        if (!$exclusions = Database::loadAssocList()) {
            return [$range];
        }

        $ranges = [];
        foreach ($exclusions as $exclusion) {
            // Subordinate has no own subordinates => has no impact on output
            if ($exclusion['lft'] + 1 == $exclusion['rgt']) {
                continue;
            }

            // Not an immediate subordinate
            if ($exclusion['lft'] != $range['lft'] + 1) {
                $boundary = $range;
                // Create a new boundary from the current left to the exclusion
                $boundary['rgt'] = $exclusion['lft'];

                // Change the new left to the other side of the exclusion
                $range['lft'] = $exclusion['rgt'];

                $ranges[] = $boundary;
                continue;
            }

            // Change the new left to the other side of the exclusion
            $range['lft'] = $exclusion['rgt'];

            if ($range['lft'] >= $range['rgt']) {
                break;
            }
        }

        // Remnants after exclusions still exist
        if ($range['lft'] < $range['rgt']) {
            $ranges[] = $range;
        }

        return $ranges;
    }
}
