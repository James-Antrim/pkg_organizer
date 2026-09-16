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

use THM\Organizer\Adapters\{Application, Database, Input, Text, User};
use THM\Organizer\Controllers\{Booked, Participant};
use THM\Organizer\Helpers\{Can, Instances as iHelper, Participation as Helper};
use THM\Organizer\Tables\{Instances as iTable, InstanceParticipants as Table};

/**
 * Class which manages stored course data.
 */
class InstanceParticipant extends BaseModel
{
    use Booked;

    /**
     * Authorizes users responsible for bookings to edit individual participation.
     * @return void
     */
    protected function authorize(): void
    {
        $bookingID = 0;

        if (!$participationID = Input::id() or !$bookingID = Helper::bookingID($participationID)) {
            Application::error(400);
        }

        if (!Can::manage('booking', $bookingID)) {
            Application::error(403);
        }
    }

    /**
     * Checks the user into instances.
     * @return bool true on success, otherwise false
     */
    public function checkin(): bool
    {
        if (!$participantID = User::id()) {
            Application::message('ORGANIZER_401', Application::ERROR);

            return false;
        }

        Participant::supplement($participantID);

        $data = Input::post();
        if (!$code = $data['code'] or !preg_match('/^[a-f0-9]{4}-[a-f0-9]{4}$/', $code)) {
            Application::message('ORGANIZER_UNIT_CODE_INVALID', Application::ERROR);

            return false;
        }

        $now   = date('H:i:s');
        $query = Database::query();
        $then  = date('H:i:s', strtotime('+60 minutes'));
        $today = date('Y-m-d');
        $query->select('i.id')
            ->from('#__organizer_instances AS i')
            ->innerJoin('#__organizer_bookings AS bk ON bk.blockID = i.blockID AND bk.unitID = i.unitID')
            ->where("bk.code = '$code'")
            ->innerJoin('#__organizer_blocks AS bl ON bl.id = i.blockID')
            ->where("bl.date = '$today'")
            ->where("((bk.startTime IS NOT NULL and bk.startTime < '$then') or bl.startTime < '$then')")
            ->where("bl.endTime > '$now'");
        Database::set($query);

        if (!$instanceIDs = Database::integers()) {
            Application::message('ORGANIZER_UNIT_CODE_INVALID', Application::ERROR);

            return false;
        }

        // Filter for bookmarked/registered
        $query = Database::query();
        $query->select('instanceID')
            ->from('#__organizer_instance_participants')
            ->where("instanceID IN (" . implode(',', $instanceIDs) . ")")
            ->where("participantID = $participantID");
        Database::set($query);

        if ($plannedIDs = Database::integers()) {
            $instanceIDs = array_intersect($plannedIDs, $instanceIDs);
        }

        foreach ($instanceIDs as $instanceID) {
            $data = ['instanceID' => $instanceID, 'participantID' => $participantID];

            $participation = new Table();
            $participation->load($data);
            $data['attended'] = 1;

            if (!$participation->save($data)) {
                Application::message(Text::_('ORGANIZER_CHECKIN_FAILED'));

                return false;
            }

            iHelper::updateNumbers($instanceID);
        }

        Application::message(Text::_('ORGANIZER_CHECKIN_SUCCEEDED'));

        return true;
    }

    /**
     * Resolves participant instance ambiguity.
     * @return void
     */
    public function confirmInstance(): void
    {
        if (!$participantID = User::id()) {
            Application::message('ORGANIZER_401', Application::ERROR);

            return;
        }

        if (!$instanceID = Input::id()) {
            Application::message('ORGANIZER_400', Application::ERROR);

            return;
        }

        $instance = new iTable();
        if (!$instance->load($instanceID)) {
            Application::message('ORGANIZER_412', Application::ERROR);

            return;
        }

        // Get all other instances relevant to the booking
        $query = Database::query();
        $query->select('id')
            ->from('#__organizer_instances')
            ->where("unitID = $instance->unitID")
            ->where("blockID = $instance->blockID")
            ->where("id != $instanceID");
        Database::set($query);

        foreach (Database::integers() as $instanceID) {
            $participation = new Table();

            if ($participation->load(['instanceID' => $instanceID, 'participantID' => $participantID])) {
                $participation->delete();
                Application::message('ORGANIZER_EVENT_CONFIRMED');
                iHelper::updateNumbers($instanceID);
            }
            else {
                Application::message('ORGANIZER_412', Application::ERROR);
            }
        }
    }

    /**
     * Confirms the participant's room and seat.
     * @return void
     */
    public function confirmSeating(): void
    {
        if (!$participantID = User::id()) {
            Application::message('ORGANIZER_401', Application::ERROR);

            return;
        }

        if (!$instanceID = Input::integer('instanceID') or !$roomID = Input::integer('roomID')) {
            Application::message('ORGANIZER_400', Application::ERROR);

            return;
        }

        $table = new Table();

        if (!$table->load(['instanceID' => $instanceID, 'participantID' => $participantID])) {
            Application::message('ORGANIZER_412', Application::ERROR);

            return;
        }

        $table->roomID = $roomID;
        $table->seat   = Input::string('seat');

        $table->store();
    }


    /**
     * Method to get a table object, load it if necessary.
     *
     * @param string $name    The table name. Optional.
     * @param string $prefix  The class prefix. Optional.
     * @param array  $options Configuration array for model. Optional.
     *
     * @return  Table  An instance participants table object
     */
    public function getTable($name = '', $prefix = '', $options = []): Table
    {
        return new Table();
    }

    /**
     * Sends a circular mail to all course participants.
     * @return bool true on success, false on error
     */
    public function notify(): bool
    {
        return false;
        /*if (!$instanceID = Input::getID())
        {
            return false;
        }

        if (!Organizations::manageableIDs() and !Instances::teaches($instanceID))
        {
            Application::error(403);
        }

        $participants = Instances::getParticipantIDs($instanceID);
        $selected     = Input::getIntCollection('cid');

        if (empty($participants) and empty($selected))
        {
            return false;
        }

        $participantIDs = $selected ?: $participants;

        $form = Input::getBatchItems();
        if (!$subject = trim($form->get('subject', '')) or !$body = trim($form->get('body', '')))
        {
            return false;
        }

        foreach ($participantIDs as $participantID)
        {
            Helpers\Mailer::notifyParticipant($participantID, $subject, $body);
        }

        return true;*/
    }

    /**
     * Attempts to save the resource.
     *
     * @param array $data the data from the form
     *
     * @return int
     */
    public function save(array $data = []): int
    {
        $this->authorize();

        $data = empty($data) ? Input::post() : $data;

        $table = new Table();
        if (!$table->load($data['id'])) {
            return false;
        }

        $table->instanceID = $data['instanceID'];
        $table->roomID     = $data['roomID'];
        $table->seat       = $data['seat'];

        $query = Database::query();
        $query->select('ip.*')
            ->from('#__organizer_instance_participants AS ip')
            ->innerJoin('#__organizer_instances AS i1 ON i1.id = ip.instanceID')
            ->innerJoin('#__organizer_bookings AS b ON b.blockID = i1.blockID AND b.unitID = i1.unitID')
            ->innerJoin('#__organizer_instances AS i2 ON i2.blockID = b.blockID AND i2.unitID = b.unitID')
            ->where("i2.id = $table->instanceID")
            ->where("ip.participantID = $table->participantID");
        Database::set($query);

        $instanceIDs = [];
        foreach (Database::arrays() as $entry) {
            $instanceIDs[$entry['instanceID']] = $entry['instanceID'];
            $table->registered                 = $table->registered ?: !empty($entry['registered']);

            if ($entry['id'] !== $table->id) {
                $otherTable = new Table();

                if (!$otherTable->load($entry['id'])) {
                    continue;
                }

                $otherTable->delete();
            }
        }

        $table->store();

        foreach ($instanceIDs as $instanceID) {
            iHelper::updateNumbers($instanceID);
        }

        return $table->id;
    }
}