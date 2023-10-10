<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use THM\Organizer\Adapters\{Application, Text};
use THM\Organizer\Helpers;

/**
 * Class loads persistent information about a course into the display context.
 */
class CourseEdit extends EditView
{
    protected $layout = 'tabs';

    /**
     * Adds a toolbar and title to the view.
     * @return void  adds toolbar items to the view
     */
    protected function addToolBar()
    {
        if ($this->item->id) {
            $title = Text::_('ORGANIZER_COURSE_EDIT');

            if ($this->adminContext) {
                $campus = Helpers\Campuses::getName($this->item->campusID);
                $dates  = Helpers\Courses::getDateDisplay($this->item->id);
                $tag    = Application::getTag();
                $name   = "name_$tag";
                $name   = $this->item->$name;

                $title .= ": $name - $campus ($dates)";
            }
        } else {
            $title = Text::_('ORGANIZER_COURSE_NEW');
        }

        parent::addToolBar();
        $this->setTitle($title);
    }

    /**
     * Creates a subtitle element from the term name and the start and end dates of the course.
     * @return void modifies the course
     */
    protected function setSubtitle()
    {
        if (empty($this->item->id)) {
            $this->subtitle = '';

            return;
        }

        $subTitle   = [];
        $subTitle[] = Helpers\Courses::getName($this->item->id);

        if ($this->item->campusID) {
            $subTitle[] = Helpers\Campuses::getName($this->item->campusID);
        }

        $subTitle[] = Helpers\Courses::getDateDisplay($this->item->id);

        $this->subtitle = '<h6 class="sub-title">' . implode('<br>', $subTitle) . '</h6>';
    }
}