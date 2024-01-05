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
use THM\Organizer\Adapters\{Database as DB, Input};
use THM\Organizer\Helpers\{Can, Monitors as Helper};

/**
 * Class retrieves information for a filtered set of monitors.
 */
class Monitors extends ListModel
{
    private const CONTENT = 1;

    protected string $defaultOrdering = 'r.name';

    protected $filter_fields = ['content', 'display', 'useDefaults'];

    /**
     * Adds the filter settings for displayed content
     *
     * @param   DatabaseQuery  $query  the query to modify
     *
     * @return void
     */
    private function contentFilter(DatabaseQuery $query): void
    {
        // All or invalid
        if (!$filter = (int) $this->state->get('filter.content') or !in_array($filter, [self::CONTENT, self::NONE])) {
            return;
        }

        $default = (bool) Input::getParams()->get('content');

        $cColumn  = DB::qn('m.content');
        $udColumn = DB::qn('m.useDefaults');

        if ($filter === self::NONE) {
            // no content & not defaulted to content or defaulted to no content
            $content  = "$cColumn = ''";
            $defaults = $default ? " AND $udColumn = 0" : " OR $udColumn = 1";
        }
        else {
            // content and not defaulted to no content or defaulted to content
            $content  = "$cColumn != ''";
            $defaults = $default ? " OR $udColumn = 1" : " AND $udColumn = 0";
        }

        $query->where("($content $defaults)");
    }

    /**
     * Adds the filter settings for display behaviour
     *
     * @param   DatabaseQuery  $query  the query to modify
     *
     * @return void
     */
    private function displayFilter(DatabaseQuery $query): void
    {
        $templateKey = $this->state->get('filter.display', '');

        // Filter null and empty string as explicit non-zero values with the consequence of no filter being applied.
        if (!is_numeric($templateKey)) {
            return;
        }

        // Constants are int.
        $templateKey = (int) $templateKey;

        if (!in_array($templateKey, Helper::LAYOUTS)) {
            return;
        }

        $default     = (int) Input::getParams()->get('display');
        $useDefaults = DB::qn('m.useDefaults');

        if (!in_array($templateKey, Helper::LAYOUTS)) {
            return;
        }

        $defaults = match ($templateKey) {
            Helper::CONTENT => $default === Helper::CONTENT ? " OR $useDefaults = 1" : " AND $useDefaults = 0",
            Helper::CURRENT => $default === Helper::CURRENT ? " OR $useDefaults = 1" : " AND $useDefaults = 0",
            Helper::MIXED => $default === Helper::MIXED ? " OR $useDefaults = 1" : " AND $useDefaults = 0",
            default => $default === Helper::UPCOMING ? " OR $useDefaults = 1" : " AND $useDefaults = 0",
        };

        $query->where('(' . DB::qn('m.display') . " = $templateKey $defaults)");
    }

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::getQuery();
        $url   = 'index.php?option=com_organizer&view=Monitor&id=';

        $access = [DB::quote((int) Can::manage('facilities')) . ' AS ' . DB::qn('access')];
        $select = DB::qn(['m.id', 'r.name', 'm.ip', 'm.useDefaults', 'm.display', 'm.content']);
        $url    = [$query->concatenate([DB::quote($url), DB::qn('m.id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge($select, $access, $url))
            ->from(DB::qn('#__organizer_monitors', 'm'))
            ->leftJoin(DB::qn('#__organizer_rooms', 'r'), DB::qc('r.id', 'm.roomID'));

        $this->filterSearch($query, ['r.name', 'm.ip']);
        $this->filterValues($query, ['useDefaults']);
        $this->displayFilter($query);
        $this->contentFilter($query);
        $this->orderBy($query);

        return $query;
    }
}
