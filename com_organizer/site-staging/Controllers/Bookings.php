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
use Joomla\CMS\Router\Route;
use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Bookings as Helper;
use THM\Organizer\Models;

/** @inheritDoc */
class Bookings extends Controller
{
    protected string $listView = 'bookings';

    protected string $resource = 'booking';

    /**
     * Class constructor
     *
     * @param   array  $config  An optional associative [] of configuration settings.
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->registerTask('add', 'add');
    }

    /**
     * Creates a new booking element for a given instance and redirects to the corresponding instance participants view.
     * @return void
     */
    public function add(): void
    {
        $model = new Models\Booking();

        if (!$bookingID = $model->add()) {
            $this->setRedirect(Input::getString('referrer'));

            return;
        }

        $url = Helpers\Routing::getRedirectBase() . "&view=booking&id=$bookingID";
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Supplements the resource.
     * @return void
     */
    public function addParticipant(): void
    {
        $model = new Models\Booking();
        $model->addParticipant();
        $url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Input::getID();
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Makes call to the models' batch function, and redirects to the view.
     * @return void
     * @throws Exception
     */
    public function batch(): void
    {
        $model = new Models\Booking();

        if ($model->batch()) {
            Application::message('ORGANIZER_UPDATE_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_UPDATE_FAIL', Application::ERROR);
        }

        $referrer = Input::getInput()->server->getString('HTTP_REFERER');
        $this->setRedirect(Route::_($referrer, false));
    }

    /**
     * Checks the selected participants into the booking.
     * @return void
     */
    public function checkin(): void
    {
        $model = new Models\Booking();
        $model->checkin();
        $url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Input::getID();
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Cleans bookings according to their current status derived by the state of associated instances, optionally cleans
     * unattended past bookings.
     *
     * @param   bool  $removeUnattended  whether unattended bookings in the past should be removed as well
     *
     * @return void
     */
    public function clean(bool $removeUnattended = false): void
    {
        // Earlier bookings will have already been cleaned.
        $earliest = date('Y-m-d', strtotime('-14 days'));
        $earliest = DB::qc('bl.date', $earliest, '>', true);

        $select = DB::query();
        $select->select('DISTINCT ' . DB::qn('bk.id'))
            ->from(DB::qn('#__organizer_bookings', 'bk'))
            ->innerJoin(DB::qn('#__organizer_blocks', 'bl'), DB::qc('bl.id', 'bk.blockID'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qcs([['i.blockID', 'bk.blockID'], ['i.unitID', 'bk.unitID']]));

        // Bookings associated with non-deprecated appointments.
        $select->where($earliest)->where(DB::qc('i.delta', 'removed', '!=', true));
        DB::set($select);
        $currentIDs = DB::integers();

        // Bookings associated with deprecated appointments.
        $select->clear('where');
        $select->where($earliest)->where(DB::qc('i.delta', 'removed', '=', true));
        DB::set($select);
        $removedIDs = DB::integers();

        // Because the instance join is on the unitID, not the instanceID, there can be overlap.
        if ($deprecatedIDs = array_diff($removedIDs, $currentIDs)) {
            $delete = DB::query();
            $delete->delete(DB::qn('#__organizer_bookings'))->whereIn(DB::qn('id'), $deprecatedIDs);
            DB::set($delete);
            DB::execute();
        }

        if (!$removeUnattended) {
            return;
        }

        // Unattended past bookings. The inner join to instance participants allows archived bookings to not be selected here.
        $select->innerJoin(DB::qn('#__organizer_instance_participants', 'ip'), DB::qc('ip.instanceID', 'i.id'));
        $past = DB::qc('bl.date', date('Y-m-d'), '<', true);

        // Attended bookings.
        $select->clear('where');
        $select->where($past)->where(DB::qc('ip.attended', Helper::ATTENDED));
        DB::set($select);
        $attendedIDs = DB::integers();

        // Unattended bookings.
        $select->clear('where');
        $select->where($past)->where(DB::qc('ip.attended', Helper::REGISTERED));
        DB::set($select);
        $registeredIDs = DB::integers();

        if ($unattendedIDs = array_diff($registeredIDs, $attendedIDs)) {
            $delete = DB::query();
            $delete->delete(DB::qn('#__organizer_bookings'))->whereIn(DB::qn('id'), $unattendedIDs);
            DB::set($delete);
            DB::execute();
        }
    }

    /**
     * Closes a booking manually.
     * @return void
     */
    public function close(): void
    {
        $model = new Models\Booking();
        $model->close();
        $url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Input::getID();
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Redirects to the edit view with an item id. Access checks performed in the view.
     * @return void
     * @throws Exception
     */
    public function editParticipants(): void
    {
        $bookingID       = Input::getID();
        $participationID = Input::getSelectedID();
        Input::set('id', $participationID);
        Input::set('bookingID', $bookingID);
        Input::set('view', 'instance_participant_edit');
        $this->display();
    }

    /**
     * Provides a singular point of entry for creation and management of a booking.
     *
     * @param   int  $instanceID  the id of an instance associated with a booking
     *
     * @return void
     */
    public function manage(int $instanceID = 0): void
    {
        if (!$instanceID) {
            if (!$instanceIDs = Input::getSelectedIDs()) {
                Application::error(400);
            }

            $instanceID = array_shift($instanceIDs);
        }

        if (!$bookingID = Helpers\Instances::bookingID($instanceID)) {
            $model = new Models\Booking();

            if (!$bookingID = $model->add()) {
                $this->setRedirect(Input::getString('referrer'));

                return;
            }
        }

        $url = Helpers\Routing::getRedirectBase() . "&view=booking&id=$bookingID";
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Provides a directed point of entry for creation and management of a booking from an instance.
     * @return void
     */
    public function manageThis(): void
    {
        if (!$instanceID = Input::getID()) {
            Application::error(400);
        }

        $this->manage($instanceID);
    }

    /**
     * Opens/reopens a booking manually.
     * @return void
     */
    public function open(): void
    {
        $model = new Models\Booking();
        $model->open();
        $url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Input::getID();
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Removes the selected participants from the list of registered participants.
     * @return void
     */
    public function removeParticipants(): void
    {
        $model = new Models\Booking();
        $model->removeParticipants();
        $url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Input::getID();
        $this->setRedirect(Route::_($url, false));
    }
}
