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

use Exception;
use THM\Organizer\Adapters\{Application, Database, Input, Text};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;
use THM\Organizer\Tables\Participants as Table;

/**
 * Class which manages stored participant data.
 */
class Participant extends MergeModel
{
    /**
     * Automatically merges participant and user entries fulfilling the these criteria:
     * - The first names and last names are the same (case insensitive)
     * - The number similar entries is exactly 2
     * - One similar entry has an email containing the component domain parameter, the other does not
     * - The similar entry with an appropriate email was created after the other
     * @return void
     */
    public function automaticMerge()
    {
        $query = Database::getQuery();
        $query->selectX(['COUNT(*) AS cardinality', 'p1.surname', 'p1.forename'], 'participants AS p1')
            ->innerJoinX('participants AS p2', ['p1.surname LIKE p2.surname', 'p1.forename LIKE p2.forename'])
            ->where(Database::qn('p1.id') . ' != ' . Database::qn('p2.id'))
            ->group('p1.surname, p1.forename');
        Database::setQuery($query);

        if (!$candidates = Database::loadAssocList()) {
            Application::message(Text::_('ORGANIZER_AUTOMATIC_MERGE_NO_CANDIDATES'));
        }

        $ambiguous   = 0;
        $failures    = 0;
        $implausible = 0;
        $successes   = 0;
        $surfeit     = 0;
        $synonyms    = 0;

        foreach ($candidates as $candidate) {
            // Candidates with a cardinality larger than two indicate multiple people with the same name
            if ($candidate['cardinality'] > 2) {
                $surfeit++;
                continue;
            }

            $query = Database::getQuery();
            $query->selectX(['id'], 'participants AS pa')
                ->where(Database::qn('surname') . "LIKE '{$candidate['surname']}'")
                ->where(Database::qn('forename') . "LIKE '{$candidate['forename']}'")
                ->order('id');
            Database::setQuery($query);

            if (!$this->selected = Database::loadColumn()) {
                $failures++;
                continue;
            }

            [$firstID, $secondID] = $this->selected;

            $firstParticipant = new Table();
            $firstParticipant->load($firstID);
            $firstUser = Helpers\Users::getUser($firstID);

            $secondParticipant = new Table();
            $secondParticipant->load($secondID);
            $secondUser = Helpers\Users::getUser($secondID);

            // Update the names being used and the corresponding attributes of the target participant
            $firstParticipant->forename = $this->compareStrings($firstParticipant->forename, $secondParticipant->forename);
            $firstParticipant->surname  = $this->compareStrings($firstParticipant->surname, $secondParticipant->surname);

            $domain         = Input::getParams()->get('emailFilter');
            $firstInternal  = strpos($firstUser->email, $domain);
            $secondInternal = strpos($secondUser->email, $domain);

            // Both users have a legitimate email address, most likely different people
            if ($firstInternal and $secondInternal) {
                $synonyms++;
                continue;
            } // The natural order of the later user being the internal user
            elseif ($secondInternal) {
                $firstUser->email    = $secondUser->email;
                $firstUser->name     = $secondUser->name;
                $firstUser->username = $secondUser->username;
            } // The implausible order of the first user being the internal user
            elseif (!$firstInternal) {
                $implausible++;
                continue;
            } // Neither user has a legitimate email address, no way to say which is correct
            else {
                $ambiguous++;
                continue;
            }

            $lastUsed = $firstUser->lastvisitDate < $secondUser->lastvisitDate ? 2 : 1;

            $firstUser->activation     = $firstUser->activation ?: $secondUser->activation;
            $firstUser->block          = $firstUser->block ?: $secondUser->block;
            $firstUser->groups         = $firstUser->groups + $secondUser->groups;
            $firstUser->guest          = null;
            $firstUser->lastResetTime  = max($firstUser->lastResetTime, $secondUser->lastResetTime);
            $firstUser->lastvisitDate  = max($firstUser->lastvisitDate, $secondUser->lastvisitDate);
            $firstUser->password       = null;
            $firstUser->password_clear = null;
            $firstUser->registerDate   = min($firstUser->registerDate, $secondUser->registerDate);
            $firstUser->resetCount     = $firstUser->resetCount + $secondUser->resetCount;
            $firstUser->requireReset   = null;
            $firstUser->sendEmail      = $firstUser->sendEmail ?: $secondUser->sendEmail;

            if ($secondUser->params) {
                $params = json_decode($secondUser->params);

                foreach ($params as $property => $value) {
                    if ($lastUsed === 1) {
                        $value = $firstUser->getParam($property, $value);
                    }

                    $firstUser->setParam($property, $value);
                }
            }

            if (!$this->updateReferences()) {
                // Messages are generated at point of failure.
                continue;
            }

            if ($lastUsed === 1) {
                $this->mergeParticipants($firstParticipant, $firstParticipant, $secondParticipant);
            }
            else {
                $this->mergeParticipants($firstParticipant, $secondParticipant, $firstParticipant);
            }

            $firstParticipant->notify = ($firstParticipant->notify or $secondParticipant->notify);

            // Delete the second user & fk deletes second participant
            if (!$secondUser->delete()) {
                $failures++;

                continue;
            }

            // Update the first entries with the merged data
            if ($firstParticipant->store() and $firstUser->save()) {
                $successes++;
            }
            else {
                $failures++;
            }
        }

        if ($successes) {
            Application::message(Text::sprintf('ORGANIZER_AUTOMATIC_MERGE_SUCCESSES', $successes));
        }

        if ($failures) {
            Application::message(Text::sprintf('ORGANIZER_AUTOMATIC_MERGE_FAILURES', $failures), Application::ERROR);
        }

        if ($ambiguous) {
            Application::message(Text::sprintf('ORGANIZER_AUTOMATIC_MERGE_AMBIGUOUS', $ambiguous), Application::WARNING);
        }

        if ($implausible) {
            Application::message(Text::sprintf('ORGANIZER_AUTOMATIC_MERGE_IMPLAUSIBLE', $implausible),
                'warning');
        }

        if ($surfeit) {
            Application::message(Text::sprintf('ORGANIZER_AUTOMATIC_MERGE_SURFEIT', $surfeit), Application::WARNING);
        }

        if ($synonyms) {
            Application::message(Text::sprintf('ORGANIZER_AUTOMATIC_MERGE_SYNONYMS', $synonyms), Application::WARNING);
        }
    }

//	private function anonymize()
//	{
//		/**
//		 * Anonymize user:
//		 * name: 'Anonymous User'
//		 * username: 'x-<id>'
//		 * password: ''
//		 * email: x.<id>@xx.xx
//		 * block: 1
//		 * sendEmail: 0
//		 * registerDate: '0000-00-00 00:00:00'
//		 * lastvisitDate: '0000-00-00 00:00:00'
//		 * activation: ''
//		 * params: '{}'
//		 * lastResetTime: '0000-00-00 00:00:00'
//		 * resetCount: 0
//		 * otpKey: ''
//		 * otep: ''
//		 * requireReset: 0
//		 * authProvider: ''
//		 */
//
//		/**
//		 * Anonymize participant:
//		 *
//		 */
//	}

    /**
     * Filters names (city, forename, surname) for actual letters and accepted special characters.
     *
     * @param   string  $name  the raw value
     *
     * @return string the cleaned value
     */
    private function cleanAlpha(string $name): string
    {
        $name = preg_replace('/[^A-ZÀ-ÖØ-Þa-zß-ÿ\p{N}_.\-\']/', ' ', $name);

        return self::cleanSpaces($name);
    }

    /**
     * Filters names (city, forename, surname) for actual letters and accepted special characters.
     *
     * @param   string  $name  the raw value
     *
     * @return string the cleaned value
     */
    private function cleanAlphaNum(string $name): string
    {
        $name = preg_replace('/[^A-ZÀ-ÖØ-Þa-zß-ÿ\d\p{N}_.\-\']/', ' ', $name);

        return self::cleanSpaces($name);
    }

    /**
     * Filters out extra spaces.
     *
     * @param   string  $string  the raw value
     *
     * @return string the cleaned value
     */
    private function cleanSpaces(string $string): string
    {
        return preg_replace('/ +/', ' ', $string);
    }

    /**
     * Compares two string values to determine the presumed correct string based on value, length and capitalization
     *
     * @param   string  $string1  the first string
     * @param   string  $string2  the second string
     *
     * @return string
     */
    private function compareStrings(string $string1, string $string2): string
    {
        // Empty loses.
        if (!$string1) {
            return $string2;
        }

        if (!$string2) {
            return $string1;
        }

        // Longer wins
        if (strlen($string1) > strlen($string2)) {
            return $string1;
        }

        if (strlen($string2) > strlen($string1)) {
            return $string2;
        }

        // Lower score (more upper case letters) wins.
        return unpack('n', $string1)[1] < unpack('n', $string2)[1] ? $string1 : $string2;
    }

    /**
     * Merges resource entries and cleans association tables.
     * @return bool  true on success, otherwise false
     * @throws Exception
     */
    public function merge(): bool
    {
        $data = empty($this->data) ? Input::getFormItems()->toArray() : $this->data;

        if (empty($data['email'])) {
            Application::message('ORGANIZER_NO_EMAIL_ADDRESS_SELECTED', Application::ERROR);

            return false;
        }

        //todo get view name (booking|course|participants)
        $this->selected = Input::getSelectedIDs();
        sort($this->selected);

        //todo differentiate rights by the called view and resource id as applicable
        if (!Helpers\Can::administrate()) {
            Application::error(403);
        }

        // Associations have to be updated before entity references are deleted by foreign keys
        if (!$this->updateReferences()) {
            // Messages are generated at point of failure.
            return false;
        }

        $email             = $data['email'];
        $mergeID           = array_shift($this->selected);
        $data['id']        = $mergeID;
        $data['programID'] = empty($data['programID']) ? null : $data['programID'];

        $participant = new Table();
        $participant->load($mergeID);

        // Case sensitive information was not being properly set without this call and store instead of save.
        $participant->bind($data);

        // Participants table has no unique columns so saving before deleting duplicates is fine.
        $participant->store();

        $user = Helpers\Users::getUser($mergeID);

        $user->email          = $email;
        $user->guest          = null;
        $user->password_clear = null;
        $user->resetCount     = (int) $user->resetCount;

        foreach ($this->selected as $deprecatedID) {
            $thisUser = Helpers\Users::getUser($deprecatedID);

            if ($thisUser->email === $email) {
                $user->name     = $thisUser->name;
                $user->password = $thisUser->password;
                $user->username = $thisUser->username;
            }

            $user->activation    = max($user->activation, $thisUser->activation);
            $user->block         = $user->block ?: $thisUser->block;
            $user->groups        = array_merge($user->groups, $thisUser->groups);
            $laterUse            = $thisUser->lastvisitDate > $user->lastvisitDate;
            $user->lastResetTime = max($user->lastResetTime, $thisUser->lastResetTime);
            $user->lastvisitDate = max($user->lastvisitDate, $thisUser->lastvisitDate);
            $user->registerDate  = min($user->registerDate, $thisUser->registerDate);
            $user->requireReset  = null;
            $user->resetCount    += (int) $thisUser->resetCount;
            $user->sendEmail     = max($user->sendEmail, $thisUser->sendEmail);

            if ($thisUser->params) {
                $theseParams = json_decode($thisUser->params);

                foreach ($theseParams as $property => $value) {
                    if (!$laterUse) {
                        $value = $user->getParam($property, $value);
                    }

                    $user->setParam($property, $value);
                }
            }

            if (!$thisUser->delete()) {
                Application::message('ORGANIZER_USER_DELETION_FAILED', Application::ERROR);

                return false;
            }
        }

        if (!$user->save()) {
            return false;
        }

        return true;
    }

    /**
     * Merges the values of two entries into the first
     *
     * @param   Table  $table    the table modified by the merge
     * @param   Table  $source   the table whose data is prioritized
     * @param   Table  $default  the table whose data is used as a default
     *
     * @return void
     */
    private function mergeParticipants(Table $table, Table $source, Table $default): void
    {
        $table->address   = $source->address ?: $default->address;
        $table->city      = $source->city ?: $default->city;
        $table->telephone = $source->telephone ?: $default->telephone;
        $table->zipCode   = $source->zipCode ?: $default->zipCode;
        $table->programID = $source->programID ?: $default->programID;
    }

    /**
     * @inheritDoc
     */
    public function save(array $data = [])
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
    }

    /**
     * Adds an organizer participant based on the information in the users table.
     *
     * @param   int   $participantID  the id of the participant/user entries
     * @param   bool  $force          forces update of the columns derived from information in the user table
     *
     * @return void
     */
    public function supplement(int $participantID, bool $force = false)
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
    }

    /**
     * Truncates the participation to the threshold set in the component parameters. Called automatically by the system
     * plugin when administrator logs in.
     * @return void
     */
    public function truncateParticipation()
    {
        $threshold = (int) Input::getParams()->get('truncateHistory');

        if (!$threshold) {
            return;
        }

        $then = date('Y-m-d', strtotime("-$threshold days"));

        $query = Database::getQuery();
        $query->select('ip.*')
            ->from('#__organizer_instance_participants AS ip')
            ->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
            ->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
            ->where("b.date <= '$then'");
        Database::setQuery($query);

        $entries = Database::loadObjectList();

        $instances = [];

        foreach ($entries as $entry) {
            $instanceID = $entry->instanceID;

            if (empty($instances[$instanceID])) {
                $instances[$instanceID] = [
                    'attended'   => (int) $entry->attended,
                    'bookmarked' => 1,
                    'registered' => (int) $entry->registered
                ];
            }
            else {
                $instances[$instanceID]['attended']   += (int) $entry->attended;
                $instances[$instanceID]['bookmarked'] += 1;
                $instances[$instanceID]['registered'] += (int) $entry->registered;
            }

            $table = new Tables\InstanceParticipants();

            if ($table->load($entry->id)) {
                $table->delete();
            }
        }

        foreach ($instances as $instanceID => $participation) {
            $table = new Tables\Instances();

            if (!$table->load($instanceID)) {
                continue;
            }

            $table->attended   = $participation['attended'];
            $table->bookmarked = $participation['bookmarked'];
            $table->registered = $participation['registered'];

            $table->store();
        }
    }

    /**
     * Updates the course participants table to reflect the merge of the persons.
     * @return bool true on success, otherwise false
     */
    public function updateCourseParticipants(): bool
    {
        if (!$courseIDs = $this->getReferencedIDs('course_participants', 'courseID')) {
            return true;
        }

        $mergeID = reset($this->selected);

        foreach ($courseIDs as $courseID) {
            $attended   = false;
            $existing   = null;
            $paid       = false;
            $registered = '';

            foreach ($this->selected as $participantID) {
                $assoc   = ['courseID' => $courseID, 'participantID' => $participantID];
                $current = new Tables\CourseParticipants();

                // The current participantID is not associated with the current course
                if (!$current->load($assoc)) {
                    continue;
                }

                $attended   = (int) ($attended or $current->attended);
                $paid       = (int) ($paid or $current->paid);
                $registered = ($registered and $registered < $current->participantDate) ? $registered : $current->participantDate;

                if (!$existing) {
                    $existing = $current;
                    continue;
                }

                $current->delete();
            }

            if (!$existing) {
                continue;
            }

            $existing->attended        = $attended;
            $existing->paid            = $paid;
            $existing->participantDate = $registered;
            $existing->participantID   = $mergeID;

            if (!$existing->store()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Updates the instance participants table to reflect the merge of the persons.
     * @return bool true on success, otherwise false
     */
    public function updateInstanceParticipants(): bool
    {
        if (!$instanceIDs = $this->getReferencedIDs('instance_participants', 'instanceID')) {
            return true;
        }

        $mergeID = reset($this->selected);

        foreach ($instanceIDs as $instanceID) {
            $attended   = false;
            $existing   = null;
            $registered = false;
            $roomID     = null;
            $seat       = null;

            foreach ($this->selected as $participantID) {
                $assoc   = ['instanceID' => $instanceID, 'participantID' => $participantID];
                $current = new Tables\InstanceParticipants();

                // The current participantID is not associated with the current course
                if (!$current->load($assoc)) {
                    continue;
                }

                $attended   = (int) ($attended or $current->attended);
                $registered = (int) ($registered or $current->registered);
                $roomID     = $current->roomID ?? $roomID;
                $seat       = $current->seat ?? $seat;

                if (!$existing) {
                    $existing = $current;
                    continue;
                }

                $current->delete();
            }

            if (!$existing) {
                continue;
            }

            $existing->attended      = $attended;
            $existing->participantID = $mergeID;
            $existing->registered    = $registered;
            $existing->roomID        = $roomID;
            $existing->seat          = $seat;

            if (!$existing->store()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function updateReferences(): bool
    {
        if (!$this->updateCourseParticipants()) {
            Application::message('ORGANIZER_COURSE_PARTICIPATION_MERGE_FAILED', Application::ERROR);

            return false;
        }

        if (!$this->updateInstanceParticipants()) {
            Application::message('ORGANIZER_INSTANCE_PARTICIPATION_MERGE_FAILED', Application::ERROR);

            return false;
        }

        return true;
    }
}
