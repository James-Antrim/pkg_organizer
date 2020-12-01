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
 * Class loads persistent information a filtered set of room types into the display context.
 */
class Roomtypes extends ListView
{
	protected $rowStructure = [
		'checkbox'    => '',
		'code'        => 'link',
		'name'        => 'link',
		'minCapacity' => 'value',
		'maxCapacity' => 'value'
		//'roomCount'   => 'value'
	];

	/**
	 * @inheritdoc
	 */
	protected function authorize()
	{
		if (!Helpers\Can::manage('facilities'))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function setHeaders()
	{
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox'    => '',
			'code'        => Helpers\HTML::sort('UNTIS_ID', 'code', $direction, $ordering),
			'name'        => Helpers\HTML::sort('NAME', 'name', $direction, $ordering),
			'minCapacity' => Helpers\HTML::sort('MIN_CAPACITY', 'minCapacity', $direction, $ordering),
			'maxCapacity' => Helpers\HTML::sort('MAX_CAPACITY', 'maxCapacity', $direction, $ordering)
			//'roomCount'   => Helpers\HTML::sort('ROOM_COUNT', 'roomCount', $direction, $ordering)
		];

		$this->headers = $headers;
	}
}
