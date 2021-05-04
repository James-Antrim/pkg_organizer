<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use JDatabaseQuery;
use Organizer\Adapters\Database;
use Organizer\Models;
use Organizer\Tables;

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
     * @return mixed int on success, otherwise null
     */
    public static function create(array $programData, string $initialName, int $categoryID)
    {
        $programTable = new Tables\Programs();
        if ($programTable->load($programData)) {
            return $programTable->id;
        }

        if (empty($initialName)) {
            return null;
        }

        $departmentID                  = Input::getInt('departmentID');
        $programData['organizationID'] = Input::getInt('organizationID', $departmentID);
        $programData['name_de']        = $initialName;
        $programData['name_en']        = $initialName;
        $programData['categoryID']     = $categoryID;

        $model     = new Models\Program();
        $programID = $model->save($programData);

        return empty($programID) ? null : $programID;
    }

    /**
     * Gets a HTML option based upon a program curriculum association
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
        Database::setQuery($query);

        if (!$program = Database::loadAssoc()) {
            return '';
        }

        $selected = in_array($range['id'], $parentIDs) ? 'selected' : '';
        $disabled = $type === 'pool' ? '' : 'disabled';

        return "<option value='{$range['id']}' $selected $disabled>{$program['name']}</option>";
    }

    /**
     * Gets the programIDs for the given resource
     *
     * @param   mixed  $identifiers  int resourceID | array ranges of subordinate resources
     *
     * @return array the program ids
     */
    public static function getIDs($identifiers): array
    {
        if (!$ranges = self::getRanges($identifiers)) {
            return [];
        }

        $ids = [];
        foreach ($ranges as $range) {
            $ids[] = $range['programID'];
        }

        $ids = array_unique($ids);
        sort($ids);

        return $ids;
    }

    /**
     * @inheritDoc
     */
    public static function getName(int $resourceID)
    {
        if (!$resourceID) {
            return Languages::_('ORGANIZER_NO_PROGRAM');
        }

        $query = Database::getQuery(true);
        $tag   = Languages::getTag();
        $parts = ["p.name_$tag", "' ('", 'd.abbreviation', "' '", 'p.accredited', "')'"];
        $query->select($query->concatenate($parts, "") . ' AS name')
            ->from('#__organizer_programs AS p')
            ->innerJoin('#__organizer_degrees AS d ON d.id = p.degreeID')
            ->where("p.id = '$resourceID'");

        Database::setQuery($query);

        return Database::loadString();
    }

    /**
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function getOptions($access = ''): array
    {
        $options = [];
        foreach (self::getResources($access) as $program) {
            if ($program['active']) {
                $options[] = HTML::_('select.option', $program['id'], $program['name']);
            }
        }

        return $options;
    }

    /**
     * Retrieves the organizationIDs associated with the program
     *
     * @param   int   $programID  the table id for the program
     * @param   bool  $short      whether or not to display an abbreviated version of fhe organization name
     *
     * @return string the organization associated with the program's documentation
     */
    public static function getOrganization(int $programID, $short = false): string
    {
        if (!$organizationIDs = self::getOrganizationIDs($programID)) {
            return Languages::_('ORGANIZER_NO_ORGANIZATION');
        }

        if (count($organizationIDs) > 1) {
            return Languages::_('ORGANIZER_MULTIPLE_ORGANIZATIONS');
        }

        return $short ? Organizations::getShortName($organizationIDs[0]) : Organizations::getName($organizationIDs[0]);
    }

    /**
     * Creates a basic query for program related items.
     *
     * @return JDatabaseQuery
     */
    public static function getQuery(): JDatabaseQuery
    {
        $query      = Database::getQuery();
        $tag        = Languages::getTag();
        $parts      = ["p.name_$tag", "' ('", 'd.abbreviation', "', '", 'p.accredited', "')'"];
        $nameClause = $query->concatenate($parts, '') . ' AS name';
        $query->select("DISTINCT p.id AS id, $nameClause, p.active")
            ->from('#__organizer_programs AS p')
            ->innerJoin('#__organizer_degrees AS d ON d.id = p.degreeID');

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

        $query = Database::getQuery();
        $query->select('DISTINCT *')
            ->from('#__organizer_curricula')
            ->where('programID IS NOT NULL ')
            ->order('lft');

        if (is_array($identifiers)) {
            self::filterSuperOrdinate($query, $identifiers);
        } else {
            $programID = (int)$identifiers;
            if ($identifiers != self::NONE) {
                $query->where("programID = $programID");
            }
        }

        Database::setQuery($query);

        return Database::loadAssocList();
    }

    /**
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function getResources($access = ''): array
    {
        $query = self::getQuery();
        $tag   = Languages::getTag();
        $query->select("d.abbreviation AS degree")
            ->innerJoin('#__organizer_curricula AS c ON c.programID = p.id')
            ->order('name');

        if ($access) {
            self::addAccessFilter($query, $access, 'program', 'p');
        }

        self::addOrganizationFilter($query, 'program', 'p');

        if (self::useCurrent()) {
            $subQuery = Database::getQuery();
            $subQuery->select("p2.name_$tag, p2.degreeID, MAX(p2.accredited) AS accredited")
                ->from('#__organizer_programs AS p2')
                ->group("p2.name_$tag, p2.degreeID");
            $conditions = "grouped.name_$tag = p.name_$tag ";
            $conditions .= "AND grouped.degreeID = p.degreeID ";
            $conditions .= "AND grouped.accredited = p.accredited ";
            $query->innerJoin("($subQuery) AS grouped ON $conditions");
        }

        Database::setQuery($query);

        return Database::loadAssocList('id');
    }

    /**
     * @inheritDoc
     */
    public static function getPrograms($identifiers): array
    {
        $ranges = [];
        foreach ($identifiers as $programID) {
            $ranges = array_merge($ranges, self::getRanges($programID));
        }

        return $ranges;
    }

    /**
     * Determines whether only the latest accreditation version of a program should be displayed in the list.
     *
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
