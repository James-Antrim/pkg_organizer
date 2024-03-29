<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\PDF;

use THM\Organizer\Adapters\{Application, Input, User};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

/**
 * Class loads persistent information about a course into the display context.
 */
class CourseParticipants extends ListView
{
    /**
     * The campus where the course takes place
     * @var string
     */
    public $campus;

    /**
     * The name of the course
     * @var string
     */
    public $course;

    /**
     * The id of the associated course.
     * @var int
     */
    public $courseID;

    /**
     * The dates as displayed in the generated document.
     * @var string
     */
    public $dates;

    /**
     * The course end date
     * @var string
     */
    public $endDate;

    /**
     * The fee required for participation in the course
     * @var int
     */
    public $fee;

    /**
     * The course start date
     * @var string
     */
    public $startDate;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $dates        = Helpers\Courses::dates($this->courseID);
        $nameProperty = 'name_' . Application::getTag();

        // Course Data is on top, because the participants are the actual list items.
        $course = new Tables\Courses();
        $course->load($this->courseID);
        $this->campus    = Helpers\Campuses::name($course->campusID);
        $this->course    = $course->$nameProperty;
        $this->endDate   = Helpers\Dates::formatDate($dates['endDate']);
        $this->fee       = $course->fee;
        $this->startDate = Helpers\Dates::formatDate($dates['startDate']);
    }

    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        if (!User::id()) {
            Application::error(401);
        }

        if (!$this->courseID = Input::getID()) {
            Application::error(400);
        }

        if (!Helpers\Can::coordinate('course', $this->courseID)) {
            Application::error(403);
        }
    }

    /**
     * Set header items.
     * @return void
     */
    public function setOverhead()
    {
        $interval  = ($this->endDate and $this->endDate != $this->startDate);
        $dates     = $interval ? "$this->startDate - $this->endDate" : $this->startDate;
        $subHeader = $this->campus ? "$this->campus $dates" : $dates;

        $this->setHeaderData('pdf_logo.png', '55', $this->course, $subHeader, self::BLACK, self::WHITE);
        $this->setFooterData(self::BLACK, self::WHITE);

        parent::setHeader();
    }
}
