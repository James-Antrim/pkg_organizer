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

use THM\Organizer\Adapters\{Application, Database as DB};
use Joomla\Database\ParameterType;

/** @inheritDoc */
class RoomKey extends EditModel
{
    protected string $tableClass = 'RoomKeys';

    /** @inheritDoc */
    public function getItem(): object
    {
        $item = parent::getItem();

        if ($item and !empty($item->useID)) {
            $tag   = Application::tag();
            $query = DB::query();
            $query->select(DB::qn("name_$tag"))
                ->from(DB::qn('#__organizer_use_groups'))
                ->where(DB::qn('id') . ' = :useID')
                ->bind(':useID', $item->useID, ParameterType::INTEGER);
            DB::set($query);
            $item->useGroup = DB::string();
        }

        return $item;
    }

}
