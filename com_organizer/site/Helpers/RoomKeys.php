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

use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input};

/**
 * Provides general functions for room type access checks, data retrieval and display.
 */
class RoomKeys extends ResourceHelper implements Selectable
{
    /**
     * @inheritDoc
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::resources() as $key) {
            $options[] = HTML::option($key['id'], $key['name']);
        }

        return $options;
    }

    /**
     * @inheritDoc
     *
     * @param   bool  $associated  whether the type needs to be associated with a room
     * @param   bool  $suppressed  whether suppressed types should also be included in the result set
     */
    public static function resources(): array
    {
        $query = DB::getQuery();
        $tag   = Application::getTag();

        $nameColumns = DB::qn(['k.key', "k.name_$tag"]);
        $select      = [
            'DISTINCT ' . DB::qn('k') . '.*',
            '( ' . $query->concatenate($nameColumns, ' - ') . ' ) AS ' . DB::qn('name'),
        ];

        $query->select($select)
            ->from(DB::qn('#__organizer_roomkeys', 'k'));

        switch (Input::getView()) {
            case 'Rooms':
                $query->innerJoin(DB::qn('#__organizer_use_codes', 'uc'), DB::qc('uc.keyID', 'k.id'))
                    ->innerJoin(DB::qn('#__organizer_roomtypes', 't'), DB::qc('t.useCode', 'uc.id'))
                    ->innerJoin(DB::qn('#__organizer_rooms', 'r'), DB::qc('r.roomtypeID', 't.id'));
                break;
            case 'RoomTypes':
                $query->innerJoin(DB::qn('#__organizer_use_codes', 'uc'), DB::qc('uc.keyID', 'k.id'))
                    ->innerJoin(DB::qn('#__organizer_roomtypes', 't'), DB::qc('t.useCode', 'uc.id'));
                break;
            default:
                break;
        }

        $query->order(DB::qn('name'));
        DB::setQuery($query);

        return DB::loadAssocList('id');
    }
}
