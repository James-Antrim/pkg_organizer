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

use THM\Organizer\Helpers\Persons as Helper;

/**
 * @inheritDoc
 */
class Person extends EditModel
{
    protected string $tableClass = 'Persons';

    /**
     * @inheritDoc
     */
    public function getItem(): object
    {
        $item                  = parent::getItem();
        $item->organizationIDs = $item->id ? Helper::organizationIDs($item->id) : [];

        return $item;
    }
}
