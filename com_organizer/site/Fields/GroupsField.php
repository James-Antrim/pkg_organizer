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
use stdClass;

/**
 * Class creates a select box for plan programs.
 */
class GroupsField extends OptionsField
{
	use Dependent;

	/**
	 * @var  string
	 */
	protected $type = 'Groups';

	/**
	 * Returns a select box where resource attributes can be selected
	 *
	 * @return stdClass[] the options for the select box
	 */
	protected function getOptions(): array
	{
		$options = parent::getOptions();
		$groups  = Helpers\Groups::getOptions();

		return array_merge($options, $groups);
	}
}
