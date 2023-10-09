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

use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Tables\Rooms as Table;

/**
 * Class which manages stored room data.
 */
class Room extends MergeModel
{
    /**
     * Activates rooms by id if a selection was made, otherwise by use in the instance_rooms table.
     * @return bool true on success, otherwise false
     */
    public function activate(): bool
    {
        $this->selected = Helpers\Input::getSelectedIDs();
        $this->authorize();

        // Explicitly selected resources
        if ($this->selected) {
            foreach ($this->selected as $selectedID) {
                $room = new Table();

                if ($room->load($selectedID)) {
                    $room->active = 1;
                    $room->store();
                    continue;
                }

                return false;
            }

            return true;
        }

        // Implicitly used resources
        $subQuery = Database::getQuery(true);
        $subQuery->select('DISTINCT roomID')->from('#__organizer_instance_rooms');
        $query = Database::getQuery(true);
        $query->update('#__organizer_rooms')->set('active = 1')->where("id IN ($subQuery)");
        Database::setQuery($query);

        return Database::execute();
    }

    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        if (!Helpers\Can::manage('facilities')) {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * Cleans an individual row for later processing.
     * - Replaces escaped quotes "" and commas in quoted values with HTML entities.
     * - Removes newline carriage return.
     *
     * @param string $row the row to clean
     *
     * @return void modifies the row
     */
    private function cleanRow(string &$row)
    {
        $row = str_replace('""', '&quot;', $row);
        $row = str_replace(chr(13) . chr(10), '', $row);

        if (preg_match_all('/"[^"\n]+"/', $row, $matches)) {
            foreach (array_unique($matches[0]) as $search) {
                $replace = $search;
                $replace = trim($replace, '"');
                $replace = str_replace(',', '&comma;', $replace);
                $row     = str_replace($search, $replace, $row);
            }
        }
    }

    /**
     * Deactivates rooms by id if a selection was made, otherwise by lack of use in the instance_rooms table.
     * @return bool true on success, otherwise false
     */
    public function deactivate(): bool
    {
        $this->selected = Helpers\Input::getSelectedIDs();
        $this->authorize();

        // Explicitly selected resources
        if ($this->selected) {
            foreach ($this->selected as $selectedID) {
                $room = new Table();

                if ($room->load($selectedID)) {
                    $room->active = 0;
                    $room->store();
                    continue;
                }

                return false;
            }

            return true;
        }

        // Implicitly unused resources
        $subQuery = Database::getQuery(true);
        $subQuery->select('DISTINCT roomID')->from('#__organizer_instance_rooms');
        $query = Database::getQuery();
        $query->update('#__organizer_rooms')->set('active = 0')->where("id NOT IN ($subQuery)");
        Database::setQuery($query);

        return Database::execute();
    }

    /**
     * Imports room data from a csv file.
     * @return bool
     */
    public function import(): bool
    {
        $this->authorize();

        $input = Helpers\Input::getInput();

        $file = $input->files->get('jform', [], 'array')['file'];
        $file = fopen($file['tmp_name'], 'r');

        $headers = fgets($file);

        // Excel adds an invisible BOM to the UTF-8 export. Go Excel!
        $headers = str_replace(chr(239) . chr(187) . chr(191), '', $headers);

        $this->cleanRow($headers);
        $headers  = explode(',', $headers);
        $expected = count($headers);

        $codeIndex = array_search('code', $headers);


        if ($codeIndex === false) {
            Helpers\OrganizerHelper::message('No code column.', 'error');

            return false;
        }

        $floorings = [
            1 => 'PVC',
            2 => 'Betonwerkstein',
            3 => 'Dielen',
            4 => 'Estrich',
            5 => 'Fliesen',
            6 => 'Gitterrost',
            7 => 'Holz',
            8 => 'Kautschuk',
            9 => 'Linoleum',
            10 => 'Parkett (nicht versiegelt)',
            11 => 'Parkett (versiegelt)',
            12 => 'Riffelblech',
            13 => 'Schutzmatten (Gummi)',
            14 => 'Teppich'
        ];

        while (($row = fgets($file)) !== false) {
            $this->cleanRow($row);
            $values = explode(',', $row);

            if (count($values) !== $expected) {
                Helpers\OrganizerHelper::message("Malformed row: $row.", 'error');
                continue;
            }

            if (!$code = $values[$codeIndex]) {
                Helpers\OrganizerHelper::message("No code value: $row.", 'error');
                continue;
            }

            $room = new Table();

            if (!$room->load(['code' => $code])) {
                Helpers\OrganizerHelper::message("Room does not exist: $code.", 'error');
                continue;
            }

            foreach ($values as $key => $value) {
                if (!$value = trim($value)) {
                    continue;
                }

                $value = html_entity_decode($value);

                switch ($headers[$key]) {
                    case 'area':
                        //echo "<pre>area!</pre>";
                        $room->area = (float) $value;
                        break;
                    case 'effCapacity':
                        $room->effCapacity = (int) $value;
                        break;
                    case 'flooring':
                        $room->flooringID = array_search($value, $floorings) ?: 1;
                        break;
                    case 'room_type':
                    case 'organization':
                        //soon
                        break;
                    // Already used to load
                    case 'code':
                    default:
                        //echo "<pre>default?</pre>";
                        break;
                }
            }

            $room->store();
        }

        fclose($file);

        Helpers\OrganizerHelper::message(Helpers\Languages::_('ORGANIZER_IMPORT_SUCCESS'));

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getTable($name = '', $prefix = '', $options = [])
    {
        return new Table();
    }

    /**
     * @inheritDoc
     */
    protected function updateReferences(): bool
    {
        if (!$this->updateReferencingTable('monitors')) {
            return false;
        }

        return $this->updateIPReferences();
    }
}
