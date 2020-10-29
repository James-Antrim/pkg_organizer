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

/**
 * Class retrieves information for a filtered set of participants.
 */
class InstanceParticipants extends Participants
{
	protected $defaultOrdering = 'fullName';

	protected $filter_fields = ['programID'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$query = parent::getListQuery();

		$this->setValueFilters($query, ['attended', 'paid']);

		$instanceID = Helpers\Input::getID();
		$query->innerJoin('#__organizer_instance_participants AS ip ON ip.participantID = pa.id')
			->where("ip.instanceID = $instanceID");

		return $query;
	}
}
