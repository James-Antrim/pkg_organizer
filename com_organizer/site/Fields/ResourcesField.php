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

use Organizer\Helpers\HTML;
use Organizer\Helpers\Languages;
use stdClass;

/**
 * Class creates a select box for predefined colors.
 */
class ResourcesField extends ColoredOptionsField
{
	/**
	 * Type
	 *
	 * @var    String
	 */
	protected $type = 'Resources';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions(): array
	{
		$options = parent::getOptions();

		$resources = [
			Languages::_('ORGANIZER_CATEGORIES')    => [
				'text'  => Languages::_('ORGANIZER_CATEGORIES'),
				'value' => 'categories'
			],
			Languages::_('ORGANIZER_EVENTS')    => [
				'text'  => Languages::_('ORGANIZER_EVENTS'),
				'value' => 'events'
			],
			Languages::_('ORGANIZER_GROUPS')    => [
				'text'  => Languages::_('ORGANIZER_GROUPS'),
				'value' => 'groups'
			],
			Languages::_('ORGANIZER_PERSONS')    => [
				'text'  => Languages::_('ORGANIZER_PERSONS'),
				'value' => 'persons'
			],
			Languages::_('ORGANIZER_POOLS')    => [
				'text'  => Languages::_('ORGANIZER_POOLS'),
				'value' => 'pools'
			],
			Languages::_('ORGANIZER_PROGRAMS') => [
				'text'  => Languages::_('ORGANIZER_PROGRAMS'),
				'value' => 'programs'
			],
			Languages::_('ORGANIZER_ROOMS')    => [
				'text'  => Languages::_('ORGANIZER_ROOMS'),
				'value' => 'rooms'
			],
			Languages::_('ORGANIZER_SUBJECTS') => [
				'text'  => Languages::_('ORGANIZER_SUBJECTS'),
				'value' => 'subjects'
			]
		];

		ksort($resources);

		foreach ($resources as $resource)
		{
			$options[] = HTML::_('select.option', $resource['value'], $resource['text']);
		}

		return $options;
	}
}
