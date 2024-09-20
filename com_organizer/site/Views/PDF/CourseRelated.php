<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2024 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\PDF;

trait CourseRelated
{
    public string $campus;
    public string $course;
    public int $courseID;
    public string $dates;
    public string $endDate;
    public int $fee;
    public string $startDate;
}