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
use Organizer\Helpers\Languages;

/**
 * Class loads the subject into the display context.
 */
class CourseItem extends ItemView
{
	// Participant statuses
	const UNREGISTERED = null, PENDING = 0, ACCEPTED = 1;

	// Course Statuses
	const EXPIRED = -1, ONGOING = 1;

	private $manages = false;
	private $participant = false;
	private $profile = false;

	/**
	 * Constructor
	 *
	 * @param   array  $config  A named configuration array for object construction.
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		if (Helpers\Users::getID())
		{
			if (Helpers\Can::scheduleTheseOrganizations() or Helpers\Can::edit('courses'))
			{
				$this->manages = true;
			}
			elseif (!$this->clientContext and Helpers\Participants::exists())
			{
				$this->profile     = true;
				$this->participant = Helpers\Courses::participates(Helpers\Input::getID());
			}
		}
	}

	/**
	 * Adds supplemental information to the display output.
	 *
	 * @return void modifies the object property supplement
	 */
	protected function addSupplement()
	{
		if ($this->manages)
		{
			return;
		}

		$course = $this->item;

		$text = '<div class="tbox-' . $course['courseStatus'] . '">' . $course['courseText'] . '</div>';

		$this->supplement = $text;
	}

	/**
	 * Adds a toolbar and title to the view.
	 *
	 * @return void  adds toolbar items to the view
	 */
	protected function addToolBar()
	{
		if (Helpers\Users::getID())
		{
			$toolbar = Toolbar::getInstance();
			$today   = Helpers\Dates::standardizeDate();

			if ($this->item['deadline'])
			{
				$deadline = date('Y-m-d',
					strtotime("-{$this->item['deadline']} Days", strtotime($this->item['startDate'])));
			}
			else
			{
				$deadline = $this->item['startDate'];
			}

			if (!$this->manages and $deadline > $today)
			{
				if ($this->profile)
				{
					if (Helpers\Participants::canRegister())
					{
						$link = 'index.php?option=com_organizer&id=' . $this->item['id'] . '&task=';
						if ($this->participant)
						{
							$icon = 'exit';
							$link .= 'courses.deregister';
							$text = Languages::_('ORGANIZER_DEREGISTER');
						}
						else
						{
							$icon = 'enter';
							$link .= 'courses.register';
							$text = Languages::_('ORGANIZER_REGISTER');
						}

						$toolbar->appendButton('Link', $icon, $text, $link);
					}
					else
					{
						$toolbar->appendButton(
							'Link',
							'vcard',
							Languages::_('ORGANIZER_PROFILE_EDIT'),
							'index.php?option=com_organizer&view=participant_edit'
						);
					}
				}
				else
				{
					$toolbar->appendButton(
						'Link',
						'user-plus',
						Languages::_('ORGANIZER_PROFILE_NEW'),
						'index.php?option=com_organizer&view=participant_edit'
					);
				}
			}
		}
	}

	/**
	 * Creates a subtitle element from the term name and the start and end dates of the course.
	 *
	 * @return void modifies the course
	 */
	protected function setSubtitle()
	{
		$this->subtitle = '<h6 class="sub-title">';

		if ($this->item['campusID'])
		{
			$campusName     = Helpers\Campuses::getName($this->item['campusID']);
			$this->subtitle .= Languages::_('ORGANIZER_CAMPUS') . " $campusName: ";
		}

		$this->subtitle .= Helpers\Courses::getDateDisplay($this->item['id']) . '</h6>';
	}
}