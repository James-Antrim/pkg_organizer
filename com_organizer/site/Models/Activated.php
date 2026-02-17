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

use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\Application;

trait Activated
{
    protected const ACTIVE = 1, ALL = -1, INACTIVE = 0, IRRELEVANT = null;

    protected const FILTERED = [self:: ALL, self::INACTIVE, self::ACTIVE];

    /**
     * Sets a campus filter for a given resource.
     *
     * @param DatabaseQuery $query the query to modify
     * @param string        $alias the alias for the linking table
     */
    protected function activeFilter(DatabaseQuery $query, string $alias = ''): void
    {
        /** @var ListModel $this */
        $status = $this->state->get('filter.active');

        // Default filter is toward active entries.
        if ($status === self::IRRELEVANT or !in_array($status = (int) $status, self::FILTERED)) {
            $status = self::ACTIVE;
        }
        // No filter
        elseif ($status === self::ALL) {
            return;
        }

        if ($alias) {
            $query->where("$alias.active = $status");
        }
        else {
            $query->where("active = $status");
        }
    }

    /**
     * Method to autopopulate the model state.
     *
     * @param string $ordering  An optional ordering field.
     * @param string $direction An optional direction (asc|desc).
     *
     * @return void populates state properties
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        /** @noinspection PhpMultipleClassDeclarationsInspection */
        parent::populateState($ordering, $direction);

        $filters = Application::userRequestState($this->context . '.filter', 'filter', [], 'array');

        if (!array_key_exists('active', $filters) or $filters['active'] === '') {
            $this->setState('filter.active', 1);
        }
    }
}