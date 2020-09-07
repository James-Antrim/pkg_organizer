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
 * Class loads participant information into the display context.
 */
class ParticipantEdit extends EditView
{

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		if ($this->item->id)
		{
			$own    = Helpers\Users::getID() === $this->item->id;
			$cancel = 'ORGANIZER_CLOSE';
			$save   = 'ORGANIZER_SAVE_CLOSE';
			$title  = $own ? 'ORGANIZER_EDIT_MY_PROFILE' : 'ORGANIZER_PARTICIPANT_EDIT';
		}
		else
		{
			$cancel = 'ORGANIZER_CANCEL';
			$save   = 'ORGANIZER_CREATE_CLOSE';
			$title  = "ORGANIZER_PROFILE_NEW";
		}

		Helpers\HTML::setTitle(Helpers\Languages::_($title), 'cog');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'save', Helpers\Languages::_($save), "participants.save", false);
		$toolbar->appendButton('Standard', 'cancel', Helpers\Languages::_($cancel), "participants.cancel", false);
	}
}
