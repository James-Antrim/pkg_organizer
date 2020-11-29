<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\XLS;

use Exception;

/**
 * Class creates a XLS file for the display of the filtered schedule information.
 */
class Rooms extends BaseView
{
	/**
	 * @inheritdoc
	 */
	public function __construct()
	{
		parent::__construct();

		$properties = $this->getProperties();
		$properties->setDescription($this->layout->getDescription());
		$properties->setTitle($this->layout->getTitle());
	}

	/**
	 * @inheritdoc
	 */
	public function display()
	{
		$this->layout->fill();
		parent::render();
	}
}
