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

use THM\Organizer\Adapters\{Application, Input, User};
use THM\Organizer\Helpers\Persons as PeHelper;
use THM\Organizer\Tables\{Participants as PaTable, Persons as PeTable};

/** @inheritDoc */
class Profile extends FormController
{
    /** @inheritDoc */
    protected function authorize(): void
    {
        if (!User::id()) {
            Application::error(401);
        }
    }

    /** @inheritDoc */
    public function process(): int
    {
        $this->checkToken();
        $this->authorize();

        $participant = new PaTable();
        $userID      = User::id();

        if (!$participant->load($userID)) {
            Application::message('ORGANIZER_412', Application::NOTICE);

            return $userID;
        }

        $data = Input::post();

        foreach ($data as $property => $value) {
            if (in_array($property, ['address', 'city', 'forename', 'surname', 'telephone', 'title', 'zipcode'])) {
                $data[$property] = Input::filter($value);
            }

            if ($property == 'programID') {
                $data[$property] = Input::filter($value, 'int');
            }

            if ($property == 'public') {
                $data[$property] = (int) Input::filter($value, 'bool');
            }
        }

        $this->validate($data, ['forename', 'surname']);

        if (!$this->store($participant, $data, $userID)) {
            Application::message('NOT_SAVED', Application::ERROR);
            return $userID;
        }

        $person = new PeTable();
        if ($personID = PeHelper::resolveUser($userID)) {
            if (!$person->load($personID)) {
                Application::message('412', Application::NOTICE);
                return $userID;
            }

            if (!$person->save($data)) {
                Application::message('NOT_SAVED', Application::ERROR);
                return $userID;
            }
        }

        Application::message('SAVED');
        return $userID;
    }

    /** @inheritDoc */
    public function save(): void
    {
        $this->process();
        $this->setRedirect("$this->baseURL&view=profile");
    }
}
