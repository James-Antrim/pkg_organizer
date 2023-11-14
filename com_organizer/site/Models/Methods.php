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
 * Class provides a standardized framework for the display of listed methods.
 */
class Methods extends ListModel
{
    protected string $defaultOrdering = 'abbreviation';

    /**
     * Method to get a list of resources from the database.
     * @return DatabaseQuery
     */
    protected function getListQuery(): DatabaseQuery
    {
        $link  = 'index.php?option=com_organizer&view=Method&id=';
        $query = DB::getQuery();
        $tag   = Application::getTag();

        $access  = [DB::quote(1) . ' AS ' . DB::qn('access')];
        $aliased = DB::qn(["abbreviation_$tag", "name_$tag"], ['abbreviation', 'name']);
        $link    = [$query->concatenate([DB::quote($link), DB::qn('id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge([DB::qn('id')], $access, $aliased, $link))
            ->from(DB::qn('#__organizer_methods'));

        $this->setSearchFilter($query, ['name_de', 'name_en', 'abbreviation_de', 'abbreviation_en']);
        $this->orderBy($query);

        return $query;
    }
}
