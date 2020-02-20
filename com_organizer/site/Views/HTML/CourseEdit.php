<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Toolbar\Toolbar;
use Organizer\Helpers;

/**
 * Class loads persistent information about a course into the display context.
 */
class CourseEdit extends EditView
{
	protected $_layout = 'tabs';

	/**
	 * Adds a toolbar and title to the view.
	 *
	 * @return void  adds toolbar items to the view
	 */
	protected function addToolBar()
	{
		if ($this->item->id)
		{
			$cancel = 'ORGANIZER_CLOSE';
			$save   = 'ORGANIZER_SAVE';
			$title  = "ORGANIZER_COURSE_EDIT";
		}
		else
		{
			$cancel = 'ORGANIZER_CANCEL';
			$save   = 'ORGANIZER_CREATE';
			$title  = "ORGANIZER_COURSE_NEW";
		}

		Helpers\HTML::setTitle(Helpers\Languages::_($title), 'contract-2');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'save', Helpers\Languages::_($save), "courses.save", false);

		if ($this->item->id)
		{

			$href   = "index.php?option=com_organizer&view=course_participants&courseID={$this->item->id}";
			$icon   = '<span class="icon-users"></span>';
			$text   = Helpers\Languages::_('ORGANIZER_MANAGE_PARTICIPANTS');
			$button = "<a class=\"btn\" href=\"$href\" target=\"_blank\">$icon$text</a>";
			$toolbar->appendButton('Custom', $button, 'participants');
		}

		$toolbar->appendButton('Standard', 'cancel', Helpers\Languages::_($cancel), "courses.cancel", false);
	}

	/**
	 * Creates a subtitle element from the term name and the start and end dates of the course.
	 *
	 * @return void modifies the course
	 */
	protected function setSubtitle()
	{
		$course = $this->item;

		if (empty($course->id))
		{
			$this->subtitle = '';

			return;
		}

		$name   = Helpers\Courses::getName($course->id);
		$dates  = Helpers\Courses::getDateDisplay($course->id);
		$termID = $course->preparatory ? Helpers\Terms::getNextID($course->termID) : $course->termID;
		$term   = Helpers\Terms::getName($termID);

		$this->subtitle = "<h6 class=\"sub-title\">$name ($course->id)<br>$term - $dates</h6>";
	}
}