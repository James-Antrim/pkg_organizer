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

/**
 * @inheritDoc
 */
class Person extends EditModel
{
    protected string $list = 'Persons';

    /**
     * Method to get a single record.
     *
     * @param   int  $pk  The id of the primary key.
     *
     * @return mixed    Object on success, false on failure.
     */
    /*public function getItem($pk = 0)
    {
        $this->item = parent::getItem($pk);

        $this->item->organizationID = $this->item->id ?
            Helpers\Persons::organizationIDs($this->item->id) : [];

        return $this->item;
    }*/
}
