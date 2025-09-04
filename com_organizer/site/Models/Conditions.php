<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2025 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Models;

use THM\Organizer\Adapters\{Application, Database as DB, Input, User};
use THM\Organizer\Helpers\{Can, Categories, Dates, Groups, Organizations};

/**
 * Class codifies the conditions for instance selection.
 */
class Conditions
{
    // Intervals
    public const DAY = 'day', HALF = 'half', MONTH = 'month', QUARTER = 'quarter', TERM = 'term', WEEK = 'week';

    // Layouts
    public const GRID = 'grid', LIST = 'list';
    public const LAYOUTS = [self::GRID, 'default' => self::LIST];

    // Instances display pattern for exported appointments
    public const ORGANIZATION = 'organization', PERSON = 'person';
    public const INSTANCES = ['default' => self::ORGANIZATION, self::PERSON];

    // Statuses
    public const NORMAL = 0, CURRENT = 1, NEW = 2, REMOVED = 3, CHANGED = 4;
    public const STATUSES = [self::CHANGED, 'export' => self::CURRENT, self::NEW, 'default' => self::NORMAL, self::REMOVED];

    public const INTERVALS = [
        Input::HTML => [
            self::GRID => [self::WEEK, self::DAY,],
            self::LIST => [self::DAY, self::HALF, self::MONTH, self::QUARTER, self:: TERM, self::WEEK],
        ],
        Input::JSON => [self::DAY, self::HALF, self::MONTH, self::QUARTER, self:: TERM, 'default' => self::WEEK],
        Input::PDF  => [self::GRID => [self::HALF, self::MONTH, self::QUARTER, self:: TERM, 'default' => self::WEEK]],
        Input::XLS  => [self::LIST => [self::DAY, self::HALF, self::MONTH, self::QUARTER, self:: TERM, 'default' => self::WEEK]],
    ];

    /** @var array the ids of the selected campuses */
    public array $campusIDs = [];
    /** @var array the ids of the selected categories */
    public array $categoryIDs = [];
    /** @var string $context the form context (com_organizer.<model><.menuID>) */
    public string $context;
    public int $courseID = 0;
    public string $date = '';
    /** @var string  the cutoff date for the relevance of changes to appointments */
    public string $delta;
    public int|null $dow = null;
    public string $endDate = '';
    public int $eventID = 0;
    /** @var array the ids of the selected groups */
    public array $groupIDs = [];
    public string $instances = self::INSTANCES['default'];
    public string $interval = '';
    public string $layout;
    /** @var array the ids of the selected methods */
    public array $methodIDs = [];
    public bool $my = false;
    /** @var array the ids of the authorized / selected organizations */
    public array $organizationIDs = [];
    /** @var array the ids of the authorized / selected persons */
    public array $personIDs = [];
    /** @var int|null the role required of the persons displayed in the exported instances (export view parameter) */
    public int|null $roleID = null;
    /** @var array the ids of the selected rooms */
    public array $roomIDs = [];
    /** @var bool whether the appointments for relevant groups should be displayed separately */
    public bool $separate = false;
    public bool $showUnpublished = false;
    public string $startDate = '';
    public int $status;
    public int $subjectID = 0;
    public int $unitID = 0;
    public int $userID;

    public function __construct(string $context = '')
    {
        #region basics
        $dateFormat = 'Y-m-d';
        $docFormat  = Input::format();
        $parameters = Input::parameters();

        $this->delta  = date($dateFormat, strtotime('-14 days'));
        $this->userID = User::id();

        if (Application::dynamic()) {
            $bound     = false;
            $startDate = null;

            if ($instances = Input::cmd('instances') and in_array($instances, self::INSTANCES)) {
                $this->instances = $instances;
            }
        }
        else {
            $this->dow = is_numeric($parameters->get('dow')) ? (int) $parameters->get('dow') : null;
            $bEndDate  = Dates::validate($parameters->get('endDate')) ? $parameters->get('endDate') : '';
            $methodIDs = array_filter($parameters->get('methodIDs'));
            $startDate = Dates::validate($parameters->get('startDate')) ? $parameters->get('startDate') : '';
            $bound     = ($this->dow or $bEndDate or $methodIDs);
        }
        #endregion

        #region when & how
        $date     = Application::userRequestState("$context.list.date", "list_date", '', 'cmd');
        $date     = Input::cmd('date', $date);
        $date     = Dates::standardize($date);
        $interval = Application::userRequestState("$context.list.interval", "list_interval", '', 'cmd');
        $interval = Input::cmd('interval', $interval);

        // For now only used for HTML, but will someday also be used for PDF & XLS
        $layout = Application::userRequestState("$context.list.layout", "list_layout", '', 'cmd');
        $layout = Input::cmd('layout', $layout);
        $layout = in_array($layout, self::LAYOUTS) ? $layout : self::LAYOUTS['default'];

        $status = Application::userRequestState("$context.filter.status", "filter_status", self::STATUSES['default'], 'int');
        $status = Input::integer('status', $status);
        $status = in_array($status, self::STATUSES) ? $status : self::STATUSES['default'];

        $this->layout = $layout;
        $this->status = $status;

        switch ($docFormat) {
            case Input::ICS:
                $this->interval = self::HALF;
                break;

            case Input::JSON:
                $this->interval = in_array($interval, self::INTERVALS[Input::JSON]) ?
                    $interval : self::INTERVALS[Input::JSON]['default'];
                break;

            case Input::PDF:
                $this->interval = in_array($interval, self::INTERVALS[Input::PDF]) ?
                    $interval : self::INTERVALS[Input::PDF]['default'];
                // List is not supported at this time
                $this->layout   = self::GRID;
                $this->separate = Input::bool('separate');

                break;

            case Input::XLS:
                $this->interval = in_array($interval, self::INTERVALS[Input::XLS]) ?
                    $interval : self::INTERVALS[Input::XLS]['default'];
                break;

            // HTML
            default:
                $this->layout = $layout;

                $format = Input::HTML;
                $mobile = Application::mobile();

                if ($layout === self::GRID) {
                    $this->interval = $mobile ? self::DAY : self::WEEK;
                }
                elseif ($bound) {
                    $date           = ($startDate and $startDate > $date) ? $startDate : $date;
                    $this->interval = self::HALF;
                }
                else {
                    $this->interval = in_array($interval, self::INTERVALS[$format][$layout]) ? $interval : self::DAY;
                }
                break;
        }

        $dateTime = strtotime($date);
        $reqDoW   = (int) date('w', $dateTime);

        // Sunday is not used for appointments
        if ($reqDoW === Dates::SUNDAY) {
            $date = date('Y-m-d', strtotime('+1 day', $dateTime));
        }

        $this->date = $date;
        $dateTime   = strtotime($date);

        [$this->startDate, $this->endDate] = match ($parameters['interval']) {
            'day' => [$date, $date],
            'half' => [date('Y-m-d', $dateTime), date('Y-m-d', strtotime('+6 month', $dateTime))],
            'month' => Dates::month($dateTime),
            'quarter' => Dates::ninetyDays($dateTime),
            'term' => self::term($date),
            default => Dates::week($dateTime),
        };

        if (!empty($bEndDate)) {
            $this->endDate = $bEndDate;
        }
        #endregion

        #region what & whether
        $personal = Application::userRequestState("$context.list.my", "list_my", 0, 'int');
        $personal = Input::integer('my', $personal);

        // Personal plans preempt filtering
        if ($personal) {
            $this->showUnpublished = true;
        }
        else {
            /*$byPerson = false;

            $campusIDs      = Application::userRequestState("$context.filter.campusIDs", "filter_campusIDs", [], 'array');
            $campusIDs      = Input::getIntArray('campusIDs', $campusIDs);
            $campusIDs      = array_filter($campusIDs, 'intval');
            $categoryID     = Application::userRequestState("$context.filter.categoryID", "filter_categoryID", 0, 'int');
            $categoryID     = Input::getInt('categoryID', $categoryID);
            $groupID        = Application::userRequestState("$context.filter.groupID", "filter_groupID", 0, 'int');
            $groupID        = Input::getInt('groupID', $groupID);
            $organizationID = Application::userRequestState("$context.filter.organizationID", "filter_organizationID", 0, 'int');
            $organizationID = Input::getInt('organizationID', $organizationID);

            $catOrgIDs = $categoryID ? Categories::organizationIDs($categoryID) : [];
            $grpCatID  = $groupID ? Groups::categoryID($groupID) : 0;
            $grpOrgIDs = $groupID ? Groups::organizationIDs($groupID) : [];

            $this->campusID  = Input::getInt('campusID', $campusID);
            $this->instances = Input::getCMD('instances', $this->instances);

            if (Application::backend()) {
                $authorized = Organizations::schedulableIDs();

                if ($organizationID and !in_array($organizationID, $authorized)) {
                    Application::error(403);
                }
                elseif (count($authorized) === 1) {
                    $organizationID = reset($authorized);
                }

                $this->showUnpublished = true;
            }

            if ($organizationID) {
                $this->organizationIDs = [$organizationID];

                if (!$this->showUnpublished) {
                    $this->showUnpublished = Organizations::viewable($organizationID);
                }

                if ($this->showUnpublished and $this->instances === 'person') {
                    $this->personIDs = Organizations::personIDs($organizationID);
                    $byPerson        = true;
                }

                // Sanity checks on subordinate resources
                if ($categoryID and (!$catOrgIDs or !in_array($organizationID, $catOrgIDs))) {
                    $categoryID = 0;
                }

                if ($groupID) {
                    $validOrg = ($grpOrgIDs and in_array($organizationID, $grpOrgIDs));
                    $validCat = (!$categoryID or $grpCatID === $categoryID);

                    if (!$validOrg or !$validCat) {
                        $groupID = 0;
                    }
                }
            }
            else {
                $this->showUnpublished = Can::administrate();

                if ($categoryID) {
                    $this->organizationIDs = $catOrgIDs;

                    // Sanity checks on subordinate resource
                    $groupID = ($groupID and $grpCatID === $categoryID) ? $groupID : 0;
                }
                elseif ($groupID) {
                    $this->organizationIDs = $grpOrgIDs;
                    $categoryID            = $grpCatID;
                }
            }

            if (!$byPerson) {

            }*/
        }

        #endregion
    }

    /**
     * Returns the end and start dates of a three-month period beginning with the date given.
     *
     * @param   int  $dateTime
     *
     * @return string[]
     */
    public static function ninetyDays(int $dateTime): array
    {
        if (Input::cmd('format') === Input::PDF) {
            $dateTime = strtotime("Monday this week", $dateTime);
        }

        return [date('Y-m-d', $dateTime), date('Y-m-d', strtotime('+90 days', $dateTime))];
    }

    /**
     * Returns the end date and start date of the term for the given date
     *
     * @param   string  $date  the date in format Y-m-d
     *
     * @return string[]
     */
    public static function term(string $date): array
    {
        $query = DB::query();
        $query->select(DB::qn(['startDate', 'endDate']))->from(DB::qn('#__organizer_terms'));
        DB::between($query, $date, 'startDate', 'endDate');
        DB::set($query);

        return array_values(DB::array());
    }
}