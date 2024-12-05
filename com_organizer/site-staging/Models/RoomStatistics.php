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
use THM\Organizer\Helpers\{Dates, Organizations, Rooms};
use THM\Organizer\Tables\Terms;

/**
 * Class calculates room usage statistics.
 */
class RoomStatistics extends BaseModel
{
    public $calendarData;

    public $endDate;

    public $endDoW;

    /**
     * Subject dependant data which would otherwise redundantly be in the calendar data
     * @var array
     */
    public $lsData;

    private $grid;

    public $metaData;

    public $rooms;

    public $roomtypes;

    public $roomtypeMap;

    public $roomData;

    public $startDate;

    public $startDoW;

    private float $threshold = .2;

    /**
     * Room_Statistics constructor.
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
                $this->setGrid();
                $this->setDates();
                $this->initializeCalendar();

                foreach ($this->rooms as $roomName => $roomData) {
                    if (!$this->setData($roomData['id'])) {
                        unset($this->rooms[$roomName]);
                        unset($this->roomtypeMap[$roomData['id']]);
                    }
                }

                foreach (array_keys($this->roomtypes) as $rtID) {
                    if (!in_array($rtID, $this->roomtypeMap)) {
                        unset($this->roomtypes[$rtID]);
                    }
                }

                $this->createUseData();
                $this->createMetaData();

                break;

            case 'html':
            default:
                break;
        }
    }

    /**
     * Aggregates the raw instance data into calendar entries
     *
     * @param   array  $ringData  the raw lesson instances for a specific room
     *
     * @return void
     */
    private function aggregateInstances(array $ringData): void
    {
        foreach ($ringData as $instance) {
            $rawConfig = json_decode($instance['configuration'], true);

            // Should not be able to occur because of the query conditions.
            if (empty($rawConfig['rooms'])) {
                continue;
            }

            $date     = $instance['date'];
            $lessonID = $instance['lessonID'];
            $method   = $instance['method'];
            $lcrsIDs  = [$instance['lcrsID'] => $instance['lcrsID']];

            foreach ($rawConfig['rooms'] as $roomID => $delta) {
                if (!in_array($roomID, array_keys($this->roomtypeMap)) or $delta == 'removed') {
                    continue;
                }

                $blocks = $this->getRelevantBlocks($instance['startTime'], $instance['endTime']);

                if (empty($blocks)) {
                    continue;
                }

                foreach ($blocks as $blockNo) {
                    if (empty($this->calendarData[$date][$blockNo][$roomID])) {
                        $this->calendarData[$date][$blockNo][$roomID] = [];
                    }

                    if (empty($this->calendarData[$date][$blockNo][$roomID][$lessonID])) {
                        $this->calendarData[$date][$blockNo][$roomID][$lessonID]           = [];
                        $this->calendarData[$date][$blockNo][$roomID][$lessonID]['method'] = $method;
                    }

                    $existingLCRSIDs = empty($this->calendarData[$date][$blockNo][$roomID][$lessonID]['lcrsIDs']) ?
                        [] : $this->calendarData[$date][$blockNo][$roomID][$lessonID]['lcrsIDs'];

                    $this->calendarData[$date][$blockNo][$roomID][$lessonID]['lcrsIDs'] = $existingLCRSIDs + $lcrsIDs;
                }
            }
        }
    }

    /**
     * Retrieves organization options
     * @return string[] id => name
     */
    public function getOrganizationOptions(): array
    {
        $options = [];
        foreach (Organizations::options(false) as $id => $name) {
            $options[$id] = $name;
        }

        return $options;
    }

    /**
     * Determines the relevant grid blocks based upon the instance start and end times
     *
     * @param   string  $startTime  the time the instance starts
     * @param   string  $endTime    the time the instance ends
     *
     * @return int[] the relevant block numbers
     */
    private function getRelevantBlocks(string $startTime, string $endTime): array
    {
        $relevantBlocks = [];

        foreach ($this->grid as $blockNo => $times) {
            $tooEarly = $times['endTime'] <= $startTime;
            $tooLate  = $times['startTime'] >= $endTime;

            if ($tooEarly or $tooLate) {
                continue;
            }

            $blockNo                  = (int) $blockNo;
            $relevantBlocks[$blockNo] = $blockNo;
        }

        return $relevantBlocks;
    }

    /**
     * Retrieves room options
     * @return string[] id => name
     */
    public function getRoomOptions(): array
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
    public function getRoomtypeOptions(): array
    {
        $options = [];
        foreach ($this->roomtypes as $roomtypeID => $roomTypeData) {
            $options[$roomtypeID] = $roomTypeData['name'];
        }

        return $options;
    }

    /**
     * Creates a calendar to associate the instances with.
     * @return void sets the object variable use
     */
    private function initializeCalendar(): void
    {
        $calendar = [];
        $startDT  = strtotime($this->startDate);
        $endDT    = strtotime($this->endDate);

        for ($currentDT = $startDT; $currentDT <= $endDT; $currentDT = strtotime('+1 days', $currentDT)) {
            $currentDoW = date('w', $currentDT);
            $invalidDoW = ($currentDoW < $this->startDoW or $currentDoW > $this->endDoW);

            if ($invalidDoW) {
                continue;
            }

            $date = date('Y-m-d', $currentDT);
            if (!isset($calendar[$date])) {
                $calendar[$date] = $this->grid;
            }
        }

        $this->calendarData = $calendar;
    }

    /**
     * Retrieves raw lesson instance information from the database
     *
     * @param   int  $roomID  the id of the room being iterated
     *
     * @return bool true if room information was found, otherwise false
     */
    private function setData(int $roomID): bool
    {
        $tag       = Application::tag();
        $ringQuery = DB::query();
        $ringQuery->select('DISTINCT ccm.id AS ccmID')
            ->from('#__organizer_calendar_configuration_map AS ccm')
            ->select('c.schedule_date AS date, c.startTime, c.endTime')
            ->innerJoin('#__organizer_calendar AS c ON c.id = ccm.calendarID')
            ->select('conf.configuration')
            ->innerJoin('#__organizer_lesson_configurations AS conf ON conf.id = ccm.configurationID')
            ->select('l.id AS lessonID, l.comment')
            ->innerJoin('#__organizer_lessons AS l ON l.id = c.lessonID')
            ->select('lcrs.id AS lcrsID')
            ->innerJoin('#__organizer_lesson_courses AS lcrs ON lcrs.lessonID = l.id')
            ->select("m.id AS methodID, m.abbreviation_$tag AS method, m.name_$tag as methodName")
            ->leftJoin('#__organizer_methods AS m ON m.id = l.methodID');

        $ringQuery->where("lcrs.delta != 'removed'");
        $ringQuery->where("l.delta != 'removed'");
        $ringQuery->where("c.delta != 'removed'");
        Dates::betweenValues($ringQuery, 'schedule_date', $this->startDate, $this->endDate);

        $regexp = '"rooms":\\{("[0-9]+":"[\w]*",)*"' . $roomID . '":("new"|"")';
        $ringQuery->where("conf.configuration REGEXP '$regexp'");
        DB::set($ringQuery);
        $ringData = DB::arrays();
        $lcrsIDs  = DB::integers(7);

        if (empty($ringData) or empty($lcrsIDs)) {
            return false;
        }

        $this->aggregateInstances($ringData);
        $this->setLSData($lcrsIDs);

        return true;
    }

    /**
     * Resolves form date information into where clauses for the query being built
     * @return void the corresponding start and end dates
     */
    private function setDates(): void
    {
        $termID = Input::getFilterID('termID');

        if ($termID) {
            $table = new Terms();

            if ($table->load($termID)) {
                $this->startDate = $table->startDate;
                $this->endDate   = $table->endDate;

                return;
            }
        }

        $dateFormat = Input::getParams()->get('dateFormat');
        $date       = Input::getCMD('date', date($dateFormat));
        $startDoWNo = empty($this->startDoW) ? 1 : $this->startDoW;
        $endDoWNo   = empty($this->endDoW) ? 6 : $this->endDoW;
        $interval   = Input::getCMD('interval', 'week');

        $dates = match ($interval) {
            'month' => Dates::oneMonth($date),
            default => Dates::week($date, $startDoWNo, $endDoWNo),
        };

        $this->startDate = $dates['startDate'];
        $this->endDate   = $dates['endDate'];
    }

    /**
     * Retrieves the selected grid from the database
     * @return void sets object variables
     */
    private function setGrid(): void
    {
        $query = DB::query();
        $query->select('grid')->from('#__organizer_grids');

        if (empty($this->parameters['gridID'])) {
            $query->where('isDefault = 1');
        }
        else {
            $query->where("id = {$this->parameters['gridID']}");
        }

        DB::set($query);

        if (!$rawGrid = DB::string()) {
            return;
        }

        $gridSettings = json_decode($rawGrid, true);

        $grid = [];

        foreach ($gridSettings['periods'] as $number => $times) {
            $grid[$number]              = [];
            $grid[$number]['startTime'] = date('H:i:s', strtotime($times['startTime']));
            $grid[$number]['endTime']   = date('H:i:s', strtotime($times['endTime']));
        }

        $this->grid     = $grid;
        $this->startDoW = $gridSettings['startDay'];
        $this->endDoW   = $gridSettings['endDay'];
    }

    /**
     * Sets mostly textual data which is dependent on the lesson subject ids
     *
     * @param   array  $lcrsIDs  the lesson subject database ids
     *
     * @return void sets object variable indexes
     */
    private function setLSData(array $lcrsIDs): void
    {
        $tag   = Application::tag();
        $query = DB::query();

        $select = 'DISTINCT lcrs.id AS lcrsID, ';
        $query->from('#__organizer_lesson_courses AS lcrs');

        // Subject Data
        $select .= 'co.id AS courseID, co.name AS courseName, co.subjectNo, co.code AS courseCode, ';
        $select .= "s.id AS subjectID, s.name_$tag AS subjectName, ";
        $select .= "s.abbreviation_$tag AS subjectAbbr, ";
        $query->innerJoin('#__organizer_courses AS co ON co.id = lcrs.courseID');
        $query->leftJoin('#__organizer_subject_events AS se ON se.courseID = co.id');
        $query->leftJoin('#__organizer_subjects AS s ON s.id = se.subjectID');

        // Group Data
        $select .= 'group.id AS groupID, group.code AS groupCode, ';
        $select .= 'group.name AS groupName, group.fullName AS groupFullName, ';
        $query->innerJoin('#__organizer_lesson_groups AS lg ON lg.lessonCourseID = lcrs.id');
        $query->innerJoin('#__organizer_groups AS group ON group.id = lg.groupID');

        // Category/Program Data
        $select .= 'cat.id AS categoryID, cat.name AS categoryName, ';
        $select .= "prog.name_$tag AS progName, prog.accredited, dg.abbreviation AS progAbbr, ";
        $query->innerJoin('#__organizer_categories AS cat ON cat.id = group.categoryID');
        $query->leftJoin('#__organizer_programs AS prog ON prog.categoryID = cat.id');
        $query->leftJoin('#__organizer_degrees AS dg ON dg.id = prog.degreeID');

        // Organization Data
        $select .= "o.id AS organizationID, o.shortName_$tag AS organization, o.name_$tag AS organizationName";
        $query->innerJoin('#__organizer_associations AS a ON a.categoryID = cat.id');
        $query->innerJoin('#__organizer_organizations AS o ON o.id = a.organizationID');

        $query->select($select);
        $query->where("lg.delta != 'removed'");
        $query->where("lcrs.id IN ('" . implode("', '", $lcrsIDs) . "')");
        DB::set($query);

        $results = DB::arrays('lcrsID');
        if (empty($results)) {
            return;
        }

        foreach ($results as $lcrsID => $lsData) {
            $this->lsData[$lcrsID] = $lsData;
        }
    }

    /**
     * Sets the rooms
     * @return void sets an object variable
     */
    private function setRooms(): void
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
    private function setRoomTypes(): void
    {
        $tag   = Application::tag();
        $query = DB::query();

        $query->select("id, name_$tag AS name, description_$tag AS description")
            ->from('#__organizer_roomtypes')
            ->order('name');

        DB::set($query);

        $this->roomtypes = DB::arrays('id');
    }

    /**
     * Creates metadata for the weeks, totals and adjusted totals. Also sets room week data.
     * @return void
     */
    private function createMetaData(): void
    {
        $this->metaData         = [];
        $this->metaData['days'] = [];
        $dailyBlocks            = count($this->grid);

        foreach (array_keys($this->calendarData) as $date) {
            $this->metaData['days'][$date]          = [];
            $this->metaData['days'][$date]['total'] = 0;
            $this->metaData['days'][$date]['use']   = 0;

            foreach ($this->rooms as $roomData) {
                $roomUse = empty($this->roomData[$roomData['id']]['days'][$date]) ?
                    0 : $this->roomData[$roomData['id']]['days'][$date];

                $this->metaData['days'][$date]['total'] += $dailyBlocks;
                $this->metaData['days'][$date]['use']   += $roomUse;
            }
        }

        $this->metaData['weeks'] = [];
        $weekNo                  = 1;

        for ($weekStartDate = $this->startDate; $weekStartDate <= $this->endDate;) {
            $week['startDate']     = $weekStartDate;
            $endDayName            = date('l', strtotime("Sunday + $this->endDoW days"));
            $weekEndDate           = date('Y-m-d', strtotime("$endDayName this week", strtotime($weekStartDate)));
            $week['endDate']       = $weekEndDate;
            $week['adjustedTotal'] = 0;
            $week['adjustedUse']   = 0;
            $week['total']         = 0;
            $week['use']           = 0;

            for ($currentDate = $weekStartDate; $currentDate <= $weekEndDate;) {
                $week['total'] += $this->metaData['days'][$currentDate]['total'];
                $week['use']   += $this->metaData['days'][$currentDate]['use'];
                $dailyAverage  = $this->metaData['days'][$currentDate]['use'] / $this->metaData['days'][$currentDate]['total'];

                if ($dailyAverage > $this->threshold) {
                    $week['adjustedTotal'] += $this->metaData['days'][$currentDate]['total'];
                    $week['adjustedUse']   += $this->metaData['days'][$currentDate]['use'];
                }

                foreach ($this->rooms as $roomData) {
                    if (empty($this->roomData[$roomData['id']]['weeks'])) {
                        $this->roomData[$roomData['id']]['weeks'] = [];
                    }

                    if (empty($this->roomData[$roomData['id']]['weeks'][$weekNo])) {
                        $this->roomData[$roomData['id']]['weeks'][$weekNo]                  = [];
                        $this->roomData[$roomData['id']]['weeks'][$weekNo]['adjustedTotal'] = 0;
                        $this->roomData[$roomData['id']]['weeks'][$weekNo]['adjustedUse']   = 0;
                        $this->roomData[$roomData['id']]['weeks'][$weekNo]['total']         = 0;
                        $this->roomData[$roomData['id']]['weeks'][$weekNo]['use']           = 0;
                    }

                    $this->roomData[$roomData['id']]['weeks'][$weekNo]['total'] += $dailyBlocks;
                    $this->roomData[$roomData['id']]['weeks'][$weekNo]['use']   +=
                        $this->roomData[$roomData['id']]['days'][$currentDate];

                    if ($dailyAverage > $this->threshold) {
                        $this->roomData[$roomData['id']]['weeks'][$weekNo]['adjustedTotal'] += $dailyBlocks;
                        $this->roomData[$roomData['id']]['weeks'][$weekNo]['adjustedUse']   +=
                            $this->roomData[$roomData['id']]['days'][$currentDate];
                    }
                }

                $currentDate = date('Y-m-d', strtotime("$currentDate + 1 days"));
            }

            $this->metaData['weeks'][$weekNo] = $week;
            $weekNo++;
            $weekStartDate = date('Y-m-d', strtotime("$weekStartDate + 7 days"));
        }

        $this->metaData['adjustedTotal'] = 0;
        $this->metaData['adjustedUse']   = 0;
        $this->metaData['total']         = 0;
        $this->metaData['use']           = 0;

        foreach ($this->metaData['weeks'] as $weekData) {
            $this->metaData['total'] += $weekData['total'];
            $this->metaData['use']   += $weekData['use'];

            if (empty($weekData['adjustedTotal'])) {
                continue;
            }

            $weeklyAverage = $weekData['adjustedUse'] / $weekData['adjustedTotal'];

            // TODO: find a good value for this through experimentation
            if ($weeklyAverage > $this->threshold) {
                $this->metaData['adjustedTotal'] += $weekData['adjustedTotal'];
                $this->metaData['adjustedUse']   += $weekData['adjustedUse'];
            }
        }
    }

    /**
     * Sums number of used blocks per room per day
     * @return void
     */
    private function createUseData(): void
    {
        $this->roomData = [];

        foreach ($this->calendarData as $date => $blocks) {
            foreach ($blocks as $blockRoomData) {
                $roomIDs = array_keys($blockRoomData);

                // This will ignore double bookings because the lessons themselves are not iterated
                foreach ($this->rooms as $room) {
                    if (empty($this->roomData[$room['id']])) {
                        $this->roomData[$room['id']]         = [];
                        $this->roomData[$room['id']]['days'] = [];
                    }

                    $newValue = empty($this->roomData[$room['id']]['days'][$date]) ?
                        0 : $this->roomData[$room['id']]['days'][$date];

                    if (in_array($room['id'], $roomIDs)) {
                        $newValue++;
                    }

                    $this->roomData[$room['id']]['days'][$date] = $newValue;
                }
            }
        }
    }
}
