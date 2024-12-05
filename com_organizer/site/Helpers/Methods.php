<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use THM\Organizer\Adapters\{Application, Database as DB, HTML};

/**
 * Provides general functions for room type access checks, data retrieval and display.
 */
class Methods extends ResourceHelper implements Selectable
{
    /**
     * Code constants
     */
    public const FINALCODE = 'KLA';

    /** @inheritDoc */
    public static function options(): array
    {
        $options = [];
        foreach (self::resources() as $method) {
            $options[] = HTML::option($method['id'], $method['name']);
        }

        return $options;
    }

    /**
     * Returns a list of workload relevant methods
     *
     * @param   bool  $ids  whether only the relevant ids should be returned
     *
     * @return array
     */
    public static function relevant(bool $ids = true): array
    {
        $methods = self::resources();
        foreach ($methods as $id => $method) {
            if (empty($method['relevant'])) {
                unset($methods[$id]);
            }
        }

        return $ids ? array_keys($methods) : $methods;
    }

    /** @inheritDoc */
    public static function resources(): array
    {
        $query = DB::query();
        $tag   = Application::tag();
        $query->select(['DISTINCT ' . DB::qn('m') . '.*', DB::qn("m.name_$tag", 'name')])
            ->from(DB::qn('#__organizer_methods', 'm'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.methodID', 'm.id'))
            ->order(DB::qn('name'));
        DB::set($query);

        return DB::arrays('id');
    }
}
