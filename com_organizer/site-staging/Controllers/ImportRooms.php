<?php

namespace THM\Organizer\Controllers;

use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Tables\Rooms as Table;

/** @inheritDoc */
class ImportRooms extends FormController
{
    use FluMoxed;

    protected string $list = 'Rooms';

    /**
     * Cleans an individual row for later processing.
     * - Replaces escaped quotes "" and commas in quoted values with HTML entities.
     * - Removes newline carriage return.
     *
     * @param   string  $row  the row to clean
     *
     * @return void
     */
    private function cleanRow(string &$row): void
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
     * Imports room data from a csv file.
     * @return void
     */
    public function import(): void
    {
        $this->checkToken();
        $this->authorize();
        Application::message('503', Application::NOTICE);
        $this->setRedirect("$this->baseURL&view=rooms");

        // Too big for joomla's comprehensive debugging.
        /*if (JDEBUG) {
            Application::message('ORGANIZER_DEBUG_ON', Application::ERROR);

            $this->setRedirect("$this->baseURL&view=rooms");
            return;
        }

        $file = Input::getInput()->files->get('file');

        if (empty($file['type']) or $file['type'] !== 'text/csv') {
            Application::message('FILE_TYPE_INVALID', Application::ERROR);
            $this->setRedirect("$this->baseURL&view=importrooms");
            return;
        }

        if (mb_detect_encoding($file['tmp_name'], 'UTF-8', true) !== 'UTF-8') {
            Application::message('FILE_ENCODING_INVALID', Application::ERROR);
            $this->setRedirect("$this->baseURL&view=importrooms");
            return;
        }

        $file = fopen($file['tmp_name'], 'r');

        $headers = fgets($file);

        // Excel adds an invisible BOM to the UTF-8 export. Go Excel!
        $headers = str_replace(chr(239) . chr(187) . chr(191), '', $headers);

        $this->cleanRow($headers);
        $headers  = explode(',', $headers);
        $expected = count($headers);

        $codeIndex = array_search('code', $headers);

        if ($codeIndex === false) {
            Application::message('No code column.', Application::ERROR);

            return false;
        }

        $floorings = [
            1  => 'PVC',
            2  => 'Betonwerkstein',
            3  => 'Dielen',
            4  => 'Estrich',
            5  => 'Fliesen',
            6  => 'Gitterrost',
            7  => 'Holz',
            8  => 'Kautschuk',
            9  => 'Linoleum',
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
                Application::message("Malformed row: $row.", Application::ERROR);
                continue;
            }

            if (!$code = $values[$codeIndex]) {
                Application::message("No code value: $row.", Application::ERROR);
                continue;
            }

            $room = new Table();

            if (!$room->load(['code' => $code])) {
                Application::message("Room does not exist: $code.", Application::ERROR);
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

        Application::message(Text::_('ORGANIZER_IMPORT_SUCCESS'));

        return true;*/
    }
}