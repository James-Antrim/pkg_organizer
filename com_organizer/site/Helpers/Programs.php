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

use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input, Text};
use THM\Organizer\Models;
use THM\Organizer\Tables;

/**
 * Provides general functions for program access checks, data retrieval and display.
 */
class Programs extends Curricula implements Selectable
{
    protected static $resource = 'program';

    /**
     * Checks if a program exists matching the identification keys. If none exist one is created.
     *
     * @param   array   $programData  the program data
     * @param   string  $initialName  the name to be used if no entry already exists
     * @param   int     $categoryID   the id of the category calling this function
     *
     * @return int int the created program's id on success, otherwise 0
     */
    public static function create(array $programData, string $initialName, int $categoryID): int
    {
        $programTable = new Tables\Programs();
        if ($programTable->load($programData)) {
            return $programTable->id;
        }

        if (empty($initialName)) {
            return 0;
        }

        $programData['organizationID'] = Input::getInt('organizationID');
        $programData['name_de']        = $initialName;
        $programData['name_en']        = $initialName;
        $programData['categoryID']     = $categoryID;

        $model     = new Models\Program();
        $programID = $model->save($programData);

        return empty($programID) ? 0 : $programID;
    }

    /**
     * Gets an HTML option based upon a program curriculum association
     *
     * @param   array   $range      the program curriculum range
     * @param   array   $parentIDs  the selected parents
     * @param   string  $type       the resource type of the form
     *
     * @return string  HTML option
     */
    public static function getCurricularOption(array $range, array $parentIDs, string $type): string
    {
        $query = self::getQuery();
        $query->where("p.id = {$range['programID']}");
        DB::setQuery($query);

        if (!$program = DB::loadAssoc()) {
            return '';
        }

        $selected = in_array($range['id'], $parentIDs) ? 'selected' : '';
        $disabled = $type === 'pool' ? '' : 'disabled';

        return "<option value='{$range['id']}' $selected $disabled>{$program['name']}</option>";
    }

    /**
     * Retrieves the id of the degree associated with the program.
     *
     * @param   int  $programID
     *
     * @return int
     */
    public static function getDegreeID(int $programID): int
    {
        $degreeID = 0;
        $program  = new Tables\Programs();

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
    public static function getIDs(array|int $identifiers): array
    {
        if (!$ranges = self::getRanges($identifiers)) {
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
     * Gets the academic level of the program. (Bachelor|Master)
     *
     * @param   int  $programID  the id of the program
     *
     * @return string
     */
    public static function getLevel(int $programID): string
    {
        return Degrees::getLevel(self::getDegreeID($programID));
    }

    /**
     * @inheritDoc
     */
    public static function getName(int $resourceID): string
    {
        if (!$resourceID) {
            return Text::_('ORGANIZER_NO_PROGRAM');
        }

        $query = DB::getQuery();
        $tag   = Application::getTag();
        $parts = ["p.name_$tag", "' ('", 'd.abbreviation', "' '", 'p.accredited', "')'"];
        $query->select($query->concatenate($parts, "") . ' AS name')
            ->from('#__organizer_programs AS p')
            ->innerJoin('#__organizer_degrees AS d ON d.id = p.degreeID')
            ->where("p.id = $resourceID");

        DB::setQuery($query);

        return DB::loadString();
    }

    /**
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function getOptions(string $access = ''): array
    {
        $options = [];
        foreach (self::getResources($access) as $program) {
            if ($program['active']) {
                $options[] = HTML::option($program['id'], $program['name']);
            }
        }

        return $options;
    }

    /**
     * Retrieves the organizationIDs associated with the program
     *
     * @param   int   $programID  the table id for the program
     * @param   bool  $short      whether to display an abbreviated version of fhe organization name
     *
     * @return string the organization associated with the program's documentation
     */
    public static function getOrganization(int $programID, bool $short = false): string
    {
        if (!$organizationIDs = self::getOrganizationIDs($programID)) {
            return Text::_('ORGANIZER_NO_ORGANIZATION');
        }

        if (count($organizationIDs) > 1) {
            return Text::_('ORGANIZER_MULTIPLE_ORGANIZATIONS');
        }

        return $short ? Organizations::getShortName($organizationIDs[0]) : Organizations::getName($organizationIDs[0]);
    }

    /**
     * Creates a basic query for program related items.
     * @return DatabaseQuery
     */
    public static function getQuery(): DatabaseQuery
    {
        $tag   = Application::getTag();
        $start = [DB::qn("p.name_$tag"), "' ('", DB::qn('d.abbreviation')];
        $end   = self::useCurrent() ? ["')'"] : ["', '", DB::qn('p.accredited'), "')'"];
        $parts = array_merge($start, $end);

        $query  = DB::getQuery();
        $select = [
            'DISTINCT ' . DB::qn('p.id', 'id'),
            $query->concatenate($parts, '') . ' AS ' . DB::qn('name'),
            DB::qn('p.active')
        ];
        $query->select($select)
            ->from(DB::qn('#__organizer_programs', 'p'))
            ->innerJoin(DB::qn('#__organizer_degrees', 'd'), DB::qn('d.id') . ' = ' . DB::qn('p.degreeID'));

        return $query;
    }

    /**
     * @inheritDoc
     */
    public static function getRanges($identifiers): array
    {
        if (!$identifiers or (!is_numeric($identifiers) and !is_array($identifiers))) {
            return [];
        }

        $query = DB::getQuery();
        $query->select('DISTINCT *')
            ->from('#__organizer_curricula')
            ->where('programID IS NOT NULL ')
            ->order('lft');

        if (is_array($identifiers)) {
            self::filterSuperOrdinate($query, $identifiers);
        }
        else {
            $programID = (int) $identifiers;
            if ($identifiers != self::NONE) {
                $query->where("programID = $programID");
            }
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
        $query = self::getQuery();
        $query->select(DB::qn('d.abbreviation', 'degree'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'c'), DB::qn('c.programID') . ' = ' . DB::qn('p.id'))
            ->order(DB::qn('name'));

        if ($access) {
            self::filterAccess($query, $access, 'program', 'p');
        }

        self::filterOrganizations($query, 'program', 'p');

        if (self::useCurrent()) {
            $tag = Application::getTag();

            $conditions = [
                DB::qn("grouped.name_$tag") . ' = ' . DB::qn("p.name_$tag"),
                DB::qn('grouped.degreeID') . ' = ' . DB::qn('p.degreeID'),
                DB::qn('grouped.accredited') . ' = ' . DB::qn('p.accredited')
            ];

            $p2id   = DB::qn('p2.degreeID');
            $p2Name = DB::qn("p2.name_$tag");
            $select = [$p2Name, $p2id, 'MAX(' . DB::qn('p2.accredited') . ') AS ' . DB::qn('accredited')];

            $join = DB::getQuery()->select($select)->from(DB::qn('#__organizer_programs', 'p2'))->group([$p2Name, 'p2.degreeID']);

            $query->innerJoin("($join) AS " . DB::qn('grouped'), $conditions);
        }
        echo "<pre>" . print_r((string) $query, true) . "</pre>";

        DB::setQuery($query);

        return DB::loadAssocList('id');
    }

    /**
     * @inheritDoc
     */
    public static function getPrograms(array|int $identifiers): array
    {
        $ranges = [];
        foreach ($identifiers as $programID) {
            $ranges = array_merge($ranges, self::getRanges($programID));
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
            $participantID = empty($selectedIDs) ? Users::getID() : $selectedIDs[0];
            $table         = new Tables\Participants();

            if (!$table->load($participantID)) {
                $useCurrent = true;
            }
        }

        return $useCurrent;
    }
}
