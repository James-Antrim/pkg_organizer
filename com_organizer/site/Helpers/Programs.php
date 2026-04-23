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

    // Default values that should not appear in names.
    public const FULLTIME = 1, ON_CAMPUS = 1;

    public const DEFAULT_ATTENDANCE = 'P', DEFAULT_FOCUS = '-', DEFAULT_FORM = 'V', DEFAULT_MINOR = '-';

    protected static string $resource = 'program';

    public const UNVERSIONED = 1996;

    /**
     * Retrieves the id of the degree associated with the program.
     *
     * @param int $programID
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

    public static function fullName(stdClass $program): string
    {
        $fullName = $program->name;

        $attRelevant  = ($program->aTypeID and $program->aTypeID != self::ON_CAMPUS);
        $formRelevant = ($program->formID and $program->formID != self::FULLTIME);
        if ($attRelevant or $formRelevant or $program->degree or $program->year) {
            $parantheticals   = [$program->degree, $program->year];
            $parantheticals[] = $attRelevant ? $program->attendanceType : null;
            if ($formRelevant) {
                // Configured values are redundantly differentiated by the type attribute
                if (str_contains(strtolower($program->form), 'dual')) {
                    $parantheticals[] = preg_replace('/ \([^)]+\)/', '', $program->form);
                }
                else {
                    $parantheticals[] = $program->form;
                }
            }
            $parantheticals = array_filter($parantheticals);
            $fullName       .= ' (' . implode(', ', $parantheticals) . ')';
        }

        if ($program->minor or $program->focus) {
            $specifics = [$program->minor, $program->focus];
            $specifics = array_filter($specifics);
            $fullName  .= ', ' . implode(', ', $specifics);
        }

        return $fullName;
    }

    /**
     * Gets the programIDs for the given resource
     *
     * @param mixed $identifiers int resourceID | array ranges of subordinate resources
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
     * @param int $programID
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

    /** @inheritDoc */
    public static function name(int $resourceID): string
    {
        if (!$resourceID) {
            return Text::_('NO_PROGRAM');
        }

        $query = self::query();
        $query->where(DB::qc('p.id', $resourceID));

        DB::set($query);

        return self::fullName(DB::object());
    }

    /**
     * Gets the academic level of the program. (Bachelor|Master)
     *
     * @param int $programID the id of the program
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
     * @param string $access any access restriction which should be performed
     */
    public static function options(string $access = ''): array
    {
        $options = [];
        foreach (self::resources($access) as $program) {
            if ($program->active) {
                $options[] = HTML::option($program->id, self::fullName($program));
            }
        }

        return $options;
    }

    /**
     * @inheritDoc
     *
     * @param string $access any access restriction which should be performed
     */
    public static function resources(string $access = ''): array
    {
        $query = self::query();
        $query->innerJoin(DB::qn('#__organizer_curricula', 'cr'), DB::qc('cr.programID', 'p.id'));

        self::filterByAccess($query, 'p', $access);
        self::filterByOrganizations($query, 'p', Input::resourceIDs('organizationID'));

        if (self::useCurrent()) {
            $tag = Application::tag();

            $conditions = DB::qcs([
                ["grouped.name_$tag", "n.name_$tag"],
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

        return DB::objects('id');
    }

    /**
     * Creates a basic query for program related items.
     * @return DatabaseQuery
     */
    public static function query(): DatabaseQuery
    {
        $query = DB::query();
        $tag   = Application::tag();
        $url   = 'index.php?option=com_organizer&view=program&id=';

        $distinct = ['DISTINCT' . DB::qn('p.id')];
        $aliased  = DB::qn(
            ["at.name_$tag", "c.name_$tag", 'd.abbreviation', "fc.name_$tag", "fq.name_$tag", "m.name_$tag", "n.name_$tag", 'p.accredited', "pf.name_$tag", "pt.name_$tag"],
            ['attendanceType', 'campus', 'degree', 'focus', 'frequency', 'minor', 'name', 'year', 'form', 'type']
        );
        $selected = DB::qn([
            'p.active', 'aTypeID', 'campusID', 'degreeID', 'fee', 'focusID', 'formID', 'frequencyID', 'minorID', 'nc',
            'nomenID', 'special', 'p.typeID'
        ]);

        $url = [$query->concatenate([DB::quote($url), DB::qn('p.id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge($distinct, $selected, $aliased, $url))
            ->from(DB::qn('#__organizer_programs', 'p'))
            ->leftJoin(DB::qn('#__organizer_attendance_types', 'at'), DB::qn('at.id') . ' = ' . DB::qn('p.aTypeID'))
            ->leftJoin(DB::qn('#__organizer_campuses', 'c'), DB::qn('c.id') . ' = ' . DB::qn('p.campusID'))
            ->innerJoin(DB::qn('#__organizer_degrees', 'd'), DB::qn('d.id') . ' = ' . DB::qn('p.degreeID'))
            ->leftJoin(DB::qn('#__organizer_foci', 'fc'), DB::qn('fc.id') . ' = ' . DB::qn('p.focusID'))
            ->leftJoin(DB::qn('#__organizer_frequencies', 'fq'), DB::qn('fq.id') . ' = ' . DB::qn('p.frequencyID'))
            ->leftJoin(DB::qn('#__organizer_program_forms', 'pf'), DB::qn('pf.id') . ' = ' . DB::qn('p.formID'))
            ->leftJoin(DB::qn('#__organizer_program_types', 'pt'), DB::qn('pt.id') . ' = ' . DB::qn('p.typeID'))
            ->leftJoin(DB::qn('#__organizer_minors', 'm'), DB::qn('m.id') . ' = ' . DB::qn('p.minorID'))
            ->innerJoin(DB::qn('#__organizer_nomina', 'n'), DB::qn('n.id') . ' = ' . DB::qn('p.nomenID'))
            ->innerJoin(DB::qn('#__organizer_program_types', 't'), DB::qn('t.id') . ' = ' . DB::qn('p.typeID'))
            ->order(DB::qn(['name', 'degree', 'year', 'minor', 'focus', 'campus', 'attendanceType', 'form']));

        return $query;
    }

    /** @inheritDoc */
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
     * @param array  $range     the program curriculum range
     * @param array  $parentIDs the selected parents
     * @param string $type      the resource type of the form
     *
     * @return null|stdClass
     */
    public static function option(array $range, array $parentIDs, string $type): null|stdClass
    {
        $query = self::query();
        $query->where(DB::qn('p.id') . ' = :programID')->bind(':programID', $range['programID'], ParameterType::INTEGER);
        DB::set($query);

        if ($program = DB::array()) {
            $option           = HTML::option($range['id'], $program['fullName']);
            $option->disable  = $type !== 'pool' ? 'disabled' : '';
            $option->selected = in_array($range['id'], $parentIDs) ? 'selected' : '';
            return $option;
        }

        return null;
    }

    /**
     * Retrieves the organizationIDs associated with the program
     *
     * @param int  $programID the table id for the program
     * @param bool $short     whether to display an abbreviated version of fhe organization name
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

    /** @inheritDoc */
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
        $selectedIDs = Input::selectedIDs();
        $useCurrent  = false;

        if (Input::view() === 'participant_edit') {
            $participantID = empty($selectedIDs) ? User::id() : $selectedIDs[0];
            $table         = new Participants();

            if (!$table->load($participantID)) {
                $useCurrent = true;
            }
        }

        return $useCurrent;
    }
}
