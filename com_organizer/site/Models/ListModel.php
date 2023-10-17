<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use Exception;
use JDatabaseQuery;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel as Base;
use Joomla\CMS\Table\Table;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use THM\Organizer\Adapters\{Application, Database, Input, Queries\QueryMySQLi};
use THM\Organizer\Helpers;
use stdClass;

/**
 * Model class for handling lists of items.
 * - Overrides/-writes to avoid deprecated code in the platform or promote ease of use
 * - Supplemental functions to extract common code from list models
 */
abstract class ListModel extends Base
{
    use Named;

    protected const ALL = 0, NONE = -1, CURRENT = 1, NEW = 2, REMOVED = 3, CHANGED = 4;

    protected string $defaultOrdering = 'name';

    protected string $defaultDirection = 'ASC';

    protected int $defaultLimit = 50;

    /**
     * The URL option for the component. If this is missing an error will be thrown because the class does not have the
     * word "Model" in its name.
     * @var string
     * @see BaseDatabaseModel
     */
    protected $option = 'com_organizer';

    /**
     * A state object. Overrides the use of the deprecated CMSObject.
     * @var    Registry
     */
    protected $state = null;

    /**
     * @inheritDoc
     */
    public function __construct($config = [], MVCFactoryInterface $factory = null)
    {
        // Preemptively set to avoid unnecessary complications.
        $this->setContext();
        $this->state = new Registry();

        try {
            parent::__construct($config, $factory);
        } catch (Exception $exception) {
            Application::handleException($exception);
        }

        $app                  = Application::getApplication();
        $this->filterFormName = strtolower(Helpers\OrganizerHelper::getClass($this));

        if (!is_numeric($this->defaultLimit)) {
            $this->defaultLimit = $app->get('list_limit', 50);
        }
    }

    /**
     * Adds a binary value filter clause for the given $query;
     *
     * @param QueryInterface $query the query to modify
     * @param string         $name  the attribute whose value to filter against
     *
     * @return void modifies the query if a binary value was delivered in the request
     */
    protected function binaryFilter(QueryInterface $query, string $name): void
    {
        $value = $this->state->get($name);

        // State default for get is null and default for request is either an empty string or not being set.
        if (!$this->isBinary($value)) {
            return;
        }

        $value = (int) $value;

        // Typical filter names are in the form 'filter.column'
        $column = strpos($name, '.') ? substr($name, strpos($name, '.') + 1) : $name;
        $column = $this->getDatabase()->quoteName($column);
        $query->where("$column = $value");
    }

    /** Provides external access to the clean cache function. This belongs in the input adapter, but I do not want to
     *  have to put in the effort to resolve everything necessary to get it there.
     * @void initiates cache cleaning
     */
    public function emptyCache(): void
    {
        $this->cleanCache();
    }

    /**
     * Filters out form inputs which should not be displayed due to menu settings.
     *
     * @param Form $form the form to be filtered
     *
     * @return void modifies $form
     */
    protected function filterFilterForm(Form $form): void
    {
        // No implementation is the default implementation.
    }

    /**
     * @inheritDoc
     * Replacing the deprecated CMSObject with Registry makes the parent no longer function correctly this compensates
     * for that.
     */
    public function getActiveFilters(): array
    {
        $activeFilters = [];

        if (!empty($this->filter_fields)) {
            foreach ($this->filter_fields as $filter) {
                $filterName = 'filter.' . $filter;
                $value      = $this->state->get($filterName);

                if ($value or is_numeric($value)) {
                    $activeFilters[$filter] = $value;
                }
            }
        }

        return $activeFilters;
    }

    /**
     * Gets the filter form. Overwrites the parent to have form names analog to the view names in which they are used.
     * Also has enhanced error reporting in the event of failure.
     *
     * @param array $data     data
     * @param bool  $loadData load current data
     *
     * @return  Form|null  the form object or null if the form can't be found
     */
    public function getFilterForm($data = [], $loadData = true): ?Form
    {
        $this->filterFormName = strtolower($this->name);

        $context = $this->context . '.filter';
        $options = ['control' => '', 'load_data' => $loadData];

        try {
            return $this->loadForm($context, $this->filterFormName, $options);
        } catch (Exception $exception) {
            Application::handleException($exception);
        }

        return null;
    }

    /**
     * @inheritDoc
     * @return  array  An array of data items on success.
     */
    public function getItems(): array
    {
        $items = parent::getItems();

        return $items ?: [];
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param string $name    the table name, unused
     * @param string $prefix  the class prefix, unused
     * @param array  $options configuration array for model, unused
     *
     * @return  Table  a table object
     */
    public function getTable($name = '', $prefix = '', $options = []): Table
    {
        // With few exception the table and list class names are identical
        $class = Application::getClass($this);
        $fqn   = "\\THM\\Groups\\Tables\\$class";

        return new $fqn();
    }

    /**
     * @inheritDoc
     */
    public function getTotal($idColumn = null)
    {
        if (empty($idColumn)) {
            return parent::getTotal();
        }

        // Get a storage key.
        $store = $this->getStoreId('getTotal');

        // Try to load the data from internal storage.
        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        // Load the total.
        $query = $this->getListQuery();
        $query->clear('select')->clear('limit')->clear('offset')->clear('order');
        $query->select("COUNT(DISTINCT ($idColumn))");
        Database::setQuery($query);
        $total = Database::loadInt();

        // Add the total to the internal cache.
        $this->cache[$store] = $total;

        return $this->cache[$store];
    }

    /**
     * @inheritDoc
     */
    protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = false): Form
    {
        if ($form = parent::loadForm($name, $source, $options, $clear, $xpath)) {
            $this->filterFilterForm($form);
        }

        return $form;
    }

    /**
     * Checks whether the given value can safely be interpreted as a binary value.
     *
     * @param mixed $value the value to be checked
     *
     * @return bool if the value can be interpreted as a binary integer
     */
    protected function isBinary(mixed $value): bool
    {
        if (!is_bool($value) and !is_numeric($value)) {
            return false;
        }

        $value = (int) $value;

        return !(($value > 1 or $value < 0));
    }

    /**
     * @inheritDoc
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = Application::getUserState($this->context, new stdClass());

        // Pre-create the list options
        if (!property_exists($data, 'list')) {
            $data->list = [];
        }

        if (!property_exists($data, 'filter')) {
            $data->filter = [];
        }

        foreach ((array) $this->state as $property => $value) {
            if (str_starts_with($property, 'list.')) {
                $listProperty              = substr($property, 5);
                $data->list[$listProperty] = $value;
            } elseif (str_starts_with($property, 'filter.')) {
                $filterProperty                = substr($property, 7);
                $data->filter[$filterProperty] = $value;
            }
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        // Receive & set filters
        $filters = Application::getUserRequestState($this->context . '.filter', 'filter', [], 'array');
        foreach ($filters as $input => $value) {
            $this->setState('filter.' . $input, $value);
        }

        $list = Application::getUserRequestState($this->context . '.list', 'list', [], 'array');
        foreach ($list as $input => $value) {
            $this->setState("list.$input", $value);
        }

        $direction    = 'ASC';
        $fullOrdering = "$this->defaultOrdering ASC";
        $ordering     = $this->defaultOrdering;

        if (!empty($list['fullordering']) and !str_contains($list['fullordering'], 'null')) {
            $pieces          = explode(' ', $list['fullordering']);
            $validDirections = ['ASC', 'DESC', ''];

            switch (count($pieces)) {
                case 1:
                    if (in_array($pieces[0], $validDirections)) {
                        $direction    = empty($pieces[0]) ? 'ASC' : $pieces[0];
                        $fullOrdering = "$this->defaultDirection $direction";
                        $ordering     = $this->defaultDirection;
                        break;
                    }

                    $direction    = $pieces[0];
                    $fullOrdering = "$pieces[0] ASC";
                    $ordering     = 'ASC';
                    break;
                case 2:
                    $direction    = !in_array($pieces[1], $validDirections) ? 'ASC' : $pieces[1];
                    $ordering     = $pieces[0];
                    $fullOrdering = "$ordering $direction";
                    break;
            }
        }

        $this->setState('list.fullordering', $fullOrdering);
        $this->setState('list.ordering', $ordering);
        $this->setState('list.direction', $direction);

        if ($format = Input::getCMD('format') and $format === 'pdf') {
            $limit = 0;
        } else {
            $limit = (isset($list['limit']) && is_numeric($list['limit'])) ? $list['limit'] : $this->defaultLimit;
        }

        $this->setState('list.limit', $limit);

        $value = Application::getUserRequestState('limitstart', 'limitstart', 0);
        $start = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);
        $this->setState('list.start', $start);
    }

    /**
     * Sets a campus filter for a given resource.
     *
     * @param JDatabaseQuery $query the query to modify
     * @param string         $alias the alias for the linking table
     */
    public function setCampusFilter(JDatabaseQuery $query, string $alias): void
    {
        $campusID = $this->state->get('filter.campusID');
        if (empty($campusID)) {
            return;
        }

        if ($campusID === '-1') {
            $query->leftJoin("#__organizer_campuses AS campusAlias ON campusAlias.id = $alias.campusID")
                ->where("campusAlias.id IS NULL");

            return;
        }

        $query->innerJoin("#__organizer_campuses AS campusAlias ON campusAlias.id = $alias.campusID")
            ->where("(campusAlias.id = $campusID OR campusAlias.parentID = $campusID)");
    }

    /**
     * Sets a campus filter for a given resource.
     *
     * @param JDatabaseQuery $query the query to modify
     * @param string         $alias the alias for the linking table
     */
    public function setActiveFilter(JDatabaseQuery $query, string $alias): void
    {
        $status = $this->state->get('filter.active');

        if (!is_numeric($status)) {
            $status = 1;
        }

        if ($status == -1) {
            return;
        }

        $query->where("$alias.active = $status");
    }

    /**
     * Provides a default method for setting filters based on id/unique values
     *
     * @param JDatabaseQuery $query      the query to modify
     * @param string         $idColumn   the id column in the table
     * @param string         $filterName the filter name to look for the id in
     *
     * @return void
     */
    protected function setIDFilter(JDatabaseQuery $query, string $idColumn, string $filterName): void
    {
        $value = $this->state->get($filterName, '');
        if ($value === '') {
            return;
        }

        /**
         * Special value reserved for empty filtering. Since an empty is dependent upon the column default, we must
         * check against multiple 'empty' values. Here we check against empty string and null. Should this need to
         * be extended we could maybe add a parameter for it later.
         */
        if ($value == '-1') {
            $query->where("$idColumn IS NULL");

            return;
        }

        // IDs are unique and therefore mutually exclusive => one is enough!
        $query->where("$idColumn = $value");
    }

    /**
     * Provides a default method for setting the list ordering
     *
     * @param JDatabaseQuery $query the query to modify
     *
     * @return void
     */
    protected function setOrdering(JDatabaseQuery $query): void
    {
        $defaultOrdering = "$this->defaultOrdering $this->defaultDirection";
        $session         = Application::getSession();
        $listOrdering    = $this->state->get('list.fullordering', $defaultOrdering);

        if (str_contains($listOrdering, 'null')) {
            $sessionOrdering = $session->get('ordering', '');
            if (empty($sessionOrdering)) {
                $session->set($this->context . '.ordering', $defaultOrdering);
                $query->order($defaultOrdering);

                return;
            }
        }

        $query->order($listOrdering);
    }

    /**
     * Sets an organization filter for the given resource.
     *
     * @param QueryMySQLi $query          the query to modify
     * @param string      $context        the resource context from which this function was called
     * @param string      $alias          the alias of the table onto which the organizations table will be joined as
     *                                    needed
     *
     * @return void
     */
    protected function setOrganizationFilter(QueryMySQLi $query, string $context, string $alias): void
    {
        $authorizedIDs  = Application::backend() ? Helpers\Can::documentTheseOrganizations() : Helpers\Organizations::getIDs();
        $organizationID = (int) $this->state->get('filter.organizationID');

        if (!$authorizedIDs or !$organizationID) {
            return;
        }

        $conditions = ["a.{$context}ID = $alias.id"];
        $join       = 'associations AS a';

        if ($organizationID === self::NONE) {
            $query->leftJoinX($join, $conditions)->where('a.organizationID IS NULL');

            return;
        }

        $in = in_array($organizationID, $authorizedIDs) ? [$organizationID] : $authorizedIDs;
        $query->innerJoinX($join, $conditions)->whereIn('a.organizationID', $in);
    }

    /**
     * Sets the search filter for the query
     *
     * @param QueryMySQLi $query       the query to modify
     * @param array       $columnNames the column names to use in the search
     *
     * @return void
     */
    protected function setSearchFilter(QueryMySQLi $query, array $columnNames): void
    {
        if (!$userInput = $this->state->get('filter.search')) {
            return;
        }

        $search = '%' . $query->escape($userInput, true) . '%';
        $where  = [];

        foreach ($columnNames as $name) {
            $name    = Database::quoteName($name);
            $where[] = "$name LIKE '$search'";
        }

        $query->andWhere($where);
    }

    /**
     * Adds a date status filter for a given resource.
     *
     * @param JDatabaseQuery $query the query to modify
     * @param string         $alias the column alias
     */
    public function setStatusFilter(JDatabaseQuery $query, string $alias): void
    {
        if (!$value = $this->state->get('filter.status')) {
            return;
        }

        $modified       = date('Y-m-d h:i:s', strtotime('-2 Weeks'));
        $modifiedClause = "AND $alias.modified > '$modified'";

        switch ($value) {
            case self::CURRENT:
                $query->where("$alias.delta != 'removed'");

                return;
            case self::CHANGED:
                $query->where("(($alias.delta = 'new' $modifiedClause) OR $alias.delta = 'removed')");

                return;
            case self::NEW:
                $query->where("($alias.delta = 'new' $modifiedClause)");

                return;
            case self::REMOVED:
                $query->where("$alias.delta = 'removed'");

                return;
        }
    }

    /**
     * Provides a default method for setting filters for non-unique values
     *
     * @param JDatabaseQuery $query        the query to modify
     * @param array          $queryColumns the filter names. names should be synonymous with db column names.
     *
     * @return void
     */
    protected function setValueFilters(JDatabaseQuery $query, array $queryColumns): void
    {
        $filters = Input::getFilterItems();
        $lists   = Input::getListItems();
        $state   = $this->getState();

        // The view level filters
        foreach ($queryColumns as $column) {
            $filterName = !str_contains($column, '.') ? $column : explode('.', $column)[1];

            $value = $filters->get($filterName);

            if (!$value and $value !== '0') {
                $value = $lists->get($filterName);
            }

            if (!$value and $value !== '0') {
                $value = $state->get("filter.$filterName");
            }

            if (!$value and $value !== '0') {
                $value = $state->get("list.$filterName");
            }

            if (!$value and $value !== '0') {
                continue;
            }

            $column = Database::quoteName($column);

            /**
             * Special value reserved for empty filtering. Since an empty is dependent upon the column default, we must
             * check against multiple 'empty' values. Here we check against empty string and null. Should this need to
             * be extended we could maybe add a parameter for it later.
             */
            if ($value === '-1') {
                $query->where("( $column = '' OR $column IS NULL )");
                continue;
            }

            if (is_numeric($value)) {
                $query->where("$column = $value");
            } elseif (is_string($value)) {
                $value = Database::quote($value);
                $query->where("$column = $value");
            } elseif (is_array($value) and $values = ArrayHelper::toInteger($value)) {
                $query->where($column . Database::makeSet($values));
            }
        }
    }
}
