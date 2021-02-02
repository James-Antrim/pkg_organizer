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

/**
 * Class retrieves information for a filtered set of participants.
 */
class Workload extends FormModel
{
	private $organizationIDs;

	public $programs = [];

	/**
	 * @inheritDoc
	 */
	protected function authorize()
	{
		if (!Helpers\Users::getID())
		{
			Helpers\OrganizerHelper::error(401);
		}

		if (!$this->organizationIDs = Helpers\Can::manageTheseOrganizations())
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = '')
	{
		$options['load_data'] = true;

		// Set the organizationID to the input before the options for person are loaded
		$organizationID = Helpers\Input::getInt('organizationID', $this->organizationIDs[0]);
		Helpers\Input::set('organizationID', $organizationID);

		return parent::loadForm($name, $source, $options, $clear, $xpath);
	}

	/**
	 * @inheritDoc
	 */
	protected function loadFormData(): array
	{
		$organizationID = Helpers\Input::getInt('organizationID', $this->organizationIDs[0]);
		$personID       = Helpers\Input::getInt('personID');
		$termID         = Helpers\Input::getInt('termID', Helpers\Terms::getCurrentID());

		return ['organizationID' => $organizationID, 'personID' => $personID, 'termID' => $termID];
	}

	/**
	 * Set dynamic data.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->setPrograms();
	}

	/**
	 * Sets program data.
	 *
	 * @return void
	 */
	private function setPrograms()
	{
		$tag   = Helpers\Languages::getTag();
		$query = Database::getQuery();
		$query->select("p.id, categoryID, p.degreeID, p.name_$tag AS program, fee, frequencyID, nc, special")
			->select('d.abbreviation AS degree')
			->from('#__organizer_programs AS p')
			->innerJoin('#__organizer_degrees AS d ON d.id = p.degreeID')
			->where('active = 1');
		Database::setQuery($query);

		$results = Database::loadAssocList();

		foreach ($results as &$program)
		{
			$organizationIDs = Helpers\Programs::getOrganizationIDs($program['id']);

			foreach (array_keys($organizations = array_flip($organizationIDs)) as $organizationID)
			{
				$organizations[$organizationID] = Helpers\Organizations::getShortName($organizationID);
			}

			asort($organizations);
			$program['organizations'] = $organizations;
			$index                    = "{$program['program']} ({$program['degree']})";
			$this->programs[$index]   = $program;
		}

		ksort($this->programs);
	}
}
