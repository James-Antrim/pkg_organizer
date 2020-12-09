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
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class retrieves information for a filtered set of colors.
 */
class ContactTracking extends ListModel
{
	protected $defaultLimit = 0;

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	/*protected function getListQuery()
	{
		/*$then  = date('Y-m-d', strtotime('-28 days'));
		$query = $this->_db->getQuery(true);
		$query->select('bo.id AS bookingID, bl.date, bl.startTime, bl.endTime')
			->select('ipa.participantID, ipe.personID, u.id AS userID, pa.id AS uParticipantID')
			->from('#__organizer_bookings AS bo')
			->innerJoin('#__organizer_instances AS i ON i.unitID = bo.unitID AND i.blockID = bo.blockID')
			->innerJoin('#__organizer_blocks AS bl ON bl.id = bo.blockID')
			->innerJoin('#__organizer_instance_participants AS ipa ON ipa.instanceID = i.id')
			->innerJoin('#__organizer_instance_persons AS ipe ON ipe.instanceID = i.id')
			->innerJoin('#__organizer_persons AS pe ON pe.id = ipe.personID')
			->leftJoin('#__users AS u ON u.username = pe.username')
			->leftJoin('#__organizer_participants AS pa ON pa.id = u.id')
			->where("bl.date > '$then'")
			->order('bl.date DESC, bl.startTime DESC');

		// TODO filter for ipa.attended

		$search = trim($this->state->get('filter.search', ''));

		// Force an empty result set if no search terms have been entered
		if (empty($search))
		{
			$query->where('bo.id = 0');
		}
		else
		{
			$userName = $query->quote($search, true);
			$wherray  = ["pe.username = $userName"];

			if ($participantID = UserHelper::getUserId($search))
			{
				$wherray[] = "ipa.participantID = $participantID";
			}

			$where = implode(' OR ', $wherray);
			$query->where("($where)");
		}

		echo "<pre>" . print_r((string) $query, true) . "</pre>";

		return $query;
	}*/

	/**
	 * @inheritdoc
	 */
	/*public function getItems()
	{
		$results      = parent::getItems();
		$bookings     = [];
		$participants = [];
		$persons      = [];
		$users        = [];

		foreach ($results as $result)
		{
			if (empty($participants[$result['participantID']]))
			{
				$participant = new Tables\Participants();
				if ($participant->load($result['participantID']))
				{
					$name = $participant->forename ? "$participant->surname, $participant->forename" : $participant->surname;

					$participants[$result['participantID']] = $name;
				}
				else
				{
					continue;
				}
			}

			$participantIndex = $participants[$result['participantID']];
			$pUserID          = $result['participantID'];

			if ($result['uParticipantID'])
			{
				if (empty($participants[$result['uParticipantID']]))
				{
					$participant = new Tables\Participants();
					if ($participant->load($result['uParticipantID']))
					{
						$name = $participant->forename ? "$participant->surname, $participant->forename" : $participant->surname;

						$participants[$result['uParticipantID']] = $name;
					}
					else
					{
						$participants[$result['uParticipantID']] = '';
					}
				}

				$personIndex = $participants[$result['uParticipantID']];
				$pUserID     = $result['uParticipantID'];
			}
			elseif ($result['userID'])
			{
				if (empty($users[$result['userID']]))
				{
					if ($pieces = Helpers\Users::resolveUserName($result['userID']))
					{
						$name = $pieces['forename'] ? "{$pieces['surname']}, {$pieces['forename']}" : $pieces['surname'];

						$users[$result['userID']] = $name;
					}
					else
					{
						$users[$result['userID']] = '';
					}
				}

				$personIndex = $users[$result['userID']];
				$pUserID     = $result['userID'];
			}
			else
			{
				if (empty($persons[$result['personID']]))
				{
					$person = new Tables\Persons();
					if ($person->load($result['personID']))
					{
						$name = $person->forename ? "$person->surname, $person->forename" : $person->surname;

						$persons[$result['personID']] = $name;
					}
					else
					{
						$persons[$result['personID']] = '';
					}
				}

				$personIndex = $persons[$result['personID']];
				$pUserID     = 0;
			}
		}

		$query = $this->_db->getQuery(true);
		$query->select("bo.id AS bookingID, bl.date, bl.startTime, bl.endTime, ipa.participantID, ipe.personID")
			->from('#__organizer_bookings AS bo')
			->innerJoin('#__organizer_instances AS i ON i.unitID = bo.unitID AND i.blockID = bo.blockID')
			->innerJoin('#__organizer_blocks AS bl ON bl.id = bo.blockID')
			->innerJoin('#__organizer_instance_participants AS ipa ON ipa.instanceID = i.id')
			->innerJoin('#__organizer_instance_persons AS ipe ON ipe.instanceID = i.id')
			->innerJoin('#__organizer_persons AS pe ON pe.id = ipe.personID')
			->order('bl.date DESC, bl.startTime DESC');

		echo "<pre>" . print_r($results, true) . "</pre>";
		die;

		return $bookings ? $bookings : [];
	}*/

	/**
	 * @inheritDoc
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState();

		// Attempt to resolve the search
		// By username for both
		// By names for both

		$filters = Helpers\Input::getFilterItems();

		if (!$search = $filters->get('search'))
		{
			return;
		}

		$userInput = $this->state->get('filter.search', '');
		if (empty($userInput))
		{
			return;
		}
		$search  = '%' . $this->_db->escape($userInput, true) . '%';
		$wherray = [];
		foreach ($columnNames as $name)
		{
			$wherray[] = "$name LIKE '$search'";
		}
		$where = implode(' OR ', $wherray);
		$query->where("($where)");
	}
}
