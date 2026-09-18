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

use Exception;
use Joomla\CMS\Application\CMSApplication;
use THM\Organizer\Adapters\{Application, Database as DB, Input, User};
use THM\Organizer\Helpers\{Instances, Participants, Routing};
use THM\Organizer\Tables\{Instances as iTable, InstanceParticipants as Table};

/** @inheritDoc */
class Checkin extends Controller
{
    /**
     * Checks the user into a booking.
     * @return void
     * @throws Exception
     */
    public function checkin(): void
    {
        $data    = Input::post();
        $session = Application::session();
        $url     = Routing::getRedirectBase() . "&view=checkin";
        $this->setRedirect($url);

        if (!User::id()) {
            /** @var CMSApplication $app */
            $app = Application::instance();
            $app->login(['username' => $data['username'], 'password' => $data['password']]);
            $session->set('organizer.checkin.username', $data['username']);
        }

        if (!$participantID = User::id()) {
            Application::message('ORGANIZER_401', Application::ERROR);
            $session->set('organizer.checkin.code', $data['code']);

            return;
        }

        Participants::supplement($participantID);

        $data = Input::post();
        if (!$code = $data['code'] or !preg_match('/^[a-f0-9]{4}-[a-f0-9]{4}$/', $code)) {
            Application::message('UNIT_CODE_INVALID', Application::ERROR);
            $session->set('organizer.checkin.code', '');

            return;
        }

        $then            = date('H:i:s', strtotime('+60 minutes'));
        $bookingOpen     = DB::qn('bk.startTime') . ' IS NOT NULL';
        $bookingStarting = DB::qc('bk.startTime', $then, '<', true);
        $blockStarting   = DB::qc('bl.startTime', $then, '<', true);

        $query = DB::query();
        $query->select(DB::qn('i.id'))
            ->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_bookings', 'bk'), DB::qcs([['bk.blockID', 'i.blockID'], ['bk.unitID', 'i.unitID']]))
            ->innerJoin(DB::qn('#__organizer_blocks', 'bl'), DB::qc('bl.id', 'i.blockID'))
            ->where(DB::qcs([
                ['bk.code', $code, '=', true],
                ['bl.date', date('Y-m-d'), '=', true],
                ['bl.endTime', date('H:i:s'), '>', true]
            ]))
            ->where("(($bookingOpen and $bookingStarting) or $blockStarting)");
        DB::set($query);

        if (!$instanceIDs = DB::integers()) {
            Application::message('UNIT_CODE_INVALID', Application::ERROR);
            $session->set('organizer.checkin.code', '');

            return;
        }

        // Filter for bookmarked/registered
        $query = DB::query();
        $query->select(DB::qn('instanceID'))
            ->from(DB::qn('#__organizer_instance_participants'))
            ->where(DB::qc('participantID', $participantID))
            ->whereIn(DB::qn('instanceID'), $instanceIDs);
        DB::set($query);

        if ($plannedIDs = DB::integers()) {
            $instanceIDs = array_intersect($plannedIDs, $instanceIDs);
        }

        foreach ($instanceIDs as $instanceID) {
            $data = ['instanceID' => $instanceID, 'participantID' => $participantID];

            $participation = new Table();
            $participation->load($data);
            $data['attended'] = 1;

            if (!$participation->save($data)) {
                Application::message('CHECKIN_FAILED');

                return;
            }

            Instances::updateNumbers($instanceID);
        }

        Application::message('CHECKIN_SUCCEEDED');
        $session->set('organizer.checkin.code', $data['code']);
    }

    /**
     * Resolves participant instance ambiguity.
     * @return void
     */
    public function confirmInstance(): void
    {
        $url = Routing::getRedirectBase() . "&view=checkin";
        $this->setRedirect($url);

        if (!$participantID = User::id()) {
            Application::message('401', Application::ERROR);

            return;
        }

        if (!$instanceID = Input::id()) {
            Application::message('400', Application::ERROR);

            return;
        }

        $instance = new iTable();
        if (!$instance->load($instanceID)) {
            Application::message('412', Application::ERROR);

            return;
        }

        // Get all other instances relevant to the booking
        $query = DB::query();
        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_instances'))
            ->where(DB::qcs([['unitID', $instance->unitID], ['blockID', $instance->blockID], ['id', $instanceID, '!=']]));
        DB::set($query);

        foreach (DB::integers() as $instanceID) {
            $participation = new Table();

            if ($participation->load(['instanceID' => $instanceID, 'participantID' => $participantID])) {
                $participation->delete();
                Application::message('EVENT_CONFIRMED');
                Instances::updateNumbers($instanceID);
            }
            else {
                Application::message('412', Application::ERROR);
            }
        }
    }

    /**
     * Confirms the participant's room and seat.
     * @return void
     */
    public function confirmSeating(): void
    {
        $url = Routing::getRedirectBase() . "&view=checkin";
        $this->setRedirect($url);

        if (User::id()) {
            if (!$participantID = User::id()) {
                Application::message('401', Application::ERROR);

                return;
            }

            if (!$instanceID = Input::integer('instanceID') or !$roomID = Input::integer('roomID')) {
                Application::message('400', Application::ERROR);

                return;
            }

            $table = new Table();

            if (!$table->load(['instanceID' => $instanceID, 'participantID' => $participantID])) {
                Application::message('412', Application::ERROR);

                return;
            }

            $table->roomID = $roomID;
            $table->seat   = Input::string('seat');

            $table->store();
        }
    }

    /**
     * Saves the participants contact data.
     * @return void
     * @see Participant::process(), Participant::prepareData()
     */
    public function contact(): void
    {
        if (User::id()) {
            $controller = new Participant();
            $controller->process();
        }

        $url = Routing::getRedirectBase() . "&view=checkin";
        $this->setRedirect($url);
    }
}
