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

		$nameParts    = [
			Database::quoteName('pa.surname'),
			"', '",
			Database::quoteName('pa.forename'),
		];
		$programParts = [
			Database::quoteName("pr.name_$tag"),
			"' ('",
			Database::quoteName('d.abbreviation'),
			"' '",
			Database::quoteName('pr.accredited'),
			"')'"
		];
		$select       = [
			'DISTINCT pa.id',
			'pa.*',
			'u.email',
			$query->concatenate($nameParts, '') . ' AS fullName',
			$query->concatenate($programParts, '') . ' AS program'
		];

		$query->selectX($select, 'participants AS pa')
			->innerJoinX('#__users AS u', ['u.id = pa.id'])
			->leftJoinX('programs AS pr', ['pr.id = pa.programID'])
			->leftJoinX('degrees AS d', ['d.id = pr.degreeID']);

		$this->setSearchFilter($query, ['pa.forename', 'pa.surname', 'pr.name_de', 'pr.name_en']);
		$this->setValueFilters($query, ['programID']);

		if ($this->state->get('filter.duplicates'))
		{
			$forename1 = Database::quoteName('pa.forename');
			$forename2 = Database::quoteName('pa2.forename');
			$likeFN1   = $query->concatenate(["'%'", 'TRIM(' . Database::quoteName('pa.forename') . ')', "'%'"], '');
			$likeFN2   = $query->concatenate(["'%'", 'TRIM(' . Database::quoteName('pa2.forename') . ')', "'%'"], '');
			$likeSN1   = $query->concatenate(["'%'", 'TRIM(' . Database::quoteName('pa.surname') . ')', "'%'"], '');
			$likeSN2   = $query->concatenate(["'%'", 'TRIM(' . Database::quoteName('pa2.surname') . ')', "'%'"], '');
			$surname1  = Database::quoteName('pa.surname');
			$surname2  = Database::quoteName('pa2.surname');

			$similarForenames = "($forename1 LIKE $likeFN2 OR $forename2 LIKE $likeFN1)";
			$similarSurnames  = "($surname1 LIKE $likeSN2 OR $surname2 LIKE $likeSN1)";
			$conditions       = "($similarForenames AND $similarSurnames)";
			$query->leftJoinX('participants AS pa2', [$conditions])
				->where(['pa.id != pa2.id'])
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
