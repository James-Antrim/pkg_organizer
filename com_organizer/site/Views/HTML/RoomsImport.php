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

use Organizer\Adapters\Toolbar;
use Organizer\Helpers\HTML;
use Organizer\Helpers\Languages;

/**
 * Class provides an interface for uploading a file containing room data.
 */
class RoomsImport extends EditView
{
	/**
	 * @inheritDoc
	 */
	protected function addToolBar()
	{
		HTML::setTitle(Languages::_('ORGANIZER_ROOMS_IMPORT'), 'upload');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton(
			'Standard',
			'upload',
			Languages::_('ORGANIZER_UPLOAD'),
			'rooms.import',
			false
		);
		$toolbar->appendButton(
			'Standard',
			'cancel',
			Languages::_('ORGANIZER_CANCEL'),
			'rooms.cancel',
			false
		);
	}
}
