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
use Joomla\CMS\MVC\{Factory\MVCFactoryInterface, Model\ListModel as Base};
use Joomla\CMS\Table\Table;
use Joomla\Database\{DatabaseQuery, ParameterType};
use Joomla\Utilities\ArrayHelper;
use stdClass;
use THM\Organizer\Adapters\{Application, Database as DB, Form, Input};
use THM\Organizer\Helpers\{Associated, Campuses};

/**
 * Model class for handling lists of items.
 * - Overrides/-writes to avoid deprecated code in the platform or promote ease of use
 * - Supplemental functions to extract common code from list models
 */
abstract class ListModel extends Base
{
    use Named;

    protected const NONE = -1, UNSELECTED = '', UNSET = null;

    protected const CURRENT = 1, NEW = 2, REMOVED = 3, CHANGED = 4;

    protected int $defaultLimit = 50;
    protected string $defaultOrdering = 'name';

    /** @inheritDoc */
    public function __construct($config = [], MVCFactoryInterface $factory = null)
    {
        // Preemptively set to avoid unnecessary complications.
        $this->setContext();

        try {
            parent::__construct($config, $factory);
        }
        catch (Exception $exception) {
            Application::handleException($exception);
        }

        $this->clean();
    }

    /**
     * Function for policing resource data.
     * @return void
     */
    protected function clean()
    {

    }

    /**
     * Area where overrideable resource access conditions can be written.
     *
     * @param   DatabaseQuery  $query
     *
     * @return void
     */
    protected function addAccess(DatabaseQuery $query): void
    {
        // As needed
    }

    /**
     * Provides external access to the clean cache function. This belongs in the input adapter, but I do not want to
     * have to put in the effort to resolve everything necessary to get it there.
     * @void initiates cache cleaning
     */
    public function emptyCache(): void
    {
        $this->cleanCache();
    }

    /**
     * Sets an access filter for a given resource. Wrapper for uniformity of filter function calls.
     *
     * @param   DatabaseQuery  $query  the query to modify
     * @param   string         $alias  the alias for the linking table
     */
    protected function filterByAccess(DatabaseQuery $query, string $alias, string $access): void
    {
        $helper = '\THM\Organizer\Helpers\\' . Application::ucClass();

        /** @var Associated $helper */
        $helper::filterByAccess($query, $alias, $access);
    }

    /**
     * Adds a binary value filter clause for the given $query;
     *
     * @param   DatabaseQuery  $query  the query to modify
     * @param   string         $name   the attribute whose value to filter against
     *
     * @return void modifies the query if a binary value was delivered in the request
     */
    protected function filterBinary(DatabaseQuery $query, string $name): void
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

    /**
     * Sets a campus filter for a given resource.
     *
     * @param   DatabaseQuery  $query  the query to modify
     * @param   string         $alias  the alias for the linking table
     */
    protected function filterByCampus(DatabaseQuery $query, string $alias): void
    {
        if (!$campusID = $this->state->get('filter.campusID')) {
            return;
        }

        Campuses::filterBy($query, $alias, (int) $campusID);
    }

    /**
     * Provides a default method for setting filters based on unique key values.
     *
     * @param   DatabaseQuery  $query   the query to modify
     * @param   string         $column  the table column where the id value is stored
     * @param   string         $field   the field name to look for the id in
     *
     * @return void
     */
    protected function filterByKey(DatabaseQuery $query, string $column, string $field): void
    {
        $value = $this->state->get("filter.$field");
        if ($value === self::UNSET or $value === self::UNSELECTED) {
            return;
        }

        $qColumn = DB::qn($column);

        if (is_numeric($value)) {
            $type  = ParameterType::INTEGER;
            $value = (int) $value;
        }
        else {
            $type = ParameterType::STRING;
        }

        if ($value === self::NONE) {
            $query->where("$column IS NULL");

            return;
        }

        // If there is a period the first part is parsed and the second part produces an error.
        $column = str_replace('.', '', $column);

        $query->where("$qColumn = :$column")->bind(":$column", $value, $type);
    }

    /**
     * Sets an organization filter for a given resource.
     *
     * @param   DatabaseQuery  $query  the query to modify
     * @param   string         $alias  the alias for the linking table
     */
    protected function filterByOrganization(DatabaseQuery $query, string $alias): void
    {
        $helper = '\THM\Organizer\Helpers\\' . Application::ucClass();

        /** @var Associated $helper */
        $helper::filterByOrganization($query, $alias, (int) $this->state->get('filter.organizationID'));
    }

    /**
     * Filters out form inputs which should not be displayed due to menu settings.
     *
     * @param   Form  $form  the form to be filtered
     *
     * @return void modifies $form
     */
    protected function filterFilterForm(Form $form): void
    {
        // No implementation is the default implementation.
    }

    /**
     * Sets the search filter for the query
     *
     * @param   DatabaseQuery  $query        the query to modify
     * @param   array          $columnNames  the column names to use in the search
     * @param   string         $alias        the optional string aliasing the main table name if id search is to be enabled
     *
     * @return void
     */
    protected function filterSearch(DatabaseQuery $query, array $columnNames, string $alias = ''): void
    {
        if (!$userInput = $this->state->get('filter.search')) {
            return;
        }

        if ($alias and preg_match('/^:(\d+)$/', $userInput, $matches)) {
            $column = DB::qn("$alias.id");
            $value  = (int) $matches[1];
            $query->where("$column = :id")->bind(':id', $value, ParameterType::INTEGER);
            return;
        }

        $search = '%' . $query->escape($userInput, true) . '%';
        $where  = [];

        foreach ($columnNames as $name) {
            $where[] = DB::qn($name) . " LIKE '$search'";
        }

        $query->where('(' . implode(' OR ', $where) . ')');
    }

    /**
     * Adds a date status filter for a given resource.
     *
     * @param   DatabaseQuery  $query  the query to modify
     * @param   string         $alias  the column alias
     */
    protected function filterStatus(DatabaseQuery $query, string $alias): void
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
     * @param   DatabaseQuery  $query         the query to modify
     * @param   array          $queryColumns  the filter names. names should be synonymous with db column names.
     *
     * @return void
     */
    protected function filterValues(DatabaseQuery $query, array $queryColumns): void
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

            $column = DB::qn($column);

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
            }
            elseif (is_string($value)) {
                $value = DB::quote($value);
                $query->where("$column = $value");
            }
            elseif (is_array($value) and $values = ArrayHelper::toInteger($value)) {
                $query->where($column . DB::makeSet($values));
            }
        }
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
     * @param   array  $data      data
     * @param   bool   $loadData  load current data
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
        }
        catch (Exception $exception) {
            Application::handleException($exception);
        }

        return null;
    }

    /**
     * Method to get an array of data items. No inheritDoc because the single return type of array.
     *
     * @return  array  an array of data items.
     */
    public function getItems(): array
    {
        $items = parent::getItems();

        return $items ?: [];
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     the table name, unused
     * @param   string  $prefix   the class prefix, unused
     * @param   array   $options  configuration array for model, unused
     *
     * @return  Table  a table object
     */
    public function getTable($name = '', $prefix = '', $options = []): Table
    {
        // With few exception the table and list class names are identical
        $class = Application::uqClass($this);
        $fqn   = "\\THM\\Organizer\\Tables\\$class";

        return new $fqn();
    }

    /** @inheritDoc */
    public function getTotal(): int
    {
        $total = parent::getTotal();

        return is_int($total) ? $total : 0;
    }

    /**
     * Checks whether the given value can safely be interpreted as a binary value.
     *
     * @param   mixed  $value  the value to be checked
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

    /** @inheritDoc */
    protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = false): Form
    {
        /** @var Form $form */
        if ($form = parent::loadForm($name, $source, $options, $clear, $xpath)) {
            $this->filterFilterForm($form);
        }

        return $form;
    }

    /** @inheritDoc */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = Application::userState($this->context, new stdClass());

        // Pre-create the list options
        if (!property_exists($data, 'list')) {
            $data->list = [];
        }

        if (!property_exists($data, 'filter')) {
            $data->filter = [];
        }

        foreach ($this->state->toArray() as $property => $value) {
            if (str_starts_with($property, 'list.')) {
                $listProperty              = substr($property, 5);
                $data->list[$listProperty] = $value;
            }
            elseif (str_starts_with($property, 'filter.')) {
                $filterProperty                = substr($property, 7);
                $data->filter[$filterProperty] = $value;
            }
        }

        return $data;
    }

    /**
     * Adds a standardized order by clause for the given $query;
     *
     * @param   DatabaseQuery  $query  the query to modify
     *
     * @return void
     */
    protected function orderBy(DatabaseQuery $query): void
    {
        if ($columns = $this->state->get('list.ordering')) {
            if (preg_match('/, */', $columns)) {
                $columns = explode(',', preg_replace('/, */', ',', $columns));
            }

            $columns = $query->quoteName($columns);

            $direction = strtoupper($query->escape($this->getState('list.direction', 'ASC')));

            if (is_array($columns)) {
                $columns = implode(" $direction, ", $columns);
            }

            $query->order("$columns $direction");
        }
    }

    /** @inheritDoc */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        // Receive & set filters
        $filters = Application::userRequestState($this->context . '.filter', 'filter', [], 'array');
        foreach ($filters as $input => $value) {
            $this->state->set('filter.' . $input, $value);
        }

        $list = Application::userRequestState($this->context . '.list', 'list', [], 'array');
        foreach ($list as $input => $value) {
            $this->state->set("list.$input", $value);
        }

        $direction    = 'ASC';
        $fullOrdering = "$this->defaultOrdering ASC";
        $ordering     = $this->defaultOrdering;

        if (!empty($list['fullordering']) and !str_contains($list['fullordering'], 'null')) {
            $pieces          = explode(' ', $list['fullordering']);
            $validDirections = ['ASC', 'DESC', ''];

            if (in_array(end($pieces), $validDirections)) {
                $direction = array_pop($pieces);
            }

            if ($pieces) {
                $ordering = implode(' ', $pieces);
            }

            $fullOrdering = "$ordering $direction";
        }

        $this->state->set('list.fullordering', $fullOrdering);
        $this->state->set('list.ordering', $ordering);
        $this->state->set('list.direction', $direction);

        if ($format = Input::getCMD('format') and $format !== 'html') {
            $limit = 0;
            $start = 0;
        }
        else {
            $limit = (isset($list['limit']) && is_numeric($list['limit'])) ? $list['limit'] : $this->defaultLimit;
            $start = $this->getUserStateFromRequest('limitstart', 'limitstart', 0);
            $start = ($limit != 0 ? (floor($start / $limit) * $limit) : 0);
        }

        $this->state->set('list.limit', $limit);
        $this->state->set('list.start', $start);
    }
}