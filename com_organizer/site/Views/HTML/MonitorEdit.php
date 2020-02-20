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
 * Class loads the monitor form into display context.
 */
class MonitorEdit extends EditView
{
	/**
	 * Adds joomla toolbar elements to the view context
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		$new   = empty($this->item->id);
		$title = $new ?
			Helpers\Languages::_('ORGANIZER_MONITOR_NEW') : Helpers\Languages::_('ORGANIZER_MONITOR_EDIT');
		Helpers\HTML::setTitle($title, 'screen');
		$toolbar   = Toolbar::getInstance();
		$applyText = $new ? Helpers\Languages::_('ORGANIZER_CREATE') : Helpers\Languages::_('ORGANIZER_APPLY');
		$toolbar->appendButton('Standard', 'apply', $applyText, 'monitors.apply', false);
		$toolbar->appendButton('Standard', 'save', Helpers\Languages::_('ORGANIZER_SAVE'), 'monitors.save', false);
		$toolbar->appendButton(
			'Standard',
			'save-new',
			Helpers\Languages::_('ORGANIZER_SAVE2NEW'),
			'monitors.save2new',
			false
		);
		$cancelText = $new ? Helpers\Languages::_('ORGANIZER_CANCEL') : Helpers\Languages::_('ORGANIZER_CLOSE');
		$toolbar->appendButton('Standard', 'cancel', $cancelText, 'monitors.cancel', false);
	}
}
