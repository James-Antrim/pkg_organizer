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
use Organizer\Adapters\Queries\QueryMySQLi;
use Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of participants.
 */
class Participants extends ListModel
{
	protected $defaultOrdering = 'fullName';

	protected $filter_fields = ['attended', 'duplicates', 'paid', 'programID'];

	/**
	 * @inheritDoc
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		if (!$this->adminContext)
		{
			$this->defaultLimit = 0;
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getListQuery()
	{
		$tag = Helpers\Languages::getTag();
		/* @var QueryMySQLi $query */
		$query = Database::getQuery();

		$programParts = ["pr.name_$tag", "' ('", 'd.abbreviation', "' '", 'pr.accredited', "')'"];
		$query->select('DISTINCT pa.id, pa.*, u.email')
			->select($query->concatenate(['pa.surname', "', '", 'pa.forename'], '') . ' AS fullName')
			->from('#__organizer_participants AS pa')
			->innerJoin('#__users AS u ON u.id = pa.id')
			->leftJoin('#__organizer_programs AS pr ON pr.id = pa.programID')
			->leftJoin('#__organizer_degrees AS d ON d.id = pr.degreeID')
			->select($query->concatenate($programParts, '') . ' AS program');

		$this->setSearchFilter($query, ['pa.forename', 'pa.surname', 'pr.name_de', 'pr.name_en']);
		$this->setValueFilters($query, ['programID']);

		if ($this->state->get('filter.duplicates'))
		{
			$likePAFN   = $query->concatenate(["'%'", 'TRIM(pa.forename)', "'%'"], '');
			$likePA2FN  = $query->concatenate(["'%'", 'TRIM(pa2.forename)', "'%'"], '');
			$conditions = "((pa.forename LIKE $likePA2FN OR pa2.forename LIKE $likePAFN)";

			$conditions .= " AND ";

			$likePASN   = $query->concatenate(["'%'", 'TRIM(pa.surname)', "'%'"], '');
			$likePA2SN  = $query->concatenate(["'%'", 'TRIM(pa2.surname)', "'%'"], '');
			$conditions .= "(pa.surname LIKE $likePA2SN OR pa2.surname LIKE $likePASN))";
			$query->leftJoin("#__organizer_participants AS pa2 ON $conditions")
				->where('pa.id != pa2.id')
				->group('pa.id');
		}

		$this->setOrdering($query);

		return $query;
	}

	/**
	 * @inheritDoc
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState($ordering, $direction);

		if ($courseID = Helpers\Input::getFilterID('course'))
		{
			$this->setState('filter.courseID', $courseID);
		}
	}
}
