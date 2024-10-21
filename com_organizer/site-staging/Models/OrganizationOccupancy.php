<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers\{Dates, Rooms};

/**
 * Class which calculates organization statistic data.
 */
class OrganizationOccupancy extends BaseModel
{
    private array $calendarData;

    public string $endDate;

    public $terms;

    public $rooms;

    public $roomtypes;

    public $roomtypeMap;

    public $startDate;

    public $useData;

    /**
     * Organization_Statistics constructor.
     *
     * @param   array  $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $format = Input::getCMD('format', 'html');

        switch ($format) {
            case 'xls':
                $this->setRoomTypes();
                $this->setRooms();

                $year            = Input::getCMD('year', date('Y'));
                $this->startDate = "$year-01-01";
                $this->endDate   = "$year-12-31";

                $this->setTerms($year);

                $this->calendarData = [];

                // the rooms property is restructured here for quicker access superfluous rooms are removed altogether
                foreach ($this->rooms as $roomName => $roomData) {
                    $booked = $this->setData($roomData['id']);
                    unset($this->rooms[$roomName]);

                    if ($booked) {
                        $this->rooms[$roomData['id']] = $roomName;
                    }
                    else {
                        unset($this->roomtypeMap[$roomData['id']]);
                    }
                }

                foreach (array_keys($this->roomtypes) as $rtID) {
                    if (!in_array($rtID, $this->roomtypeMap)) {
                        unset($this->roomtypes[$rtID]);
                    }
                }

                $this->createUseData();

                break;

            case 'html':
            default:
                $this->setRooms();
                $this->setRoomTypes();

                break;
        }
    }

    /**
     * Restructures the data for the organization usage statistics
     * @return void
     */
    private function createUseData()
    {
        $this->useData          = [];
        $this->useData['total'] = [];

        foreach ($this->terms as $term) {
            $termName                 = $term['name'];
            $this->useData[$termName] = [];

            $currentDate = max($term['startDate'], $this->startDate);
            $endDate     = min($this->endDate, $term['endDate']);

            while ($currentDate <= $endDate) {
                if (empty($this->calendarData[$currentDate])) {
                    continue;
                }

                foreach ($this->calendarData[$currentDate] as $times => $roomOrgs) {
                    [$startTime, $endTime] = explode('-', $times);
                    $minutes = round((strtotime($endTime) - strtotime($startTime)) / 60);

                    foreach ($roomOrgs as $roomID => $organizations) {
                        $organizationName = $this->getOrganizationName($organizations);
                        $this->setUseData('total', $organizationName, $roomID, $minutes);
                        $this->setUseData($termName, $organizationName, $roomID, $minutes);
                    }
                }

                $currentDate = date('Y-m-d', strtotime('+1 day', strtotime($currentDate)));
            }

            ksort($this->useData['total']);
            ksort($this->useData[$termName]);
        }
        unset($this->calendarData);
    }

    /**
     * Aggregates the raw instance data into calendar entries
     *
     * @param   array  $rawInstances  the raw lesson instances for a specific room
     *
     * @return void
     */
    private function aggregateInstances(array $rawInstances)
    {
        foreach ($rawInstances as $rawInstance) {
            $rawConfig = json_decode($rawInstance['configuration'], true);

            // Should not be able to occur because of the query conditions.
            if (empty($rawConfig['rooms'])) {
                continue;
            }

            $date  = $rawInstance['date'];
            $times = "{$rawInstance['startTime']}-{$rawInstance['endTime']}";

            foreach ($rawConfig['rooms'] as $roomID => $delta) {
                if (!in_array($roomID, array_keys($this->roomtypeMap)) or $delta == 'removed') {
                    continue;
                }

                if (empty($this->calendarData[$date])) {
                    $this->calendarData[$date] = [];
                }

                if (empty($this->calendarData[$date][$times])) {
                    $this->calendarData[$date][$times] = [];
                }

                if (empty($this->calendarData[$date][$times][$roomID])) {
                    $this->calendarData[$date][$times][$roomID] = [];
                }

                $this->calendarData[$date][$times][$roomID][$rawInstance['organizationID']] = $rawInstance['organization'];
            }
        }
    }

    /**
     * Makes the organization name or organization name aggregate
     *
     * @param $organizations
     *
     * @return string the organization name
     */
    private function getOrganizationName($organizations)
    {
        $noOrgs = count($organizations);

        if ($noOrgs === 1) {
            return array_pop($organizations);
        }

        $count            = 1;
        $organizationName = '';

        asort($organizations);

        foreach ($organizations as $organization) {
            if ($count == 1) {
                $organizationName .= $organization;
            }
            elseif ($count == $noOrgs) {
                $organizationName .= " & $organization";
            }
            else {
                $organizationName .= ", $organization";
            }

            $count++;
        }

        return $organizationName;
    }

    /**
     * Retrieves room options
     * @return string[] id => name
     */
    public function getRoomOptions()
    {
        $options = [];
        foreach ($this->rooms as $roomName => $roomData) {
            $options[$roomData['id']] = $roomName;
        }

        return $options;
    }

    /**
     * Retrieves room type options
     * @return string[] id => name
     */
    public function getRoomtypeOptions()
    {
        $options = [];
        foreach ($this->roomtypes as $roomTypeID => $roomtypeData) {
            $options[$roomTypeID] = $roomtypeData['name'];
        }

        return $options;
    }

    /**
     * Creates year selection options
     * @return string[]
     */
    public function getYearOptions()
    {
        $options = [];

        $query = DB::getQuery();
        $query->select('DISTINCT YEAR(schedule_date) AS year')->from('#__organizer_calendar')->order('year');

        DB::setQuery($query);
        $years = DB::loadColumn();

        if (!empty($years)) {
            foreach ($years as $year) {
                $options[$year] = $year;
            }
        }

        return $options;
    }

    /**
     * Retrieves raw lesson instance information from the database
     *
     * @param   int  $roomID  the id of the room being iterated
     *
     * @return bool true if room information was found, otherwise false
     */
    private function setData(int $roomID)
    {
        $tag     = Application::tag();
        $cSelect = "c.schedule_date AS date, TIME_FORMAT(c.startTime, '%H:%i') AS startTime, ";
        $cSelect .= "TIME_FORMAT(c.endTime, '%H:%i') AS endTime";

        $ringQuery = DB::getQuery();
        $ringQuery->select('DISTINCT ccm.id AS ccmID')
            ->from('#__organizer_calendar_configuration_map AS ccm')
            ->select($cSelect)
            ->innerJoin('#__organizer_calendar AS c ON c.id = ccm.calendarID')
            ->select('conf.configuration')
            ->innerJoin('#__organizer_lesson_configurations AS conf ON conf.id = ccm.configurationID')
            ->innerJoin('#__organizer_lessons AS l ON l.id = c.lessonID')
            ->select("o.id AS organizationID, o.shortName_$tag AS organization")
            ->innerJoin('#__organizer_organizations AS o ON o.id = l.organizationID')
            ->select('lcrs.id AS lcrsID')
            ->innerJoin('#__organizer_lesson_courses AS lcrs ON lcrs.lessonID = l.id');


        $ringQuery->where("lcrs.delta != 'removed'");
        $ringQuery->where("l.delta != 'removed'");
        $ringQuery->where("c.delta != 'removed'");
        Dates::betweenValues($ringQuery, 'schedule_date', $this->startDate, $this->endDate);

        $regexp = '"rooms":\\{("[0-9]+":"[\w]*",)*"' . $roomID . '":("new"|"")';
        $ringQuery->where("conf.configuration REGEXP '$regexp'");
        DB::setQuery($ringQuery);

        if (!$roomConfigurations = DB::loadAssocList()) {
            return false;
        }

        $this->aggregateInstances($roomConfigurations);

        return true;
    }

    /**
     * Sets the rooms
     * @return void sets an object variable
     */
    private function setRooms()
    {
        $rooms       = Rooms::getPlannedRooms();
        $roomtypeMap = [];

        foreach ($rooms as $room) {
            $roomtypeMap[$room['id']] = $room['roomtypeID'];
        }

        $this->rooms       = $rooms;
        $this->roomtypeMap = $roomtypeMap;
    }

    /**
     * Sets the available room types based on the rooms
     * @return void sets the room types object variable
     */
    private function setRoomTypes()
    {
        $query = DB::getQuery();
        $tag   = Application::tag();

        $query->select("id, name_$tag AS name, description_$tag AS description");
        $query->from('#__organizer_roomtypes');
        $query->order('name');
        DB::setQuery($query);

        $this->roomtypes = DB::loadAssocList('id');
    }

    /**
     * Retrieves the relevant term data from the database
     *
     * @param   string  $year  the year used for the statistics generation
     *
     * @return void true if the query was successful, otherwise false
     */
    private function setTerms(string $year): void
    {
        $query = DB::getQuery();
        $query->select('*')->from('#__organizer_terms')
            ->where("(YEAR(startDate) = $year OR YEAR(endDate) = $year)")
            ->order('startDate');
        DB::setQuery($query);

        $this->terms = DB::loadAssocList('id');

    }

    /**
     * Sets/sums individual usage values in it's container property
     *
     * @param   string  $termName  the name of the term
     * @param   string  $orgName   the name of the organization
     * @param   int     $roomID    the id of the room
     * @param   int     $value     the number of minutes
     *
     * @return void
     */
    private function setUseData(string $termName, string $orgName, int $roomID, int $value)
    {
        if (empty($this->useData[$termName][$orgName])) {
            $this->useData[$termName][$orgName] = [];
        }

        $existingValue = empty($this->useData[$termName][$orgName][$roomID]) ?
            0 : $this->useData[$termName][$orgName][$roomID];

        $this->useData[$termName][$orgName][$roomID] = $existingValue + $value;
    }
}
