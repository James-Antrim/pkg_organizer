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
use THM\Organizer\Adapters\Application;

/**
 * Model class for handling grids of values.
 * - Overrides/-writes to avoid deprecated code in the platform or promote ease of use
 * - Supplemental functions to extract common code from list models
 * - Ignores ListModel functions getTable, getTotal
 */
abstract class GridModel extends Base
{
    use Filtered;
    use Named;

    protected const NONE = -1, UNSELECTED = '', UNSET = null;

    protected const CURRENT = 1, NEW = 2, REMOVED = 3, CHANGED = 4;

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
    }

    /** @inheritDoc */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);
        $this->setFilters();

        $this->state->set('list.limit', 0);
        $this->state->set('list.start', 0);
    }
}