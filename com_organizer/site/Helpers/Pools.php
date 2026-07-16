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
use stdClass;
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input};
use THM\Organizer\Tables\Pools as Table;

/**
 * Provides general functions for (subject) pool access checks, data retrieval and display.
 */
class Pools extends Curricula implements Selectable, Subordinate
{
    protected static string $resource = 'pool';

    /**
     * Creates a text for the required pool credit points
     *
     * @param array $pool the pool
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
     * Retrieves the range of the selected resource exclusive subordinate pools.
     *
     * @param array $range the original range of a pool
     *
     * @return array[]  boundary values
     */
    private static function filterExclusions(array $range): array
    {
        $query = DB::query();
        $query->select('*')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('poolID') . ' IS NOT NULL')
            ->where(DB::qcs([['lft', ':left', '>'], ['rgt', ':right', '<']]))
            ->bind(':left', $range['lft'], ParameterType::INTEGER)
            ->bind(':right', $range['rgt'], ParameterType::INTEGER)
            ->order('lft');
        DB::set($query);

        if (!$exclusions = DB::arrays()) {
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
     * @param array|int $identifiers int poolID | array ranges of subordinate resources
     *
     * @return array[] the pool ranges
     */
    public static function filterRanges(array|int $identifiers): array
    {
        if (!$ranges = self::rows($identifiers)) {
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
     * @param string $name  the name of the pool
     * @param int    $level the structural depth
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
     * @param int $poolID
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
        $tag             = Application::tag();

        return [
            'abbreviation' => $table->{"abbreviation_$tag"},
            'bgColor' => Fields::color($fieldID, $organizationID),
            'description' => $table->{"description_$tag"},
            'field' => $fieldID ? Fields::name($fieldID) : '',
            'fieldID' => $table->fieldID,
            'id' => $table->id,
            'maxCrP' => $table->maxCrP,
            'minCrP' => $table->minCrP,
            'name' => $table->{"fullName_$tag"}
        ];
    }

    /**
     * @inheritDoc
     *
     * @param string $access any access restriction which should be performed
     */
    public static function options(string $access = ''): array
    {
        $options = [];
        foreach (self::resources($access) as $pool) {
            $options[] = HTML::option($pool['id'], $pool['name']);
        }

        return $options;
    }

    /**
     * Gets an option based upon a pool curriculum association.
     *
     * @param array $range     the curriculum range entry
     * @param array $parentIDs the currently assigned superordinate elements
     *
     * @return null|stdClass
     */
    public static function option(array $range, array $parentIDs): null|stdClass
    {
        $poolsTable = new Table();

        if ($poolsTable->load($range['poolID'])) {
            $nameColumn   = 'fullName_' . Application::tag();
            $indentedName = Pools::indentName($poolsTable->$nameColumn, $range['level']);

            $option           = HTML::option($range['id'], $indentedName);
            $option->disable  = '';
            $option->selected = in_array($range['id'], $parentIDs) ? 'selected' : '';
            return $option;
        }

        return null;
    }

    /** @inheritDoc */
    public static function rows(array|int $identifiers): array
    {
        if (empty($identifiers) or $identifiers === self::NONE) {
            return [];
        }

        $poolID = DB::qn('poolID');
        $query  = DB::query();
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

        DB::set($query);

        return DB::arrays();
    }

    /**
     * @inheritDoc
     *
     * @param string $access any access restriction which should be performed
     */
    public static function resources(string $access = ''): array
    {
        $programID = Input::integer('programID');
        $poolID    = Input::integer('poolID');

        if (!$programID and !$poolID) {
            return [];
        }

        $query = DB::query();
        $tag   = Application::tag();
        $query->select(['DISTINCT ' . DB::qn('p') . '.*', DB::qn("p.fullName_$tag", 'name')])
            ->from(DB::qn('#__organizer_pools', 'p'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'c'), DB::qc('c.poolID', 'p.id'))
            ->where(DB::qn('lft') . '> :left AND ' . DB::qn('rgt') . '< :right')
            ->order('name');

        self::filterByAccess($query, 'p', $access);

        if ($poolID and $ranges = self::rows($poolID)) {
            // Adjust the ranges for pool self inclusion
            $left  = $ranges[0]['lft'] - 1;
            $right = $ranges[0]['rgt'] + 1;

            $query->bind(':left', $left, ParameterType::INTEGER)
                ->bind(':right', $right, ParameterType::INTEGER);

            DB::set($query);

            $results = DB::arrays('id');

            if ($results and (count($results) > 1 or !$programID)) {
                return $results;
            }
        }

        if ($programID and $ranges = Programs::rows($programID)) {

            $query->bind(':left', $ranges[0]['lft'], ParameterType::INTEGER)
                ->bind(':right', $ranges[0]['rgt'], ParameterType::INTEGER);

            DB::set($query);

            if ($results = DB::arrays('id')) {
                return $results;
            }
        }

        return [];
    }

    /** @inheritDoc */
    public static function subordinate(stdClass $resource, int $organizationID, int $parentID, int $programCID): bool
    {
        $HISinOneID = (int) $resource->ElementId ?? 0;
        $nameDE     = $resource->Titel->de ?? '';
        if (!$HISinOneID or !$nameDE) {
            Application::message('HIO_STRUCTURE_INVALID', Application::ERROR);
            return false;
        }

        $pool         = self::resolve($HISinOneID, $nameDE, $programCID);
        $subordinates = $resource->children->child ?? [];

        $pool->HISinOneID  = $HISinOneID;
        $pool->fullName_de = $nameDE;
        $pool->fullName_en = $resource->Titel->en ?? $nameDE;
        $pool->expiration  = $resource->Gueltig_bis ?? date('Y-m-d', strtotime('+50 years'));
        $pool->maxCrP      = (int) $resource->CreditPoints ?? 0;

        if (!$pool->store()) {
            return false;
        }

        self::associate($organizationID, $pool->id);
        $curriculumID = self::insert($parentID, $pool->id);

        return self::processCollection($subordinates, $organizationID, $curriculumID, $programCID);
    }

    /**
     * Resolves identifiers to a table entry if possible.
     *
     * @param int    $HISinOneID the HISinOne 'ElementId' allows for targeted identification directly
     * @param string $name       the pool may allow for identification in the context of the parent's curriculum table ID
     * @param int    $programID  the relevant program's id in the curricula table
     * @return Table
     */
    private static function resolve(int $HISinOneID, string $name, int $programID): Table
    {
        $table = new Table();
        if ($table->load(['HISinOneID' => $HISinOneID])) {
            return $table;
        }

        $parent = Curricula::row($programID);

        $query = DB::query();
        $query->select(DB::qn('p.id'))
            ->from(DB::qn('#__organizer_pools', 'p'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'c'), DB::qc('c.poolID', 'p.id'))
            ->where(DB::qcs([['p.fullName_de', $name, '=', true], ['c.lft', $parent['lft'], '>'], ['c.rgt', $parent['rgt'], '<']]));
        DB::set($query);

        if ($poolID = DB::integer()) {
            $table->load($poolID);
        }

        return $table;
    }
}
