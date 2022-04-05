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
 * Class creates a select box for predefined colors.
 */
class UnitStatesField extends ColoredOptionsField
{
	/**
	 * Type
	 *
	 * @var    String
	 */
	protected $type = 'UnitStates';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions(): array
	{
		return [
			(object) [
				'text'  => Helpers\Languages::_('ORGANIZER_ALL_UNITS'),
				'value' => ''
			],
			(object) [
				'text'  => Helpers\Languages::_('ORGANIZER_CURRENT_UNITS'),
				'value' => 1
			],
			(object) [
				'text'  => Helpers\Languages::_('ORGANIZER_CHANGED_UNITS'),
				'value' => 4
			],
			(object) [
				'style' => "background-color:#a0cb5b;",
				'text'  => Helpers\Languages::_('ORGANIZER_NEW_UNITS'),
				'value' => 2
			],
			(object) [
				'style' => "background-color:#cd8996;",
				'text'  => Helpers\Languages::_('ORGANIZER_REMOVED_UNITS'),
				'value' => 3
			]
		];
	}
}
