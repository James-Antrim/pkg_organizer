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
use THM\Organizer\Tables\{Associations, Curricula as CTable, Organizations as OTable, Participants, Programs as Table};
use THM\Organizer\Helpers\Programs as Helper;

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
        $program = new Table();

        if ($program->load($programID)) {
            return $program->degreeID ?: 0;
        }

        return 0;
    }

    /**
     * Filters overhead from the curriculum.
     *
     * @param stdClass $program the program wrapper object
     * @return array|false
     */
    public static function filterCurriculum(stdClass $program): object|false
    {
        return $program->PO ?? false;
    }

    /**
     * Filters overhead from the subordinates.
     *
     * @param stdClass $program the program wrapper object
     * @return array|false
     */
    public static function filterSubordinates(stdClass $program): array|false
    {
        if (empty($program->children) or empty($program->children->child)) {
            return false;
        }

        $innerWrapper = $program->children->child;

        if (is_array($innerWrapper)) {
            Application::message('Non-standard standard structure at level PO + 1', Application::NOTICE);
            $innerWrapper = array_filter($innerWrapper);
            $innerWrapper = array_pop($innerWrapper);
            if (!is_object($innerWrapper)) {
                Application::message('Deviant value at level PO + 1', Application::ERROR);
                return false;
            }
        }

        if (empty($innerWrapper->children) or empty($innerWrapper->children->child)) {
            return false;
        }

        $curriculum = $innerWrapper->children->child;

        if (is_array($curriculum)) {
            Application::message('Non-standard standard structure at level PO + 2', Application::NOTICE);
            $curriculum = array_filter($curriculum);
            $curriculum = array_pop($curriculum);
            if (!is_object($curriculum)) {
                Application::message('Deviant value at level PO + 2', Application::ERROR);
                return false;
            }
        }

        if (empty($curriculum->children) or empty($curriculum->children->child)) {
            return false;
        }

        $subordinates = $curriculum->children->child;

        if (!is_array($subordinates)) {
            Application::message('Deviant value at level PO + 3', Application::ERROR);
            return false;
        }

        return $subordinates;
    }

    /**
     * Filters overhead out of the response.
     *
     * @param stdClass $response the response object
     * @return array|false|stdClass
     */
    public static function filterPrograms(stdClass $response): array|false|stdClass
    {
        if (empty($response->courseOfStudiesWithStructure_out)) {
            return false;
        }

        if (empty($response->courseOfStudiesWithStructure_out->courseOfStudies)) {
            return false;
        }

        if (empty($response->courseOfStudiesWithStructure_out->courseOfStudies->courseOfStudy)) {
            return false;
        }

        return $response->courseOfStudiesWithStructure_out->courseOfStudies->courseOfStudy;
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

    /**
     * Retrieves the HISinOne system id of the program.
     *
     * @param int $programID
     *
     * @return int
     */
    public static function HISinOneID(int $programID): int
    {
        $program = new Table();
        if ($program->load($programID)) {
            return $program->HISinOneID ?: 0;
        }

        return 0;
    }

    /**
     * Retrieves program information relevant for soap queries to the HIO system.
     *
     * @param int $programID the id of the degree program
     *
     * @return string
     */
    public static function HISinOneKey(int $programID): string
    {
        $h     = ["'H' as `h`"];
        $part1 = DB::qn(['d.code', 'n.code', 'm.code', 'f.code'], ['degree', 'program', 'minor', 'focus']);
        $part2 = DB::qn(
            ['p.accredited', 'c.code', 'at.code', 'pf.code', 'pt.code'],
            ['year', 'campus', 'attendance', 'form', 'type']);
        $query = DB::query();
        $query->select(array_merge($part1, $h, $part2))
            ->from(DB::qn('#__organizer_programs', 'p'))
            ->innerJoin(DB::qn('#__organizer_attendance_types', 'at'), DB::qc('at.id', 'p.aTypeID'))
            ->innerJoin(DB::qn('#__organizer_degrees', 'd'), DB::qc('d.id', 'p.degreeID'))
            ->innerJoin(DB::qn('#__organizer_nomina', 'n'), DB::qc('n.id', 'p.nomenID'))
            ->leftJoin(DB::qn('#__organizer_campuses', 'c'), DB::qc('c.id', 'p.campusID'))
            ->leftJoin(DB::qn('#__organizer_foci', 'f'), DB::qc('f.id', 'p.focusID'))
            ->leftJoin(DB::qn('#__organizer_minors', 'm'), DB::qc('m.id', 'p.minorID'))
            ->leftJoin(DB::qn('#__organizer_program_forms', 'pf'), DB::qc('pf.id', 'p.formID'))
            ->leftJoin(DB::qn('#__organizer_program_types', 'pt'), DB::qc('pt.id', 'p.typeID'))
            ->where(DB::qc('p.id', $programID));
        DB::set($query);

        if (!$identifiers = DB::array()) {
            return '';
        }

        if (empty($identifiers['attendance'])) {
            $identifiers['attendance'] = Helper::DEFAULT_ATTENDANCE;
        }

        if (empty($identifiers['focus'])) {
            $identifiers['focus'] = Helper::DEFAULT_FOCUS;
        }

        if (empty($identifiers['form'])) {
            $identifiers['form'] = Helper::DEFAULT_FORM;
        }

        if (empty($identifiers['minor'])) {
            $identifiers['minor'] = Helper::DEFAULT_MINOR;
        }

        return implode('|', $identifiers) . '|';
    }

    /**
     * Resolves the program 'UniqueName' to its constituent identifiers.
     * @param string $identifiers the abbreviated collection of attribute values used to uniquely identify the program
     * @param object $program     the program data to be imported
     * @return array
     */
    private static function identifiers(string $identifiers, object $program): array
    {
        $identifiers = explode('|', $identifiers);
        // Index 4 is always 'H'; Index 10 is always empty
        unset($identifiers[4], $identifiers[10]);
        // Directly assigning to variables requires sequential keys.
        $identifiers = array_values($identifiers);
        [$degree, $nomen, $minor, $focus, $accredited, $campus, $attendance, $form, $type] = $identifiers;
        $identifiers = [
            'accredited' => preg_match('/\d{4}/', $accredited) ? $accredited : Helper::UNVERSIONED,
            'aTypeID' => AttendanceTypes::code($attendance, $program->Studienart->Name),
            'campusID' => Campuses::code($campus, $program->Studienort->Name),
            'degreeID' => Degrees::code($degree, $program->Abschluss->Name),
            'formID' => ProgramForms::code($form, $program->Studientyp->Name),
            'nomenID' => Nomina::code($nomen, $program->Fach->Name),
            'typeID' => ProgramTypes::code($type, $program->Studienform->Name),
        ];

        if ($focus !== Helper::DEFAULT_FOCUS) {
            $identifiers['focusID'] = Foci::code($focus, $program->Zusatz->Name);
        }

        if ($minor !== Helper::DEFAULT_MINOR) {
            $identifiers['minorID'] = Minors::code($minor, $program->Vertiefung->Name);
        }

        foreach ($identifiers as $identifier) {
            if (empty($identifier)) {
                return [];
            }
        }

        return $identifiers;
    }

    /**
     * Imports a single program resource response from HISinOne.
     * @param stdClass $program
     * @return bool
     */
    public static function importSingle(stdClass $program): bool
    {
        $HISinOneID  = $program->CoSId ?? null;
        $identifiers = $program->Uniquename ?? null;
        $oCode       = $program->OrgUnit->Uniquename ?? null;

        if (!$HISinOneID or !$identifiers or !$oCode) {
            Application::message('HIO_STRUCTURE_INVALID', Application::ERROR);
            return false;
        }

        $HISUpdate = ['HISinOneID' => $HISinOneID];
        if (!$identifiers = self::identifiers($identifiers, $program)) {
            Application::message('HIO_RESOURCE_MISSING', Application::ERROR);
            return false;
        }

        $table = new Table();

        // Existent migrated/HIS entry overwrite identifiers to save time against individual checks
        if ($table->load($HISUpdate)) {
            $table->save($identifiers);
        } // Existent non-migrated entry add HISinOneID
        elseif ($table->load($identifiers)) {
            $table->save($HISUpdate);
        } // Fresh "program"
        else {
            $identifiers['HISinOneID'] = $HISinOneID;
            if (!$table->save($identifiers)) {
                Application::message('ORGANIZER_NOT_SAVED', Application::ERROR);
                return false;
            }
        }

        $programID = $table->id;

        // IT-Services decided to use inofficial abbreviations, so several need resolution.
        if (!empty(Organizations::HISinOneResolution[$oCode])) {
            $oCode = Organizations::HISinOneResolution[$oCode];
        }

        $organization = new OTable();
        if ($organization->load(['abbreviation_de' => $oCode])) {
            $association    = new Associations();
            $organizationID = $organization->id;
            $references     = ['organizationID' => $organizationID, 'programID' => $programID];

            if (!$association->load($references)) {
                $association->save($references);
            }
        }
        else {
            Application::message(Text::sprintf('PROGRAM_ORGANIZATION_UNKNOWN', $programID, $oCode), Application::WARNING);
            return true;
        }

        if (!$ranges = self::rows($programID) or empty($ranges[0])) {
            $range        = ['parentID' => null, 'programID' => $programID, 'ordering' => 0];
            $curriculumID = self::addRange($range);
        }
        else {
            $curriculumID = $ranges[0]['id'];
        }

        if ($program = self::filterCurriculum($program)) {
            $table->expiration = $program->Gueltig_bis;
            $table->store();

            if ($curriculum = self::filterSubordinates($program)) {
                return self::processCollection($curriculum, $organizationID, $curriculumID, $curriculumID);
            }
        }

        return true;
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
