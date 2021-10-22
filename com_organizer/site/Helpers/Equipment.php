<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use Organizer\Adapters\Database;

/**
 * Provides general functions for room type access checks, data retrieval and display.
 */
class Equipment extends ResourceHelper implements Selectable
{
    use Filtered;

    private const NO = 0, YES = 1;

    /**
     * @inheritDoc
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::getResources() as $type)
        {
            $options[] = HTML::_('select.option', $type['id'], $type['name']);
        }

        return $options;
    }

    /**
     * @inheritDoc
     *
     * @param   bool  $associated  whether the type needs to be associated with a room
     * @param   bool  $public
     */
    public static function getResources($associated = self::YES, $suppress = self::NO): array
    {
        $tag = Languages::getTag();

        $query = Database::getQuery(true);
        $query->select("DISTINCT t.*, t.id AS id, t.name_$tag AS name")
            ->from('#__organizer_equipment AS t');

        $query->order('name');
        Database::setQuery($query);

        return Database::loadAssocList('id');
    }
}
