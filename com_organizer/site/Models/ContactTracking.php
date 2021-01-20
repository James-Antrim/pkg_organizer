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

use Organizer\Adapters\Database;
use Organizer\Helpers;
use stdClass;

/**
 * Class retrieves information for a filtered set of colors.
 */
class ContactTracking extends ListModel
{
	protected $defaultLimit = 0;

	/**
	 * Adds entries to the items structure.
	 *
	 * @param   array  &$items  the items to be displayed
	 * @param   array   $data   the data from the resource
	 *
	 * @return void
	 */
	private function addItem(array &$items, array $data)
	{
		$date  = $data['date'];
		$index = "{$data['surname']}-{$data['forename']}-{$data['username']}";

		if (empty($items[$index]))
		{
			$name            = $data['surname'];
			$name            .= $data['forename'] ? ", {$data['forename']}" : '';
			$item            = new stdClass();
			$item->address   = empty($data['address']) ? '' : $data['address'];
			$item->city      = empty($data['city']) ? '' : $data['city'];
			$item->dates     = [];
			$item->email     = empty($data['email']) ? '' : $data['email'];
			$item->person    = $name;
			$item->role      = empty($data['role']) ? '' : $data['role'];
			$item->telephone = empty($data['telephone']) ? '' : $data['telephone'];
			$item->username  = empty($data['username']) ? '' : $data['username'];
			$item->zipCode   = empty($data['zipCode']) ? '' : $data['zipCode'];

			$items[$index] = $item;
		}

		$item = $items[$index];

		if (empty($item->dates[$date]))
		{
			$item->dates[$date] = [];
		}

		if (empty($item->dates[$date][$data['name']]))
		{
			$item->dates[$date][$data['name']] = $data['minutes'];
		}
		else
		{
			$item->dates[$date][$data['name']] += $data['minutes'];
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getListQuery()
	{
		$now   = date('H:i:s');
		$then  = date('Y-m-d', strtotime("-28 days"));
		$today = date('Y-m-d');
		$query = $this->_db->getQuery(true);
		$query->select('bo.id, bo.startTime, bo.endTime')
			->select('bl.date, bl.startTime AS defaultStart, bl.endTime AS defaultEnd')
			->from('#__organizer_bookings AS bo')
			->innerJoin('#__organizer_instances AS i ON i.unitID = bo.unitID AND i.blockID = bo.blockID')
			->innerJoin('#__organizer_blocks AS bl ON bl.id = bo.blockID')
			->innerJoin('#__organizer_instance_participants AS ipa ON ipa.instanceID = i.id')
			->innerJoin('#__organizer_instance_persons AS ipe ON ipe.instanceID = i.id')
			->where("bl.date >= '$then'")
			->where("(bl.date < '$today' OR (bl.date = '$today' AND bl.endTime <= '$now'))")
			->where('ipa.attended = 1')
			->order('bl.date DESC, bl.startTime DESC')
			->group('bo.id');

		$participantID = $this->state->get('participantID', 0);
		$personID      = $this->state->get('personID', 0);

		// Force an empty result set if no search terms have been entered
		if (!$participantID and !$personID)
		{
			$query->where('bo.id = 0');
		}
		else
		{
			$wherray = [];

			if ($participantID)
			{
				$wherray[] = "ipa.participantID = $participantID";
			}

			if ($personID)
			{
				$wherray[] = "ipe.personID = $personID";
			}

			$query->where('(' . implode(' OR ', $wherray) . ')');
		}

		return $query;
	}

	/**
	 * @inheritdoc
	 */
	public function getItems(): array
	{
		$items         = [];
		$participantID = $this->state->get('participantID', 0);
		$personID      = $this->state->get('personID', 0);
		$tag           = Helpers\Languages::getTag();

		$participantQuery = Database::getQuery();
		$participantQuery->select('p.id AS participantID, forename, surname, username, address, city, email, telephone, zipCode')
			->from('#__organizer_participants AS p')
			->innerJoin('#__users AS u ON u.id = p.id')
			->innerJoin('#__organizer_instance_participants AS ip ON ip.participantID = p.id')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->innerJoin('#__organizer_bookings AS b ON b.unitID = i.unitID AND i.blockID = b.blockID');

		$personQuery = Database::getQuery();
		$personQuery->select('pr.forename AS defaultForename, pr.surname AS defaultSurname, pr.username')
			->select('pt.forename, pt.surname, address, city, email, telephone, zipCode')
			->select("r.name_$tag AS role")
			->from('#__organizer_persons AS pr')
			->innerJoin('#__organizer_instance_persons AS ip ON ip.personID = pr.id')
			->innerJoin('#__organizer_roles AS r ON r.id = ip.roleID')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->innerJoin('#__organizer_bookings AS b ON b.unitID = i.unitID AND i.blockID = b.blockID')
			->leftJoin('#__users AS u ON u.username = pr.username')
			->leftJoin('#__organizer_participants AS pt ON pt.id = u.id');

		foreach (parent::getItems() as $booking)
		{
			$bData         = ['id' => $booking->id, 'name' => Helpers\Bookings::getName($booking->id)];
			$bData['date'] = $booking->date;
			$endTime       = $booking->endTime ? $booking->endTime : $booking->defaultEnd;
			$startTime     = $booking->startTime ? $booking->startTime : $booking->defaultStart;

			// +60 Secondds to be inclusive of the last minute.
			$bData['minutes'] = ceil((strtotime($endTime) + 60 - strtotime($startTime)) / 60);

			$participantQuery->clear('where');
			$participantQuery->where("b.id = $booking->id")
				->where('ip.attended = 1')
				->where("ip.participantID != $participantID");
			Database::setQuery($participantQuery);

			foreach (Database::loadAssocList() as $person)
			{
				$data              = [];
				$data['forename']  = $person['forename'];
				$data['surname']   = $person['surname'];
				$data['username']  = $person['username'];
				$data['address']   = $person['address'];
				$data['city']      = $person['city'];
				$data['email']     = $person['email'];
				$data['telephone'] = $person['telephone'];
				$data['zipCode']   = $person['zipCode'];

				$this->addItem($items, array_merge($bData, $data));
			}

			$personQuery->clear('where');
			$personQuery->where("b.id = $booking->id")
				->where("ip.delta != 'removed'")
				->where("ip.personID != $personID");
			Database::setQuery($personQuery);

			foreach (Database::loadAssocList() as $person)
			{
				$data              = [];
				$data['forename']  = $person['forename'] ? $person['forename'] : $person['defaultForename'];
				$data['surname']   = $person['surname'] ? $person['surname'] : $person['defaultSurname'];
				$data['username']  = $person['username'] ? $person['username'] : '';
				$data['address']   = $person['address'] ? $person['address'] : '';
				$data['city']      = $person['city'] ? $person['city'] : '';
				$data['email']     = $person['email'] ? $person['email'] : '';
				$data['telephone'] = $person['telephone'] ? $person['telephone'] : '';
				$data['zipCode']   = $person['zipCode'] ? $person['zipCode'] : '';
				$data              = array_merge($bData, $data);

				if ($person['role'])
				{
					$data['name'] .= ' (*)';
				}

				$this->addItem($items, array_merge($bData, $data));
			}
		}

		if ($items)
		{
			ksort($items);
		}
		elseif ($search = $this->state->get('filter.search'))
		{
			$none = sprintf(Helpers\Languages::_('ORGANIZER_EMPTY_CONTACT_RESULT_SET'), $search);
			Helpers\OrganizerHelper::message($none, 'notice');
		}

		return $items;
	}

	/**
	 * Performs final the final integrity check between the participants and persons result sets.
	 *
	 * @param   array  $participantIDs  the participants id results
	 * @param   array  $personIDs       the persons id results
	 *
	 * @return void
	 */
	private function finalCheck(array $participantIDs, array $personIDs)
	{
		$participantID = $participantIDs ? $participantIDs[0] : 0;
		$personID      = $personIDs ? $personIDs[0] : 0;
		$filters       = Helpers\Input::getFilterItems();
		$search        = $filters->get('search');
		$tooMany       = sprintf(Helpers\Languages::_('ORGANIZER_TOO_MANY_RESULTS'), $search);

		// User and person resource usernames don't resolve to the same physical person.
		if ($participantID and $personID and (int) $personID !== Helpers\Persons::getIDByUserID($participantID))
		{
			$this->forceEmpty();
			Helpers\OrganizerHelper::message($tooMany, 'notice');

			return;
		}

		$this->state->set('participantID', $participantID);
		$this->state->set('personID', $personID);
	}

	/**
	 * Sets selection criteria to empty values to avoid positive results from previous queries.
	 *
	 * @return void
	 */
	private function forceEmpty()
	{
		$this->state->set('participantID', 0);
		$this->state->set('personID', 0);
	}

	/**
	 * @inheritDoc
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState();

		$filters = Helpers\Input::getFilterItems();

		if (!$search = $filters->get('search'))
		{
			$this->forceEmpty();

			return;
		}

		$tooMany = sprintf(Helpers\Languages::_('ORGANIZER_TOO_MANY_RESULTS'), $search);
		$search  = explode(' ', $search);

		// Users/participants by username
		$query = Database::getQuery();
		$query->select('p.id')
			->from('#__organizer_participants AS p')
			->innerJoin('#__users AS u ON u.id = p.id')
			->where("(u.username = '" . implode("' OR u.username = '", $search) . "')");
		Database::setQuery($query);

		if ($participantIDs = Database::loadColumn() and count($participantIDs) > 1)
		{
			$this->forceEmpty();
			Helpers\OrganizerHelper::message($tooMany, 'notice');

			return;
		}

		// Person by username
		$query = Database::getQuery();
		$query->select('id')
			->from('#__organizer_persons')
			->where("(username = '" . implode("' OR username = '", $search) . "')");
		Database::setQuery($query);

		if ($personIDs = Database::loadColumn() and count($personIDs) > 1)
		{
			$this->forceEmpty();
			Helpers\OrganizerHelper::message($tooMany, 'notice');

			return;
		}

		if ($participantIDs or $personIDs)
		{
			$this->finalCheck($participantIDs, $personIDs);

			return;
		}

		// Participants by full name
		$subQuery = Database::getQuery();
		$subQuery->select('id, ' . $subQuery->concatenate(['surname', 'forename'], ' ') . ' AS fullName')
			->from('#__organizer_participants');
		$query = Database::getQuery();
		$query->select('p1.id, p2.fullname')
			->from('#__organizer_participants AS p1')
			->innerJoin("($subQuery) AS p2 ON p2.id = p1.id")
			->where("(p2.fullName LIKE '%" . implode("%' AND p2.fullName LIKE '%", $search) . "%')");
		Database::setQuery($query);

		if ($participantIDs = Database::loadColumn() and count($participantIDs) > 1)
		{
			$this->forceEmpty();
			Helpers\OrganizerHelper::message($tooMany, 'notice');

			return;
		}

		// Persons by full name
		$subQuery = Database::getQuery();
		$subQuery->select('id, ' . $subQuery->concatenate(['surname', 'forename'], ' ') . ' AS fullName')
			->from('#__organizer_persons');
		$query = Database::getQuery();
		$query->select('p1.id, p2.fullname')
			->from('#__organizer_persons AS p1')
			->innerJoin("($subQuery) AS p2 ON p2.id = p1.id")
			->where("(p2.fullName LIKE '%" . implode("%' AND p2.fullName LIKE '%", $search) . "%')");
		Database::setQuery($query);

		if ($personIDs = Database::loadColumn() and count($personIDs) > 1)
		{
			$this->forceEmpty();
			Helpers\OrganizerHelper::message($tooMany, 'notice');

			return;
		}

		if ($participantIDs or $personIDs)
		{
			$this->finalCheck($participantIDs, $personIDs);

			return;
		}

		// No resolution
		$this->forceEmpty();
	}
}
