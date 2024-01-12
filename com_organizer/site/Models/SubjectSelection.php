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
use THM\Organizer\Adapters\{Application, Database};
use THM\Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of subjects. Modal view.
 */
class SubjectSelection extends ListModel
{
    /**
     * Method to get a list of resources from the database.
     * @return DatabaseQuery
     */
    protected function getListQuery(): DatabaseQuery
    {
        $tag = Application::getTag();

        $query = Database::getQuery();

        $query->select("DISTINCT s.id, code, fullName_$tag AS name")->from('#__organizer_subjects AS s');

        $searchFields = [
            'fullName_de',
            'abbreviation_de',
            'fullName_en',
            'abbreviation_en',
            'code',
            'description_de',
            'objective_de',
            'content_de',
            'description_en',
            'objective_en',
            'content_en'
        ];
        $this->filterSearch($query, $searchFields);
        $this->filterValues($query, ['code', 'fieldID']);

        if ($programID = $this->state->get('filter.programID', '')) {
            Helpers\Subjects::filterProgram($query, $programID, 'subjectID', 's');
        }

        if ($poolID = $this->state->get('filter.poolID', '')) {
            Helpers\Subjects::filterPool($query, $poolID, 's');
        }

        $this->orderBy($query);

        return $query;
    }
}
