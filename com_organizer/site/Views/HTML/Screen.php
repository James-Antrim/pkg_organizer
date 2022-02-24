<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Uri\Uri;
use Organizer\Adapters;

/**
 * Class loads filtered events into the display context.
 */
class Screen extends BaseView
{
	protected $layout = 'upcoming_instances';

	public $model;

	/**
	 * Loads persistent data into the view context
	 *
	 * @param   string  $tpl  the name of the template to load
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
		//https://www.thm.de/dev/organizer/?option=com_organizer&view=screen&tmpl=component&room=A20.2.11&layout=upcoming_instances
		//https://www.thm.de/dev/organizer/?option=com_organizer&view=screen&tmpl=component&room=A20.2.11&layout=current_instances
		//https://www.thm.de/dev/organizer/?option=com_organizer&view=screen&tmpl=component&room=A20.2.11&layout=file
		$this->model = $this->getModel();

		$this->setLayout($this->model->layout);

		Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/screen.css');

		parent::display($tpl);
	}
}
