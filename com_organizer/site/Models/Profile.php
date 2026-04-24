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

use THM\Organizer\Adapters\{Form, User};
use THM\Organizer\Controllers\Participant as Controller;
use THM\Organizer\Helpers\{Participants as PaHelper, Persons as PeHelper};
use THM\Organizer\Tables\{Participants as PaTable, Persons as PeTable};

/**
 * Class loads a form for editing building data.
 */
class Profile extends FormModel
{
    /** @inheritDoc */
    public function getForm($data = [], $loadData = false): Form
    {
        /** @var Form $form */
        $form = parent::getForm($data, $loadData);
        $user = User::instance();

        if (!PaHelper::exists($user->id)) {
            Controller::supplement($user->id);
        }

        $participant = new PaTable();
        $participant->load($user->id);
        $form->bind($participant);
        $form->setValue('participantID', null, $participant->id);
        $person = new PeTable();

        if ($personID = PeHelper::resolveUser($user->id) and $person->load($personID)) {
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
}
