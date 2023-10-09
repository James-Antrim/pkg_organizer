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

use THM\Organizer\Adapters\Database;
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

/**
 * Class loads a form for editing building data.
 */
class RoomkeyEdit extends EditModel
{
    /**
     * Checks access to edit the resource.
     * @return void
     */
    protected function authorize()
    {
        if (!Helpers\Can::manage('facilities')) {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    public function getItem($pk = 0)
    {
        parent::getItem($pk);

        if ($this->item and !empty($this->item->useID)) {
            $tag   = Helpers\Languages::getTag();
            $query = Database::getQuery();
            $query->select("name_$tag")->from('#__organizer_use_groups')->where("id = {$this->item->useID}");
            Database::setQuery($query);
            $this->item->useGroup = Database::loadString();
        }

        return $this->item;
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param string $name    The table name. Optional.
     * @param string $prefix  The class prefix. Optional.
     * @param array  $options Configuration array for model. Optional.
     *
     * @return Tables\Roomkeys  A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = []): Tables\Roomkeys
    {
        return new Tables\Roomkeys();
    }
}
