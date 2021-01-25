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
 * Class loads the schedule upload form into display context.
 */
class BookingEdit extends EditView
{
	/**
	 * @inheritDoc
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_('ORGANIZER_UPLOAD_PARTICIPANTS'), 'users');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton(
			'Standard',
			'upload',
			Helpers\Languages::_('ORGANIZER_UPLOAD'),
			'bookings.upload',
			false
		);
		$toolbar->appendButton(
			'Standard',
			'cancel',
			Helpers\Languages::_('ORGANIZER_CANCEL'),
			'bookings.cancel',
			false
		);
	}
}
