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
use Joomla\CMS\Factory;
use THM\Organizer\Adapters\{Application, Input, User};
use THM\Organizer\Helpers;
use THM\Organizer\Models;

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
        $data    = Input::getFormItems();
        $session = Factory::getSession();

        if (!User::id()) {
            /** @var CMSApplication $app */
            $app = Application::getApplication();
            $app->login(['username' => $data['username'], 'password' => $data['password']]);
            $session->set('organizer.checkin.username', $data['username']);
        }

        if (User::id()) {
            $model = new Models\InstanceParticipant();

            // Code was invalid, no reason to keep it.
            $code = $model->checkin() ? $data['code'] : '';
            $session->set('organizer.checkin.code', $code);
        }
        else {
            $session->set('organizer.checkin.code', $data['code']);
        }

        $url = Helpers\Routing::getRedirectBase() . "&view=checkin";
        $this->setRedirect($url);
    }

    /**
     * Resolves participant instance ambiguity.
     * @return void
     */
    public function confirmInstance(): void
    {
        if (User::id()) {
            $model = new Models\InstanceParticipant();
            $model->confirmInstance();
        }

        $url = Helpers\Routing::getRedirectBase() . "&view=checkin";
        $this->setRedirect($url);
    }

    /**
     * Confirms the participant's room and seat.
     * @return void
     */
    public function confirmSeating(): void
    {
        if (User::id()) {
            $model = new Models\InstanceParticipant();
            $model->confirmSeating();
        }

        $url = Helpers\Routing::getRedirectBase() . "&view=checkin";
        $this->setRedirect($url);
    }

    /**
     * Saves the participants contact data.
     * @return void
     * @see Participant::save(), Participant::prepareData()
     */
    public function contact(): void
    {
        //$numeric  = ['id', 'programID'];
        //$nullable = ['programID'];
        //$required = ['address', 'city', 'forename', 'id', 'surname', 'telephone', 'zipCode'];
        if (User::id()) {
            $model = new Models\Participant();
            $model->save();
        }

        $url = Helpers\Routing::getRedirectBase() . "&view=checkin";
        $this->setRedirect($url);
    }
}
