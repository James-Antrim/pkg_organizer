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
use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Helpers\Languages;

/**
 * Class searches THM Organizer resources for resources and views relevant to the given search query.
 */
class Search extends ListModel
{
	private const TEACHER = 1, SPEAKER = 4;

	private $items = [];

	private $terms = [];

	/**
	 * Adds clauses to the room query for a capacity or room types.
	 *
	 * @param   JDatabaseQuery  $query     the query to be modified
	 * @param   int             $capacity  the capacity from the terms
	 * @param   string          $typeIDs   the resolved room type ids
	 *
	 * @return void modifies the query
	 */
	private function addRoomClauses(JDatabaseQuery $query, int $capacity, string $typeIDs)
	{
		if ($capacity and $typeIDs)
		{
			$query->where("((r.capacity >= $capacity OR r.capacity = 0) AND rt.id IN ($typeIDs))");
		}
		elseif ($capacity)
		{
			$query->where("r.capacity >= $capacity");
		}
		elseif ($typeIDs)
		{
			$query->where("rt.id IN ($typeIDs)");
		}
	}

	/**
	 * @inheritDoc
	 * @return  array  An array of data items on success.
	 */
	public function getItems(): array
	{
		$stateChanged = true;

		if (!$stateChanged)
		{
			return $this->items;
		}

		$this->setTerms();

		$items = ['exact' => [], 'strong' => [], 'good' => [], 'mentioned' => [], 'related' => [],];

		switch ($this->state->get('list.resource'))
		{
			// Prioritize these...
			case 'categories':
				// $this->searchCategories($items);
				break;
			case 'events':
				// $this->searchEvents($items);
				break;
			case 'groups':
				// $this->searchGroups($items);
				break;
			case 'persons':
				// $this->searchPersons($items);
				break;
			case 'pools':
				// $this->searchPools($items);
				break;
			case 'programs':
				// $this->searchPrograms($items);
				break;
			case 'rooms':
				$this->searchRooms($items);
				break;
			case 'subjects':
				$this->searchSubjects($items);
				break;
			default:
				// $this->searchCategories($items);
				// $this->searchEvents($items);
				// $this->searchGroups($items);
				// $this->searchPersons($items);
				// $this->searchPools($items);
				// $this->searchPrograms($items);
				$this->searchRooms($items);
				$this->searchSubjects($items);
				break;
		}


		// flatten the hierarchy

		$this->items = [];

		foreach ($items as $resources)
		{
			foreach ($resources as $results)
			{
				foreach ($results as $result)
				{
					$this->items[] = (object) $result;
				}
			}
		}

		// New search term while paginated
		if ((int) $this->state->get('list.start') >= count($this->items))
		{
			$this->state->set('list.start', 0);
		}

		return $this->items;
	}

	/**
	 * @inheritDoc
	 */
	public function getTotal($idColumn = null): int
	{
		return count($this->items);
	}

	/**
	 * @inheritDoc
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Retrieve container with the previous state
		parent::populateState();
		// Compare states and set variable if state has changed
		// Replace conteiner with this state
	}

	/**
	 * Processes room results into a standardized array for output
	 *
	 * @param   array &$results  the room results
	 *
	 * @return array of formatted room results
	 */
	private function processRooms(array $results): array
	{
		$rooms = [];

		foreach ($results as $room)
		{
			$roomID         = $room['id'];
			$rooms[$roomID] = [];

			$rooms[$roomID]['text'] = Languages::_('ORGANIZER_ROOM') . ": {$room['name']}";

			$description = empty($room['description']) ? $room['type'] : $room['description'];

			if (empty($room['capacity']))
			{
				$capacity = '';
			}
			else
			{
				$capacity = ' (~' . $room['capacity'] . ' ' . Languages::_('ORGANIZER_SEATS') . ')';
			}

			$rooms[$roomID]['description'] = "$description$capacity";

			$rooms[$roomID]['links'] = [
				'grid' => "?option=com_thm_organizer&view=schedule&roomIDs={$room['id']}",
				'list' => "?option=com_organizer&view=instances&roomID={$room['id']}"
			];
		}

		return $rooms;
	}

	/**
	 * Processes event/subject results into a standardized array for output
	 *
	 * @param   array  $resources  the event/subject results
	 *
	 * @return array $subjects
	 */
	private function processSnE(array $resources): array
	{
		$results = [];

		foreach ($resources as $resource)
		{
			$eventID   = $resource['eventID'];
			$links     = [];
			$subjectID = $resource['id'];

			if ($subjectID)
			{
				$description = Helpers\Subjects::getProgramName($subjectID);
				$key         = "subject-$subjectID";
				$label       = Languages::_('ORGANIZER_SUBJECT') . ': ';
				$name        = Helpers\Subjects::getName($subjectID, true);

				$links['subject_item'] = "?option=com_organizer&view=subject_item&id=$subjectID";
			}
			else
			{
				$description = Helpers\Events::getCategoryNames($eventID);
				$key         = "event-$eventID";
				$label       = Languages::_('ORGANIZER_EVENT') . ': ';
				$name        = Helpers\Events::getName($resource['eventID']);
			}

			if ($eventID)
			{
				$links['grid'] = "?option=com_thm_organizer&view=schedule&subjectIDs=$eventID";
				$links['list'] = "?option=com_organizer&view=instances&eventID=$eventID";
			}

			$results[$key] = [];

			$results[$key]['description'] = $description;
			$results[$key]['text']        = $label . $name;
			$results[$key]['links']       = $links;
		}

		return $results;
	}

	/**
	 * Checks for room types which match the the capacity and unresolvable terms. If resolved removes the type from the
	 * list of potential non-conventional/conformin room names.
	 *
	 * @param   array  $ncRooms   an array of terms which could not be resolved
	 * @param   int    $capacity  the requested capacity
	 *
	 * @return array the room type ids which matched the criteria
	 */
	private function resolveRoomTypes(array &$ncRooms, int $capacity): array
	{
		if (!$ncRooms and !$capacity)
		{
			return [];
		}

		$query = Database::getQuery();
		$query->select('DISTINCT id')->from('#__organizer_roomtypes');

		$typeIDs = [];

		foreach ($ncRooms as $key => $term)
		{
			$query->clear('where');
			$query->where("(name_de LIKE '%$term%' OR name_en LIKE '%$term%')");

			if ($capacity)
			{
				// Opens conjunctive clause and cap from type
				$query->where("(minCapacity IS NULL OR minCapacity = 0 OR minCapacity <= $capacity)");
				$query->where("(maxCapacity IS NULL OR maxCapacity = 0 OR maxCapacity >= $capacity)");
			}

			Database::setQuery($query);

			if ($resultIDs = Database::loadIntColumn())
			{
				// The term is a type or type-like => remove as potential room
				unset($ncRooms[$key]);
				$typeIDs = array_merge($typeIDs, $resultIDs);
			}
		}

		if ($typeIDs = array_unique($typeIDs))
		{
			return $typeIDs;
		}

		if ($capacity)
		{
			// If the existing capacity is not valid, it is also irrelevant.
			$maxCapacityValid = "(maxCapacity IS NOT NULL AND maxCapacity > 0)";
			$minCapacityValid = "(minCapacity IS NOT NULL AND minCapacity > 0)";
			$query->where("($maxCapacityValid OR $minCapacityValid)")
				->where("(minCapacity IS NULL OR minCapacity = '0' OR minCapacity <= '$capacity')")
				->where("(maxCapacity IS NULL OR maxCapacity = '0' OR maxCapacity >= '$capacity')");

			Database::setQuery($query);

			return Database::loadIntColumn();
		}

		return [];
	}

	/**
	 * Set the search terms.
	 *
	 * @return void sets the $terms property
	 */
	private function setTerms()
	{
		if (!$rawSearch = $this->state->get('filter.search'))
		{
			$this->terms = [];

			return;
		}

		$prohibited     = ['\\', '\'', '"', '%', '_', '(', ')'];
		$safeSearch     = str_replace($prohibited, '', $rawSearch);
		$standardSearch = strtolower($safeSearch);

		// Remove English and German ordinals
		$standardSearch = preg_replace('/ (.*[1-9])(?:\.|st|nd|rd|th)(.*)/', "$1$2", $standardSearch);

		$resource    = $this->state->get('list.resource');
		$this->terms = [];

		if (!$resource or $resource === 'groups' or $resource === 'pools')
		{
			// Filter out semester terms so that both the number and the word semster are one term.
			preg_match_all('/[1-9] (semester|sem)/', $standardSearch, $semesters);


			// Remove the semester terms from the search and add them to the terms
			if (!empty($semesters))
			{
				foreach ($semesters[0] as $semester)
				{
					$this->terms[]  = $semester;
					$standardSearch = str_replace($semester, '', $standardSearch);
				}
			}
		}

		// Add the original search to the beginning of the array
		array_unshift($this->terms, $standardSearch);

		$remainingTerms = explode(' ', $standardSearch);

		$whiteNoise = [
			'der',
			'die',
			'das',
			'den',
			'dem',
			'des',
			'einer',
			'eine',
			'ein',
			'einen',
			'einem',
			'eines',
			'und',
			'the',
			'a',
			'and',
			'oder',
			'or',
			'aus',
			'von',
			'of',
			'from',
		];

		foreach ($remainingTerms as $term)
		{
			$isWhiteNoise   = in_array($term, $whiteNoise);
			$isSingleLetter = (!is_numeric($term) and strlen($term) < 2);

			if ($isWhiteNoise or $isSingleLetter)
			{
				continue;
			}

			$this->terms[] = $term;
		}

		// Remove non-unique terms to prevent bloated queries
		$this->terms = array_unique($this->terms);
	}

	/**
	 * Retrieves prioritized organization search results
	 *
	 * @return void adds to the results property
	 */
	private function searchOrganizations()
	{
		$wherray = [];

		foreach ($this->searchTerms as $term)
		{
			if (is_numeric($term))
			{
				$clause    = "o.name_de LIKE '$term %' OR o.name_en LIKE '$term %' ";
				$clause    .= "OR o.shortName_de LIKE '$term %' OR o.shortName_en LIKE '$term %'";
				$wherray[] = $clause;
			}
			elseif (strlen($term) < 4)
			{
				$clause    = "o.shortName_de LIKE '%$term' OR o.shortName_en LIKE '%$term'";
				$wherray[] = $clause;
			}
			else
			{
				$clause    = "o.shortName_de LIKE '%$term' OR o.shortName_en LIKE '%$term'";
				$clause    .= " OR o.name_de LIKE '%$term' OR o.name_en LIKE '%$term'";
				$wherray[] = $clause;
			}
		}

		// Exact
		$cQuery = Database::getQuery();
		$cQuery->select("p.id AS programID, c.id AS categoryID, lft, rgt, o.id AS organizationID")
			->from('#__organizer_categories AS c')
			->leftJoin('#__organizer_programs AS p ON p.categoryID = c.id')
			->leftJoin('#__organizer_curricula AS m ON m.programID = p.id')
			->innerJoin('#__organizer_associations AS a ON a.categoryID = c.id')
			->innerJoin('#__organizer_organizations AS o on o.id = a.organizationID');
		$this->addInclusiveConditions($cQuery, $wherray);
		Database::setQuery($cQuery);
		$categories = Database::loadAssocList();

		$pQuery = Database::getQuery();
		$pQuery->select("p.id AS programID, c.id AS categoryID, lft, rgt, o.id AS organizationID")
			->from('#__organizer_programs AS p')
			->innerJoin('#__organizer_curricula AS m ON m.programID = p.id')
			->leftJoin('#__organizer_categories AS c ON c.id = p.categoryID')
			->innerJoin('#__organizer_associations AS a ON a.programID = p.id')
			->innerJoin('#__organizer_organizations AS o on o.id = a.organizationID');
		$this->addInclusiveConditions($pQuery, $wherray);
		Database::setQuery($pQuery);
		$programs = Database::loadAssocList();

		// Organization has no related resources => entry display pointless
		if (!$resources = array_merge($categories, $programs))
		{
			return;
		}

		$organizationIDs = [];

		foreach ($resources as $resource)
		{
			$organizationIDs[$resource['organizationID']] = $resource['organizationID'];
		}

		$this->results['exact']['organizations'] = $this->processOrganizations($organizationIDs);
		$this->results['related']['programs']    = $this->processPrograms($resources);

		// Strong Related programs will not be displayed => no selection and no secondary processing.
		$cQuery->clear('select')->clear('where');
		$cQuery->select('DISTINCT o.id');
		$this->addInclusiveConditions($cQuery, $wherray);
		Database::setQuery($cQuery);
		$cOrganizationIDS = Database::loadIntColumn();

		$pQuery->clear('select')->clear('where');
		$pQuery->select('DISTINCT o.id');
		$this->addInclusiveConditions($pQuery, $wherray);
		Database::setQuery($pQuery);
		$pOrganizationIDS = Database::loadIntColumn();

		if (!$organizationIDs = array_unique(array_merge($cOrganizationIDS, $pOrganizationIDS)))
		{
			return;
		}

		$this->results['strong']['organizations'] = $this->processOrganizations($organizationIDs);
	}

	/**
	 * Sets prioritized room search results.
	 *
	 * @params array &$items the container with the results
	 *
	 * @return void adds to the results property
	 */
	private function searchRooms(array &$items)
	{
		$tag   = Languages::getTag();
		$query = Database::getQuery();
		$query->select('r.id , r.name, r.capacity')
			->select("rt.name_$tag as type, rt.description_$tag as description")
			->from('#__organizer_rooms AS r')
			->leftJoin('#__organizer_roomtypes AS rt ON rt.id = r.roomtypeID')
			->order('r.name ASC');

		// EXACT => most room searches should be of this variety

		$wherray = [];

		foreach ($this->terms as $term)
		{
			$wherray[] = "r.name LIKE '$term'";
		}

		$query->where('(' . implode(' OR ', $wherray) . ')');
		Database::setQuery($query);

		$items['exact']['rooms'] = $this->processRooms(Database::loadAssocList());

		// STRONG => NC
		$capacity = 0;
		$ncRooms  = [];
		$wherray  = [];
		$query->clear('where');

		// Strong matches
		foreach ($this->terms as $index => $term)
		{
			// The reserved index for the complete search is irrelevant as such here
			if (count($this->terms) > 1 and $index === 0)
			{
				continue;
			}

			// Resolve context terms.
			$isBuilding = preg_match("/^[\p{L}}][\d]{1,2}$/", $term, $matches) !== false;
			$isCapacity = preg_match("/^\d+$/", $term, $matches) !== false;
			$isFloor    = preg_match("/^[\p{L}}][\d]{1,2}\.[\d]{1,2}\.*$/", $term, $matches) !== false;

			if ($isBuilding or $isFloor)
			{
				$wherray[] = "r.name LIKE '$term%'";

				continue;
			}

			if ($isCapacity)
			{
				$number = (int) $term;

				// The number most likely denotes a module which is a part of a series: 'math 2'
				if ($number < 5)
				{
					continue;
				}

				// Bigger numbers will trump smaller ones in regard to capacity, so they are superfluous.
				$capacity = $number > $capacity ? (int) $term : $capacity;
				continue;
			}

			// Potential non-conforming name or room type
			$ncRooms[] = $term;
		}

		$typeIDs = $this->resolveRoomTypes($ncRooms, $capacity);
		$typeIDs = $typeIDs ? "'" . implode("', '", $typeIDs) . "'" : '';

		// Filtered against types in resolveRoomTypes.
		foreach ($ncRooms as $ncRoom)
		{
			$wherray[] = "r.name LIKE '%$ncRoom%'";
		}

		if ($wherray)
		{
			$query->where('(' . implode(' OR ', $wherray) . ')');
			$this->addRoomClauses($query, $capacity, $typeIDs);

			Database::setQuery($query);

			$items['strong']['rooms'] = $this->processRooms(Database::loadAssocList());
		}

		if (!$capacity and !$typeIDs)
		{
			return;
		}

		// Related => has type or capacity relevance

		$query->clear('where');
		$this->addRoomClauses($query, $capacity, $typeIDs);

		Database::setQuery($query);

		$items['related']['rooms'] = $this->processRooms(Database::loadAssocList());
	}

	/**
	 * Retrieves prioritized subject search results
	 *
	 * @return void adds to the results property
	 */
	private function searchSubjects(array &$items)
	{
		// Numeric flavoring: Mathematics '2'
		$salt  = [];
		$terms = $this->terms;

		foreach ($terms as $index => $term)
		{
			$short     = strlen($term) < 3;
			$isRoman   = preg_match("/^([ivx]+)$/", $term, $matches);
			$isNumeric = is_numeric($term);

			if ($short or $isRoman or $isNumeric)
			{
				unset($terms[$index]);

				if ($isRoman or $isNumeric)
				{
					$salt[] = $term;
				}
			}
		}

		// Nothing relevant survived the filter.
		if (!$termCount = count($terms))
		{
			return;
		}

		$today = date('Y-m-d');

		$eQuery = Database::getQuery();
		$eQuery->select('DISTINCT e.id AS eventID, s.id')
			->from('#__organizer_events AS e')
			->innerJoin('#__organizer_instances AS i ON i.eventID = e.id')
			->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
			->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
			->leftJoin('#__organizer_subject_events AS se ON se.eventID = e.id')
			->leftJoin('#__organizer_subjects AS s ON s.id = se.subjectID')
			->where("i.delta != 'removed'")
			->where("u.delta != 'removed'")
			->where("b.date >= '$today'");

		$sQuery = Database::getQuery();
		$sQuery->select('DISTINCT s.id, e.id AS eventID')
			->from('#__organizer_subjects AS s')
			->leftJoin('#__organizer_subject_events AS se on se.subjectID = s.id')
			->leftJoin('#__organizer_events AS e on e.id = se.eventID')
			->leftJoin('#__organizer_instances AS i on i.eventID = e.id')
			->leftJoin('#__organizer_units AS u on u.id = i.unitID')
			->where("(i.delta IS NULL OR i.delta != 'removed')")
			->where("(u.delta IS NULL OR u.delta != 'removed')");

		$eNameColumns = [
			'e.name_de',
			'e.name_en'
		];

		$sNameColumns = [
			's.abbreviation_de',
			's.abbreviation_en',
			's.code',
			's.fullName_de',
			's.fullName_en',
			's.shortName_de',
			's.shortName_en'
		];

		// Only one salting: resolve against number/roman?
		$salt       = count($salt) === 1 ? array_unshift($salt) : '';
		$eventIDs   = [];
		$eWherray   = [];
		$subjectIDs = [];
		$sWherray   = [];

		// Exact: the original search term matches the text of a naming field or one of the terms matches the code /////
		$initialTerm = current($terms);

		foreach ($eNameColumns as $column)
		{
			$eWherray[] = "$column LIKE '$initialTerm'";
		}

		foreach ($sNameColumns as $column)
		{
			$sWherray[] = "$column LIKE '$initialTerm'";
		}

		foreach ($terms as $term)
		{
			$eWherray[] = "e.subjectNo LIKE '$term'";
			$sWherray[] = "s.code LIKE '$term'";
		}

		$eQuery->where('(' . implode(' OR ', $eWherray) . ')');
		$sQuery->where('(' . implode(' OR ', $sWherray) . ')');

		Database::setQuery($sQuery);

		if ($subjects = Database::loadAssocList())
		{
			$eventIDs   = array_unique(array_merge($eventIDs, array_filter(Database::loadIntColumn(1))));
			$subjectIDs = array_unique(array_merge($subjectIDs, array_filter(Database::loadIntColumn())));

			$items['exact']['subjects'] = $this->processSnE($subjects);
		}

		if ($eventIDs)
		{
			$eQuery->where('e.id NOT IN (' . implode(',', $eventIDs) . ')');
		}

		$sIDString = $subjectIDs ? implode(',', $subjectIDs) : '';
		$sIDClause = $sIDString ? "(s.id IS NULL OR s.id IN ($sIDString))" : 's.id IS NULL';
		$eQuery->where($sIDClause);

		Database::setQuery($eQuery);

		if ($events = Database::loadAssocList())
		{
			$eventIDs   = array_unique(array_merge($eventIDs, array_filter(Database::loadIntColumn())));
			$subjectIDs = array_unique(array_merge($subjectIDs, array_filter(Database::loadIntColumn(1))));

			$pEvents = $this->processSnE($events);

			$items['exact']['subjects'] = empty($items['exact']['subjects']) ?
				$pEvents : array_merge($items['exact']['subjects'], $pEvents);
		}

		// Strong: all terms are present and salt is present if relevant ///////////////////////////////////////////////
		$eQuery->clear('where')
			->where("i.delta != 'removed'")
			->where("u.delta != 'removed'")
			->where("b.date >= '$today'");
		$nameDEArray = [];
		$nameENArray = [];
		$sQuery->clear('where')
			->where("(i.delta IS NULL OR i.delta != 'removed')")
			->where("(u.delta IS NULL OR u.delta != 'removed')");

		if ($subjectIDs)
		{
			$sQuery->where('s.id NOT IN (' . implode(',', $subjectIDs) . ')');
		}

		foreach ($terms as $term)
		{
			$eQuery->where("(e.name_de LIKE '%$term%' OR e.name_en LIKE '%$term%')");
			$nameDEArray[] = "s.fullName_de LIKE '%$term%'";
			$nameENArray[] = "s.fullName_en LIKE '%$term%'";
		}

		if ($salt)
		{
			$eQuery->where("(e.name_de LIKE '% $salt' OR e.name_en LIKE '% $salt')");
			$nameDEArray[] = "s.fullName_de LIKE '% $salt'";
			$nameENArray[] = "s.fullName_en LIKE '% $salt'";
		}

		$nameDEClause = '(' . implode(' AND ', $nameDEArray) . ')';
		$nameENClause = '(' . implode(' AND ', $nameENArray) . ')';
		$sQuery->where("($nameDEClause OR $nameENClause)");

		Database::setQuery($sQuery);

		if ($subjects = Database::loadAssocList())
		{
			$eventIDs   = array_unique(array_merge($eventIDs, array_filter(Database::loadIntColumn(1))));
			$subjectIDs = array_unique(array_merge($subjectIDs, array_filter(Database::loadIntColumn())));

			$items['strong']['subjects'] = $this->processSnE($subjects);
		}

		if ($eventIDs)
		{
			$eQuery->where('e.id NOT IN (' . implode(',', $eventIDs) . ')');
		}

		$sIDString = $subjectIDs ? implode(',', $subjectIDs) : '';
		$sIDClause = $sIDString ? "(s.id IS NULL OR s.id IN ($sIDString))" : 's.id IS NULL';
		$eQuery->where($sIDClause);

		Database::setQuery($eQuery);

		if ($events = Database::loadAssocList())
		{
			$eventIDs   = array_unique(array_merge($eventIDs, array_filter(Database::loadIntColumn())));
			$subjectIDs = array_unique(array_merge($subjectIDs, array_filter(Database::loadIntColumn(1))));

			$pEvents = $this->processSnE($events);

			$items['strong']['subjects'] = empty($items['strong']['subjects']) ?
				$pEvents : array_merge($items['strong']['subjects'], $pEvents);
		}

		// Good: a term is present and salt is present if relevant /////////////////////////////////////////////////////
		$eQuery->clear('where')
			->where("i.delta != 'removed'")
			->where("u.delta != 'removed'")
			->where("b.date >= '$today'");
		$sQuery->clear('where')
			->where("(i.delta IS NULL OR i.delta != 'removed')")
			->where("(u.delta IS NULL OR u.delta != 'removed')");

		if ($subjectIDs)
		{
			$sQuery->where('s.id NOT IN (' . implode(',', $subjectIDs) . ')');
		}

		$eWherray = [];
		$sWherray = [];
		foreach ($terms as $term)
		{
			foreach ($eNameColumns as $column)
			{
				$eWherray[] = "$column LIKE '%$term%'";
			}

			foreach ($sNameColumns as $column)
			{
				$sWherray[] = "$column LIKE '%$term%'";
			}
		}

		$eTermsClause = '(' . implode(' OR ', $eWherray) . ')';
		$sTermsClause = '(' . implode(' OR ', $sWherray) . ')';

		$eWherray = [];
		$sWherray = [];
		if ($salt)
		{
			foreach ($eNameColumns as $column)
			{
				$eWherray[] = "$column LIKE '% $salt%'";
			}

			foreach ($sNameColumns as $column)
			{
				$sWherray[] = "$column LIKE '% $salt'";
			}
		}

		$eSaltClause = $eWherray ? '(' . implode(' OR ', $eWherray) . ')' : '';
		$sSaltClause = $sWherray ? '(' . implode(' OR ', $sWherray) . ')' : '';

		$eWhere = $eSaltClause ? "(($eTermsClause) AND ($eSaltClause))" : "($eTermsClause)";
		$sWhere = $sSaltClause ? "(($sTermsClause) AND ($sSaltClause))" : "($sTermsClause)";

		$eQuery->where($eWhere);
		$sQuery->where($sWhere);

		Database::setQuery($sQuery);

		if ($subjects = Database::loadAssocList())
		{
			$subjectIDs = array_unique(array_merge($subjectIDs, array_filter(Database::loadIntColumn())));

			$items['good']['subjects'] = $this->processSnE($subjects);
		}

		if ($eventIDs)
		{
			$eQuery->where('e.id NOT IN (' . implode(',', $eventIDs) . ')');
		}

		$sIDString = $subjectIDs ? implode(',', $subjectIDs) : '';
		$sIDClause = $sIDString ? "(s.id IS NULL OR s.id IN ($sIDString))" : 's.id IS NULL';
		$eQuery->where($sIDClause);

		Database::setQuery($eQuery);

		if ($events = Database::loadAssocList())
		{
			$eventIDs   = array_unique(array_merge($eventIDs, array_filter(Database::loadIntColumn())));
			$subjectIDs = array_unique(array_merge($subjectIDs, array_filter(Database::loadIntColumn(1))));

			$pEvents = $this->processSnE($events);

			$items['good']['subjects'] = empty($items['good']['subjects']) ?
				$pEvents : array_merge($items['good']['subjects'], $pEvents);
		}

		// Mentioned: term appears in a describing field ///////////////////////////////////////////////////////////////
		// No searching event text fields at this time.
		$sQuery->clear('where')
			->where("(i.delta IS NULL OR i.delta != 'removed')")
			->where("(u.delta IS NULL OR u.delta != 'removed')");

		if ($subjectIDs)
		{
			$sQuery->where('s.id NOT IN (' . implode(',', $subjectIDs) . ')');
		}

		$textColumns = [
			's.content_de',
			's.content_en',
			's.description_de',
			's.description_en',
			's.objective_de',
			's.objective_en'
		];

		$wherray = [];
		foreach ($terms as $term)
		{
			foreach ($textColumns as $column)
			{
				$wherray[] = "$column LIKE '%$term%'";
			}
		}

		$sQuery->where('(' . implode(' OR ', $wherray) . ')');
		Database::setQuery($sQuery);

		if ($subjects = Database::loadAssocList())
		{
			$subjectIDs = array_unique(array_merge($subjectIDs, array_filter(Database::loadIntColumn())));

			$items['mentioned']['subjects'] = $this->processSnE($subjects);
		}

		$relevantRoles = [self::TEACHER, self::SPEAKER];

		// Related: terms match subjects: coordinator or teacher; events: coordinator, speaker or teacher //////////////
		$eQuery->clear('where')
			->leftJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
			->leftJoin('#__organizer_persons AS p1 ON p1.id = ip.personID')
			->leftJoin('#__organizer_event_coordinators AS ec ON ec.eventID = e.id')
			->leftJoin('#__organizer_persons AS p2  ON p2.id = ec.personID')
			->where('(roleID IS NULL OR roleID IN (' . implode(',', $relevantRoles) . '))')
			->where("i.delta != 'removed'")
			->where("u.delta != 'removed'")
			->where("b.date >= '$today'")
			->where("ip.delta != 'removed'")
			->where("((p1.id IS NOT NULL AND p1.public = 1) OR p2.id IS NOT NULL)");
		$sQuery->clear('where')
			->innerJoin('#__organizer_subject_persons AS sp ON sp.subjectID = s.id')
			->innerJoin('#__organizer_persons AS p ON p.id = sp.personID')
			->where("(i.delta IS NULL OR i.delta != 'removed')")
			->where("(u.delta IS NULL OR u.delta != 'removed')");

		if ($subjectIDs)
		{
			$sQuery->where('s.id NOT IN (' . implode(',', $subjectIDs) . ')');
		}

		if ($termCount == 1)
		{
			$eQuery->where("(p1.surname LIKE '%$initialTerm%' OR p2.surname LIKE '%$initialTerm%')");
			$sQuery->where("p.surname LIKE '%$initialTerm%'");
		}
		else
		{
			$eWherray = [];
			$sWherray = [];

			foreach ($terms as $oKey => $outerTerm)
			{
				// Initial term is all terms
				if ($oKey === 0)
				{
					continue;
				}

				foreach ($terms as $iKey => $innerTerm)
				{
					if ($iKey === 0 or $iKey == $oKey)
					{
						continue;
					}

					$eWherray[] = "(p1.surname LIKE '%$outerTerm%' AND p1.forename LIKE '%$innerTerm%')";
					$eWherray[] = "(p2.surname LIKE '%$outerTerm%' AND p2.forename LIKE '%$innerTerm%')";
					$sWherray[] = "(p.surname LIKE '%$outerTerm%' AND p.forename LIKE '%$innerTerm%')";
				}
			}

			$eQuery->where('(' . implode(' OR ', $eWherray) . ')');
			$sQuery->where('(' . implode(' OR ', $sWherray) . ')');
		}

		Database::setQuery($sQuery);

		if ($subjects = Database::loadAssocList())
		{
			$subjectIDs = array_unique(array_merge($subjectIDs, array_filter(Database::loadIntColumn())));

			$items['related']['subjects'] = $this->processSnE($subjects);
		}

		if ($eventIDs)
		{
			$eQuery->where('e.id NOT IN (' . implode(',', $eventIDs) . ')');
		}

		$sIDString = $subjectIDs ? implode(',', $subjectIDs) : '';
		$sIDClause = $sIDString ? "(s.id IS NULL OR s.id IN ($sIDString))" : 's.id IS NULL';
		$eQuery->where($sIDClause);

		Database::setQuery($eQuery);

		if ($events = Database::loadAssocList())
		{
			$pEvents = $this->processSnE($events);

			$items['related']['subjects'] = empty($items['related']['subjects']) ?
				$pEvents : array_merge($items['related']['subjects'], $pEvents);
		}
	}
}
