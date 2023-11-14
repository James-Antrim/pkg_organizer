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
use Joomla\Database\ParameterType;
use THM\Organizer\Adapters\{Application, Database as DB};
use THM\Organizer\Helpers\Can;

/**
 * Class retrieves the data regarding a filtered set of buildings.
 */
class CleaningGroups extends ListModel
{
    protected $filter_fields = ['relevant'];

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::getQuery();
        $tag   = Application::getTag();
        $url   = 'index.php?option=com_organizer&view=CleaningGroup&id=';

        $select = [
            '*',
            DB::quote((int) Can::manage('facilities')) . ' AS ' . DB::qn('access'),
            DB::qn("name_$tag", 'name'),
            $query->concatenate([DB::quote($url), DB::qn('id')], '') . ' AS ' . DB::qn('url')
        ];

        $query->select($select)
            ->from(DB::qn('#__organizer_cleaning_groups'));

        $this->setSearchFilter($query, ['name_de', 'name_en']);

        $relevant = $this->state->get('filter.relevant');

        if (is_numeric($relevant) and in_array((int) $relevant, [0, 1])) {
            $query->where(DB::qn('relevant') . ' = :relevant')->bind(':relevant', $relevant, ParameterType::INTEGER);
        }

        $this->orderBy($query);

        return $query;
    }
}
