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

/**
 * Class retrieves the data regarding a filtered set of buildings.
 */
class Buildings extends ListModel
{
    protected $filter_fields = ['campusID', 'propertyType'];

    /**
     * Method to get a list of resources from the database.
     *
     * @return JDatabaseQuery
     */
    protected function getListQuery()
    {
        $query = $this->_db->getQuery(true);

        $query->select('b.id, b.name, propertyType, campusID, c1.parentID, b.address, c1.city, c2.city AS parentCity')
            ->from('#__organizer_buildings AS b')
            ->innerJoin('#__organizer_campuses AS c1 ON c1.id = b.campusID')
            ->leftJoin('#__organizer_campuses AS c2 ON c2.id = c1.parentID');

        $this->setSearchFilter($query, ['b.name', 'b.address', 'c1.city', 'c2.city']);
        $this->setValueFilters($query, ['propertyType']);


        if ($campusID = $this->state->get('filter.campusID', '')) {
            if ($campusID === '-1') {
                $query->where('campusID IS NULL');
            } else {
                $query->where("(b.campusID = $campusID OR c1.parentID = $campusID)");
            }
        }

        $this->setOrdering($query);

        return $query;
    }
}
