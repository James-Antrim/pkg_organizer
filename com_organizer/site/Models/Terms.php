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

use Organizer\Helpers\Languages;

/**
 * Class retrieves information for a filtered set of degrees.
 */
class Terms extends ListModel
{
	/**
	 * @inheritDoc
	 */
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = ['name', 'abbreviation', 'code'];
		}

		parent::__construct($config);
	}

	/**
	 * @inheritDoc
	 */
	protected function getListQuery()
	{
		$tag   = Languages::getTag();
		$query = $this->_db->getQuery(true);
		$query->select("id, fullName_$tag as term, startDate, endDate")
			->from('#__organizer_terms')
			->order('startDate');

		return $query;
	}
}
