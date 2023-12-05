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
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input, Text};
use THM\Organizer\Models;
use THM\Organizer\Tables\{Participants, Programs as Table};

/**
 * Provides general functions for program access checks, data retrieval and display.
 */
class Programs extends Curricula implements Selectable
{
    use Active;

    protected static string $resource = 'program';

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
        $programTable = new Table();
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
     * @inheritDoc
     */
    public static function documentable(string $resource = 'program'): array
    {
        return parent::documentable($resource);
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
        if (!$ranges = self::ranges($identifiers)) {
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
     * @inheritDoc
     */
    public static function getName(int $resourceID): string
    {
        if (!$resourceID) {
            return Text::_('NO_PROGRAM');
        }

        $query = DB::getQuery();
        $tag   = Application::getTag();

        $parts = [DB::qn("p.name_$tag"), "' ('", DB::qn('d.abbreviation'), "' '", DB::qn('p.accredited'), "')'"];
        $query->select($query->concatenate($parts, "") . ' AS ' . DB::qn('name'))
            ->from(DB::qn('#__organizer_programs', 'p'))
            ->innerJoin(DB::qn('#__organizer_degrees', 'd'), DB::qc('d.id', 'p.degreeID'))
            ->where(DB::qn('p.id') . ' = :programID')
            ->bind(':programID', $resourceID, ParameterType::INTEGER);

        DB::setQuery($query);

        return DB::loadString();
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
        return Degrees::getLevel(self::degreeID($programID));
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
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function getResources(string $access = ''): array
    {
        $query = self::query();
        $query->select(DB::qn('d.abbreviation', 'degree'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'c'), DB::qc('c.programID', 'p.id'))
            ->order(DB::qn('name'));

        if ($access) {
            self::filterAccess($query, $access, 'program', 'p');
        }

        self::filterOrganizations($query, 'program', 'p');

        if (self::useCurrent()) {
            $tag = Application::getTag();

            $conditions = DB::qcs([
                ["grouped.name_$tag", "p.name_$tag"],
                ['grouped.degreeID', 'p.degreeID'],
                ['grouped.accredited', 'p.accredited'],
            ]);

            $p2id   = DB::qn('p2.degreeID');
            $p2Name = DB::qn("p2.name_$tag");
            $select = [$p2Name, $p2id, 'MAX(' . DB::qn('p2.accredited') . ') AS ' . DB::qn('accredited')];

            $join = DB::getQuery()->select($select)->from(DB::qn('#__organizer_programs', 'p2'))->group([$p2Name, 'p2.degreeID']);

            $query->innerJoin("($join) AS " . DB::qn('grouped'), $conditions);
        }

        DB::setQuery($query);

        return DB::loadAssocList('id');
    }

    /**
     * Creates a basic query for program related items.
     * @return DatabaseQuery
     */
    public static function query(): DatabaseQuery
    {
        $query = DB::getQuery();
        $tag   = Application::getTag();
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
    public static function ranges(array|int $identifiers): array
    {
        if (!$identifiers or $identifiers === self::NONE) {
            return [];
        }

        $programID = DB::qn('programID');
        $query     = DB::getQuery();
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

        DB::setQuery($query);

        return DB::loadAssocList();
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
    public static function option(array $range, array $parentIDs, string $type): string
    {
        $query = self::query();
        $query->where(DB::qn('p.id') . ' = :programID')->bind(':programID', $range['programID'], ParameterType::INTEGER);
        DB::setQuery($query);

        if (!$program = DB::loadAssoc()) {
            return '';
        }

        $selected = in_array($range['id'], $parentIDs) ? 'selected' : '';
        $disabled = $type === 'pool' ? '' : 'disabled';

        return "<option value='{$range['id']}' $selected $disabled>{$program['name']}</option>";
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

        return $short ? Organizations::getShortName($organizationIDs[0]) : Organizations::getName($organizationIDs[0]);
    }

    /**
     * @inheritDoc
     */
    public static function programs(array|int $identifiers): array
    {
        $ranges = [];
        foreach ($identifiers as $programID) {
            $ranges = array_merge($ranges, self::ranges($programID));
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
            $table         = new Participants();

            if (!$table->load($participantID)) {
                $useCurrent = true;
            }
        }

        return $useCurrent;
    }
}
