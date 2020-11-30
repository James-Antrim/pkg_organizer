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

use Organizer\Adapters;
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
		$instanceIDs = self::getInstanceIDs($bookingID);
		$instance    = new Tables\Instances();
		if (!$instance->load($instanceIDs[0]))
		{
			return '';
		}

		$block = new Tables\Blocks();
		if (!$block->load($instance->blockID))
		{
			return '';
		}

		$date      = Dates::formatDate($block->date);
		$endTime   = Dates::formatEndTime($block->endTime);
		$startTime = Dates::formatTime($block->startTime);

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
		$query = Adapters\Database::getQuery();
		$query->select('DISTINCT i.id')
			->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_bookings AS b ON b.blockID = i.blockID and b.unitID = i.unitID')
			->where("b.id = $bookingID")
			->order('i.id');
		Adapters\Database::setQuery($query);

		return Adapters\Database::loadIntColumn();
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
}
