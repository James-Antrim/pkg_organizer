<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use JDatabaseQuery;
use Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of din types.
 */
class Dintypes extends ListModel
{
    /**
     * Method to get a list of resources from the database.
     *
     * @return JDatabaseQuery
     */
    protected function getListQuery()
    {
        $tag = Helpers\Languages::getTag();

        $query = $this->_db->getQuery(true);
        $query->select("DISTINCT t.id, t.name_$tag AS name, t.din_code")
            ->from('#__organizer_room_dintypes AS t');

        $this->setSearchFilter($query, ['name_en','name_de', 'din_code']);
        $this->setOrdering($query);

        return $query;
    }
}
