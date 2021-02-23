<?php
/**
 * @package     Organizer\Models
 * @extension   Organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use JDatabaseQuery;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Tables;

class Checkin extends FormModel
{
	/**
	 * @var array
	 */
	private $instances;

	/**
	 * @var Tables\Participants
	 */
	private $participant;

	/**
	 * @var int|null
	 */
	private $roomID;

	/**
	 * @inheritDoc
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		// Force component template
		if (Helpers\Input::getCMD('tmpl') !== 'component')
		{
			$query = Helpers\Input::getInput()->server->get('QUERY_STRING', '', 'raw') . '&tmpl=component';
			Helpers\OrganizerHelper::getApplication()->redirect(Uri::current() . "?$query");
		}

		$form    = $this->getForm();
		$session = Factory::getSession();

		if ($username = $session->get('organizer.checkin.username'))
		{
			$form->setValue('username', null, $username);
		}

		if ($code = $session->get('organizer.checkin.code') or $code = Helpers\Input::getCMD('code'))
		{
			$form->setValue('code', null, $code);
		}

		$participant = new Tables\Participants();

		if ($participantID = Helpers\Users::getID())
		{
			$participant->load($participantID);

			$form->setValue('id', null, $participantID);
			$form->setValue('address', null, $participant->address);
			$form->setValue('city', null, $participant->city);
			$form->setValue('forename', null, $participant->forename);
			$form->setValue('surname', null, $participant->surname);
			$form->setValue('telephone', null, $participant->telephone);
			$form->setValue('zipCode', null, $participant->zipCode);
		}

		$this->participant = $participant;

		$this->setInstances();
	}

	/**
	 * @inheritDoc
	 */
	protected function authorize()
	{
		if (Helpers\Input::getCMD('layout') === 'profile' and !Helpers\Users::getID())
		{
			Helpers\OrganizerHelper::error(401);
		}
	}

	/**
	 * Loads participant data for the current user.
	 *
	 * @return Tables\Participants
	 */
	public function getParticipant(): Tables\Participants
	{
		return $this->participant;
	}

	/**
	 * Gets the instances relevant to the booking and person.
	 *
	 * @return array
	 */
	public function getInstances(): array
	{
		return $this->instances;
	}

	/**
	 * Gets a query where common statements are already included.
	 *
	 * @param   int  $participantID  the id of the participant for which to find checkins
	 *
	 * @return JDatabaseQuery
	 */
	private function getQuery(int $participantID): JDatabaseQuery
	{
		$today = date('Y-m-d');
		$query = Database::getQuery();
		$query->select('instanceID, roomID, seat')
			->from('#__organizer_instance_participants AS ip')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
			->where("ip.participantID = $participantID")
			->where("ip.attended = 1")
			->where("b.date = '$today'");

		return $query;
	}

	/**
	 * Gets the instances relevant to the booking and person.
	 *
	 * @return int|null
	 */
	public function getRoomID(): ?int
	{
		return $this->roomID;
	}

	/**
	 * Gets the instance related information.
	 *
	 * @return void
	 */
	public function setInstances()
	{
		$instances = [];
		$roomID    = null;

		if (!$participantID = $this->participant->id)
		{
			$this->instances = $instances;
			$this->roomID    = $roomID;

			return;
		}

		$now = date('H:i:s');

		// Ongoing
		$query = $this->getQuery($participantID);
		$query->where("b.startTime <= '$now'")->where("b.endTime >= '$now'");
		Database::setQuery($query);

		if (!$participation = Database::loadAssocList())
		{
			// Upcoming
			$then  = date('H:i:s', strtotime('+60 minutes'));
			$query = $this->getQuery($participantID);
			$query->where("b.startTime >= '$now'")->where("b.startTime <= '$then'");
			Database::setQuery($query);

			$participation = Database::loadAssocList();
		}

		$form = $this->getForm();

		foreach ($participation as $index => $entry)
		{
			$instanceID        = $entry['instanceID'];
			$instances[$index] = Helpers\Instances::getInstance($instanceID);
			$form->setValue('instanceID', null, $instanceID);

			if (empty($roomID))
			{
				$roomID         = $entry['roomID'];
				$roomIDs        = Helpers\Instances::getRoomIDs($instanceID);
				$selectedRoomID = count($roomIDs) === 1 ? reset($roomIDs) : $entry['roomID'];
				$form->setValue('roomID', null, $selectedRoomID);
			}

			if ($roomID)
			{
				$instances[$index]['room'] = Helpers\Rooms::getName($roomID);
				$instances[$index]['seat'] = $entry['seat'];
			}
		}

		$this->instances = $instances;
		$this->roomID    = $roomID;
	}
}