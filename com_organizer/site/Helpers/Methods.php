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

use THM\Organizer\Adapters\{Application, Database};

/**
 * Provides general functions for room type access checks, data retrieval and display.
 */
class Methods extends ResourceHelper implements Selectable
{
    /**
     * Code constants
     */
    public const FINALCODE = 'KLA';

    /**
     * @inheritDoc
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::getResources() as $method) {
            $options[] = HTML::_('select.option', $method['id'], $method['name']);
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public static function getResources(): array
    {
        $query = Database::getQuery();
        $tag   = Application::getTag();
        $query->select("DISTINCT m.*, m.name_$tag AS name")
            ->from('#__organizer_methods AS m')
            ->innerJoin('#__organizer_instances AS i ON i.methodID = m.id')
            ->order('name');
        Database::setQuery($query);

        return Database::loadAssocList('id');
    }
}
