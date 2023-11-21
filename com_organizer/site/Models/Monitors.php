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

use Joomla\Database\{DatabaseQuery, ParameterType};
use THM\Organizer\Adapters\{Database as DB, Input};
use THM\Organizer\Helpers\Can;
use THM\Organizer\Helpers\Monitors as Helper;

/**
 * Class retrieves information for a filtered set of monitors.
 */
class Monitors extends ListModel
{
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
        if (!$content = (string) $this->state->get('filter.content')) {
            return;
        }

        $content  = $content === (string) self::NONE ? '' : $content;
        $default  = (string) Input::getParams()->get('content');
        $assigned = DB::qn('m.content') . ' = :content';

        if ($content === $default) {
            $useDefaults = DB::qn('useDefaults') . ' = 1';
            $query->where("($assigned OR $useDefaults)");
        }
        else {
            $query->where($assigned);
        }

        $query->bind(':content', $content);
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
        $templateKey = $this->state->get('filter.display');

        // Filter null and empty string as explicit non-zero values.
        if (!is_numeric($templateKey)) {
            return;
        }

        $templateKey = (int) $templateKey;

        if (!in_array($templateKey, Helper::LAYOUTS)) {
            return;
        }

        $assigned = DB::qn('m.display') . ' = :template';
        $default  = Input::getParams()->get('display');

        if (is_numeric($default) and (int) $default === $templateKey) {
            $useDefaults = DB::qn('useDefaults') . ' = 1';
            $query->where("($assigned OR $useDefaults)");
        }
        else {
            $query->where($assigned);
        }

        $query->bind(':template', $templateKey, ParameterType::INTEGER);

    }

    /**
     * Method to get a list of resources from the database.
     * @return DatabaseQuery
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
