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
use Joomla\CMS\Form\Form;
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
	 * Gets the booking table entry, and fills appropriate form field values.
	 *
	 * @return Tables\Bookings
	 */
	public function getBooking()
	{
		$bookingID = Helpers\Input::getID();
		$booking   = new Tables\Bookings();
		$booking->load($bookingID);

		return $booking;
	}

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$query = parent::getListQuery();
		$tag   = Helpers\Languages::getTag();

		$this->setValueFilters($query, ['attended', 'paid']);

		$bookingID = Helpers\Input::getID();
		$query->select("e.name_$tag AS event")
			->innerJoin('#__organizer_instance_participants AS ip ON ip.participantID = pa.id')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.id')
			->innerJoin('#__organizer_events AS e ON e.id = i.eventID')
			->innerJoin('#__organizer_bookings AS b ON b.blockID = i.blockID AND b.unitID = i.unitID')
			->where("b.id = $bookingID");

		//->where('ip.attended = 1');

		return $query;
	}

	/**
	 * Wrapper method for Joomla\CMS\MVC\Model\ListModel which has a mixed return type.
	 *
	 * @return  array  An array of data items on success.
	 */
	public function getItems()
	{
		foreach ($items = parent::getItems() as $key => $item)
		{
			$item->complete = true;

			$columns = ['address', 'city', 'forename', 'surname', 'telephone', 'zipCode'];
			foreach ($columns as $column)
			{
				if (empty($item->$column))
				{
					$item->complete = false;
					continue 2;
				}
			}
		}

		return $items ? $items : [];
	}

	/**
	 * Method to get a form object.
	 *
	 * @param   string          $name     The name of the form.
	 * @param   string          $source   The form source. Can be XML string if file flag is set to false.
	 * @param   array           $options  Optional array of options for the form creation.
	 * @param   boolean         $clear    Optional argument to force load a new form.
	 * @param   string|boolean  $xpath    An optional xpath to search for the fields.
	 *
	 * @return  Form|boolean  Form object on success, False on error.
	 * @noinspection PhpMissingParamTypeInspection
	 */
	protected function loadForm($name, $source = null, $options = array(), $clear = false, $xpath = false)
	{
		$form = parent::loadForm($name, $source, $options, $clear, $xpath);

		$booking = $this->getBooking();
		$form->setValue('notes', 'supplement', $booking->notes);

		return $form;
	}

	/**
	 * Saves supplemental information about the entry.
	 *
	 * @return bool
	 */
	public function supplement()
	{
		$bookingID  = Helpers\Input::getID();
		$supplement = Helpers\Input::getSupplementalItems();

		if (!$bookingID or !$notes = $supplement->get('notes'))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_400');

			return false;
		}

		$booking = new Tables\Bookings();

		if (!$booking->load($bookingID))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_412');

			return false;
		}

		$booking->notes = $notes;

		return $booking->store();
	}
}
