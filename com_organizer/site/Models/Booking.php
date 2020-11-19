<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use JDatabaseQuery;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class retrieves information for a filtered set of participants.
 */
class Booking extends Participants
{
	protected $defaultOrdering = 'fullName';

	protected $filter_fields = ['programID'];

	/**
	 * Creates a new entry in the booking table for the given instance.
	 *
	 * @return int the id of the booking entry
	 */
	public function add()
	{
		if (!$userID = Helpers\Users::getID())
		{
			Helpers\OrganizerHelper::error(401);
		}

		if (!$instanceID = Helpers\Input::getID())
		{
			Helpers\OrganizerHelper::error(400);
		}

		if (!Helpers\Can::manage('instance', $instanceID))
		{
			Helpers\OrganizerHelper::error(403);
		}

		$instance = new Tables\Instances();
		if (!$instance->load($instanceID))
		{
			Helpers\OrganizerHelper::error(412);
		}

		$booking = new Tables\Bookings();
		$keys    = ['blockID' => $instance->blockID, 'unitID' => $instance->unitID];

		if (!$booking->load($keys))
		{
			$hash         = hash('adler32', (int) $instance->blockID . $instance->unitID);
			$keys['code'] = substr($hash, 0, 4) . '-' . substr($hash, 4);
			$booking->save($keys);
		}

		return $booking->id;
	}

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$query = parent::getListQuery();

		$this->setValueFilters($query, ['attended', 'paid']);

		$bookingID = Helpers\Input::getID();
		$query->innerJoin('#__organizer_instance_participants AS ip ON ip.participantID = pa.id')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.id')
			->innerJoin('#__organizer_bookings AS b ON b.blockID = i.blockID AND b.unitID = i.unitID')
			->where("b.id = $bookingID");

		return $query;
	}
}
