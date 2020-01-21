<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Joomla\CMS\Table\Table;
use Organizer\Helpers\Can;
use Organizer\Helpers\Named;
use Organizer\Helpers\OrganizerHelper;

/**
 * Class loads non-item-specific form data.
 */
class ItemModel extends BaseModel
{
	use Named;

	protected $option = 'com_organizer';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->setContext();
	}

	/**
	 * Provides a strict access check which can be overwritten by extending classes.
	 *
	 * @return bool  true if the user can access the view, otherwise false
	 */
	protected function allowView()
	{
		return Can::administrate();
	}
}
