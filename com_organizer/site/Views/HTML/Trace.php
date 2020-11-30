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
 * Class loads persistent information a filtered set of colors into the display context.
 */
class Trace extends ListView
{
	protected $rowStructure = ['checkbox' => '', 'person' => 'value', 'event' => 'list'];

	/**
	 * Adds a toolbar and title to the view.
	 *
	 * @return void  sets context variables
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_("ORGANIZER_TRACE"), 'list-2');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'envelope', Helpers\Languages::_('ORGANIZER_NOTIFY'), "", false);
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		if (!Helpers\Can::manageTheseOrganizations())
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Function to set the object's headers property
	 *
	 * @return void sets the object headers property
	 */
	public function setHeaders()
	{
		$headers = [
			'checkbox' => '',
			'person'   => Helpers\Languages::_('ORGANIZER_PERSON'),
			'event'    => Helpers\Languages::_('ORGANIZER_EVENT')
		];

		$this->headers = $headers;
	}
}
