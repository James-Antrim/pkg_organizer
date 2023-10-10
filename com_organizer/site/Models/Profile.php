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

use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

/**
 * Class loads a form for editing building data.
 */
class Profile extends FormModel
{
    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        if (!Helpers\Users::getID()) {
            Application::error(401);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getForm($data = [], $loadData = false)
    {
        $form = parent::getForm($data, $loadData);
        $user = Helpers\Users::getUser();

        if (!Helpers\Participants::exists($user->id)) {
            $model = new Participant();
            $model->supplement($user->id);
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
        } else {
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
        $userID      = Helpers\Users::getID();
        $participant = new Tables\Participants();

        if (!$participant->load($userID)) {
            Helpers\OrganizerHelper::message('ORGANIZER_412', 'notice');

            return;
        }

        $data = Input::getFormItems();
        $participant->bindRegistry($data);

        if (!$participant->store()) {
            Helpers\OrganizerHelper::message('ORGANIZER_PROFILE_NOT_SAVED', 'error');

            return;
        }

        $person = new Tables\Persons();

        if ($personID = Helpers\Persons::getIDByUserID($userID)) {
            if (!$person->load($personID)) {
                Helpers\OrganizerHelper::message('ORGANIZER_412', 'notice');

                return;
            }

            $person->bindRegistry($data);

            if (!$person->store()) {
                Helpers\OrganizerHelper::message('ORGANIZER_PROFILE_NOT_SAVED', 'error');

                return;
            }
        }

        Helpers\OrganizerHelper::message('ORGANIZER_PROFILE_SAVED', 'success');
    }
}
