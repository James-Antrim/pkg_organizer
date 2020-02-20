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
	protected function addToolBar()
	{
		$new   = empty($this->item->id);
		$title = $new ?
			Helpers\Languages::_('ORGANIZER_PARTICIPANT_NEW') : Helpers\Languages::_('ORGANIZER_PARTICIPANT_EDIT');
		Helpers\HTML::setTitle($title, 'user');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'save', Helpers\Languages::_('ORGANIZER_SAVE'), 'participants.save', false);
		$cancelText = $new ?
			Helpers\Languages::_('ORGANIZER_CANCEL') : Helpers\Languages::_('ORGANIZER_CLOSE');
		$toolbar->appendButton('Standard', 'cancel', $cancelText, 'participants.cancel', false);
	}
}
