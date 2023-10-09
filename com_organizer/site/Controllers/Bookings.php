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
use Organizer\Controller;
use Organizer\Helpers;
use Organizer\Models;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Bookings extends Controller
{
    protected $listView = 'bookings';

    protected $resource = 'booking';

    /**
     * Class constructor
     *
     * @param array $config An optional associative [] of configuration settings.
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
    public function add()
    {
        $model = new Models\Booking();

        if (!$bookingID = $model->add()) {
            $this->setRedirect(Helpers\Input::getString('referrer'));

            return;
        }

        $url = Helpers\Routing::getRedirectBase() . "&view=booking&id=$bookingID";
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Supplements the resource.
     * @return void
     */
    public function addParticipant()
    {
        $model = new Models\Booking();
        $model->addParticipant();
        $url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Helpers\Input::getID();
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Makes call to the models' batch function, and redirects to the view.
     * @return void
     * @throws Exception
     */
    public function batch()
    {
        $model = new Models\Booking();

        if ($model->batch()) {
            Helpers\OrganizerHelper::message('ORGANIZER_UPDATE_SUCCESS', 'success');
        } else {
            Helpers\OrganizerHelper::message('ORGANIZER_UPDATE_FAIL', 'error');
        }

        $referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');
        $this->setRedirect(Route::_($referrer, false));
    }

    /**
     * Checks the selected participants into the booking.
     * @return void
     */
    public function checkin()
    {
        $model = new Models\Booking();
        $model->checkin();
        $url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Helpers\Input::getID();
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Closes a booking manually.
     * @return void
     */
    public function close()
    {
        $model = new Models\Booking();
        $model->close();
        $url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Helpers\Input::getID();
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Redirects to the edit view with an item id. Access checks performed in the view.
     * @return void
     * @throws Exception
     */
    public function editParticipants()
    {
        $bookingID       = Helpers\Input::getID();
        $participationID = Helpers\Input::getSelectedID();
        Helpers\Input::set('id', $participationID);
        Helpers\Input::set('bookingID', $bookingID);
        Helpers\Input::set('view', 'instance_participant_edit');
        $this->display();
    }

    /**
     * Provides a singular point of entry for creation and management of a booking.
     * @return void
     */
    public function manage(int $instanceID = 0)
    {
        if (!$instanceID) {
            if (!$instanceIDs = Helpers\Input::getSelectedIDs()) {
                Helpers\OrganizerHelper::error(400);
            }

            $instanceID = array_shift($instanceIDs);
        }

        if (!$bookingID = Helpers\Instances::getBookingID($instanceID)) {
            $model = new Models\Booking();

            if (!$bookingID = $model->add()) {
                $this->setRedirect(Helpers\Input::getString('referrer'));

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
    public function manageThis()
    {
        if (!$instanceID = Helpers\Input::getID()) {
            Helpers\OrganizerHelper::error(400);
        }

        $this->manage($instanceID);
    }

    /**
     * Opens/reopens a booking manually.
     * @return void
     */
    public function open()
    {
        $model = new Models\Booking();
        $model->open();
        $url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Helpers\Input::getID();
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Removes the selected participants from the list of registered participants.
     * @return void
     */
    public function removeParticipants()
    {
        $model = new Models\Booking();
        $model->removeParticipants();
        $url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Helpers\Input::getID();
        $this->setRedirect(Route::_($url, false));
    }
}
