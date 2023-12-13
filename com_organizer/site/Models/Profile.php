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

use THM\Organizer\Adapters\{Application, Form, Input, User};
use THM\Organizer\Controllers\Participant;
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

/**
 * Class loads a form for editing building data.
 */
class Profile extends OldFormModel
{
    /**
     * @inheritDoc
     */
    protected function authorize(): void
    {
        if (!User::id()) {
            Application::error(401);
        }
    }

    /**
     * @inheritDoc
     */
    public function getForm($data = [], $loadData = false): Form
    {
        $form = parent::getForm($data, $loadData);
        $user = User::instance();

        if (!Helpers\Participants::exists($user->id)) {
            Participant::supplement($user->id);
        }

        $participant = new Tables\Participants();
        $participant->load($user->id);
        $form->bind($participant);
        $form->setValue('participantID', null, $participant->id);
        $person = new Tables\Persons();

        if ($personID = Helpers\Persons::getIDByUserID($user->id) and $person->load($personID)) {
            $form->removeField('programID');
            $form->setValue('public', null, $person->public);
            $form->setValue('title', null, $person->title);
        }
        else {
            $form->removeField('public');
            $form->removeField('title');
        }

        return $form;
    }

    /**
     * Saves personal information to the participants and persons tables.
     * @return void
     */
    public function save()
    {
        $this->authorize();
        $userID      = User::id();
        $participant = new Tables\Participants();

        if (!$participant->load($userID)) {
            Application::message('ORGANIZER_412', Application::NOTICE);

            return;
        }

        $data = Input::getFormItems();

        if (!$participant->save($data)) {
            Application::message('ORGANIZER_PROFILE_NOT_SAVED', Application::ERROR);

            return;
        }

        $person = new Tables\Persons();

        if ($personID = Helpers\Persons::getIDByUserID($userID)) {
            if (!$person->load($personID)) {
                Application::message('ORGANIZER_412', Application::NOTICE);

                return;
            }

            if (!$person->save($data)) {
                Application::message('ORGANIZER_PROFILE_NOT_SAVED', Application::ERROR);

                return;
            }
        }

        Application::message('ORGANIZER_PROFILE_SAVED');
    }
}
