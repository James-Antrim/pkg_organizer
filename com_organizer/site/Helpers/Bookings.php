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
	public static function getDateTimeDisplay(int $bookingID): string
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

		// It is enough to load a single one, because if the instance does not have an event, there is only one.
		$instance = new Tables\Instances();
		if (!$instance->load(['blockID' => $booking->blockID, 'unitID' => $booking->unitID]))
		{
			return '';
		}

		$endTime   = $booking->endTime ?: $block->endTime;
		$startTime = $booking->startTime ?: $block->startTime;
		$date      = Dates::formatDate($block->date);
		$endTime   = $instance->eventID ? Dates::formatEndTime($endTime) : Dates::formatTime($endTime);
		$startTime = Dates::formatTime($startTime);

		return "$date $startTime - $endTime";
	}

	/**
	 * Retrieves a list of instance IDs for instances which fulfill the requirements.
	 *
	 * @param   int  $bookingID  the id of the booking entry
	 *
	 * @return array the ids matching the conditions
	 */
	public static function getInstanceIDs(int $bookingID): array
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
	 * Gets instance options for the booking entry.
	 *
	 * @param   int  $bookingID  the id of the booking to get instance options for
	 *
	 * @return array
	 */
	public static function getInstanceOptions(int $bookingID): array
	{
		$options = [];

		foreach (self::getInstanceIDs($bookingID) as $instanceID)
		{
			if ($name = Instances::getName($instanceID))
			{
				$options[$name] = HTML::_('select.option', $instanceID, $name);
			}
		}

		ksort($options);

		return $options;
	}

	/**
	 * Gets the localized name of the events associated with the booking and the name of the booking's method.
	 *
	 * @param   int  $resourceID  the id of the booking entry
	 *
	 * @return string
	 */
	public static function getName(int $resourceID): string
	{
		$method = '';
		$names  = [];

		foreach (self::getInstanceIDs($resourceID) as $instanceID)
		{
			if ($name = Instances::getName($instanceID, false))
			{
				$names[] = $name;

				if (empty($method))
				{
					$method = Instances::getMethod($instanceID);
				}
			}
		}

		$names = array_unique($names);
		asort($names);
		$names = implode(', ', $names);

		// Removes potentially redundant methods which are also a part of the instance event name.
		$names .= ($method and strpos($names, $method) === false) ? " - $method" : '';

		return $names;
	}

	/**
	 * Gets the localized name of the events associated with the booking and the name of the booking's method.
	 *
	 * @param   int  $bookingID  the id of the booking entry
	 *
	 * @return array
	 */
	public static function getNames(int $bookingID): array
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
	public static function getParticipantCount(int $bookingID, int $roomID = 0): int
	{
		$query = Database::getQuery();
		$query->select('COUNT(DISTINCT ip.participantID)')
			->from('#__organizer_instance_participants AS ip')
			->innerJoin('#__organizer_instances AS i on i.id = ip.instanceID')
			->innerJoin('#__organizer_bookings AS b ON b.unitID = i.unitID AND b.blockID = i.blockID')
			->where('ip.attended = 1')
			->where("b.id = $bookingID");

		if ($roomID)
		{
			$query->where("ip.roomID = $roomID");
		}

		Database::setQuery($query);

		return Database::loadInt();
	}

	/**
	 * Gets instance options for the booking entry.
	 *
	 * @param   int  $bookingID  the id of the booking to get instance options for
	 *
	 * @return array
	 */
	public static function getRooms(int $bookingID): array
	{
		$rooms = [];

		foreach (self::getInstanceIDs($bookingID) as $instanceID)
		{
			foreach (Instances::getRoomIDs($instanceID) as $roomID)
			{
				$room = new Tables\Rooms();
				$room->load($roomID);

				if ($room->virtual)
				{
					continue;
				}

				$rooms[$roomID] = $room->name;
			}
		}

		asort($rooms);

		return $rooms;
	}

	/**
	 * Gets instance options for the booking entry.
	 *
	 * @param   int  $bookingID  the id of the booking to get instance options for
	 *
	 * @return array
	 */
	public static function getRoomOptions(int $bookingID): array
	{
		$options = [];

		foreach (self::getInstanceIDs($bookingID) as $instanceID)
		{
			foreach (Instances::getRoomIDs($instanceID) as $roomID)
			{
				$name           = Rooms::getName($roomID);
				$options[$name] = HTML::_('select.option', $roomID, $name);
			}
		}

		ksort($options);

		return $options;
	}
}
