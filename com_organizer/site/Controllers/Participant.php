<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

/**
 * @inheritDoc
 */
class Participant extends FormController
{
    protected string $list = 'Participants';

    /**
     * Filters names (city, forename, surname) for actual letters and accepted special characters.
     *
     * @param   string  $name  the raw value
     *
     * @return string the cleaned value
     */
    /*private function cleanAlpha(string $name): string
    {//unpaired symbol?}
        $name = preg_replace('/[^A-ZÀ-ÖØ-Þa-zß-ÿ\p{N}_.\-\']/', ' ', $name);

        return self::cleanSpaces($name);
    }*/


    /**
     * Filters out extra spaces.
     *
     * @param   string  $string  the raw value
     *
     * @return string the cleaned value
     */
    /*private function cleanSpaces(string $string): string
    {
        return preg_replace('/ +/', ' ', $string);
    }*/

    /**
     * Filters names (city, forename, surname) for actual letters and accepted special characters.
     *
     * @param   string  $name  the raw value
     *
     * @return string the cleaned value
     */
    /*private function cleanAlphaNum(string $name): string
    {//unpaired symbol?}
        $name = preg_replace('/[^A-ZÀ-ÖØ-Þa-zß-ÿ\d\p{N}_.\-\']/', ' ', $name);

        return self::cleanSpaces($name);
    }*/

    /**
     * @inheritDoc
     */
    /*public function save(array $data = [])
    {
        $data = empty($data) ? Input::getFormItems()->toArray() : $data;

        if (!isset($data['id'])) {
            Application::message('ORGANIZER_400', Application::ERROR);

            return false;
        }

        if (!Helpers\Can::edit('participant', $data['id'])) {
            Application::error(403);
        }

        $numericFields = ['id', 'programID'];

        switch (Input::getTask()) {
            case 'participants.save':
                $requiredFields = ['address', 'city', 'forename', 'id', 'programID', 'surname', 'zipCode'];
                break;
            case 'checkin.contact':
                $requiredFields = ['address', 'city', 'forename', 'id', 'surname', 'telephone', 'zipCode'];
                break;
            default:
                Application::error(501);

                return false;

        }

        foreach ($data as $index => $value) {
            if (in_array($index, $requiredFields)) {
                $data[$index] = trim($value);

                if (empty($data[$index])) {
                    Application::message('ORGANIZER_400', Application::WARNING);

                    return false;
                }

                if (in_array($index, $numericFields)) {
                    if (!is_numeric($value)) {
                        Application::message('ORGANIZER_400', Application::WARNING);

                        return false;
                    }

                    $value = (int) $value;
                }

                if ($index === 'programID' and $value === -1) {
                    $data[$index] = null;
                }
            }
        }

        $data['address']   = self::cleanAlphaNum($data['address']);
        $data['city']      = self::cleanAlpha($data['city']);
        $data['forename']  = self::cleanAlpha($data['forename']);
        $data['surname']   = self::cleanAlpha($data['surname']);
        $data['telephone'] = empty($data['telephone']) ? '' : self::cleanAlphaNum($data['telephone']);
        $data['zipCode']   = self::cleanAlphaNum($data['zipCode']);

        $table = new Table();

        if ($table->load($data['id'])) {
            $altered = false;

            foreach ($data as $property => $value) {
                if (property_exists($table, $property)) {
                    $table->set($property, $value);
                    $altered = true;
                }
            }

            if ($altered) {
                if ($table->store()) {
                    Application::message('ORGANIZER_CHANGES_SAVED');
                }
                else {
                    Application::message('ORGANIZER_CHANGES_NOT_SAVED', Application::ERROR);
                }
            }

            return $data['id'];
        }

        // 'Manual' insertion because the table's primary key is also a foreign key.
        $relevantData = (object) $data;

        foreach ($relevantData as $property => $value) {
            if (!property_exists($table, $property)) {
                unset($relevantData->$property);
            }
        }

        if (Database::insertObject('#__organizer_participants', $relevantData)) {
            Application::message('ORGANIZER_PARTICIPANT_ADDED');

            return $data['id'];
        }

        Application::message('ORGANIZER_PARTICIPANT_NOT_ADDED');

        return false;
    }*/

    /**
     * Adds an organizer participant based on the information in the users table.
     *
     * @param   int   $participantID  the id of the participant/user entries
     * @param   bool  $force          forces update of the columns derived from information in the user table
     *
     * @return void
     */
    /*public function supplement(int $participantID, bool $force = false)
    {
        $exists = Helpers\Participants::exists($participantID);

        if ($exists and !$force) {
            return;
        }

        $names = Helpers\Users::resolveUserName($participantID);
        $query = Database::getQuery();

        $forename = $query->quote($names['forename']);
        $surname  = $query->quote($names['surname']);

        if (!$exists) {
            $query->insert('#__organizer_participants')
                ->columns('id, forename, surname')
                ->values("$participantID, $forename, $surname");
        }
        else {
            $query->update('#__organizer_persons')
                ->set("forename = $forename")
                ->set("surname = $surname")
                ->where("id = $participantID");
        }

        Database::setQuery($query);

        Database::execute();
    }*/
}
