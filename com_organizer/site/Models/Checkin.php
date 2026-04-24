<?php
/**
 * @package     Organizer\Models
 * @extension   Organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use JDatabaseQuery;
use Joomla\CMS\{Application\CMSApplication, Uri\Uri};
use THM\Organizer\Adapters\{Application, Database, FormFactory, Input, MVCFactory, User};
use THM\Organizer\Helpers;
use THM\Organizer\Tables\Participants as Table;

class Checkin extends FormModel
{
    private array $instances;

    private Table $participant;

    private int|null $roomID = null;

    private string|null $seat = null;

    /** @inheritDoc */
    public function __construct($config, MVCFactory $factory, FormFactory $formFactory)
    {
        parent::__construct($config, $factory, $formFactory);

        // Force component template
        if (Input::cmd('tmpl') !== 'component') {
            /** @var CMSApplication $app */
            $app   = Application::instance();
            $query = Input::instance()->server->get('QUERY_STRING', '', 'raw') . '&tmpl=component';
            $app->redirect(Uri::current() . "?$query");
        }

        $form    = $this->getForm();
        $session = Application::session();

        if ($username = $session->get('organizer.checkin.username')) {
            $form->setValue('username', null, $username);
        }

        if ($code = $session->get('organizer.checkin.code') or $code = Input::cmd('code')) {
            $form->setValue('code', null, $code);
        }

        $participant = new Table();

        if ($participantID = User::id() and $participant->load($participantID)) {
            $form->bind($participant);
        }

        $this->participant = $participant;

        $this->setInstances();
    }

    /**
     * Loads participant data for the current user. Used implicitly in view: $this->get('Participant').
     * @return Table
     */
    public function getParticipant(): Table
    {
        return $this->participant;
    }

    /**
     * Gets the instances relevant to the booking and person.
     * @return array[]
     */
    public function getInstances(): array
    {
        return $this->instances;
    }

    /**
     * Gets a query where common statements are already included.
     *
     * @param int $participantID the id of the participant for which to find checkins
     *
     * @return JDatabaseQuery
     */
    private function getQuery(int $participantID): JDatabaseQuery
    {
        $today = date('Y-m-d');
        $query = Database::query();
        $query->select('instanceID, roomID, seat')
            ->from('#__organizer_instance_participants AS ip')
            ->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
            ->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
            ->where("ip.participantID = $participantID")
            ->where("ip.attended = 1")
            ->where("b.date = '$today'");

        return $query;
    }

    /**
     * Gets the instances relevant to the booking and person.
     * @return int|null
     */
    public function getRoomID(): ?int
    {
        return $this->roomID;
    }

    /**
     * Gets the instances relevant to the booking and person.
     * @return null|string
     */
    public function getSeat(): ?string
    {
        return $this->seat;
    }

    /**
     * Gets the instance related information.
     * @return void
     */
    public function setInstances(): void
    {
        $instances = [];
        $roomID    = null;
        $seat      = null;

        if (!$participantID = $this->participant->id) {
            $this->instances = $instances;

            return;
        }

        $now = date('H:i:s');

        // Ongoing
        $query = $this->getQuery($participantID);
        $query->where("b.startTime <= '$now'")->where("b.endTime >= '$now'");
        Database::set($query);

        if (!$participation = Database::arrays()) {
            // Upcoming
            $then  = date('H:i:s', strtotime('+60 minutes'));
            $query = $this->getQuery($participantID);
            $query->where("b.startTime >= '$now'")->where("b.startTime <= '$then'");
            Database::set($query);

            $participation = Database::arrays();
        }

        $form = $this->getForm();

        foreach ($participation as $index => $entry) {
            $instanceID                = $entry['instanceID'];
            $instances[$index]         = Helpers\Instances::instance($instanceID);
            $instances[$index]['seat'] = $entry['seat'];
            $form->setValue('instanceID', null, $instanceID);

            if (empty($roomID)) {
                $roomID         = $entry['roomID'];
                $roomIDs        = Helpers\Instances::getRoomIDs($instanceID);
                $selectedRoomID = count($roomIDs) === 1 ? reset($roomIDs) : $entry['roomID'];
                $form->setValue('roomID', null, $selectedRoomID);
            }

            if ($seat === null and $entry['seat'] !== null) {
                $seat = $entry['seat'];
                $form->setValue('seat', null, $seat);
            }

            if ($roomID) {
                $instances[$index]['room'] = Helpers\Rooms::name($roomID);
            }

        }

        $this->instances = $instances;
        $this->roomID    = $roomID;
        $this->seat      = $seat;
    }
}