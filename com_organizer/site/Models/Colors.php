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
use THM\Organizer\Adapters\{Application, Database as DB};

/**
 * Class retrieves information for a filtered set of colors.
 */
class Colors extends ListModel
{
    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $tag   = Application::getTag();
        $query = DB::getQuery();

        $query->select([DB::qn('id'), DB::qn("name_$tag", 'name'), DB::qn('color')])
            ->from(DB::qn('#__organizer_colors'));

        $this->setSearchFilter($query, ['name_de', 'name_en', 'color']);
        $this->setValueFilters($query, ['color']);
        $this->setIDFilter($query, 'id', 'filter.name');

        $this->setOrdering($query);

        return $query;
    }
}
