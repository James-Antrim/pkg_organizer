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

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers;
use Organizer\Helpers\Languages;
use Organizer\Tables;

/**
 * Class loads persistent information a filtered set of course participants into the display context.
 */
class CourseParticipants extends Participants
{
	protected $rowStructure = [
		'checkbox'    => '',
		'fullName'    => 'value',
		'email'       => 'value',
		'programName' => 'value',
		'status'      => 'value',
		'paid'        => 'value',
		'attended'    => 'value'
	];

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		$courseID = Helpers\Input::getID();
		$course   = new Tables\Courses();
		$course->load($courseID);
		$title = Languages::_('ORGANIZER_PARTICIPANTS');

		Helpers\HTML::setTitle($title, 'users');

		$admin       = Helpers\Can::administrate();
		$coordinates = Helpers\Courses::coordinates($courseID);
		$toolbar     = Toolbar::getInstance();

		/*if ($admin)
		{
			$toolbar->appendButton(
				'Standard',
				'edit',
				Languages::_('ORGANIZER_EDIT'),
				'participants.edit',
				true
			);
		}*/

		$toolbar->appendButton(
			'Standard',
			'checkin',
			Languages::_('ORGANIZER_ACCEPT'),
			'course_participants.accept',
			true
		);

		$toolbar->appendButton(
			'Standard',
			'checkbox-unchecked',
			Languages::_('ORGANIZER_WAITLIST'),
			'course_participants.waitlist',
			true
		);

		$toolbar->appendButton(
			'Confirm',
			Languages::_('ORGANIZER_DELETE_CONFIRM'),
			'user-minus',
			Languages::_('ORGANIZER_DELETE'),
			'course_participants.remove',
			true
		);


		$link = 'index.php?option=com_organizer&view=badges&format=pdf&id=' . Helpers\Input::getID();
		$toolbar->appendButton('Link', 'tags-2', Languages::_('ORGANIZER_DOWNLOAD_BADGES'), $link);

		/*$if          = "alert('" . Languages::_('ORGANIZER_LIST_SELECTION_WARNING') . "');";
		$else        = "jQuery('#modal-circular').modal('show'); return true;";
		$script      = 'onclick="if(document.adminForm.boxchecked.value==0){' . $if . '}else{' . $else . '}"';
		$batchButton = '<button id="participant-mail" data-toggle="modal" class="btn btn-small" ' . $script . '>';

		$title       = Languages::_('ORGANIZER_MAIL');
		$batchButton .= '<span class="icon-envelope" title="' . $title . '"></span>' . " $title";

		$batchButton .= '</button>';

		$toolbar->appendButton('Custom', $batchButton, 'batch');*/

		if ($admin)
		{
			/*$toolbar->appendButton(
				'Standard',
				'attachment',
				Languages::_('ORGANIZER_MERGE'),
				'participants.mergeView',
				true
			);*/
		}
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return bool true if the use may access the view, otherwise false
	 */
	protected function allowAccess()
	{
		if ($courseID = Helpers\Input::getID())
		{
			return Helpers\Can::manage('course', $courseID);
		}

		return Helpers\Can::administrate();
	}

	/**
	 * Method to create a list output
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		// Set batch template path
		$this->batch = ['batch_circular'];

		parent::display($tpl);
	}

	/**
	 * Modifies document variables and adds links to external files
	 *
	 * @return void
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		Factory::getDocument()->addStyleSheet(Uri::root() . 'components/com_organizer/css/modal.css');
	}

	/**
	 * Function to set the object's headers property
	 *
	 * @return void sets the object headers property
	 */
	protected function setHeaders()
	{
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox'    => Helpers\HTML::_('grid.checkall'),
			'fullName'    => Helpers\HTML::sort('NAME', 'fullName', $direction, $ordering),
			'email'       => Helpers\HTML::sort('EMAIL', 'email', $direction, $ordering),
			'programName' => Helpers\HTML::sort('PROGRAM', 'programName', $direction, $ordering),
			'status'      => Helpers\HTML::sort('STATUS', 'status', $direction, $ordering),
			'paid'        => Helpers\HTML::sort('PAID', 'paid', $direction, $ordering),
			'attended'    => Helpers\HTML::sort('ATTENDED', 'attended', $direction, $ordering)
		];

		$this->headers = $headers;
	}

	/**
	 * Creates a subtitle element from the term name and the start and end dates of the course.
	 *
	 * @return void modifies the course
	 */
	protected function setSubtitle()
	{
		$courseID = Helpers\Input::getID();

		$subTitle   = [];
		$subTitle[] = Helpers\Courses::getName($courseID);

		if ($campusID = Helpers\Courses::getCampusID($courseID))
		{
			$subTitle[] = Helpers\Campuses::getName($campusID);
		}

		$subTitle[] = Helpers\Courses::getDateDisplay($courseID);

		$this->subtitle = '<h6 class="sub-title">' . implode('<br>', $subTitle) . '</h6>';
	}

	/**
	 * Processes the items in a manner specific to the view, so that a generalized  output in the layout can occur.
	 *
	 * @return void processes the class items property
	 */
	protected function structureItems()
	{
		$index           = 0;
		$link            = 'index.php?option=com_organizer&view=participant_edit&id=';
		$structuredItems = [];

		$admin     = Helpers\Can::administrate();
		$checked   = '<span class="icon-checkbox-checked"></span>';
		$courseID  = Helpers\Input::getID();
		$expired   = Helpers\Courses::isExpired($courseID);
		$unchecked = '<span class="icon-checkbox-unchecked"></span>';

		foreach ($this->items as $item)
		{
			$item->programName = Helpers\Programs::getName($item->programID);

			if (!$expired)
			{
				$item->status = $this->getAssocToggle(
					'course_participants',
					'courseID',
					$courseID,
					'participantID',
					$item->id,
					$item->status,
					Languages::_('ORGANIZER_TOGGLE_ACCEPTED'),
					'status'
				);
			}
			else
			{
				$item->status = $item->status ? $checked : $unchecked;
			}

			if ($admin or !$item->attended)
			{
				$item->attended = $this->getAssocToggle(
					'course_participants',
					'courseID',
					$courseID,
					'participantID',
					$item->id,
					$item->attended,
					Languages::_('ORGANIZER_TOGGLE_ATTENDED'),
					'attended'
				);
			}
			else
			{
				$item->attended = $checked;
			}

			if ($admin or !$item->paid)
			{
				$item->paid = $this->getAssocToggle(
					'course_participants',
					'courseID',
					$courseID,
					'participantID',
					$item->id,
					$item->paid,
					Languages::_('ORGANIZER_TOGGLE_PAID'),
					'paid'
				);
			}
			else
			{
				$item->paid = $checked;
			}

			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
