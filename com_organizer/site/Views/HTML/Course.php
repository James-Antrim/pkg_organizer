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
class Course extends FormView
{
    protected string $layout = 'tabs';

    /**
     * Adds a toolbar and title to the view.
     *
     * @param   array   $buttons
     * @param   string  $constant  *
     *
     * @return void  adds toolbar items to the view
     */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        if ($this->item->id) {
            $title = Text::_('COURSE_EDIT');

            if (Application::backend()) {
                $campus = Helpers\Campuses::name($this->item->campusID);
                $dates  = Helpers\Courses::displayDate($this->item->id);
                $tag    = Application::getTag();
                $name   = "name_$tag";
                $name   = $this->item->$name;

                $title .= ": $name - $campus ($dates)";
            }
        }
        else {
            $title = Text::_('ADD_COURSE');
        }

        parent::addToolBar();
        $this->setTitle($title);
    }

    /**
     * Creates a subtitle element from the term name and the start and end dates of the course.
     * @return void modifies the course
     */
    protected function setSubtitle(): void
    {
        if (empty($this->item->id)) {
            $this->subtitle = '';

            return;
        }

        $subTitle   = [];
        $subTitle[] = Helpers\Courses::name($this->item->id);

        if ($this->item->campusID) {
            $subTitle[] = Helpers\Campuses::name($this->item->campusID);
        }

        $subTitle[] = Helpers\Courses::displayDate($this->item->id);

        $this->subtitle = '<h6 class="sub-title">' . implode('<br>', $subTitle) . '</h6>';
    }
}