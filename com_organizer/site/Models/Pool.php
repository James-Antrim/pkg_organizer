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

use THM\Organizer\Helpers\{Pools as Helper, Programs};

/** @inheritDoc */
class Pool extends EditModel
{
    protected string $tableClass = 'Pools';

    /** @inheritDoc */
    public function getItem(): object
    {
        if (!$item = $this->item) {
            $item                 = parent::getItem();
            $ranges               = Helper::rows($item->id);
            $item->programIDs     = empty($ranges) ? [] : Programs::extractIDs($ranges);
            $item->superordinates = Helper::superValues($item->id, 'pool');
            $this->item           = $item;
        }
        return $item;
    }
}
