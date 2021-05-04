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
 * Class retrieves information for a filtered set of subjects. Modal view.
 */
class SubjectSelection extends ListModel
{
    /**
     * Method to get a list of resources from the database.
     *
     * @return JDatabaseQuery
     */
    protected function getListQuery()
    {
        $tag   = Helpers\Languages::getTag();
        $query = $this->_db->getQuery(true);

        $query->select("DISTINCT s.id, code, fullName_$tag AS name")->from('#__organizer_subjects AS s');

        $searchFields = [
            'fullName_de',
            'shortName_de',
            'abbreviation_de',
            'fullName_en',
            'shortName_en',
            'abbreviation_en',
            'code',
            'description_de',
            'objective_de',
            'content_de',
            'description_en',
            'objective_en',
            'content_en'
        ];
        $this->setSearchFilter($query, $searchFields);
        $this->setValueFilters($query, ['code', 'fieldID']);

        if ($programID = $this->state->get('filter.programID', '')) {
            Helpers\Subjects::setProgramFilter($query, $programID, 'subject', 's');
        }

        if ($poolID = $this->state->get('filter.poolID', '')) {
            Helpers\Subjects::setPoolFilter($query, $poolID, 's');
        }

        $this->setOrdering($query);

        return $query;
    }
}
