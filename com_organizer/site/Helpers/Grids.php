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

use Organizer\Adapters\Database;
use Organizer\Tables\Grids as Table;

/**
 * Class provides general functions for retrieving building data.
 */
class Grids extends ResourceHelper implements Selectable
{
    /**
     * @inheritDoc
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::getResources() as $grid) {
            $options[] = HTML::_('select.option', $grid['id'], $grid['name']);
        }

        return $options;
    }

    /**
     * Retrieves the default grid.
     *
     * @param bool $onlyID whether only the id will be returned, defaults to true
     *
     * @return int|Table int the id, otherwise the grid table entry
     */
    public static function getDefault(bool $onlyID = true)
    {
        $query = Database::getQuery();
        $query->select('id')->from('#__organizer_grids')->where('isDefault = 1');
        Database::setQuery($query);

        $gridID = Database::loadInt();

        if ($onlyID) {
            return $gridID;
        }

        $table = new Table();
        $table->load($gridID);

        return $table;
    }

    /**
     * Retrieves the grid property for the given grid.
     *
     * @param int $gridID the grid id
     *
     * @return string string the grid json string on success, otherwise null
     */
    public static function getGrid(int $gridID): string
    {
        $table = new Table();
        $table->load($gridID);

        return $table->grid ?: '';
    }

    /**
     * @inheritDoc
     */
    public static function getResources(): array
    {
        $query = Database::getQuery();
        $tag   = Languages::getTag();
        $query->select("*, name_$tag as name, isDefault")->from('#__organizer_grids')->order('name');
        Database::setQuery($query);

        return Database::loadAssocList('id');
    }

}
