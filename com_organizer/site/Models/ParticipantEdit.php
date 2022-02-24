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

use Joomla\CMS\MVC\Model\AdminModel;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class loads a form for editing participant data.
 */
class ParticipantEdit extends EditModel
{
	private $participantID;

	/**
	 * Checks access to edit the resource.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		if (!Helpers\Can::edit('participant', (int) $this->participantID))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   int  $participantID  The id of the primary key.
	 *
	 * @return mixed    Object on success, false on failure.
	 */
	public function getItem($participantID = 0)
	{
		$this->participantID = $participantID ? $participantID : Helpers\Input::getSelectedID(Helpers\Users::getID());

		$this->authorize();

		// Prevents duplicate execution from getForm and getItem
		if (isset($this->item->id) and ($this->item->id === $participantID))
		{
			return $this->item;
		}

		// I assume I skipped parent because of performed access checks.
		$this->item           = AdminModel::getItem($this->participantID);
		$this->item->referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');

		// New participants need the user id as the participant id
		$this->item->id = $this->item->id ? $this->item->id : $this->participantID;

		return $this->item;
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Participants A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Participants();
	}
}
