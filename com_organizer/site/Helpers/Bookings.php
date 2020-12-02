<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use Organizer\Adapters\Database;
use Organizer\Tables;

/**
 * Provides functions for XML instance validation and modeling.
 */
class Bookings extends ResourceHelper
{
	/**
	 * Creates a display of formatted times for a booking.
	 *
	 * @param   int  $bookingID  the id of the booking entry
	 *
	 * @return string the dates to display
	 */
	public static function getDateTimeDisplay(int $bookingID)
	{
		$booking = new Tables\Bookings();
		if (!$booking->load($bookingID))
		{
			return '';
		}

		$block = new Tables\Blocks();
		if (!$block->load($booking->blockID))
		{
			return '';
		}

		$date      = Dates::formatDate($block->date);
		$endTime   = Dates::formatEndTime($booking->endTime);
		$startTime = Dates::formatTime($booking->startTime);

		return "$date $startTime - $endTime";
	}

	/**
	 * Retrieves a list of instance IDs for instances which fulfill the requirements.
	 *
	 * @param   int  $bookingID  the id of the booking entry
	 *
	 * @return array the ids matching the conditions
	 */
	public static function getInstanceIDs(int $bookingID)
	{
		$query = Database::getQuery();
		$query->select('DISTINCT i.id')
			->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_bookings AS b ON b.blockID = i.blockID and b.unitID = i.unitID')
			->where("b.id = $bookingID")
			->order('i.id');
		Database::setQuery($query);

		return Database::loadIntColumn();
	}

	/**
	 * Gets the localized name of the events associated with the booking and the name of the booking's method.
	 *
	 * @param   int  $bookingID  the id of the booking entry
	 *
	 * @return array
	 */
	public static function getNames(int $bookingID)
	{
		$names = [];

		foreach (self::getInstanceIDs($bookingID) as $instanceID)
		{
			if ($name = Instances::getName($instanceID))
			{
				$names[] = $name;
			}
		}

		$names = array_unique($names);
		asort($names);

		return $names;
	}

	/**
	 * Gets the count of participants who attended the booking.
	 *
	 * @param   int  $bookingID
	 *
	 * @return int the number of attending participants
	 */
	public static function getParticipantCount(int $bookingID)
	{
		$query = Database::getQuery();
		$query->select('COUNT(DISTINCT ip.participantID)')
			->from('#__organizer_instance_participants AS ip')
			->innerJoin('#__organizer_instances AS i on i.id = ip.instanceID')
			->innerJoin('#__organizer_bookings AS b ON b.unitID = i.unitID AND b.blockID = i.blockID')
			->where('ip.attended = 1')
			->where("b.id = $bookingID");
		Database::setQuery($query);

		return Database::loadInt();
	}
}
