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
use Joomla\CMS\Factory;
use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Helpers\Languages;
use Organizer\Tables;

/**
 * Class searches THM Organizer resources for resources and views relevant to the given search query.
 */
class Search extends ListModel
{
	private const TEACHER = 1, SPEAKER = 4;

	private $authorized;

	private $filteredTerms = [];

	private $categoryIDs = [];

	private $degrees = [];

	private $items = [];

	private $organizationIDs = [];

	private $programDENames = [];

	private $programENNames = [];

	private $programIDs = [];

	private $semesters = [];

	private $terms = [];

	private $whiteNoise = [
		'ab',
		'aber',
		'aboard',
		'about',
		'above',
		'across',
		'after',
		'against',
		'ago',
		'all',
		'alle',
		'allerdings',
		'along',
		'als',
		'also',
		'although',
		'amid',
		'among',
		'an',
		'and',
		'andererseits',
		'anschließend',
		'apart',
		'around',
		'as',
		'at',
		'auf',
		'aus',
		'außer',
		'außerdem',
		'außerhalb',
		'bald',
		'before',
		'behind',
		'bei',
		'beide',
		'below',
		'beneath',
		'beside',
		'besides',
		'between',
		'bevor',
		'beyond',
		'bis',
		'both',
		'but',
		'by',
		'concerning',
		'considering',
		'da',
		'dabei',
		'dadurch',
		'dafür',
		'dagegen',
		'damit',
		'danach',
		'dann',
		'darauf',
		'darum',
		'davor',
		'dazu',
		'denn',
		'deshalb',
		'despite',
		'deswegen',
		'doch',
		'down',
		'durch',
		'during',
		'einerseits',
		'ehe',
		'einige',
		'either',
		'except',
		'far',
		'ferner',
		'falls',
		'folglich',
		'following',
		'for',
		'from',
		'front',
		'gegenüber',
		'genauso',
		'hinter',
		'however',
		'immerhin',
		'in',
		'indem',
		'inside',
		'into',
		'inzwischen',
		'jedoch',
		'just',
		'lang',
		'like',
		'long',
		'meanwhile',
		'minus',
		'mit',
		'nach',
		'nachdem',
		'near',
		'neben',
		'neither',
		'next',
		'noch',
		'nor',
		'obwohl',
		'oder',
		'of',
		'off',
		'on',
		'only',
		'onto',
		'opposite',
		'or',
		'out',
		'outside',
		'over',
		'past',
		'per',
		'plus',
		'regarding',
		'round',
		'save',
		'schließlich',
		'seit',
		'seitdem',
		'since',
		'so',
		'sodass',
		'solange',
		'soon',
		'später',
		'some',
		'sondern',
		'sooft',
		'through',
		'than',
		'then',
		'til',
		'till',
		'to',
		'too',
		'toward',
		'towards',
		'trotzdem',
		'über',
		'um',
		'und',
		'under',
		'underneath',
		'unless',
		'unlike',
		'unter',
		'until',
		'up',
		'upon',
		'versus',
		'via',
		'von',
		'vor',
		'vorher',
		'während',
		'wann',
		'weder',
		'weil',
		'weit',
		'wenn',
		'when',
		'where',
		'whereas',
		'whether',
		'while',
		'with',
		'within',
		'without',
		'wohingegen',
		'yet',
		'zu',
		'zuvor',
		'zwar',
		'zwischen'
	];

	/**
	 * @inheritDoc
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->authorized = Helpers\Can::manageTheseOrganizations();
	}

	/**
	 * Adds clauses to the room query for a max capacity or room types. Max capacity is used here for consistency with
	 * room type values.
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
			$query->where("((r.maxCapacity >= $capacity OR r.maxCapacity = 0) AND rt.id IN ($typeIDs))");
		}
		elseif ($capacity)
		{
			$query->where("r.maxCapacity >= $capacity");
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
		$this->setTerms();

		$items = ['exact' => [], 'strong' => [], 'good' => [], 'mentioned' => [], 'related' => [],];

		if ($this->terms)
		{
			switch ($this->state->get('list.resource'))
			{
				case 'organizations':
					$this->searchOrganizations($items);
					break;
				case 'cnp':
					$this->searchOrganizations($items, false);
					$this->searchCnP($items);
					break;
				case 'gnp':
					$this->searchOrganizations($items, false);
					$this->searchCnP($items, false);
					$this->searchGnP($items);
					break;
				case 'ens':
					$this->searchEnS($items);
					break;
				case 'persons':
					$this->searchPersons($items);
					break;
				case 'rooms':
					$this->searchRooms($items);
					break;
				default:
					$this->searchOrganizations($items);
					$this->searchCnP($items);
					$this->searchGnP($items);
					$this->searchEnS($items);
					$this->searchPersons($items);
					$this->searchRooms($items);
					break;
			}
		}

		// flatten the hierarchy
		$this->items   = [];
		$resourceOrder = ['gnp', 'cnp', 'ens', 'persons', 'organizations', 'rooms'];

		foreach ($items as $resources)
		{
			foreach ($resourceOrder as $resource)
			{
				if (!empty($resources[$resource]))
				{
					foreach ($resources[$resource] as $result)
					{
						$this->items[] = (object) $result;
					}
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
	 * Filters pool terms to non-compound terms not occurring in the program title.
	 *
	 * @return array
	 */
	private function getPoolTerms(): array
	{
		$poolTerms = $this->terms;

		$deNames = [];
		foreach ($this->programDENames as $groupedNames)
		{
			$deNames = array_merge($deNames, $groupedNames);
		}

		$enNames = [];
		foreach ($this->programENNames as $groupedNames)
		{
			$enNames = array_merge($enNames, $groupedNames);
		}

		foreach ($poolTerms as $key => $term)
		{
			if (strpos($term, ' ') !== false)
			{
				unset($poolTerms[$key]);
				continue;
			}

			foreach ($deNames as $deName)
			{
				if (strpos($deName, $term) !== false)
				{
					unset($poolTerms[$key]);
					continue 2;
				}
			}

			foreach ($enNames as $enName)
			{
				if (strpos($enName, $term) !== false)
				{
					unset($poolTerms[$key]);
					continue 2;
				}
			}
		}

		return $poolTerms;
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
		parent::populateState();

		$get     = Helpers\Input::getString('search');
		$session = Factory::getSession();
		$pSearch = (string) $session->get('organizer.search.search');

		// No previous and there now is one or previous and the current one is different
		if ((!$pSearch and $get) or ($pSearch and $pSearch !== $get))
		{
			$session->set('organizer.search.search', $get);
			$this->state->set('filter.search', $get);
		}
	}

	/**
	 * Removes special characters and converts the string to lower case.
	 *
	 * @param   string  $string
	 *
	 * @return string the prepared string
	 */
	private function prepareString(string $string): string
	{
		return strtolower(str_replace(['\\', '\'', '"', '%', '_', '(', ')'], '', $string));
	}

	/**
	 * Processes category / program results into a standardized array for output. Programs are prioritized in the output
	 * for ease of comprehension.
	 *
	 * @param   array  $resources  the category and program search results
	 *
	 * @return array the structured results
	 */
	private function processCnP(array $resources): array
	{
		$results = [];

		foreach ($resources as $resource)
		{
			$categoryID = $resource['categoryID'];
			$links      = [];
			$programID  = $resource['programID'];

			if ($programID)
			{
				$key   = "program-$programID";
				$left  = $resource['lft'];
				$label = Languages::_('ORGANIZER_PROGRAM') . ': ';
				$name  = Helpers\Programs::getName($programID);
				$right = $resource['rgt'];

				if ($left and $right and $right - $left >= 2)
				{
					$links['subjects']   = "?option=com_organizer&view=subjects&programID=$programID";
					$links['curriculum'] = "?option=com_organizer&view=curriculum&programID=$programID";
				}

				$organizationIDs = Helpers\Programs::getOrganizationIDs($programID);
			}
			else
			{
				$key   = "category-$categoryID";
				$label = Languages::_('ORGANIZER_CATEGORY') . ': ';
				$name  = Helpers\Categories::getName($categoryID);

				$organizationIDs = Helpers\Categories::getOrganizationIDs($categoryID);
			}

			$description   = '';
			$organizations = [];

			foreach ($organizationIDs as $organizationID)
			{
				$organizations[] = Helpers\Organizations::getName($organizationID);
			}

			if ($organizations)
			{
				$count = count($organizations);
				asort($organizations);
				if ($count === 1)
				{
					$description = array_shift($organizations);
				}
				elseif ($count === 2)
				{
					$description = implode(' & ', $organizations);
				}
				else
				{
					$last        = array_pop($organizations);
					$description = implode(', ', $organizations) . ", & $last";
				}
			}

			if ($categoryID)
			{
				$links['grid'] = "?option=com_thm_organizer&view=schedule&programIDs=$programID";
				$links['list'] = "?option=com_organizer&view=instances&categoryID=$categoryID";
			}

			// Nothing to link => entry is pointless
			if (!$links)
			{
				continue;
			}

			$results[$key]                = [];
			$results[$key]['description'] = $description;
			$results[$key]['text']        = $label . $name;
			$results[$key]['links']       = $links;
		}

		return $results;
	}

	/**
	 * Processes event/subject results into a standardized array for output
	 *
	 * @param   array  $resources  the event/subject results
	 *
	 * @return array the structured results
	 */
	private function processEnS(array $resources): array
	{
		$results = [];

		foreach ($resources as $resource)
		{
			$eventID   = $resource['eventID'];
			$links     = [];
			$subjectID = $resource['subjectID'];

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
	 * Processes group / pool results into a standardized array for output. Pools are prioritized in the output
	 * for ease of comprehension.
	 *
	 * @param   array  $resources  the category and program search results
	 *
	 * @return array the structured results
	 */
	private function processGnP(array $resources): array
	{
		$results = [];

		foreach ($resources as $resource)
		{
			$groupID = $resource['groupID'];
			$links   = [];
			$poolID  = $resource['poolID'];

			if ($poolID)
			{
				$key   = "pool-$poolID";
				$label = Languages::_('ORGANIZER_POOL') . ': ';
				$left  = $resource['lft'];
				$name  = Helpers\Pools::getFullName($poolID);
				$right = $resource['rgt'];

				if ($left and $right and $right - $left >= 2)
				{
					$links['subjects'] = "?option=com_organizer&view=subjects&poolID=$poolID";
				}

				$description = Helpers\Pools::getProgramName($poolID);
			}
			else
			{
				$key   = "group-$groupID";
				$label = Languages::_('ORGANIZER_GROUP') . ': ';
				$name  = Helpers\Groups::getFullName($groupID);

				$description = Helpers\Groups::getCategoryName($groupID);
			}

			if ($groupID)
			{
				$links['grid'] = "?option=com_thm_organizer&view=schedule&poolIDs=$groupID";
				$links['list'] = "?option=com_organizer&view=instances&groupID=$groupID";
			}

			// Nothing to link => entry is pointless
			if (!$links)
			{
				continue;
			}

			$results[$key]                = [];
			$results[$key]['description'] = $description;
			$results[$key]['text']        = $label . $name;
			$results[$key]['links']       = $links;
		}

		return $results;
	}

	/**
	 * Processes organization results into a standardized array for output
	 *
	 * @param   array  $organizationIDs  the organization ids
	 *
	 * @return array the structured results
	 */
	private function processOrganizations(array $organizationIDs): array
	{
		$label         = Languages::_('ORGANIZER_ORGANIZATION') . ': ';
		$organizations = [];

		foreach ($organizationIDs as $organizationID)
		{
			$links['grid'] = "?option=com_thm_organizer&view=schedule&departmentIDs=$organizationID";
			$links['list'] = "?option=com_organizer&view=instances&organizationID=$organizationID";

			$organizations[$organizationID]          = ['description' => ''];
			$organizations[$organizationID]['text']  = $label . Helpers\Organizations::getName($organizationID);
			$organizations[$organizationID]['links'] = $links;
		}

		return $organizations;
	}

	/**
	 * Processes person results into a standardized array for output.
	 *
	 * @param   array  $personIDs  the category and program search results
	 *
	 * @return array the structured results
	 */
	private function processPersons(array $personIDs): array
	{
		$label   = Languages::_('ORGANIZER_PERSON') . ': ';
		$persons = [];
		$userID  = Helpers\Users::getID();

		foreach ($personIDs as $personID)
		{
			$links = [];

			$coordinates     = Helpers\Subjects::coordinates(0, $personID);
			$identity        = ($userID and Helpers\Persons::getIDByUserID($userID) === $personID);
			$organizationIDs = Helpers\Persons::getOrganizationIDs($personID);
			$names           = Helpers\Persons::getOrganizationNames($personID);
			$released        = Helpers\Persons::released($personID);
			$teaches         = Helpers\Subjects::teaches(0, $personID);
			$wedge           = ($organizationIDs and array_intersect($this->authorized, $organizationIDs));

			if ($coordinates or $teaches)
			{
				$links['subjects'] = "?option=com_organizer&view=subjects&personID=$personID";
			}

			if ($identity or $released or $wedge)
			{
				$links['grid'] = "?option=com_thm_organizer&view=schedule&teacherIDs=$personID";
				$links['list'] = "?option=com_organizer&view=instances&personID=$personID";
			}

			if ($links)
			{
				$persons[$personID] = [];

				$persons[$personID]['description'] = $names ?: '';
				$persons[$personID]['text']        = $label . Helpers\Persons::getDefaultName($personID);
				$persons[$personID]['links']       = $links;
			}
		}

		return $persons;
	}

	/**
	 * Processes room results into a standardized array for output
	 *
	 * @param   array &$results  the room results
	 *
	 * @return array the structured results
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

			if (empty($room['effCapacity']))
			{
				$capacity = '';
			}
			else
			{
				$capacity = ' (~' . $room['effCapacity'] . ' ' . Languages::_('ORGANIZER_SEATS') . ')';
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
	 * Attempts to resolve a pool result to the corresponding group.
	 *
	 * @param   array  $pool  the pool result
	 *
	 * @return int the id of the group on success, otherwise 0
	 */
	private function resolveGroup(array $pool): int
	{
		$parts = explode(' ', $pool['name_de']);

		foreach ($parts as $key => $value)
		{
			if (in_array($value, $this->whiteNoise))
			{
				unset($parts[$key]);
			}
		}

		$deClause = "g.name_de LIKE '%" . implode("%' AND g.name_de LIKE '%", $parts) . "%'";

		$parts = explode(' ', $pool['name_en']);

		foreach ($parts as $key => $value)
		{
			if (in_array($value, $this->whiteNoise))
			{
				unset($parts[$key]);
			}
		}

		$enClause = "g.name_en LIKE '%" . implode("%' AND g.name_en LIKE '%", $parts) . "%'";

		$query = Database::getQuery();
		$query->select('DISTINCT g.id AS groupID')
			->from('#__organizer_groups AS g')
			->where("g.categoryID = {$pool['categoryID']}")
			->where("(($deClause) OR ($enClause))");

		Database::setQuery($query);

		return Database::loadInt();
	}

	/**
	 * Attempts to resolve a group result to the corresponding pools.
	 *
	 * @param   array  $group  the group result
	 *
	 * @return array the pool entries associated with the pool
	 */
	private function resolvePools(array $group): array
	{
		if (empty($group['categoryID']))
		{
			return [];
		}

		$parts = explode(' ', $group['name_de']);

		foreach ($parts as $key => $value)
		{
			if (in_array($value, $this->whiteNoise))
			{
				unset($parts[$key]);
			}
		}

		$deClause = "po.fullName_de LIKE '%" . implode("%' AND po.fullName_de LIKE '%", $parts) . "%'";

		$parts = explode(' ', $group['name_en']);

		foreach ($parts as $key => $value)
		{
			if (in_array($value, $this->whiteNoise))
			{
				unset($parts[$key]);
			}
		}

		$enClause = "po.fullName_de LIKE '%" . implode("%' AND po.fullName_de LIKE '%", $parts) . "%'";

		$conditions = "c2.lft < c1.lft AND c2.rgt > c1.rgt";
		$query      = Database::getQuery();
		$query->select('DISTINCT po.id AS poolID, po.fullName_de AS name_de, po.fullName_en AS name_en')
			->select('pr.id AS programID, pr.categoryID')
			->select('c1.lft, c1.rgt')
			->from('#__organizer_pools AS po')
			->innerJoin('#__organizer_curricula AS c1 ON c1.poolID = po.id')
			->innerJoin("#__organizer_curricula AS c2 ON $conditions")
			->innerJoin('#__organizer_programs AS pr ON pr.id = c2.programID')
			->where("pr.categoryID = {$group['categoryID']}")
			->where("(($deClause) OR ($enClause))");

		Database::setQuery($query);

		return Database::loadAssocList('poolID');
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
	 * Retrieves prioritized category/program search results. Programs are prioritized in the output for ease of
	 * comprehension.
	 *
	 * @param   array &$items      the container with the results
	 * @param   bool   $requested  true: results added to output; false: results used for subordinate context
	 *
	 * @return void modifies &$items
	 */
	private function searchCnP(array &$items, bool $requested = true)
	{
		if ((!$this->terms or empty($this->terms[0])) and !$this->degrees)
		{
			return;
		}

		$categoryIDs = [];
		$programIDs  = [];

		$noInitial = false;
		$terms     = $this->terms;

		foreach ($terms as $index => $term)
		{
			// Too many false positives for short strings.
			$short = strlen($term) < 4;

			// No categories or programs with roman numerals.
			$isRoman = preg_match("/^([ivx]+)$/", $term, $matches);

			// Most relevant case would be year of accrediation, but most people will not enter this.
			$isNumeric = is_numeric($term);

			if ($short or $isRoman or $isNumeric)
			{
				if ($index === 0)
				{
					$noInitial = true;
				}

				unset($terms[$index]);
			}
		}

		// If the initial term never existed or was unset in the filtering process don't set.
		$initialTerm = (empty($terms) or $noInitial) ? '' : array_shift($terms);

		$cQuery = Database::getQuery();
		$cQuery->select('DISTINCT c.id AS categoryID, p.id AS programID, lft, rgt, o.id AS organizationID')
			->from('#__organizer_categories AS c')
			->leftJoin('#__organizer_programs AS p ON p.categoryID = c.id')
			->leftJoin('#__organizer_curricula AS m ON m.programID = p.id')
			->innerJoin('#__organizer_associations AS a ON a.categoryID = c.id')
			->innerJoin('#__organizer_organizations AS o on o.id = a.organizationID');

		$pQuery = Database::getQuery();
		$pQuery->select('DISTINCT p.id AS programID, c.id AS categoryID, lft, rgt, o.id AS organizationID')
			->from('#__organizer_programs AS p')
			->innerJoin('#__organizer_curricula AS m ON m.programID = p.id')
			->leftJoin('#__organizer_categories AS c ON c.id = p.categoryID')
			->innerJoin('#__organizer_associations AS a ON a.programID = p.id')
			->innerJoin('#__organizer_organizations AS o on o.id = a.organizationID');

		/**
		 * -- Exact --
		 * @ Exact Degree IDs!
		 * Category: First term begins the name and a resolved exact degree abbreviation is present.
		 * Program: First term is the name and is associated with a resolved degree id.
		 *
		 * @ No Exact Degree IDs
		 * Category: First term is the name.
		 * Program: None.
		 */

		$getCategories = false;
		$getPrograms   = false;

		if ($initialTerm)
		{
			$getCategories = true;

			if ($this->degrees and !empty($this->degrees['exact']))
			{
				$getPrograms = true;

				$degrees    = $this->degrees['exact'];
				$cdDEClause = "c.name_de LIKE '%" . implode("%' OR c.name_de LIKE '%", $degrees) . "%'";
				$cdENClause = "c.name_en LIKE '%" . implode("%' OR c.name_en LIKE '%", $degrees) . "%'";
				$cQuery->where("($cdDEClause OR $cdENClause)");
				$cQuery->where("(c.name_de LIKE '$initialTerm%' OR c.name_en LIKE '$initialTerm%')");


				$pQuery->where("(p.name_de LIKE '$initialTerm' OR p.name_en LIKE '$initialTerm')")
					->where('p.degreeID IN (' . implode(',', array_keys($degrees)) . ')');
			}
			else
			{
				$cQuery->where("(c.name_de LIKE '$initialTerm' OR c.name_en LIKE '$initialTerm')");
			}
		}

		if ($getPrograms)
		{
			Database::setQuery($pQuery);

			if ($programs = Database::loadAssocList('programID'))
			{
				$this->categoryIDs['exact'] = array_filter(Database::loadIntColumn(1));
				$this->programIDs['exact']  = array_filter(array_keys($programs));

				$programIDs = $this->programIDs['exact'];

				if ($requested)
				{
					$items['exact']['cnp'] = $this->processCnP($programs);
				}
			}
		}

		if ($getCategories)
		{
			if ($programIDs)
			{
				$cQuery->where('(p.id IS NULL OR p.id NOT IN (' . implode(',', $programIDs) . '))');
			}

			Database::setQuery($cQuery);

			if ($categories = Database::loadAssocList('categoryID'))
			{
				$categoryIDs = array_filter(array_keys($categories));
				$programIDs  = array_unique(array_merge($programIDs, array_filter(Database::loadIntColumn(1))));

				$this->categoryIDs['exact'] = empty($this->categoryIDs['exact']) ?
					$categoryIDs : array_unique(array_merge($categoryIDs, $this->categoryIDs['exact']));
				$this->programIDs['exact']  = $programIDs;

				if ($requested)
				{
					$pCategories           = $this->processCnP($categories);
					$items['exact']['cnp'] = empty($items['exact']['cnp']) ?
						$pCategories : array_merge($items['exact']['cnp'], $pCategories);
				}
			}
		}

		/**
		 * -- Strong --
		 * @ Exact Degree IDs
		 * Category: All terms are present in the name and exact degree abbreviation is present.
		 * Program: All terms are present in the name and associated with an exact degree id match.
		 *
		 * @ Good Degree IDs
		 * Category: First term begins the name and a good degree abbreviation is present.
		 * Program: First term is the name associated with a good degree id match.
		 *
		 * @ No Degree IDs!
		 * Category: All terms are present in the name.
		 * Program: None.
		 */

		$cQuery->clear('where');
		$getCategories = false;
		$getPrograms   = false;
		$pQuery->clear('where');

		if ($initialTerm or $terms)
		{
			if ($this->degrees)
			{
				$cClauses = [];
				$pClauses = [];

				if ($terms and !empty($this->degrees['exact']))
				{
					$degrees    = $this->degrees['exact'];
					$cdDEClause = "c.name_de LIKE '%" . implode("%' OR c.name_de LIKE '%", $degrees) . "%'";
					$cdENClause = "c.name_en LIKE '%" . implode("%' OR c.name_en LIKE '%", $degrees) . "%'";
					$cdClause   = "($cdDEClause OR $cdENClause)";
					$ctDEClause = "c.name_de LIKE '%" . implode("%' AND c.name_de LIKE '%", $terms) . "%'";
					$ctENClause = "c.name_en LIKE '%" . implode("%' AND c.name_en LIKE '%", $terms) . "%'";
					$ctClause   = "(($ctDEClause) OR ($ctENClause))";
					$cClauses[] = "($cdClause AND $ctClause)";
					$pdClause   = 'p.degreeID IN (' . implode(',', array_keys($degrees)) . ')';
					$ptDEClause = "p.name_de LIKE '%" . implode("%' AND p.name_de LIKE '%", $terms) . "%'";
					$ptENClause = "p.name_en LIKE '%" . implode("%' AND p.name_en LIKE '%", $terms) . "%'";
					$ptClause   = "(($ptDEClause) OR ($ptENClause))";
					$pClauses[] = "($pdClause AND $ptClause)";
				}

				if ($initialTerm and !empty($this->degrees['good']))
				{
					$degrees    = $this->degrees['good'];
					$cdDEClause = "c.name_de LIKE '%" . implode("%' OR c.name_de LIKE '%", $degrees) . "%'";
					$cdENClause = "c.name_en LIKE '%" . implode("%' OR c.name_en LIKE '%", $degrees) . "%'";
					$cdClause   = "($cdDEClause OR $cdENClause)";
					$ctClause   = "(c.name_de LIKE '$initialTerm%' OR c.name_en LIKE '$initialTerm%')";
					$cClauses[] = "($cdClause AND $ctClause)";
					$pdClause   = 'p.degreeID IN (' . implode(',', array_keys($degrees)) . ')';
					$ptClause   = "(p.name_de LIKE '$initialTerm%' OR p.name_en LIKE '$initialTerm%')";
					$pClauses[] = "($pdClause AND $ptClause)";
				}

				// Both resources have clauses set under the same circumstances
				if ($cClauses)
				{
					$getCategories = true;
					$cQuery->where('(' . implode(' OR ', $cClauses) . ')');
					$getPrograms = true;
					$pQuery->where('(' . implode(' OR ', $pClauses) . ')');

				}

			}
			elseif ($terms)
			{
				$getCategories = true;

				$ctDEClause = "c.name_de LIKE '%" . implode("%' AND c.name_de LIKE '%", $terms) . "%'";
				$ctENClause = "c.name_en LIKE '%" . implode("%' AND c.name_en LIKE '%", $terms) . "%'";
				$cQuery->where("(($ctDEClause) OR ($ctENClause))");
			}
		}

		if ($getPrograms)
		{
			if ($programIDs)
			{
				$pQuery->where('p.id NOT IN (' . implode(',', $programIDs) . ')');
			}

			Database::setQuery($pQuery);

			if ($programs = Database::loadAssocList('programID'))
			{
				$this->categoryIDs['strong'] = array_filter(Database::loadIntColumn(1));
				$this->programIDs['strong']  = array_filter(array_keys($programs));

				$programIDs = array_unique(array_merge($programIDs, $this->programIDs['strong']));

				if ($requested)
				{
					$items['strong']['cnp'] = $this->processCnP($programs);
				}
			}
		}

		if ($getCategories)
		{
			if ($categoryIDs)
			{
				$cQuery->where('c.id NOT IN (' . implode(',', $categoryIDs) . ')');
			}

			if ($programIDs)
			{
				$cQuery->where('(p.id IS NULL OR p.id NOT IN (' . implode(',', $programIDs) . '))');
			}

			Database::setQuery($cQuery);

			if ($categories = Database::loadAssocList('categoryID'))
			{
				$sCategoryIDs = array_filter(array_keys($categories));
				$sProgramIDs  = array_filter(Database::loadIntColumn(1));

				$this->categoryIDs['strong'] = empty($this->categoryIDs['strong']) ?
					$sCategoryIDs : array_unique(array_merge($sCategoryIDs, $this->categoryIDs['strong']));
				$this->programIDs['strong']  = empty($this->programIDs['strong']) ?
					$sProgramIDs : array_unique(array_merge($sProgramIDs, $this->programIDs['strong']));

				$categoryIDs = array_unique(array_merge($categoryIDs, $sCategoryIDs));
				$programIDs  = array_unique(array_merge($programIDs, $sProgramIDs));


				if ($requested)
				{
					$pCategories            = $this->processCnP($categories);
					$items['strong']['cnp'] = empty($items['strong']['cnp']) ?
						$pCategories : array_merge($items['strong']['cnp'], $pCategories);
				}
			}
		}

		/**
		 * -- Good --
		 * @ Exact Degree IDs
		 * Category: A term is present in the name and exact degree abbreviation is present.
		 * Program: A term is present in the name and associated with an exact degree id match.
		 *
		 * @ Good Degree IDs
		 * Category: All terms are present in the name and a good degree abbreviation is present.
		 * Program: All terms are present in the name and associated with a good degree id match.
		 *
		 * @ No Degree IDs!
		 * Category: All terms are present in the name.
		 * Program: First term is the name.
		 *
		 * Categories and Programs with a configured Levenshtein distance of 0 are added to $this->categoryIDs['exact']
		 * and $this->programIDs['exact'] here after the fact. Additional terms for subordinate resources prevent them
		 * from being detected in the exact or strong areas, which is actually fine for the resources themselves, since
		 * the user is not looking specifically for those resources.
		 */

		$cQuery->clear('where');
		$eCategoryIDs  = [];
		$eProgramIDs   = [];
		$getCategories = false;
		$getPrograms   = false;
		$pQuery->clear('where');

		if ($initialTerm or $terms)
		{
			if ($this->degrees)
			{
				$cClauses = [];
				$pClauses = [];

				if ($this->filteredTerms and !empty($this->degrees['exact']))
				{
					$degrees    = $this->degrees['exact'];
					$cdDEClause = "c.name_de LIKE '%" . implode("%' OR c.name_de LIKE '%", $degrees) . "%'";
					$cdENClause = "c.name_en LIKE '%" . implode("%' OR c.name_en LIKE '%", $degrees) . "%'";
					$cdClause   = "($cdDEClause OR $cdENClause)";
					$ctDEClause = "c.name_de LIKE '%" . implode("%' OR c.name_de LIKE '%", $this->filteredTerms) . "%'";
					$ctENClause = "c.name_en LIKE '%" . implode("%' OR c.name_en LIKE '%", $this->filteredTerms) . "%'";
					$ctClause   = "($ctDEClause OR $ctENClause)";
					$cClauses[] = "($cdClause AND $ctClause)";
					$pdClause   = 'p.degreeID IN (' . implode(',', array_keys($degrees)) . ')';
					$ptDEClause = "p.name_de LIKE '%" . implode("%' OR p.name_de LIKE '%", $this->filteredTerms) . "%'";
					$ptENClause = "p.name_en LIKE '%" . implode("%' OR p.name_en LIKE '%", $this->filteredTerms) . "%'";
					$ptClause   = "($ptDEClause OR $ptENClause)";
					$pClauses[] = "($pdClause AND $ptClause)";
				}

				if ($initialTerm and !empty($this->degrees['good']))
				{
					$degrees    = $this->degrees['good'];
					$cdDEClause = "c.name_de LIKE '%" . implode("%' OR c.name_de LIKE '%", $degrees) . "%'";
					$cdENClause = "c.name_en LIKE '%" . implode("%' OR c.name_en LIKE '%", $degrees) . "%'";
					$cdClause   = "($cdDEClause OR $cdENClause)";
					$ctDEClause = "c.name_de LIKE '%" . implode("%' AND c.name_de LIKE '%", $terms) . "%'";
					$ctENClause = "c.name_en LIKE '%" . implode("%' AND c.name_en LIKE '%", $terms) . "%'";
					$ctClause   = "(($ctDEClause) OR ($ctENClause))";
					$cClauses[] = "($cdClause AND $ctClause)";
					$pdClause   = 'p.degreeID IN (' . implode(',', array_keys($degrees)) . ')';
					$ptDEClause = "c.name_de LIKE '%" . implode("%' AND c.name_de LIKE '%", $terms) . "%'";
					$ptENClause = "c.name_en LIKE '%" . implode("%' AND c.name_en LIKE '%", $terms) . "%'";
					$ptClause   = "(($ptDEClause) OR ($ptENClause))";
					$pClauses[] = "($pdClause AND $ptClause)";
				}

				// Both resources have clauses set under the same circumstances
				if ($cClauses)
				{
					$getCategories = true;
					$cQuery->where('(' . implode(' OR ', $cClauses) . ')');
					$getPrograms = true;
					$pQuery->where('(' . implode(' OR ', $pClauses) . ')');
				}
			}
			else
			{
				if ($initialTerm)
				{
					$getPrograms = true;
					$pQuery->where("(p.name_de LIKE '$initialTerm' OR p.name_en LIKE '$initialTerm')");
				}

				if ($terms)
				{
					$getCategories = true;

					$ctDEClause = "c.name_de LIKE '%" . implode("%' OR c.name_de LIKE '%", $terms) . "%'";
					$ctENClause = "c.name_en LIKE '%" . implode("%' OR c.name_en LIKE '%", $terms) . "%'";
					$cQuery->where("($ctDEClause OR $ctENClause)");
				}
			}
		}

		if ($getPrograms)
		{
			if ($programIDs)
			{
				$pQuery->where('p.id NOT IN (' . implode(',', $programIDs) . ')');
			}

			Database::setQuery($pQuery);

			if ($programs = Database::loadAssocList('programID'))
			{
				$theseProgramIDs  = array_filter(array_keys($programs));
				$programIDs       = array_unique(array_merge($programIDs, $theseProgramIDs));
				$theseCategoryIDs = array_filter(Database::loadIntColumn(1));

				foreach ($theseProgramIDs as $key => $programID)
				{
					$program = new Tables\Programs();

					if (!$program->load($programID))
					{
						continue;
					}

					$name = $this->prepareString($program->name_de);

					// The name is a true subset of the initial term => probably a search for a group/pool
					if (levenshtein($name, $initialTerm, 0, 5, 5) === 0)
					{
						$eProgramIDs[$programID] = $programID;

						if (!empty($theseCategoryIDs[$key]))
						{
							$eCategoryIDs[$theseCategoryIDs[$key]] = $theseCategoryIDs[$key];
						}

						continue;
					}

					$name = $this->prepareString($program->name_en);

					if (levenshtein($name, $initialTerm, 0, 5, 5) === 0)
					{
						$eProgramIDs[$programID] = $programID;

						if (!empty($theseCategoryIDs[$key]))
						{
							$eCategoryIDs[$theseCategoryIDs[$key]] = $theseCategoryIDs[$key];
						}
					}
				}

				if ($requested)
				{
					$items['good']['cnp'] = $this->processCnP($programs);
				}
			}
		}

		if ($getCategories)
		{
			if ($categoryIDs)
			{
				$cQuery->where('c.id NOT IN (' . implode(',', $categoryIDs) . ')');
			}

			if ($programIDs)
			{
				$cQuery->where('(p.id IS NULL OR p.id NOT IN (' . implode(',', $programIDs) . '))');
			}

			Database::setQuery($cQuery);

			if ($categories = Database::loadAssocList('categoryID'))
			{
				$theseCategoryIDs = array_filter(array_keys($categories));
				$categoryIDs      = array_unique(array_merge($categoryIDs, $theseCategoryIDs));
				$theseProgramIDs  = array_filter(Database::loadIntColumn(1));
				$programIDs       = array_unique(array_merge($programIDs, $theseProgramIDs));

				foreach ($theseCategoryIDs as $key => $categoryID)
				{
					$category = new Tables\Categories();

					if (!$category->load($categoryID))
					{
						continue;
					}

					$name = $this->prepareString($category->name_de);

					// The name is a true subset of the initial term => probably a search for a group/pool
					if (levenshtein($name, $initialTerm, 0, 5, 5) === 0)
					{
						$eCategoryIDs[$categoryID] = $categoryID;

						if (!empty($theseProgramIDs[$key]))
						{
							$eProgramIDs[$theseProgramIDs[$key]] = $theseProgramIDs[$key];
						}

						continue;
					}

					$name = $this->prepareString($category->name_en);

					if (levenshtein($name, $initialTerm, 0, 5, 5) === 0)
					{
						$eCategoryIDs[$categoryID] = $categoryID;

						if (!empty($theseProgramIDs[$key]))
						{
							$eProgramIDs[$theseProgramIDs[$key]] = $theseProgramIDs[$key];
						}
					}
				}

				if ($requested)
				{
					$pCategories          = $this->processCnP($categories);
					$items['good']['cnp'] = empty($items['good']['cnp']) ?
						$pCategories : array_merge($items['good']['cnp'], $pCategories);
				}
			}
		}

		if ($eCategoryIDs)
		{
			$this->categoryIDs['exact'] = empty($this->categoryIDs['exact']) ?
				$eCategoryIDs : array_unique(array_merge($eCategoryIDs, $this->categoryIDs['exact']));
		}

		if ($eProgramIDs)
		{
			$this->programIDs['exact'] = empty($this->programIDs['exact']) ?
				$eProgramIDs : array_unique(array_merge($eProgramIDs, $this->programIDs['exact']));
		}

		/**
		 * -- Mentioned --
		 * Anything here just seems to muddy the result set.
		 */

		/**
		 * -- Related --
		 * @ Organization IDs
		 * Category: The category is associated with a resolved organization.
		 * Program: The program is associated with a resolved organization.
		 */

		if ($this->organizationIDs)
		{

			$cQuery->clear('where')->where('o.id IN (' . implode(',', $this->organizationIDs) . ')');
			$pQuery->clear('where')->where('o.id IN (' . implode(',', $this->organizationIDs) . ')');

			if ($programIDs)
			{
				$pQuery->where('p.id NOT IN (' . implode(',', $programIDs) . ')');
			}

			Database::setQuery($pQuery);

			if ($programs = Database::loadAssocList('programID'))
			{
				$programIDs = array_unique(array_merge($programIDs, array_filter(array_keys($programs))));

				if ($requested)
				{
					$items['mentioned']['cnp'] = $this->processCnP($programs);
				}
			}

			if ($categoryIDs)
			{
				$cQuery->where('c.id NOT IN (' . implode(',', $categoryIDs) . ')');
			}

			if ($programIDs)
			{
				$cQuery->where('(p.id IS NULL OR p.id NOT IN (' . implode(',', $programIDs) . '))');
			}

			Database::setQuery($cQuery);

			if ($categories = Database::loadAssocList('categoryID'))
			{
				if ($requested)
				{
					$pCategories               = $this->processCnP($categories);
					$items['mentioned']['cnp'] = empty($items['mentioned']['cnp']) ?
						$pCategories : array_merge($items['mentioned']['cnp'], $pCategories);
				}
			}
		}

		if ($this->programIDs)
		{
			$degrees = [];
			foreach ($this->degrees as $groupedDegrees)
			{
				$degrees = $groupedDegrees + $degrees;
			}

			foreach ($this->programIDs as $strength => $programIDs)
			{
				$this->programDENames[$strength] = [];
				$this->programENNames[$strength] = [];

				foreach ($programIDs as $programID)
				{
					$program = new Tables\Programs();

					if (!$program->load($programID))
					{
						continue;
					}

					$suffix = empty($degrees[$program->degreeID]) ? '' : ' ' . $degrees[$program->degreeID];

					$this->programDENames[$strength][] = $this->prepareString($program->name_de . $suffix);
					$this->programENNames[$strength][] = $this->prepareString($program->name_en . $suffix);
				}
			}
		}
	}

	/**
	 * Retrieves prioritized event/subject search results.
	 *
	 * @param   array &$items  the container with the results
	 *
	 * @return void modifies &$items
	 */
	private function searchEnS(array &$items)
	{
		// Numeric flavoring: Mathematics '2'
		$salt  = [];
		$terms = $this->terms;

		foreach ($terms as $index => $term)
		{
			// Probable organization abbreviations.
			$short = strlen($term) < 4;

			// Probable sequence 'numbers'
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
		$eQuery->select('DISTINCT e.id AS eventID, s.id AS subjectID')
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
		$sQuery->select('DISTINCT s.id as subjectID, e.id AS eventID')
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
			's.fullName_en'
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
			$subjectIDs = array_unique(array_merge($subjectIDs, array_filter(Database::loadIntColumn())));

			$items['exact']['ens'] = $this->processEnS($subjects);
		}

		if ($subjectIDs)
		{
			$eQuery->where('(s.id IS NULL OR s.id NOT IN (' . implode(',', $subjectIDs) . '))');
		}

		Database::setQuery($eQuery);

		if ($events = Database::loadAssocList())
		{
			$eventIDs   = array_unique(array_merge($eventIDs, array_filter(Database::loadIntColumn())));
			$subjectIDs = array_unique(array_merge($subjectIDs, array_filter(Database::loadIntColumn(1))));

			$pEvents = $this->processEnS($events);

			$items['exact']['ens'] = empty($items['exact']['ens']) ?
				$pEvents : array_merge($items['exact']['ens'], $pEvents);
		}

		// Strong: all terms are present and salt is present if relevant ///////////////////////////////////////////////
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

		// If there is only one term to compare it should make up 80% of the name to get a strong rating.
		if (count($terms) === 1)
		{
			$term      = reset($terms);
			$length    = strlen($term);
			$eDEClause = "e.name_de LIKE '%$term%' AND $length / " . $eQuery->charLength('e.name_de') . " > .8";
			$eENClause = "e.name_en LIKE '%$term%' AND $length / " . $eQuery->charLength('e.name_en') . " > .8";
			$sDEClause = "s.fullName_de LIKE '%$term%' AND $length / " . $eQuery->charLength('s.fullName_de') . " > .8";
			$sENClause = "s.fullName_en LIKE '%$term%' AND $length / " . $eQuery->charLength('s.fullName_en') . " > .8";
		}
		else
		{
			$eDEClause = "e.name_de LIKE '%" . implode("%' AND e.name_de LIKE '%", $terms) . "%'";
			$eENClause = "e.name_en LIKE '%" . implode("%' AND e.name_en LIKE '%", $terms) . "%'";
			$sDEClause = "s.fullName_de LIKE '%" . implode("%' AND s.fullName_de LIKE '%", $terms) . "%'";
			$sENClause = "s.fullName_en LIKE '%" . implode("%' AND s.fullName_en LIKE '%", $terms) . "%'";
		}

		if ($salt)
		{

			$eDEClause .= " AND e.name_de LIKE '% $salt'";
			$eENClause .= " AND e.name_en LIKE '% $salt'";
			$sDEClause .= " AND s.fullName_de LIKE '% $salt'";
			$sENClause .= " AND s.fullName_en LIKE '% $salt'";
		}

		$eQuery->where("(($eDEClause) OR ($eENClause))");
		$sQuery->where("(($sDEClause) OR ($sENClause))");

		Database::setQuery($sQuery);

		if ($subjects = Database::loadAssocList())
		{
			$subjectIDs = array_unique(array_merge($subjectIDs, array_filter(Database::loadIntColumn())));

			$items['strong']['ens'] = $this->processEnS($subjects);
		}

		if ($eventIDs)
		{
			$eQuery->where('e.id NOT IN (' . implode(',', $eventIDs) . ')');
		}

		if ($subjectIDs)
		{
			$eQuery->where('(s.id IS NULL OR s.id NOT IN (' . implode(',', $subjectIDs) . '))');
		}

		Database::setQuery($eQuery);

		if ($events = Database::loadAssocList())
		{
			$eventIDs   = array_unique(array_merge($eventIDs, array_filter(Database::loadIntColumn())));
			$subjectIDs = array_unique(array_merge($subjectIDs, array_filter(Database::loadIntColumn(1))));

			$pEvents = $this->processEnS($events);

			$items['strong']['ens'] = empty($items['strong']['ens']) ?
				$pEvents : array_merge($items['strong']['ens'], $pEvents);
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
		foreach ($this->filteredTerms as $term)
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

			$items['good']['ens'] = $this->processEnS($subjects);
		}

		if ($eventIDs)
		{
			$eQuery->where('e.id NOT IN (' . implode(',', $eventIDs) . ')');
		}

		if ($subjectIDs)
		{
			$eQuery->where('(s.id IS NULL OR s.id NOT IN (' . implode(',', $subjectIDs) . '))');
		}

		Database::setQuery($eQuery);

		if ($events = Database::loadAssocList())
		{
			$eventIDs   = array_unique(array_merge($eventIDs, array_filter(Database::loadIntColumn())));
			$subjectIDs = array_unique(array_merge($subjectIDs, array_filter(Database::loadIntColumn(1))));

			$pEvents = $this->processEnS($events);

			$items['good']['ens'] = empty($items['good']['ens']) ?
				$pEvents : array_merge($items['good']['ens'], $pEvents);
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
		foreach ($this->filteredTerms as $term)
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

			$items['mentioned']['ens'] = $this->processEnS($subjects);
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

		// The query is set 'manually'. This flag takes the place of what is often otherwise wherray checks.
		$runQuery = false;
		if ($termCount == 1)
		{
			$eQuery->where("(p1.surname LIKE '%$initialTerm%' OR p2.surname LIKE '%$initialTerm%')");
			$runQuery = true;
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

			// Both are set under the same conditions.
			if ($eWherray)
			{
				$eQuery->where('(' . implode(' OR ', $eWherray) . ')');
				$runQuery = true;
				$sQuery->where('(' . implode(' OR ', $sWherray) . ')');
			}
		}

		if (!$runQuery)
		{
			return;
		}

		Database::setQuery($sQuery);

		if ($subjects = Database::loadAssocList())
		{
			$subjectIDs = array_unique(array_merge($subjectIDs, array_filter(Database::loadIntColumn())));

			$items['related']['ens'] = $this->processEnS($subjects);
		}

		if ($eventIDs)
		{
			$eQuery->where('e.id NOT IN (' . implode(',', $eventIDs) . ')');
		}

		if ($subjectIDs)
		{
			$eQuery->where('(s.id IS NULL OR s.id NOT IN (' . implode(',', $subjectIDs) . '))');
		}

		Database::setQuery($eQuery);

		if ($events = Database::loadAssocList())
		{
			$pEvents = $this->processEnS($events);

			$items['related']['ens'] = empty($items['related']['ens']) ?
				$pEvents : array_merge($items['related']['ens'], $pEvents);
		}
	}

	/**
	 * Retrieves prioritized group/pool search results. Pools are prioritized in the output for ease of
	 * comprehension.
	 *
	 * @param   array &$items  the container with the results
	 *
	 * @return void modifies &$items
	 */
	private function searchGnP(array &$items)
	{
		$categoryIDs = $this->categoryIDs;
		$programIDs  = $this->programIDs;
		$semesters   = $this->semesters;
		$terms       = array_values($this->terms);

		// No context and no clues
		if (!$categoryIDs and !$programIDs and !$this->terms)
		{
			return;
		}

		$poolTerms = $this->getPoolTerms();

		$groupIDs = [];
		$poolIDs  = [];

		$gQuery = Database::getQuery();
		$gQuery->select('DISTINCT g.id AS groupID, g.categoryID, g.name_de, g.name_en')
			->from('#__organizer_groups AS g');

		$conditions = "c2.lft < c1.lft AND c2.rgt > c1.rgt";
		$poQuery    = Database::getQuery();
		$poQuery->select('DISTINCT po.id AS poolID, po.fullName_de AS name_de, po.fullName_en AS name_en')
			->select('pr.id AS programID, pr.categoryID')
			->select('c1.lft, c1.rgt')
			->from('#__organizer_pools AS po')
			->innerJoin('#__organizer_curricula AS c1 ON c1.poolID = po.id')
			->innerJoin("#__organizer_curricula AS c2 ON $conditions")
			->innerJoin('#__organizer_programs AS pr ON pr.id = c2.programID');

		/**
		 * -- Exact --
		 * @Groups1: Exact categoryID and associated with an exact semester or the non-program terms have an 80% coverage.
		 * @Groups2: Exact programID and program name covers the group name (Finals Groups=.
		 * @Groups3: Filtered initial term (to include degree abbreviations as salt) has an 80% coverage.
		 * @Pools  : Exact programID and associated with an exact semester or the non-program terms have an 80% coverage.
		 */

		$exactCategoryIDs = ($categoryIDs and !empty($categoryIDs['exact'])) ? $categoryIDs['exact'] : [];
		$exactProgramIDs  = ($programIDs and !empty($programIDs['exact'])) ? $programIDs['exact'] : [];
		$exactSemesters   = ($semesters and $semesters['exact']) ? $semesters['exact'] : [];

		// Groups1
		if ($exactCategoryIDs)
		{
			$gQuery->where('g.categoryID IN (' . implode(',', $exactCategoryIDs) . ')');

			$wherray = [];

			foreach ($exactSemesters as $semester)
			{
				$wherray[] = "g.name_de LIKE '%$semester%'";
				$wherray[] = "g.name_en LIKE '%$semester%'";
			}

			if ($poolTerms)
			{
				$term      = implode('%', $poolTerms);
				$length    = strlen($term);
				$wherray[] = "(g.name_de LIKE '%$term%' AND $length / " . $poQuery->charLength('g.name_de') . " > .8)";
				$wherray[] = "(g.name_en LIKE '%$term%' AND $length / " . $poQuery->charLength('g.name_en') . " > .8)";
			}

			if ($wherray)
			{
				$gQuery->where('(' . implode(' OR ', $wherray) . ')');

				Database::setQuery($gQuery);

				if ($groups = Database::loadAssocList('groupID'))
				{
					$results = [];

					foreach ($groups as $groupID => $group)
					{
						$pools = $this->resolvePools($group);

						if ($pools)
						{
							foreach ($pools as $poolID => $pool)
							{
								if (in_array($poolID, $poolIDs))
								{
									continue;
								}

								$pool['groupID'] = $groupID;
								$results[]       = $pool;
							}

							$poolIDs = array_merge($poolIDs, array_filter(array_keys($pools)));
						}
						else
						{
							$group['categoryID'] = 0;
							$group['poolID']     = 0;
							$results[]           = $group;
						}
					}

					$groupIDs = array_merge($groupIDs, array_filter(array_keys($groups)));

					$items['exact']['gnp'] = $this->processGnP($results);
				}
			}
		}

		if ($exactProgramIDs)
		{
			// Groups2
			if (!empty($this->programDENames['exact']) and !empty($this->programENNames['exact']))
			{
				$gQuery->clear('where');

				if ($groupIDs)
				{
					$gQuery->where('g.id NOT IN(' . implode(',', $groupIDs) . ')');
				}

				$wherray = [];

				// Leading placeholders: no; trailing placeholders: yes
				foreach ($this->programDENames['exact'] as $name)
				{
					$name      = str_replace(' ', '%', $name);
					$wherray[] = "g.name_de LIKE '$name%'";
				}

				foreach ($this->programENNames['exact'] as $name)
				{
					$name      = str_replace(' ', '%', $name);
					$wherray[] = "g.name_en LIKE '$name%'";
				}

				$gQuery->where('(' . implode(' OR ', $wherray) . ')');

				Database::setQuery($gQuery);

				if ($groups = Database::loadAssocList('groupID'))
				{
					$results = [];

					foreach ($groups as $groupID => $group)
					{
						$pools = $this->resolvePools($group);

						if ($pools)
						{
							foreach ($pools as $poolID => $pool)
							{
								if (in_array($poolID, $poolIDs))
								{
									continue;
								}

								$pool['groupID'] = $groupID;
								$results[]       = $pool;
							}

							$poolIDs = array_merge($poolIDs, array_filter(array_keys($pools)));
						}
						else
						{
							$group['categoryID'] = 0;
							$group['poolID']     = 0;
							$results[]           = $group;
						}
					}

					$groupIDs = array_merge($groupIDs, array_filter(array_keys($groups)));

					$results               = $this->processGnP($results);
					$items['exact']['gnp'] = empty($items['exact']['gnp']) ?
						$results : array_merge($items['exact']['gnp'], $results);
				}
			}

			// Pools
			$poQuery->where('pr.id IN (' . implode(',', $exactProgramIDs) . ')');

			if ($poolIDs)
			{
				$poQuery->where('po.id NOT IN(' . implode(',', $poolIDs) . ')');
			}

			$wherray = [];

			foreach ($exactSemesters as $semester)
			{
				$wherray[] = "po.fullName_de LIKE '%$semester%'";
				$wherray[] = "po.fullName_en LIKE '%$semester%'";
			}

			if ($poolTerms)
			{
				$term      = implode('%', $poolTerms);
				$length    = strlen($term);
				$wherray[] = "(po.fullName_de LIKE '%$term%' AND $length / " . $poQuery->charLength('po.fullName_de') . " > .8)";
				$wherray[] = "(po.fullName_en LIKE '%$term%' AND $length / " . $poQuery->charLength('po.fullName_en') . " > .8)";
			}

			if ($wherray)
			{
				$poQuery->where('(' . implode(' OR ', $wherray) . ')');

				Database::setQuery($poQuery);

				if ($pools = Database::loadAssocList('poolID'))
				{
					$poolIDs       = array_merge($poolIDs, array_filter(array_keys($pools)));
					$theseGroupIDs = [];

					foreach ($pools as $poolID => $pool)
					{
						if (empty($pool['categoryID']))
						{
							$pools[$poolID]['groupID'] = 0;
							continue;
						}

						if ($groupID = $this->resolveGroup($pool))
						{
							$theseGroupIDs[$groupID] = $groupID;
						}

						$pools[$poolID]['groupID'] = $groupID;
					}

					$groupIDs = array_merge($groupIDs, array_filter($theseGroupIDs));

					$results               = $this->processGnP($pools);
					$items['exact']['gnp'] = empty($items['exact']['gnp']) ?
						$results : array_merge($items['exact']['gnp'], $results);
				}
			}
		}

		// Groups3
		if ($terms)
		{
			$gQuery->clear('where');

			if ($groupIDs)
			{
				$gQuery->where('g.id NOT IN(' . implode(',', $groupIDs) . ')');
			}

			// Degrees are still relevant here for abbreviated search terms.
			$degrees = [];
			foreach ($this->degrees as $groupedDegrees)
			{
				$degrees = $groupedDegrees + $degrees;
			}

			$initial = reset($terms);
			$term    = strpos($initial, ' ') === false ? implode('%', $terms) : str_replace(' ', '%', $initial);

			if ($degrees)
			{
				$deClauses = [];
				$enClauses = [];

				foreach ($degrees as $degree)
				{
					$thisTerm    = "$term%$degree";
					$length      = strlen($thisTerm);
					$deClauses[] = "(g.name_de LIKE '%$thisTerm%' AND $length / " . $gQuery->charLength('g.name_de') . " > .8)";
					$enClauses[] = "(g.name_en LIKE '%$thisTerm%' AND $length / " . $gQuery->charLength('g.name_en') . " > .8)";
				}

				$deClause = implode(' OR ', $deClauses);
				$enClause = implode(' OR ', $enClauses);
			}
			else
			{
				$length   = strlen($term);
				$deClause = "(g.name_de LIKE '%$term%' AND $length / " . $gQuery->charLength('g.name_de') . " > .8)";
				$enClause = "(g.name_en LIKE '%$term%' AND $length / " . $gQuery->charLength('g.name_en') . " > .8)";
			}

			$gQuery->where("($deClause OR $enClause)");

			Database::setQuery($gQuery);

			if ($groups = Database::loadAssocList('groupID'))
			{
				$results = [];

				foreach ($groups as $groupID => $group)
				{
					$pools = $this->resolvePools($group);

					if ($pools)
					{
						foreach ($pools as $poolID => $pool)
						{
							if (in_array($poolID, $poolIDs))
							{
								continue;
							}

							$pool['groupID'] = $groupID;
							$results[]       = $pool;
						}

						$poolIDs = array_merge($poolIDs, array_filter(array_keys($pools)));
					}
					else
					{
						$group['categoryID'] = 0;
						$group['poolID']     = 0;
						$results[]           = $group;
					}
				}

				$groupIDs = array_merge($groupIDs, array_filter(array_keys($groups)));

				$results               = $this->processGnP($results);
				$items['exact']['gnp'] = empty($items['exact']['gnp']) ?
					$results : array_merge($items['exact']['gnp'], $results);
			}
		}

		/**
		 * -- Strong --
		 * @Groups:
		 * -Exact categoryID & strong semester & non-program terms have a 60% coverage.
		 * -Strong categoryID & exact semester & non-program terms have a 60% coverage.
		 *
		 * @Pools :
		 * -Exact programID & strong semester & non-program terms have a 60% coverage.
		 * -Strong programID & exact semester & non-program terms have a 60% coverage.
		 */

		$strongCategoryIDs = ($categoryIDs and !empty($categoryIDs['strong'])) ? $categoryIDs['strong'] : [];
		$strongProgramIDs  = ($programIDs and !empty($programIDs['strong'])) ? $programIDs['strong'] : [];
		$strongSemesters   = ($semesters and $semesters['strong']) ? $semesters['strong'] : [];

		if ($exactCategoryIDs)
		{
			$gQuery->clear('where')->where('g.categoryID IN (' . implode(',', $exactCategoryIDs) . ')');

			if ($groupIDs)
			{
				$gQuery->where('g.id NOT IN(' . implode(',', $groupIDs) . ')');
			}

			$wherray = [];

			foreach ($strongSemesters as $semester)
			{
				$wherray[] = "g.name_de LIKE '%$semester%'";
				$wherray[] = "g.name_en LIKE '%$semester%'";
			}

			if ($poolTerms)
			{
				$term      = implode('%', $poolTerms);
				$length    = strlen($term);
				$wherray[] = "(g.name_de LIKE '%$term%' AND $length / " . $poQuery->charLength('g.name_de') . " > .6)";
				$wherray[] = "(g.name_en LIKE '%$term%' AND $length / " . $poQuery->charLength('g.name_en') . " > .6)";
			}

			if ($wherray)
			{
				$gQuery->where('(' . implode(' OR ', $wherray) . ')');

				Database::setQuery($gQuery);

				if ($groups = Database::loadAssocList('groupID'))
				{
					$results = [];

					foreach ($groups as $groupID => $group)
					{
						$pools = $this->resolvePools($group);

						if ($pools)
						{
							foreach ($pools as $poolID => $pool)
							{
								if (in_array($poolID, $poolIDs))
								{
									continue;
								}

								$pool['groupID'] = $groupID;
								$results[]       = $pool;
							}

							$poolIDs = array_merge($poolIDs, array_filter(array_keys($pools)));
						}
						else
						{
							$group['categoryID'] = 0;
							$group['poolID']     = 0;
							$results[]           = $group;
						}
					}

					$groupIDs = array_merge($groupIDs, array_filter(array_keys($groups)));

					$items['strong']['gnp'] = $this->processGnP($results);
				}
			}
		}

		if ($strongCategoryIDs)
		{
			$gQuery->clear('where')->where('g.categoryID IN (' . implode(',', $strongCategoryIDs) . ')');

			if ($groupIDs)
			{
				$gQuery->where('g.id NOT IN(' . implode(',', $groupIDs) . ')');
			}

			$wherray = [];

			foreach ($exactSemesters as $semester)
			{
				$wherray[] = "g.name_de LIKE '%$semester%'";
				$wherray[] = "g.name_en LIKE '%$semester%'";
			}

			if ($poolTerms)
			{
				$term      = implode('%', $poolTerms);
				$length    = strlen($term);
				$wherray[] = "(g.name_de LIKE '%$term%' AND $length / " . $poQuery->charLength('g.name_de') . " > .6)";
				$wherray[] = "(g.name_en LIKE '%$term%' AND $length / " . $poQuery->charLength('g.name_en') . " > .6)";
			}

			if ($wherray)
			{
				$gQuery->where('(' . implode(' OR ', $wherray) . ')');

				Database::setQuery($gQuery);

				if ($groups = Database::loadAssocList('groupID'))
				{
					$results = [];

					foreach ($groups as $groupID => $group)
					{
						$pools = $this->resolvePools($group);

						if ($pools)
						{
							foreach ($pools as $poolID => $pool)
							{
								if (in_array($poolID, $poolIDs))
								{
									continue;
								}

								$pool['groupID'] = $groupID;
								$results[]       = $pool;
							}

							$poolIDs = array_merge($poolIDs, array_filter(array_keys($pools)));
						}
						else
						{
							$group['categoryID'] = 0;
							$group['poolID']     = 0;
							$results[]           = $group;
						}
					}

					$groupIDs = array_merge($groupIDs, array_filter(array_keys($groups)));

					$results                = $this->processGnP($results);
					$items['strong']['gnp'] = empty($items['strong']['gnp']) ?
						$results : array_merge($items['strong']['gnp'], $results);
				}
			}
		}

		if ($exactProgramIDs)
		{
			$poQuery->clear('where')->where('pr.id IN (' . implode(',', $exactProgramIDs) . ')');

			if ($poolIDs)
			{
				$poQuery->where('po.id NOT IN(' . implode(',', $poolIDs) . ')');
			}

			$wherray = [];

			foreach ($strongSemesters as $semester)
			{
				$wherray[] = "po.fullName_de LIKE '%$semester%'";
				$wherray[] = "po.fullName_en LIKE '%$semester%'";
			}

			if ($poolTerms)
			{
				$term      = implode('%', $poolTerms);
				$length    = strlen($term);
				$wherray[] = "(po.fullName_de LIKE '%$term%' AND $length / " . $poQuery->charLength('po.fullName_de') . " > .6)";
				$wherray[] = "(po.fullName_en LIKE '%$term%' AND $length / " . $poQuery->charLength('po.fullName_en') . " > .6)";
			}

			if ($wherray)
			{
				$poQuery->where('(' . implode(' OR ', $wherray) . ')');

				Database::setQuery($poQuery);

				if ($pools = Database::loadAssocList('poolID'))
				{
					$poolIDs       = array_merge($poolIDs, array_filter(array_keys($pools)));
					$theseGroupIDs = [];

					foreach ($pools as $poolID => $pool)
					{
						if (empty($pool['categoryID']))
						{
							$pools[$poolID]['groupID'] = 0;
							continue;
						}

						if ($groupID = $this->resolveGroup($pool))
						{
							$theseGroupIDs[$groupID] = $groupID;
						}

						$pools[$poolID]['groupID'] = $groupID;
					}

					$groupIDs = array_merge($groupIDs, array_filter($theseGroupIDs));

					$results                = $this->processGnP($pools);
					$items['strong']['gnp'] = empty($items['strong']['gnp']) ?
						$results : array_merge($items['strong']['gnp'], $results);
				}
			}
		}

		if ($strongProgramIDs)
		{
			$poQuery->clear('where')->where('pr.id IN (' . implode(',', $strongProgramIDs) . ')');

			if ($poolIDs)
			{
				$poQuery->where('po.id NOT IN(' . implode(',', $poolIDs) . ')');
			}

			$wherray = [];

			foreach ($exactSemesters as $semester)
			{
				$wherray[] = "po.fullName_de LIKE '%$semester%'";
				$wherray[] = "po.fullName_en LIKE '%$semester%'";
			}

			if ($poolTerms)
			{
				$term      = implode('%', $poolTerms);
				$length    = strlen($term);
				$wherray[] = "(po.fullName_de LIKE '%$term%' AND $length / " . $poQuery->charLength('po.fullName_de') . " > .6)";
				$wherray[] = "(po.fullName_en LIKE '%$term%' AND $length / " . $poQuery->charLength('po.fullName_en') . " > .6)";
			}

			if ($wherray)
			{
				$poQuery->where('(' . implode(' OR ', $wherray) . ')');

				Database::setQuery($poQuery);

				if ($pools = Database::loadAssocList('poolID'))
				{
					$poolIDs       = array_merge($poolIDs, array_filter(array_keys($pools)));
					$theseGroupIDs = [];

					foreach ($pools as $poolID => $pool)
					{
						if (empty($pool['categoryID']))
						{
							$pools[$poolID]['groupID'] = 0;
							continue;
						}

						if ($groupID = $this->resolveGroup($pool))
						{
							$theseGroupIDs[$groupID] = $groupID;
						}

						$pools[$poolID]['groupID'] = $groupID;
					}

					$groupIDs = array_merge($groupIDs, array_filter($theseGroupIDs));

					$results                = $this->processGnP($pools);
					$items['strong']['gnp'] = empty($items['strong']['gnp']) ?
						$results : array_merge($items['strong']['gnp'], $results);
				}
			}
		}

		/**
		 * -- Good --
		 * @Groups:
		 * -Exact categoryID & good semester & non-program terms have a 60% coverage.
		 * -Strong categoryID & strong semester & non-program terms have a 60% coverage.
		 *
		 * @Pools :
		 * -Exact programID & good semester & non-program terms have a 60% coverage.
		 * -Strong programID & strong semester & non-program terms have a 60% coverage.
		 */

		$goodSemesters = ($semesters and $semesters['good']) ? $semesters['good'] : [];

		if ($exactCategoryIDs and $goodSemesters)
		{
			$gQuery->clear('where')->where('g.categoryID IN (' . implode(',', $exactCategoryIDs) . ')');

			if ($groupIDs)
			{
				$gQuery->where('g.id NOT IN(' . implode(',', $groupIDs) . ')');
			}

			$wherray = [];

			foreach ($goodSemesters as $semester)
			{
				$wherray[] = "g.name_de LIKE '%$semester%'";
				$wherray[] = "g.name_en LIKE '%$semester%'";
			}

			if ($poolTerms)
			{
				$term      = implode('%', $poolTerms);
				$length    = strlen($term);
				$wherray[] = "(g.name_de LIKE '%$term%' AND $length / " . $poQuery->charLength('g.name_de') . " > .6)";
				$wherray[] = "(g.name_en LIKE '%$term%' AND $length / " . $poQuery->charLength('g.name_en') . " > .6)";
			}

			if ($wherray)
			{
				$gQuery->where('(' . implode(' OR ', $wherray) . ')');

				Database::setQuery($gQuery);

				if ($groups = Database::loadAssocList('groupID'))
				{
					$results = [];

					foreach ($groups as $groupID => $group)
					{
						$pools = $this->resolvePools($group);

						if ($pools)
						{
							foreach ($pools as $poolID => $pool)
							{
								if (in_array($poolID, $poolIDs))
								{
									continue;
								}

								$pool['groupID'] = $groupID;
								$results[]       = $pool;
							}

							$poolIDs = array_merge($poolIDs, array_filter(array_keys($pools)));
						}
						else
						{
							$group['categoryID'] = 0;
							$group['poolID']     = 0;
							$results[]           = $group;
						}
					}

					$groupIDs = array_merge($groupIDs, array_filter(array_keys($groups)));

					$items['good']['gnp'] = $this->processGnP($results);
				}
			}
		}

		if ($strongCategoryIDs and $strongSemesters)
		{
			$gQuery->clear('where')->where('g.categoryID IN (' . implode(',', $strongCategoryIDs) . ')');

			if ($groupIDs)
			{
				$gQuery->where('g.id NOT IN(' . implode(',', $groupIDs) . ')');
			}

			$wherray = [];

			foreach ($strongSemesters as $semester)
			{
				$wherray[] = "g.name_de LIKE '%$semester%'";
				$wherray[] = "g.name_en LIKE '%$semester%'";
			}

			if ($poolTerms)
			{
				$term      = implode('%', $poolTerms);
				$length    = strlen($term);
				$wherray[] = "(g.name_de LIKE '%$term%' AND $length / " . $poQuery->charLength('g.name_de') . " > .6)";
				$wherray[] = "(g.name_en LIKE '%$term%' AND $length / " . $poQuery->charLength('g.name_en') . " > .6)";
			}

			if ($wherray)
			{
				$gQuery->where('(' . implode(' OR ', $wherray) . ')');

				Database::setQuery($gQuery);

				if ($groups = Database::loadAssocList('groupID'))
				{
					$results = [];

					foreach ($groups as $groupID => $group)
					{
						$pools = $this->resolvePools($group);

						if ($pools)
						{
							foreach ($pools as $poolID => $pool)
							{
								if (in_array($poolID, $poolIDs))
								{
									continue;
								}

								$pool['groupID'] = $groupID;
								$results[]       = $pool;
							}

							$poolIDs = array_merge($poolIDs, array_filter(array_keys($pools)));
						}
						else
						{
							$group['categoryID'] = 0;
							$group['poolID']     = 0;
							$results[]           = $group;
						}
					}

					$groupIDs = array_merge($groupIDs, array_filter(array_keys($groups)));

					$results              = $this->processGnP($results);
					$items['good']['gnp'] = empty($items['good']['gnp']) ?
						$results : array_merge($items['good']['gnp'], $results);
				}
			}
		}

		if ($exactProgramIDs and $goodSemesters)
		{
			$poQuery->clear('where')->where('pr.id IN (' . implode(',', $exactProgramIDs) . ')');

			if ($poolIDs)
			{
				$poQuery->where('po.id NOT IN(' . implode(',', $poolIDs) . ')');
			}

			$wherray = [];

			foreach ($goodSemesters as $semester)
			{
				$wherray[] = "po.fullName_de LIKE '%$semester%'";
				$wherray[] = "po.fullName_en LIKE '%$semester%'";
			}

			if ($poolTerms)
			{
				$term      = implode('%', $poolTerms);
				$length    = strlen($term);
				$wherray[] = "(po.fullName_de LIKE '%$term%' AND $length / " . $poQuery->charLength('po.fullName_de') . " > .6)";
				$wherray[] = "(po.fullName_en LIKE '%$term%' AND $length / " . $poQuery->charLength('po.fullName_en') . " > .6)";
			}

			if ($wherray)
			{
				$poQuery->where('(' . implode(' OR ', $wherray) . ')');

				Database::setQuery($poQuery);

				if ($pools = Database::loadAssocList('poolID'))
				{
					$poolIDs       = array_merge($poolIDs, array_filter(array_keys($pools)));
					$theseGroupIDs = [];

					foreach ($pools as $poolID => $pool)
					{
						if (empty($pool['categoryID']))
						{
							$pools[$poolID]['groupID'] = 0;
							continue;
						}

						if ($groupID = $this->resolveGroup($pool))
						{
							$theseGroupIDs[$groupID] = $groupID;
						}

						$pools[$poolID]['groupID'] = $groupID;
					}

					$groupIDs = array_merge($groupIDs, array_filter($theseGroupIDs));

					$results              = $this->processGnP($pools);
					$items['good']['gnp'] = empty($items['good']['gnp']) ?
						$results : array_merge($items['good']['gnp'], $results);
				}
			}
		}

		if ($strongProgramIDs and $strongSemesters)
		{
			$poQuery->clear('where')->where('pr.id IN (' . implode(',', $strongProgramIDs) . ')');

			if ($poolIDs)
			{
				$poQuery->where('po.id NOT IN(' . implode(',', $poolIDs) . ')');
			}

			$wherray = [];

			foreach ($strongSemesters as $semester)
			{
				$wherray[] = "po.fullName_de LIKE '%$semester%'";
				$wherray[] = "po.fullName_en LIKE '%$semester%'";
			}

			if ($poolTerms)
			{
				$term      = implode('%', $poolTerms);
				$length    = strlen($term);
				$wherray[] = "(po.fullName_de LIKE '%$term%' AND $length / " . $poQuery->charLength('po.fullName_de') . " > .6)";
				$wherray[] = "(po.fullName_en LIKE '%$term%' AND $length / " . $poQuery->charLength('po.fullName_en') . " > .6)";
			}

			if ($wherray)
			{
				$poQuery->where('(' . implode(' OR ', $wherray) . ')');

				Database::setQuery($poQuery);

				if ($pools = Database::loadAssocList('poolID'))
				{
					$poolIDs       = array_merge($poolIDs, array_filter(array_keys($pools)));
					$theseGroupIDs = [];

					foreach ($pools as $poolID => $pool)
					{
						if (empty($pool['categoryID']))
						{
							$pools[$poolID]['groupID'] = 0;
							continue;
						}

						if ($groupID = $this->resolveGroup($pool))
						{
							$theseGroupIDs[$groupID] = $groupID;
						}

						$pools[$poolID]['groupID'] = $groupID;
					}

					$groupIDs = array_merge($groupIDs, array_filter($theseGroupIDs));

					$results              = $this->processGnP($pools);
					$items['good']['gnp'] = empty($items['good']['gnp']) ?
						$results : array_merge($items['good']['gnp'], $results);
				}
			}
		}

		/**
		 * -- Mentioned --
		 */

		/**
		 * -- Related --
		 * Groups and pools associated with exact category and program results.
		 */
		$gQuery->clear('where');
		$poQuery->clear('where');

		if ($exactCategoryIDs)
		{
			$gQuery->where('g.categoryID IN (' . implode(',', $exactCategoryIDs) . ')');

			if ($groupIDs)
			{
				$gQuery->where('g.id NOT IN(' . implode(',', $groupIDs) . ')');
			}

			Database::setQuery($gQuery);

			if ($groups = Database::loadAssocList('groupID'))
			{
				$results = [];

				foreach ($groups as $groupID => $group)
				{
					$pools = $this->resolvePools($group);

					if ($pools)
					{
						foreach ($pools as $poolID => $pool)
						{
							if (in_array($poolID, $poolIDs))
							{
								continue;
							}

							$pool['groupID'] = $groupID;
							$results[]       = $pool;
						}

						$poolIDs = array_merge($poolIDs, array_filter(array_keys($pools)));
					}
					else
					{
						$group['categoryID'] = 0;
						$group['poolID']     = 0;
						$results[]           = $group;
					}
				}

				$items['related']['gnp'] = $this->processGnP($results);
			}
		}

		if ($exactProgramIDs)
		{
			$poQuery->where('pr.id IN (' . implode(',', $exactProgramIDs) . ')');

			if ($poolIDs)
			{
				$poQuery->where('po.id NOT IN(' . implode(',', $poolIDs) . ')');
			}

			Database::setQuery($poQuery);

			if ($pools = Database::loadAssocList('poolID'))
			{
				foreach ($pools as $poolID => $pool)
				{
					if (empty($pool['categoryID']))
					{
						$pools[$poolID]['groupID'] = 0;
						continue;
					}

					$pools[$poolID]['groupID'] = $this->resolveGroup($pool);
				}

				$results                 = $this->processGnP($pools);
				$items['related']['gnp'] = empty($items['related']['gnp']) ?
					$results : array_merge($items['related']['gnp'], $results);
			}
		}
	}

	/**
	 * Retrieves prioritized organization search results.
	 *
	 * @param   array &$items      the container with the results
	 * @param   bool   $requested  true: results added to output; false: results used for subordinate context
	 *
	 * @return void modifies &$items
	 */
	private function searchOrganizations(array &$items, bool $requested = true)
	{
		if (!$this->terms or empty($this->terms[0]))
		{
			return;
		}

		$nameColumns     = [
			'abbreviation_de',
			'abbreviation_en',
			'fullName_de',
			'fullName_en',
			'name_de',
			'name_en',
			'shortName_de',
			'shortName_en'
		];
		$shortColumns    = ['abbreviation_de', 'abbreviation_en', 'shortName_de', 'shortName_en'];
		$organizationIDs = [];
		$wherray         = [];

		$query = Database::getQuery();
		$query->select('DISTINCT id')->from('#__organizer_organizations');
		$allTerms = $this->terms[0];

		// Exact: the original term exists (CI) as a name attribute.
		foreach ($nameColumns as $column)
		{
			$wherray[] = "$column LIKE '$allTerms'";
		}

		if ($wherray)
		{
			$query->where('(' . implode(' OR ', $wherray) . ')');
			Database::setQuery($query);

			if ($organizations = Database::loadIntColumn())
			{
				$this->organizationIDs = $organizations;
				$organizationIDs       = array_unique(array_merge($organizationIDs, $organizations));

				if ($requested)
				{
					$items['exact']['organizations'] = $this->processOrganizations($organizations);
				}
			}
		}

		// Strong: one name column has all search terms (CI).
		$query->clear('where');
		$skipInitial = count($this->terms) > 1;
		$wherray     = [];

		if ($organizationIDs)
		{
			$query->where('id NOT IN (' . implode(',', $organizationIDs) . ')');
		}

		foreach ($nameColumns as $column)
		{
			$thisWherray = [];

			foreach ($this->terms as $key => $term)
			{
				if ($key === 0 and $skipInitial)
				{
					continue;
				}

				if (strlen($term) < 4 and !in_array($column, $shortColumns))
				{
					continue;
				}

				$thisWherray[] = "$column LIKE '%$term%'";
			}

			if ($thisWherray)
			{
				$wherray[] = '(' . implode(' AND ', $thisWherray) . ')';
			}
		}

		if ($wherray)
		{
			$query->where('(' . implode(' OR ', $wherray) . ')');
			Database::setQuery($query);

			if ($organizations = Database::loadIntColumn())
			{
				$organizationIDs = array_unique(array_merge($organizationIDs, $organizations));

				if ($requested)
				{
					$items['strong']['organizations'] = $this->processOrganizations($organizations);
				}
			}
		}

		// Good: one name column has a filtered search term (CI).
		$query->clear('where');
		$wherray = [];

		if ($organizationIDs)
		{
			$query->where('id NOT IN (' . implode(',', $organizationIDs) . ')');
		}

		foreach ($nameColumns as $column)
		{
			foreach ($this->terms as $key => $term)
			{
				if ($key === 0 and $skipInitial or strlen($term) < 4 or is_numeric($term))
				{
					continue;
				}

				$wherray[] = "$column LIKE '%$term%'";
			}
		}

		if ($wherray)
		{
			$query->where('(' . implode(' OR ', $wherray) . ')');
			Database::setQuery($query);

			if ($organizations = Database::loadIntColumn())
			{
				if ($requested)
				{
					$items['good']['organizations'] = $this->processOrganizations($organizations);
				}
			}
		}
	}

	/**
	 * Retrieves prioritized person search results.
	 *
	 * @param   array &$items  the container with the results
	 *
	 * @return void modifies &$items
	 */
	private function searchPersons(array &$items)
	{
		$terms = $this->terms;

		// No names shorter than two characters
		foreach ($terms as $index => $term)
		{
			if (strlen($term) < 2)
			{
				unset($terms[$index]);
			}
		}

		if (!$terms = array_values($terms))
		{
			return;
		}

		$allIDs = [];
		$count  = count($terms);

		$query = Database::getQuery();
		$query->select('id')->from('#__organizer_persons')->order('surname, forename');

		/**
		 * --EXACT--
		 * Near matches against both forename and surname
		 */
		if ($count >= 2)
		{
			$wherray    = [];
			$innerTerms = $terms;

			foreach ($terms as $outerTerm)
			{
				foreach ($innerTerms as $iKey => $innerTerm)
				{
					if ($outerTerm == $innerTerm)
					{
						unset($innerTerms[$iKey]);
						continue;
					}

					// lnf/fnf
					$wherray[] = "(surname LIKE '%$outerTerm%' AND forename LIKE '%$innerTerm%')";
					$wherray[] = "(surname LIKE '%$innerTerm%' AND forename LIKE '%$outerTerm%')";
				}
			}

			$query->where('(' . implode(' OR ', $wherray) . ')');
			Database::setQuery($query);

			if ($personIDs = Database::loadIntColumn())
			{
				$allIDs = $personIDs;

				$items['exact']['persons'] = $this->processPersons($personIDs);
			}
		}

		/**
		 * --STRONG--
		 * ~Exact match on surname
		 */
		$query->clear('where');
		$wherray = [];

		if ($allIDs)
		{
			$query->where('id NOT IN (' . implode(',', $allIDs) . ')');
		}

		foreach ($terms as $term)
		{
			$wherray[] = "surname LIKE '$term'";
		}

		$query->where('(' . implode(' OR ', $wherray) . ')');
		Database::setQuery($query);

		if ($personIDs = Database::loadIntColumn())
		{
			$allIDs = array_merge($personIDs, $allIDs);

			$items['strong']['persons'] = $this->processPersons($personIDs);
		}

		/**
		 * --GOOD--
		 * ~Near match on surname
		 */
		$query->clear('where');
		$wherray = [];

		if ($allIDs)
		{
			$query->where('id NOT IN (' . implode(',', $allIDs) . ')');
		}

		foreach ($terms as $term)
		{
			$wherray[] = "surname LIKE '%$term%'";
		}

		$query->where('(' . implode(' OR ', $wherray) . ')');
		Database::setQuery($query);

		if ($personIDs = Database::loadIntColumn())
		{
			$items['good']['persons'] = $this->processPersons($personIDs);
		}
	}

	/**
	 * Sets prioritized room search results.
	 *
	 * @param   array &$items  the container with the results
	 *
	 * @return void modifies &$items
	 */
	private function searchRooms(array &$items)
	{
		$tag   = Languages::getTag();
		$terms = $this->terms;

		foreach ($terms as $key => $value)
		{
			if (strlen($value) < 5)
			{
				unset($terms[$key]);
			}
		}

		// Everything was too short
		if (empty($terms))
		{
			return;
		}

		// Re-key
		$terms = array_values($terms);

		$query = Database::getQuery();
		$query->select('r.id , r.name, r.effCapacity')
			->select("rt.name_$tag as type, rt.description_$tag as description")
			->from('#__organizer_rooms AS r')
			->leftJoin('#__organizer_roomtypes AS rt ON rt.id = r.roomtypeID')
			->order('r.name ASC');

		// EXACT => most room searches should be of this variety

		$wherray = [];

		foreach ($terms as $term)
		{
			$wherray[] = "r.name LIKE '$term'";
		}

		$query->where('(' . implode(' OR ', $wherray) . ')');
		Database::setQuery($query);

		if ($results = $this->processRooms(Database::loadAssocList()))
		{
			$items['exact']['rooms'] = $results;
		}

		// STRONG => NC
		$capacity = 0;
		$ncRooms  = [];
		$wherray  = [];
		$query->clear('where');

		// Strong matches
		foreach ($terms as $index => $term)
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

			if ($results = $this->processRooms(Database::loadAssocList()))
			{
				$items['strong']['rooms'] = $results;
			}
		}

		if (!$capacity and !$typeIDs)
		{
			return;
		}

		// Related => has type or capacity relevance

		$query->clear('where');
		$this->addRoomClauses($query, $capacity, $typeIDs);

		Database::setQuery($query);

		if ($results = $this->processRooms(Database::loadAssocList()))
		{
			$items['related']['rooms'] = $results;
		}
	}

	/**
	 * Creates a filtered list of terms suitable for use in chained 'OR' clauses or in searches of long texts.
	 *
	 * @return void
	 */
	private function setFilteredTerms()
	{
		foreach ($this->terms as $term)
		{
			if (strpos($term, ' ') === false and !in_array($term, $this->whiteNoise))
			{
				$this->filteredTerms[] = $term;
			}
		}
	}

	/**
	 * Sets plausible degree ids for later use in searches.
	 *
	 * @param   string  &$search  the string containing the search terms
	 *
	 * @return void
	 */
	private function setDegrees(string &$search)
	{
		// Exact: abbreviation or level and type could be resolved; Good: level could be resolved
		$degrees = ['exact' => [], 'good' => []];
		preg_match_all('/((^| )([bm]\.?(a|b\.?a|ed|eng|sc)\.?)(?![A-ZÀ-ÖØ-Þa-zß-ÿ\d]))/', $search, $abbreviations);

		if ($abbreviations and !empty($abbreviations[3]))
		{
			foreach ($abbreviations[3] as $abbreviation)
			{
				$search = str_replace($abbreviation, '', $search);
				$alias  = str_replace('.', '', trim($abbreviation));
				$degree = new Tables\Degrees();

				if ($degree->load(['alias' => $alias]))
				{
					$degrees['exact'][$degree->id] = $degree->abbreviation;
				}
			}
		}

		$degrees['exact'] = array_unique($degrees['exact']);
		$previousIDs      = array_keys($degrees['exact']);
		preg_match_all('/((^| )bachelor|master($| ))/', $search, $levels);

		if ($levels and $levels = $levels[0])
		{
			// If levels are existent check for and remove types.
			if ($arts = strpos($search, 'arts') !== false)
			{
				$search = str_replace('arts', '', $search);
			}

			$administration = strpos($search, 'administration') !== false;
			$business       = strpos($search, 'business') !== false;
			$ba             = ($administration and $business);
			if ($ba)
			{
				$search = str_replace('business', '', str_replace('administration', '', $search));
				$search = preg_replace('/(^| )engineering($| )/', ' ', $search, 1);
			}

			if ($education = strpos($search, 'education') !== false)
			{
				$search = str_replace('education', '', $search);
			}

			if ($engineering = strpos($search, 'engineering') !== false)
			{
				$search = str_replace('engineering', '', $search);
			}

			if ($science = strpos($search, 'science') !== false)
			{
				$search = str_replace('science', '', $search);
			}

			$someType = ($arts or $ba or $education or $engineering or $science);

			foreach ($levels as $level)
			{
				$level  = trim($level);
				$search = str_replace($level, '', $search);
				$alias  = $level[0];

				if ($someType)
				{
					if ($arts)
					{
						$thisAlias = $alias . 'a';
						$degree    = new Tables\Degrees();

						if ($degree->load(['alias' => $thisAlias]))
						{
							$degrees['exact'][$degree->id] = $degree->abbreviation;
						}
					}

					if ($ba)
					{
						$thisAlias = $alias . 'ba';
						$degree    = new Tables\Degrees();

						if ($degree->load(['alias' => $thisAlias]))
						{
							$degrees['exact'][$degree->id] = $degree->id;
						}
					}

					if ($education)
					{
						$thisAlias = $alias . 'ed';
						$degree    = new Tables\Degrees();

						if ($degree->load(['alias' => $thisAlias]))
						{
							$degrees['exact'][$degree->id] = $degree->id;
						}
					}

					if ($engineering)
					{
						$thisAlias = $alias . 'eng';
						$degree    = new Tables\Degrees();

						if ($degree->load(['alias' => $thisAlias]))
						{
							$degrees['exact'][$degree->id] = $degree->id;
						}
					}

					if ($education)
					{
						$thisAlias = $alias . 'ed';
						$degree    = new Tables\Degrees();

						if ($degree->load(['alias' => $thisAlias]))
						{
							$degrees['exact'][$degree->id] = $degree->id;
						}
					}
				}
				else
				{
					$query = Database::getQuery();
					$query->select('DISTINCT id, abbreviation')
						->from('#__organizer_degrees')
						->where("alias LIKE '$alias%'");

					if ($previousIDs)
					{
						$ids = implode(',', $previousIDs);
						$query->where("id NOT IN ($ids)");
					}

					Database::setQuery($query);

					if ($results = Database::loadAssocList())
					{
						$resultIDs   = Database::loadIntColumn();
						$previousIDs = array_unique(array_merge($previousIDs, $resultIDs));

						foreach ($results as $result)
						{
							$degrees['good'][$result['id']] = $result['abbreviation'];
						}
					}
				}
			}
		}

		$this->degrees = ($degrees['exact'] or $degrees['good']) ? $degrees : [];
	}

	/**
	 * Sets plausible semester strings for later use in searches.
	 *
	 * @param   string  &$search  the string containing the search terms
	 *
	 * @return void
	 */
	private function setSemesters(string &$search)
	{
		// Remove English and German ordinals
		$search = preg_replace('/([1-9])(?:\.|st|nd|rd|th|te)/', "$1", $search);

		$salt      = '';
		$semesters = ['exact' => [], 'strong' => [], 'good' => []];

		// Filter out expected longer form semester names
		$pattern = '/(^| )([1-9]( ?[\/&] ?[1-9])*|abschluss-?|elective|final) ?(semesters?|sem.)/';
		preg_match_all($pattern, $search, $lSemesters);

		if ($lSemesters and !empty($lSemesters[0]))
		{
			$lSemesters = $lSemesters[0];
			$search     = str_replace($lSemesters, ' ', $search);

			// Filter out flavour particles unique to semesters
			$pattern = '/(^| )(((begin|start) (ss|ws))|((mit|no|ohne|with) (schwerpunkt|focus))|(optional))($| )/';
			preg_match_all($pattern, $search, $salts);

			// If there are multiple salt there would need to be an ordering which is beyond this scope
			if ($salts and $salts[0] and count($salts[0]) === 1)
			{
				$salt   = trim($salts[0][0]);
				$search = str_replace($salt, ' ', $search);
			}

			foreach ($lSemesters as $semester)
			{
				// REGEX had leading and trailing spaces and replacing braces may have added them
				$semester = trim($semester);

				// Ensure that positive match by going with the least common denominator in contex 'sem'
				$semester = str_replace('ester', '', $semester);

				// Standardize spacing for the differing 'Abschluss' pools
				$semester = str_replace(['sss', 'ss-s'], 'ss s', $semester);

				// If there is salt exact is the semester and salt
				if ($salt)
				{
					$semesters['exact'][]  = str_replace(' ', '%', "$semester $salt");
					$semesters['strong'][] = str_replace(' ', '%', $semester);
				}
				else
				{
					$semesters['exact'][] = str_replace(' ', '%', $semester);
				}

				// Check for aggregated numerical semesters
				preg_match_all('!\d+!', $semester, $numbers);

				if ($numbers and $numbers[0] and count($numbers[0]) > 1)
				{
					foreach ($numbers[0] as $number)
					{
						$strength = empty($semesters['strong']) ? 'strong' : 'good';

						$semesters[$strength][] = "$number%sem";
					}
				}
			}
		}

		// Filter out any remaining instances of the semester keyword. Only relevant in groups/pool context.
		preg_match_all('/(^| )(semesters?|sem.?)($| )/', $search, $sSemesters);

		if ($sSemesters and !empty($sSemesters[0]))
		{
			$sSemesters = $sSemesters[0];
			$search     = str_replace($sSemesters, ' ', $search);

			if (!$salt)
			{
				// Filter out flavour particles unique to semesters
				$pattern = '/(^| )(((begin|start) (ss|ws))|((mit|no|ohne|with) (schwerpunkt|focus))|(optional))($| )/';
				preg_match_all($pattern, $search, $salts);

				// If there are multiple salt there would need to be an ordering which is beyond this scope
				if ($salts and $salts[0] and count($salts[0]) === 1)
				{
					$salt   = trim($salts[0][0]);
					$search = str_replace($salt, ' ', $search);
				}
			}

			if ($salt)
			{
				foreach ($sSemesters as $semester)
				{
					// REGEX had leading and trailing spaces and replacing braces may have added them
					$semester = trim($semester);

					// Ensure that positive match by going with the least common denominator in contex 'sem'
					$semester = str_replace('ester', '', $semester);

					$semesters['good'][] = str_replace(' ', '%', "$semester $salt");
				}
			}
		}

		$this->semesters = $semesters;
	}

	/**
	 * Set the search terms.
	 *
	 * @return void sets the $terms property
	 */
	private function setTerms()
	{
		if (!$search = $this->state->get('filter.search'))
		{
			$this->terms = [];

			return;
		}

		$search = $this->prepareString($search);

		$this->setDegrees($search);
		$this->setSemesters($search);

		$this->terms = [];
		$search      = trim(preg_replace('/ +/', ' ', $search));

		// Add the 'original' search to first index of the array
		array_unshift($this->terms, $search);

		$articles = [
			'a',
			'das',
			'dem',
			'den',
			'der',
			'des',
			'die',
			'ein',
			'eine',
			'einem',
			'einen',
			'einer',
			'eines',
			'the',
		];

		foreach ($articles as $article)
		{
			$search = preg_replace("/(^| )$article($| )/", ' ', $search);
		}

		$remainingTerms = array_filter(explode(' ', $search));

		foreach ($remainingTerms as $term)
		{
			// Anything under 3 characters will produce a mountain of irrelevant positives.
			if (is_numeric($term) or strlen($term) < 3)
			{
				continue;
			}

			$this->terms[] = $term;
		}

		// Remove non-unique terms to prevent bloated queries
		$this->terms = array_unique($this->terms);

		$this->setFilteredTerms();
	}
}