<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2024 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Models;

use THM\Organizer\Helpers\Associated as Helper;

abstract class Associated extends EditModel
{
    /**
     * @inheritDoc
     */
    public function getItem(): object
    {
        /** @var Helper $helper */
        $helper = '\THM\Organizer\Helpers\\' . $this->tableClass;
        $item   = parent::getItem();

        $item->organizationIDs = $item->id ? $helper::organizationIDs($item->id) : [];

        return $item;
    }

}