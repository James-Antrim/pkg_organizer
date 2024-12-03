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
use Joomla\Database\{DatabaseQuery};
use THM\Organizer\Adapters\{Application, Input};

/**
 * Model class for handling lists of items.
 * - Overrides/-writes to avoid deprecated code in the platform or promote ease of use
 * - Supplemental functions to extract common code from list models
 */
abstract class ListModel extends Base
{
    use Filtered;
    use Named;

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

    /** @inheritDoc */
    public function getItems(): array
    {
        $items = parent::getItems();

        return $items ?: [];
    }

    /** @inheritDoc */
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
        $this->setFilters();

        if ($fullOrdering = $this->state->get('list.fullordering')) {
            $pieces          = explode(' ', $fullOrdering);
            $validDirections = ['ASC', 'DESC', ''];

            if (in_array(end($pieces), $validDirections)) {
                $direction = array_pop($pieces);
            }

            if ($pieces) {
                $ordering = implode(' ', $pieces);
            }

            $fullOrdering = "$ordering $direction";
        }
        else {
            $direction    = 'ASC';
            $fullOrdering = "$this->defaultOrdering ASC";
            $ordering     = $this->defaultOrdering;
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