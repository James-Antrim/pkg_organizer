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

/** @inheritDoc */
class Foci extends ListModel
{
    use Rudimentary;

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        return $this->query('focus', 'foci');
    }
}
