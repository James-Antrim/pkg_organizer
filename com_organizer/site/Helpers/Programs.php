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

use Joomla\Database\{DatabaseQuery, ParameterType};
use stdClass;
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input, Text, User};
use THM\Organizer\Tables\{Curricula as CTable, Participants, Programs as Table};

/**
 * Provides general functions for program access checks, data retrieval and display.
 */
class Programs extends Curricula implements Selectable
{
    use Active;

    protected static string $resource = 'program';

    /**
     * Retrieves the id of the degree associated with the program.
     *
     * @param   int  $programID
     *
     * @return int
     */
    public static function degreeID(int $programID): int
    {
        $degreeID = 0;
        $program  = new Table();

        if ($program->load($programID)) {
            $degreeID = $program->degreeID;
        }

        return $degreeID;
    }

    /**
     * Gets the programIDs for the given resource
     *
     * @param   mixed  $identifiers  int resourceID | array ranges of subordinate resources
     *
     * @return int[] the program ids
     */
    public static function extractIDs(array|int $identifiers): array
    {
        if (!$ranges = self::rows($identifiers)) {
            return [];
        }

        $ids = [];
        foreach ($ranges as $range) {
            $ids[] = (int) $range['programID'];
        }

        $ids = array_unique($ids);
        sort($ids);

        return $ids;
    }

    /**
     * Checks whether the given program has configured subordinates.
     *
     * @param   int  $programID
     *
     * @return bool
     */
    public static function hasSubordinates(int $programID): bool
    {
        $curriculum = new CTable();

        if (!$curriculum->load(['programID' => $programID])) {
            return false;
        }

        return $curriculum->rgt - $curriculum->lft > 1;
    }

    /**
     * @inheritDoc
     */
    public static function name(int $resourceID): string
    {
        if (!$resourceID) {
            return Text::_('NO_PROGRAM');
        }

        $query = DB::query();
        $tag   = Application::tag();

        $parts = [DB::qn("p.name_$tag"), "' ('", DB::qn('d.abbreviation'), "' '", DB::qn('p.accredited'), "')'"];
        $query->select($query->concatenate($parts, "") . ' AS ' . DB::qn('name'))
            ->from(DB::qn('#__organizer_programs', 'p'))
            ->innerJoin(DB::qn('#__organizer_degrees', 'd'), DB::qc('d.id', 'p.degreeID'))
            ->where(DB::qn('p.id') . ' = :programID')
            ->bind(':programID', $resourceID, ParameterType::INTEGER);

        DB::set($query);

        return DB::string();
    }

    /**
     * Gets the academic level of the program. (Bachelor|Master)
     *
     * @param   int  $programID  the id of the program
     *
     * @return string
     */
    public static function level(int $programID): string
    {
        return Degrees::level(self::degreeID($programID));
    }

    /**
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function options(string $access = ''): array
    {
        $options = [];
        foreach (self::resources($access) as $program) {
            if ($program['active']) {
                $options[] = HTML::option($program['id'], $program['name']);
            }
        }

        return $options;
    }

    /**
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function resources(string $access = ''): array
    {
        $query = self::query();
        $query->select(DB::qn('d.abbreviation', 'degree'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'c'), DB::qc('c.programID', 'p.id'))
            ->order(DB::qn('name'));

        self::filterByAccess($query, 'p', $access);
        self::filterByOrganizations($query, 'p', Input::resourceIDs('organizationID'));

        if (self::useCurrent()) {
            $tag = Application::tag();

            $conditions = DB::qcs([
                ["grouped.name_$tag", "p.name_$tag"],
                ['grouped.degreeID', 'p.degreeID'],
                ['grouped.accredited', 'p.accredited'],
            ]);

            $p2id   = DB::qn('p2.degreeID');
            $p2Name = DB::qn("p2.name_$tag");
            $select = [$p2Name, $p2id, 'MAX(' . DB::qn('p2.accredited') . ') AS ' . DB::qn('accredited')];

            $join = DB::query()->select($select)->from(DB::qn('#__organizer_programs', 'p2'))->group([$p2Name, 'p2.degreeID']);

            $query->innerJoin("($join) AS " . DB::qn('grouped'), $conditions);
        }

        DB::set($query);

        return DB::arrays('id');
    }

    /**
     * Creates a basic query for program related items.
     * @return DatabaseQuery
     */
    public static function query(): DatabaseQuery
    {
        $query = DB::query();
        $tag   = Application::tag();
        $url   = 'index.php?option=com_organizer&view=Program&id=';

        $start = [DB::qn("p.name_$tag"), "' ('", DB::qn('d.abbreviation')];
        $end   = self::useCurrent() ? ["')'"] : ["', '", DB::qn('p.accredited'), "')'"];
        $parts = array_merge($start, $end);
        $url   = [$query->concatenate([DB::quote($url), DB::qn('p.id')], '') . ' AS ' . DB::qn('url')];

        $select = [
            'DISTINCT ' . DB::qn('p.id', 'id'),
            $query->concatenate($parts, '') . ' AS ' . DB::qn('name'),
            DB::qn('p.active')
        ];
        $query->select(array_merge($select, $url))
            ->from(DB::qn('#__organizer_programs', 'p'))
            ->innerJoin(DB::qn('#__organizer_degrees', 'd'), DB::qn('d.id') . ' = ' . DB::qn('p.degreeID'));

        return $query;
    }

    /**
     * @inheritDoc
     */
    public static function rows(array|int $identifiers): array
    {
        if (!$identifiers or $identifiers === self::NONE) {
            return [];
        }

        $programID = DB::qn('programID');
        $query     = DB::query();
        $query->select('DISTINCT *')
            ->from(DB::qn('#__organizer_curricula'))
            ->where("$programID IS NOT NULL")
            ->order(DB::qn('lft'));

        if (is_array($identifiers)) {
            self::filterSuperOrdinate($query, $identifiers);
        }
        else {
            $query->where("$programID = :programID")->bind(':programID', $identifiers, ParameterType::INTEGER);
        }

        DB::set($query);

        return DB::arrays();
    }

    /**
     * Gets an option based upon a program curriculum association
     *
     * @param   array   $range      the program curriculum range
     * @param   array   $parentIDs  the selected parents
     * @param   string  $type       the resource type of the form
     *
     * @return null|stdClass
     */
    public static function option(array $range, array $parentIDs, string $type): null|stdClass
    {
        $query = self::query();
        $query->where(DB::qn('p.id') . ' = :programID')->bind(':programID', $range['programID'], ParameterType::INTEGER);
        DB::set($query);

        if ($program = DB::array()) {
            $option           = HTML::option($range['id'], $program['name']);
            $option->disable  = $type !== 'pool' ? 'disabled' : '';
            $option->selected = in_array($range['id'], $parentIDs) ? 'selected' : '';
            return $option;
        }

        return null;
    }

    /**
     * Retrieves the organizationIDs associated with the program
     *
     * @param   int   $programID  the table id for the program
     * @param   bool  $short      whether to display an abbreviated version of fhe organization name
     *
     * @return string the organization associated with the program's documentation
     */
    public static function organization(int $programID, bool $short = false): string
    {
        if (!$organizationIDs = self::organizationIDs($programID)) {
            return Text::_('NO_ORGANIZATION');
        }

        if (count($organizationIDs) > 1) {
            return Text::_('MULTIPLE_ORGANIZATIONS');
        }

        return $short ? Organizations::getShortName($organizationIDs[0]) : Organizations::name($organizationIDs[0]);
    }

    /**
     * @inheritDoc
     */
    public static function programs(array|int $identifiers): array
    {
        $ranges = [];
        foreach ($identifiers as $programID) {
            $ranges = array_merge($ranges, self::rows($programID));
        }

        return $ranges;
    }

    /**
     * Determines whether only the latest accreditation version of a program should be displayed in the list.
     * @return bool
     */
    private static function useCurrent(): bool
    {
        $selectedIDs = Input::getSelectedIDs();
        $useCurrent  = false;

        if (Input::getView() === 'participant_edit') {
            $participantID = empty($selectedIDs) ? User::id() : $selectedIDs[0];
            $table         = new Participants();

            if (!$table->load($participantID)) {
                $useCurrent = true;
            }
        }

        return $useCurrent;
    }
}
