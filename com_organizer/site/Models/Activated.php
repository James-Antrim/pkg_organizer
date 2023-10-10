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

use THM\Organizer\Adapters\Application;

trait Activated
{
    /**
     * Method to auto-populate the model state.
     *
     * @param string $ordering  An optional ordering field.
     * @param string $direction An optional direction (asc|desc).
     *
     * @return void populates state properties
     */
    protected function populateState($ordering = null, $direction = null)
    {
        /** @noinspection PhpMultipleClassDeclarationsInspection */
        parent::populateState($ordering, $direction);

        $app     = Application::getApplication();
        $filters = $app->getUserStateFromRequest($this->context . '.filter', 'filter', [], 'array');

        if (!array_key_exists('active', $filters) or $filters['active'] === '') {
            $this->setState('filter.active', 1);
        }
    }
}