<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use Organizer\Helpers;

/**
 * Class creates a select box for plan programs.
 */
class SurfacesField extends OptionsField
{
	/**
	 * @var  string
	 */
	protected $type = 'Surfaces';

	/**
	 * Returns a select box where resource attributes can be selected
	 *
	 * @return array the options for the select box
	 */
	protected function getOptions(): array
	{
		$options  = parent::getOptions();
		$surfaces = Helpers\Surfaces::getOptions();

		return array_merge($options, $surfaces);
	}
}
