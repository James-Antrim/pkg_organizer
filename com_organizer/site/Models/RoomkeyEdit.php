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

use THM\Organizer\Adapters\{Application, Database};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

/**
 * Class loads a form for editing building data.
 */
class RoomkeyEdit extends EditModelOld
{
    /**
     * Checks access to edit the resource.
     * @return void
     */
    protected function authorize()
    {
        if (!Helpers\Can::manage('facilities')) {
            Application::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    public function getItem()
    {
        parent::getItem();

        if ($this->item and !empty($this->item->useID)) {
            $tag   = Application::getTag();
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
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return Tables\RoomKeys  A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = []): Tables\RoomKeys
    {
        return new Tables\RoomKeys();
    }
}
