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

use THM\Organizer\Adapters\{Application, Database, Database as DB, HTML};
use THM\Organizer\Tables\Grids as Table;

/**
 * Class provides general functions for retrieving building data.
 */
class Grids extends ResourceHelper implements Selectable
{
    public const DEFAULT = 1, STANDARD = 0;

    public const PUBLISHED_STATES = [
        self::DEFAULT  => [
            'class'  => 'publish',
            'column' => 'isDefault',
            'task'   => '',
            'tip'    => 'CURRENT_DEFAULT'
        ],
        self::STANDARD => [
            'class'  => 'unpublish',
            'column' => 'isDefault',
            'task'   => 'default',
            'tip'    => 'CLICK_TO_DEFAULT'
        ]
    ];

    /**
     * @inheritDoc
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::getResources() as $grid) {
            $options[] = HTML::option($grid['id'], $grid['name']);
        }

        return $options;
    }

    /**
     * Retrieves the default grid.
     *
     * @param   bool  $onlyID  whether only the id will be returned, defaults to true
     *
     * @return int|Table int the id, otherwise the grid table entry
     */
    public static function getDefault(bool $onlyID = true): int|Table
    {
        $query = DB::getQuery();
        $query->select(DB::qn('id'))->from(DB::qn('#__organizer_grids'))->where(DB::qn('isDefault') . ' = 1');
        DB::setQuery($query);

        if (!$gridID = DB::loadInt()) {
            return 0;
        }

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
     * @param   int  $gridID  the grid id
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
        $tag   = Application::getTag();

        $select = ['*', DB::qn("name_$tag", 'name')];
        $query->select($select)
            ->from(DB::qn('#__organizer_grids'))
            ->order(DB::qn('name'));
        Database::setQuery($query);

        return Database::loadAssocList('id');
    }


    /**
     * Removes the default status from all grids.
     * @return bool
     */
    public static function resetDefault(): bool
    {
        $query = DB::getQuery();
        $query->update(DB::qn('#__organizer_grids'))->set(DB::qn('isDefault') . ' = 0');
        DB::setQuery($query);

        return DB::execute();
    }
}
