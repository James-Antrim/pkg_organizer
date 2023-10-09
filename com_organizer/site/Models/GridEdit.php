<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Organizer\Tables;

/**
 * Class loads a form for editing grid data.
 */
class GridEdit extends EditModel
{
    /**
     * @inheritDoc
     * @return Tables\Grids A Table object
     */
    public function getTable($name = '', $prefix = '', $options = []): Tables\Grids
    {
        return new Tables\Grids();
    }
}
