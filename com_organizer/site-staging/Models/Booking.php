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

use Joomla\CMS\{Form\Form, User\User};
use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input, Text, User as UAdapter};
use THM\Organizer\Controllers\{Participant, Participated};
use THM\Organizer\Helpers\{Can, Bookings as Helper, Participants as PHelper};
use THM\Organizer\Tables;

/**
 * Class retrieves information for a filtered set of participants.
 */
class Booking extends Participants
{
    use Participated;

    public Tables\Bookings $booking;

    protected string $defaultOrdering = 'fullName';

    protected $filter_fields = ['instanceID', 'roomID', 'status'];

    /** @inheritDoc */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->booking = $this->getBooking();
    }

    /**
     * Creates a new entry in the booking table for the given instance.
     * @return int the id of the booking entry
     */
    public function add(): int
    {
        if (!UAdapter::id()) {
            Application::error(401);
        }

        if (!$instanceIDs = Input::getSelectedIDs()) {
            Application::error(400);
        }

        $instanceID = array_shift($instanceIDs);

        if (!Can::manage('instance', $instanceID)) {
            Application::error(403);
        }

        $block    = new Tables\Blocks();
        $instance = new Tables\Instances();
        if (!$instance->load($instanceID) or !$block->load($instance->blockID)) {
            Application::error(412);
        }

        if ($instance->delta === 'removed') {
            Application::message('ORGANIZER_DEPRECATED_INSTANCE', Application::NOTICE);

            return 0;
        }

        $booking = new Tables\Bookings();
        $keys    = ['blockID' => $instance->blockID, 'unitID' => $instance->unitID];

        if (!$booking->load($keys)) {
            $hash   = hash('adler32', $instance->blockID . $instance->unitID);
            $values = ['code' => substr($hash, 0, 4) . '-' . substr($hash, 4)];

            if ($booking->save(array_merge($keys, $values))) {
                Application::message('ORGANIZER_BOOKING_CREATED');
            }
            else {
                Application::message('ORGANIZER_BOOKING_NOT_CREATED', Application::ERROR);
            }
        }

        return $booking->id;
    }

    /**
     * Adds a participant to the instance(s) of the booking.
     * @return void
     */
    public function addParticipant(): void
    {
        $this->authorize();

        $listItems = Input::getListItems();
        $input     = $listItems->get('username');

        if (empty($input) or !$input = trim($input)) {
            Application::error(400);
        }

        $bookingID = Input::getID();

        // Manually unset the username, so it isn't later added to the state
        Input::getInput()->set('list', ['fullordering' => $listItems->get('fullordering')]);

        $existing = true;
        $query    = DB::query();
        $query->select('id')->from('#__users')->where("username = " . $query->quote($input));
        DB::set($query);

        if ($participantID = DB::integer()) {
            if (!PHelper::exists($participantID)) {
                Participant::supplement($participantID);
                $existing = false;
            }
        }
        else {
            $input   = mb_convert_encoding($input, 'ISO-8859-1', 'utf-8');
            $content = http_build_query(['name' => $input]);
            $header  = "Content-type: application/x-www-form-urlencoded\r\n";
            $context = stream_context_create([
                'http' => [
                    'header'  => $header,
                    'method'  => 'POST',
                    'content' => $content
                ]
            ]);

            if (!$response = file_get_contents('https://scripts.its.thm.de/emsearch/emsearch.cgi', false, $context)) {
                Application::message('ORGANIZER_503', Application::ERROR);

                return;
            }

            // Determine the response charset
            $charset = 'utf-8';
            foreach ($http_response_header as $httpHeader) {
                $position = strpos($httpHeader, 'charset=');
                if ($position !== false) {
                    $charset = substr($httpHeader, $position + strlen('charset='));
                }
            }

            $count  = substr_count($response, '<li>');
            $over30 = str_contains($response, 'mehr als 30');

            if ($count > 1 or $over30) {
                $message = Text::sprintf('ORGANIZER_TOO_MANY_RESULTS', $input);
                Application::message($message, Application::NOTICE);

                return;
            }
            elseif (!$count) {
                Application::message('ORGANIZER_EMPTY_RESULT_SET', Application::NOTICE);

                return;
            }

            // Remove characters upto and after li-tags inclusively
            $response = mb_convert_encoding($response, 'utf-8', $charset);
            $response = substr($response, strpos($response, '<li>') + 4);
            $response = substr($response, 0, strpos($response, '</li>'));
            $email    = $name = $username = '';

            // Attributes are unique to tags now
            if (preg_match('/<b>(.*?)<\/b>/', $response, $matches)) {
                $name = $matches[1];
            }
            if (preg_match('/<i>(.*?)<\/i>/', $response, $matches)) {
                $username = $matches[1];
            }
            if (preg_match('/<a[^>]*>(.*?)<\/a>/', $response, $matches)) {
                $email = $matches[1];
            }

            // Avoid potential inconsistent external data delivery
            if (!$email or !$name or !$username) {
                Application::message('ORGANIZER_412', Application::ERROR);

                return;
            }

            $query->clear('where');
            $query->where("username = '$username'");
            DB::set($query);
            $userNameID = DB::integer();

            $query->clear('where');
            $query->where("email = '$email'");

            if ($userNameID) {
                $query->where("id != $userNameID");
            }

            DB::set($query);
            $emailID = DB::integer();

            // These cannot be the same because of the email query's construction
            if ($userNameID and $emailID) {
                $userNameParticipant = new Tables\Participants();
                $emailParticipant    = new Tables\Participants();

                // One of the users does not exist as a participant (best case)
                if (!$userNameParticipant->load($userNameID) or !$emailParticipant->load($emailID)) {
                    if ($userNameParticipant->id) {
                        $deleteID      = $emailID;
                        $participantID = $userNameID;
                    }
                    else {
                        $deleteID      = $userNameID;
                        $participantID = $emailID;
                    }
                } // Merge
                else {
                    $deleteID      = $emailID;
                    $participantID = $userNameID;

                    foreach (array_keys($this->_db->getTableColumns('#__organizer_participants')) as $column) {
                        if ($column === 'id') {
                            continue;
                        }

                        if (!$userNameParticipant->$column and $emailParticipant->$column) {
                            $userNameParticipant->$column = $emailParticipant->$column;
                        }
                    }

                    $userNameParticipant->store();
                    $this->reReference('course', $participantID, $emailID, 'courseID');
                    $this->reReference('instance', $participantID, $emailID, 'instanceID');
                }

                $user = new User();
                $user->load($deleteID);
                $user->delete();
            }
            elseif ($userNameID or $emailID) {
                $participantID = $userNameID ?: $emailID;
            }
            else {
                $data     = [
                    'block'    => 0,
                    'email'    => $email,
                    'groups'   => [2],
                    'name'     => $name,
                    'username' => $username
                ];
                $existing = false;

                $user = new User();
                $user->bind($data);
                $user->save();

                if (!$participantID = $user->id) {
                    Application::message('ORGANIZER_PARTICIPANT_NOT_IMPORTED', Application::ERROR);

                    return;
                }
            }

            Participant::supplement($participantID, true);
        }

        $instanceIDs = Helper::instanceIDs($bookingID);

        // Check for existing entries in an existing participant's personal schedule
        if ($existing) {
            $query = DB::query();
            $query->select('id')
                ->from('#__organizer_instance_participants')
                ->where("participantID = $participantID")
                ->where('instanceID IN (' . implode(',', $instanceIDs) . ')');
            DB::set($query);

            if ($ipaIDs = DB::integers()) {
                foreach ($ipaIDs as $ipaID) {
                    $participation = new Tables\InstanceParticipants();
                    $participation->load($ipaID);
                    $participation->attended = 1;

                    if (!$participation->store()) {
                        Application::message('ORGANIZER_PARTICIPANT_NOT_ADDED', Application::ERROR);

                        return;
                    }

                    $this->updateIPNumbers($participation->instanceID);
                }

                Application::message('ORGANIZER_PARTICIPANT_ADDED');

                return;
            }
        }

        $data = ['attended' => 1, 'participantID' => $participantID];
        foreach ($instanceIDs as $instanceID) {
            $data['instanceID'] = $instanceID;
            $participation      = new Tables\InstanceParticipants();
            if (!$participation->save($data)) {
                Application::message('ORGANIZER_PARTICIPANT_NOT_ADDED', Application::ERROR);

                return;
            }

            $this->updateIPNumbers($instanceID);
        }

        Application::message('ORGANIZER_PARTICIPANT_ADDED');
    }

    /**
     * Performs authorization checks for booking dm functions.
     * @return void
     */
    private function authorize(): void
    {
        if (!$bookingID = Input::getID()) {
            Application::error(400);
        }

        if (!Can::manage('booking', $bookingID)) {
            Application::error(403);
        }
    }

    /**
     * Updates the selected participation entries with the selected instance and/or room.
     * @return bool
     */
    public function batch(): bool
    {
        $this->authorize();

        $batch      = Input::getBatchItems();
        $instanceID = (int) $batch->get('instanceID');
        $roomID     = (int) $batch->get('roomID');

        if (!$instanceID and !$roomID) {
            return true;
        }

        foreach (Input::getSelectedIDs() as $participationID) {
            $participation = new Tables\InstanceParticipants();

            if (!$participation->load($participationID)) {
                return false;
            }

            if ($instanceID) {
                $participation->instanceID = $instanceID;
            }

            if ($roomID) {
                $participation->roomID = $roomID;
            }

            if (!$participation->store()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes booking unassociated with attendance.
     * @return void
     */
    public function clean(): void
    {
        $today = date('Y-m-d');
        $query = DB::query();
        $query->select('DISTINCT bk.id')
            ->from('#__organizer_bookings AS bk')
            ->innerJoin('#__organizer_blocks AS bl ON bl.id = bk.blockID')
            ->where("bl.date < '$today'");
        DB::set($query);

        if (!$allIDs = DB::column()) {
            Application::message(Text::_('ORGANIZER_BOOKINGS_NOT_DELETED'), Application::NOTICE);

            return;
        }

        $query->innerJoin('#__organizer_instances AS i ON i.blockID = bk.blockID AND i.unitID = bk.unitID')
            ->innerJoin('#__organizer_instance_participants AS ip ON ip.instanceID = i.id')
            ->where('ip.attended = 1');
        DB::set($query);

        if (!$attendedIDs = DB::column()) {
            Application::message(Text::_('ORGANIZER_BOOKINGS_NOT_DELETED'), Application::NOTICE);

            return;
        }

        if (!$unAttendedIDs = array_diff($allIDs, $attendedIDs)) {
            Application::message(Text::_('ORGANIZER_BOOKINGS_NOT_DELETED'), Application::NOTICE);

            return;
        }

        $query = DB::query();
        $query->delete('#__organizer_bookings')->where('id IN (' . implode(',', $unAttendedIDs) . ')');
        DB::set($query);

        if (DB::execute()) {
            $constant = 'ORGANIZER_BOOKINGS_DELETED';
            $type     = Application::MESSAGE;
        }
        else {
            $constant = 'ORGANIZER_BOOKINGS_NOT_DELETED';
            $type     = Application::ERROR;
        }

        Application::message(Text::_($constant), $type);
    }

    /**
     * Checks the selected participants into the booking.
     * @return void
     */
    public function checkin(): void
    {
        $this->authorize();

        if (!Helper::instanceIDs(Input::getID())) {
            Application::error(400);
        }

        $count = 0;

        foreach (Input::getSelectedIDs() as $participationID) {
            $participation = new Tables\InstanceParticipants();

            if ($participation->load($participationID)) {
                $participation->attended = true;

                if ($participation->store()) {
                    $this->updateIPNumbers($participation->instanceID);
                    $count++;
                }
            }
        }

        $type    = $count ? Application::MESSAGE : Application::NOTICE;
        $message = Text::sprintf('ORGANIZER_CHECKED_IN_COUNT', $count);
        Application::message($message, $type);
    }

    /**
     * Closes a booking manually.
     * @return void
     */
    public function close(): void
    {
        $this->authorize();

        $block     = new Tables\Blocks();
        $booking   = new Tables\Bookings();
        $bookingID = Input::getID();

        if (!$booking->load($bookingID) or !$block->load($booking->blockID)) {
            Application::message('ORGANIZER_412', Application::ERROR);

            return;
        }

        $now   = date('H:i:s');
        $today = date('Y-m-d');

        if ($block->date === $today and $now > $block->startTime) {
            $booking->endTime = $now;

            if ($booking->store()) {
                Application::message('ORGANIZER_BOOKING_CLOSED');

                return;
            }
        }

        Application::message('ORGANIZER_BOOKING_NOT_CLOSED', Application::NOTICE);
    }

    /** @inheritDoc */
    protected function filterFilterForm(Form $form): void
    {
        parent::filterFilterForm($form);

        $bookingID = Input::getID();

        if (!Application::backend()) {
            $form->removeField('limit', 'list');
        }

        $bookingDate = $this->booking->get('date');
        $now         = date('H:i:s');
        $start       = $this->booking->startTime ?: $this->booking->get('defaultStartTime');
        $started     = $now > $start;
        $today       = date('Y-m-d');

        if ($today > $bookingDate or !$started) {
            $form->removeField('username', 'list');
        }

        if (count(Helper::instanceOptions($bookingID)) === 1) {
            $form->removeField('instanceID', 'filter');
            unset($this->filter_fields[array_search('instanceID', $this->filter_fields)]);
        }

        if (count(Helper::roomOptions($bookingID)) <= 1) {
            $form->removeField('roomID', 'filter');
            unset($this->filter_fields[array_search('roomID', $this->filter_fields)]);
        }
    }

    /**
     * Gets the booking table entry, and fills appropriate form field values.
     * @return Tables\Bookings
     */
    public function getBooking(): Tables\Bookings
    {
        $bookingID = Input::getID();
        $booking   = new Tables\Bookings();
        $booking->load($bookingID);

        $block = new Tables\Blocks();
        $block->load($booking->blockID);
        $booking->set('date', $block->date);
        $booking->set('defaultEndTime', $block->endTime);
        $booking->set('defaultStartTime', $block->startTime);

        return $booking;
    }

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $bookingID = Input::getID();
        $query     = parent::getListQuery();
        $query->select('r.name AS room, ip.id AS ipaID, ip.attended, ip.seat, ip.registered')
            ->innerJoin('#__organizer_instance_participants AS ip ON ip.participantID = pa.id')
            ->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
            ->innerJoin('#__organizer_bookings AS b ON b.blockID = i.blockID AND b.unitID = i.unitID')
            ->leftJoin('#__organizer_rooms AS r ON r.id = ip.roomID')
            ->where("b.id = $bookingID")
            ->where('(ip.attended = 1 or ip.registered = 1)');

        $this->filterValues($query, ['instanceID', 'roomID']);

        switch ((int) $this->state->get('filter.status')) {
            case Helper::ATTENDEES:
                $query->where('ip.attended = 1');
                break;
            case Helper::IMPROPER:
                $query->where('ip.attended = 1')->where('ip.registered = 0');
                break;
            case Helper::ONLY_REGISTERED:
                $query->where('ip.attended = 0')->where('ip.registered = 1');
                break;
            case Helper::PROPER:
                $query->where('ip.attended = 1')->where('ip.registered = 1');
                break;
        }

        $query->group('participantID');

        return $query;
    }

    /** @inheritDoc */
    public function getItems(): array
    {
        $bookingID = Input::getID();
        $query     = DB::query();
        $tag       = Application::tag();
        $query->select("e.name_$tag AS event")
            ->from('#__organizer_events AS e')
            ->innerJoin('#__organizer_instances AS i ON i.eventID = e.id')
            ->innerJoin('#__organizer_bookings AS b ON b.blockID = i.blockID AND b.unitID = i.unitID')
            ->innerJoin('#__organizer_instance_participants AS ip ON ip.instanceID = i.id');

        $rooms      = Helper::rooms($bookingID);
        $updateID   = 0;
        $updateRoom = '';

        if (count($rooms) === 1) {
            $updateID   = array_key_first($rooms);
            $updateRoom = reset($rooms);
        }

        $warning      = HTML::icon('fa fa-exclamation-triangle yellow');
        $eventWarning = $warning . ' ' . Text::_('SELECT_EVENT');
        $roomWarning  = $warning . ' ' . Text::_('SELECT_ROOM');

        foreach ($items = parent::getItems() as $item) {
            if ($item->attended and empty($item->room)) {
                if ($updateID) {
                    $table = new Tables\InstanceParticipants();
                    $table->load($item->ipaID);
                    $table->roomID = $updateID;
                    $table->store();
                    $item->room = $updateRoom;
                }
                else {
                    $item->room = $roomWarning;
                }
            }

            $columns        = ['address', 'city', 'forename', 'surname', 'telephone', 'zipCode'];
            $item->complete = true;

            foreach ($columns as $column) {
                if (empty($item->$column)) {
                    $item->complete = false;

                    break;
                }
            }

            $query->clear('where');
            $query->where("b.id = $bookingID")->where("ip.participantID = $item->id");
            DB::set($query);

            if ($events = DB::column()) {
                $item->event = count($events) > 1 ? $eventWarning : $events[0];
            }
            else {
                $item->event = '';
            }

        }

        return $items ?: [];
    }

    /**
     * Opens/reopens a booking manually.
     * @return void
     */
    public function open(): void
    {
        $this->authorize();

        $block     = new Tables\Blocks();
        $booking   = new Tables\Bookings();
        $bookingID = Input::getID();

        if (!$booking->load($bookingID) or !$block->load($booking->blockID)) {
            Application::message('ORGANIZER_412', Application::ERROR);

            return;
        }

        $now  = date('H:i:s');
        $then = date('H:i:s', strtotime('-60 minutes', strtotime($block->startTime)));

        // Reopen before default end
        if ($booking->endTime and $now > $booking->endTime and $now < $block->endTime) {
            $booking->endTime = null;

            if ($booking->store()) {
                Application::message('ORGANIZER_BOOKING_REOPENED');
            }

            return;
        }

        // Early start
        if ($now > $then and (empty($booking->startTime) or $now < $booking->startTime)) {
            $booking->startTime = $now;

            if ($booking->store()) {
                Application::message('ORGANIZER_BOOKING_OPENED');

                return;
            }
        }

        Application::message('ORGANIZER_BOOKING_NOT_OPENED', Application::NOTICE);
    }

    /** @inheritDoc */
    protected function populateState($ordering = null, $direction = null): void
    {
        if (Input::getListItems()->get('username')) {
            $this->addParticipant();
        }

        parent::populateState($ordering, $direction);
    }

    /**
     * Removes the selected participants from the list of registered participants.
     * @return void
     */
    public function removeParticipants(): void
    {
        $this->authorize();

        if (!$participationIDs = Input::getSelectedIDs()) {
            Application::message('ORGANIZER_400', Application::WARNING);

            return;
        }

        foreach ($participationIDs as $participationID) {
            $table = new Tables\InstanceParticipants();

            if (!$table->load($participationID)) {
                Application::message('ORGANIZER_412', Application::NOTICE);

                return;
            }

            $instanceID = $table->instanceID;

            if (!$table->delete()) {
                Application::message('ORGANIZER_PARTICIPANTS_NOT_REMOVED', Application::ERROR);

                return;
            }

            $this->updateIPNumbers($instanceID);
        }

        Application::message('ORGANIZER_PARTICIPANTS_REMOVED');
    }

    /**
     * Re-references entries in the course/instance participants tables for the given participant ids.
     *
     * @param   string  $table     the unique part of the table name (course|instance)
     * @param   int     $toID      the id to use in the reference tables
     * @param   int     $fromID    the id to replace/delete in the reference tables
     * @param   string  $fkColumn  the fk column name away from the instances table (courseID|instanceID)
     *
     * @return void
     */
    private function reReference(string $table, int $toID, int $fromID, string $fkColumn): void
    {
        $buffer    = [];
        $fqClass   = 'THM\\Organizer\\Tables\\' . ucfirst($table) . 'Participants';
        $protected = ['id', 'instanceID', $fkColumn];

        $query = DB::query();
        $query->select('*')->from("#__organizer_{$table}_participants")->where("instanceID IN ($toID, $fromID)");
        DB::set($query);
        $references = DB::arrays();

        // Delete redundant entries buffering necessary values
        foreach ($references as $reference) {
            $index = $reference[$fkColumn];

            if ($entry = $buffer[$index]) {
                foreach (array_keys($entry) as $column) {
                    if (in_array($column, $protected)) {
                        continue;
                    }

                    // If one reference attended, paid, ... it is valid for all
                    if ($reference[$column] > $entry[$column]) {
                        $entry[$column] = $reference[$column];
                    }
                }

                $buffer[$index] = $entry;

                $table = new $fqClass();
                $table->delete($reference['id']);
            }
            else {
                $buffer[$index] = $reference;
            }
        }

        foreach ($buffer as $entry) {
            $table = new $fqClass();
            $table->load($entry['id']);
            $table->save($entry);
        }
    }
}
