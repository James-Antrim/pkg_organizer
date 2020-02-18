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

use Organizer\Helpers\OrganizerHelper;

/**
 * Class standardizes the getName function across classes.
 */
trait Contextualized
{
	/**
	 * @var string the textual context in which form information will be saved as necessary
	 */
	protected $context;

	/**
	 * Sets context variables as requested.
	 *
	 * @return void modifies object properties
	 */
	public function setContext()
	{
		if (property_exists($this, 'option'))
		{
			$this->option = 'com_organizer';
		}

		$this->context = strtolower('com_organizer.' . OrganizerHelper::getClass($this));
	}
}
