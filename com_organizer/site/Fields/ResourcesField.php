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
			Languages::_('ORGANIZER_CATEGORIES_AND_PROGRAMS') => [
				'text'  => Languages::_('ORGANIZER_CATEGORIES_AND_PROGRAMS'),
				'value' => 'cnp'
			],
			Languages::_('ORGANIZER_EVENTS_AND_SUBJECTS')     => [
				'text'  => Languages::_('ORGANIZER_EVENTS_AND_SUBJECTS'),
				'value' => 'ens'
			],
			Languages::_('ORGANIZER_GROUPS_AND_POOLS')        => [
				'text'  => Languages::_('ORGANIZER_GROUPS_AND_POOLS'),
				'value' => 'gnp'
			],
			Languages::_('ORGANIZER_ORGANIZATIONS')           => [
				'text'  => Languages::_('ORGANIZER_ORGANIZATIONS'),
				'value' => 'organizations'
			],
			Languages::_('ORGANIZER_PERSONS')                 => [
				'text'  => Languages::_('ORGANIZER_PERSONS'),
				'value' => 'persons'
			],
			Languages::_('ORGANIZER_ROOMS')                   => [
				'text'  => Languages::_('ORGANIZER_ROOMS'),
				'value' => 'rooms'
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
