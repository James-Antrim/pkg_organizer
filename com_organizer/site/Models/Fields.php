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
 * Class retrieves information for a filtered set of fields (of expertise).
 */
class Fields extends ListModel
{
	protected $defaultOrdering = 'name';

	protected $filter_fields = ['colorID', 'organizationID'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$tag   = Helpers\Languages::getTag();
		$query = $this->_db->getQuery(true);

		$query->select("DISTINCT f.id, code, f.name_$tag AS name")->from('#__organizer_fields AS f');

		$this->setSearchFilter($query, ['f.name_de', 'f.name_en', 'code']);

		$colorID        = Helpers\Input::getFilterID('color');
		$organizationID = Helpers\Input::getFilterID('organization');
		if ($colorID or $organizationID)
		{
			if ($colorID === self::NONE or $organizationID === self::NONE)
			{
				$query->leftJoin('#__organizer_field_colors AS fc ON fc.fieldID = f.id');
			}
			else
			{
				$query->innerJoin('#__organizer_field_colors AS fc ON fc.fieldID = f.id');
			}

			if ($colorID)
			{
				$colorFilter = $colorID === self::NONE ? 'colorID IS NULL' : "colorID = $colorID";
				$query->where($colorFilter);
			}

			if ($organizationID)
			{
				$organizationFilter = $organizationID === self::NONE ?
					'organizationID IS NULL' : "organizationID = $organizationID";
				$query->where($organizationFilter);
			}
		}

		$this->setOrdering($query);

		return $query;
	}
}
