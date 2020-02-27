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

use Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of (lesson) methods into the display context.
 */
class Methods extends ListView
{
	protected $rowStructure = ['checkbox' => '', 'abbreviation' => 'link', 'name' => 'link'];

	/**
	 * Function to set the object's headers property
	 *
	 * @return void sets the object headers property
	 */
	public function setHeaders()
	{
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox'     => '',
			'abbreviation' => Helpers\HTML::sort('ABBREVIATION', 'abbreviation', $direction, $ordering),
			'name'         => Helpers\HTML::sort('NAME', 'name', $direction, $ordering)
		];

		$this->headers = $headers;
	}
}
