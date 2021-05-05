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
 * Class creates a select box for booking instances.
 */
class BookingRoomsField extends OptionsField
{
	/**
	 * @var  string
	 */
	protected $type = 'BookingRooms';

	/**
	 * Returns an array of booking room options
	 *
	 * @return array  the pool options
	 */
	protected function getOptions(): array
	{
		$options = parent::getOptions();
		$rooms   = Helpers\Bookings::getRoomOptions(Helpers\Input::getID());

		return array_merge($options, $rooms);
	}
}
