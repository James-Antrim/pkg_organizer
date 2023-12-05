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

use Joomla\Database\ParameterType;
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input};
use THM\Organizer\Tables\Pools as Table;

/**
 * Provides general functions for (subject) pool access checks, data retrieval and display.
 */
class Pools extends Curricula implements Selectable
{
    use Filtered;

    protected static string $resource = 'pool';

    /**
     * Creates a text for the required pool credit points
     *
     * @param   array  $pool  the pool
     *
     * @return string  the required amount of credit points
     */
    public static function crpText(array $pool): string
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
     * @inheritDoc
     */
    public static function documentable(string $resource = 'pool'): array
    {
        return parent::documentable($resource);
    }

    /**
     * Retrieves the range of the selected resource exclusive subordinate pools.
     *
     * @param   array  $range  the original range of a pool
     *
     * @return array[]  boundary values
     */
    private static function filterExclusions(array $range): array
    {
        $query = DB::getQuery();
        $query->select('*')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('poolID') . ' IS NOT NULL')
            ->where(DB::qcs([['lft', ':left', '>'], ['rgt', ':right', '<']]))
            ->bind(':left', $range['lft'], ParameterType::INTEGER)
            ->bind(':right', $range['rgt'], ParameterType::INTEGER)
            ->order('lft');
        DB::setQuery($query);

        if (!$exclusions = DB::loadAssocList()) {
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

    /**
     * Gets the mapped curricula ranges for the given pool
     *
     * @param   array|int  $identifiers  int poolID | array ranges of subordinate resources
     *
     * @return array[] the pool ranges
     */
    public static function filterRanges(array|int $identifiers): array
    {
        if (!$ranges = self::ranges($identifiers)) {
            return [];
        }

        $filteredBoundaries = [];
        foreach ($ranges as $range) {
            $filteredBoundaries = self::filterExclusions($range);
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
    public static function indentName(string $name, int $level): string
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
     * Loads an array modeling the attributes of the resource.
     *
     * @param   int  $poolID
     *
     * @return array
     */
    public static function load(int $poolID): array
    {
        $table = new Table();

        if (!$table->load($poolID)) {
            return [];
        }

        $fieldID         = $table->fieldID ?: 0;
        $organizationIDs = self::organizationIDs($table->id);
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
    public static function parentOptions(): string
    {
        $resourceID   = Input::getID();
        $resourceType = Input::getCMD('type');

        // Pending program ranges are dependent on selected programs.
        $programIDs    = Input::getFilterIDs('program');
        $programRanges = Programs::programs($programIDs);

        $options = self::superOptions($resourceID, $resourceType, $programRanges);

        return implode('', $options);
    }

    /**
     * Gets an HTML option based upon a pool curriculum association
     *
     * @param   array  $range      the curriculum range entry
     * @param   array  $parentIDs  the selected parents
     *
     * @return string  HTML option
     */
    public static function option(array $range, array $parentIDs): string
    {
        $poolsTable = new Table();

        if (!$poolsTable->load($range['poolID'])) {
            return '';
        }

        $nameColumn   = 'fullName_' . Application::getTag();
        $indentedName = Pools::indentName($poolsTable->$nameColumn, $range['level']);

        $selected = in_array($range['id'], $parentIDs) ? 'selected' : '';

        return "<option value='{$range['id']}' $selected>$indentedName</option>";
    }

    /**
     * @inheritDoc
     */
    public static function ranges(array|int $identifiers): array
    {
        if (empty($identifiers) or $identifiers === self::NONE) {
            return [];
        }

        $poolID = DB::qn('poolID');
        $query  = DB::getQuery();
        $query->select('DISTINCT *')
            ->from(DB::qn('#__organizer_curricula'))
            ->where("$poolID IS NOT NULL")
            ->order(DB::qn('lft'));

        if (is_array($identifiers)) {
            self::filterSuperOrdinate($query, $identifiers);
        }
        else {
            $query->where("$poolID = :poolID")->bind(':poolID', $identifiers, ParameterType::INTEGER);
        }

        DB::setQuery($query);

        return DB::loadAssocList();
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

        if (!$ranges = $poolID ? self::ranges($poolID) : Programs::ranges($programID)) {
            return [];
        }

        $query = DB::getQuery();
        $tag   = Application::getTag();
        $query->select(['DISTINCT ' . DB::qn('p') . '.*', DB::qn("p.fullName_$tag", 'name')])
            ->from(DB::qn('#__organizer_pools', 'p'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'c'), DB::qc('c.poolID', 'p.id'))
            ->where(DB::qcs([['lft', ':left', '>'], ['rgt', ':right', '<']]))
            ->bind(':left', $ranges[0]['lft'], ParameterType::INTEGER)
            ->bind(':right', $ranges[0]['rgt'], ParameterType::INTEGER)
            ->order('name');

        if (!empty($access)) {
            self::filterAccess($query, $access, 'pool', 'p');
        }

        DB::setQuery($query);

        return DB::loadAssocList('id');
    }
}
