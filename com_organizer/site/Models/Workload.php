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

use Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of participants.
 */
class Workload extends FormModel
{
	/**
	 * @inheritDoc
	 */
	protected function authorize()
	{
		if (!Helpers\Users::getID())
		{
			Helpers\OrganizerHelper::error(401);
		}

		if (!$organizationIDs = Helpers\Can::manageTheseOrganizations())
		{
			Helpers\OrganizerHelper::error(403);
		}

		// TODO if personID and person not associated with an organization 403
	}

	/**
	 * @inheritDoc
	 */
	protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = '')
	{
		$options['load_data'] = true;

		return parent::loadForm($name, $source, $options, $clear, $xpath);
	}

	/**
	 * @inheritDoc
	 */
	protected function loadFormData(): array
	{
		$personID = Helpers\Input::getInt('personID');
		$termID = Helpers\Input::getInt('termID', Helpers\Terms::getCurrentID());

		return ['personID' => $personID, 'termID' => $termID];
	}
}
