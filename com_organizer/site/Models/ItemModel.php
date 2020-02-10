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

use Organizer\Helpers\Can;

/**
 * Class loads non-item-specific form data.
 */
class ItemModel extends BaseModel
{
	use Contextualized;

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
