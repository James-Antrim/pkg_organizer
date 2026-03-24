<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2024 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Models;

use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\{Application, Database as DB};

/**
 * Common code base for filtered views.
 */
trait Rudimentary
{
    /**
     * Creates a standardized list query for rudimentary resources.
     *
     * @param string $singular the name of the resource
     * @param string $plural   the plural of the resource name
     * @return DatabaseQuery
     */
    protected function query(string $singular, string $plural): DatabaseQuery
    {
        $query = DB::query();
        $tag   = Application::tag();
        $url   = "index.php?option=com_organizer&view=$singular&id=";

        $access  = [DB::quote(1) . ' AS ' . DB::qn('access')];
        $aliased = DB::qn(["alias_$tag", "name_$tag"], ['alias', 'name']);
        $select  = DB::qn(['id', 'code']);
        $url     = [$query->concatenate([DB::quote($url), DB::qn('id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge($select, $access, $aliased, $url))
            ->from(DB::qn("#__organizer_$plural"))
            ->order(DB::qn("name_$tag"));

        $this->filterSearch($query, ['alias_de', 'alias_en', 'code', 'name_de', 'name_en']);

        return $query;
    }
}