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

use Joomla\CMS\Table\Table as CoreTable;
use THM\Organizer\Adapters\{Application, Input, User};
use THM\Organizer\Helpers\{Can, Participants};
use THM\Organizer\Tables\Table;

/** @inheritDoc */
class Participant extends FormController
{
    /** @inheritDoc */
    protected function authorize(): void
    {
        if (!$id = Input::id()) {
            Application::error(400);
        }
        elseif (!Can::edit('participant', $id)) {
            Application::error(403);
        }
    }

    /** @inheritDoc */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        // Cannot require users other than the actual participant to know all the participant's data points.
        $required = ((int) $data['id'] === User::id()) ?
            ['address', 'city', 'forename', 'id', 'programID', 'surname', 'zipCode'] : [];
        $this->validate($data, $required);

        $data['address']   = Participants::cleanAlphaNum($data['address']);
        $data['city']      = Participants::cleanAlpha($data['city']);
        $data['forename']  = Participants::cleanAlpha($data['forename']);
        $data['surname']   = Participants::cleanAlpha($data['surname']);
        $data['telephone'] = empty($data['telephone']) ? '' : Participants::cleanAlphaNum($data['telephone']);
        $data['zipCode']   = Participants::cleanAlphaNum($data['zipCode']);

        return $data;
    }

    /** @inheritDoc */
    protected function store(CoreTable $table, array $data, int $id = 0): int
    {
        // The primary key is also a foreign key to users, so there may not be a table entry for a non-zero id.
        $table->load($id);

        if ($table->save($data)) {
            /** @var Table $table */
            return $table->id;
        }

        return $id;
    }
}
