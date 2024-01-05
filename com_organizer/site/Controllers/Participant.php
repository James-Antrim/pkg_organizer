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

use Joomla\CMS\Table\Table as JTable;
use THM\Organizer\Adapters\{Application, Database as DB, Input, User};
use THM\Organizer\Helpers\{Can, Participants};
use Joomla\Database\ParameterType;
use THM\Organizer\Tables\Table;

/**
 * @inheritDoc
 */
class Participant extends FormController
{
    protected string $list = 'Participants';

    /**
     * @inheritDoc
     */
    protected function authorize(): void
    {
        if (!$id = Input::getID()) {
            Application::error(400);
        }
        elseif (!Can::edit('participant', $id)) {
            Application::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        // Cannot require users other than the actual participant to know all the participant's data points.
        $required = ((int) $data['id'] === User::id()) ?
            ['address', 'city', 'forename', 'id', 'programID', 'surname', 'zipCode'] : [];
        $this->validate($data, $required);

        $data['address']   = self::cleanAlphaNum($data['address']);
        $data['city']      = self::cleanAlpha($data['city']);
        $data['forename']  = self::cleanAlpha($data['forename']);
        $data['surname']   = self::cleanAlpha($data['surname']);
        $data['telephone'] = empty($data['telephone']) ? '' : self::cleanAlphaNum($data['telephone']);
        $data['zipCode']   = self::cleanAlphaNum($data['zipCode']);

        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function store(JTable $table, array $data, int $id = 0): int
    {
        // The primary key is also a foreign key to users, so there may not be a table entry for a non-zero id.
        $table->load($id);

        if ($table->save($data)) {
            /** @var Table $table */
            return $table->id;
        }

        return $id;
    }

    /**
     * Adds an organizer participant based on the information in the users table.
     *
     * @param   int   $participantID  the id of the participant/user entries
     * @param   bool  $force          forces update of the columns derived from information in the user table
     *
     * @return void
     */
    public static function supplement(int $participantID, bool $force = false): void
    {
        if ($exists = Participants::exists($participantID) and !$force) {
            return;
        }

        $forename = DB::qn('forename');
        $id       = DB::qn('id');
        $names    = self::parseNames($participantID);
        $query    = DB::getQuery();
        $surname  = DB::qn('surname');
        $table    = DB::qn('#__organizer_participants');

        if (!$exists) {
            $query->insert($table)->columns([$id, $forename, $surname])->values(':id, :forename, :surname');
        }
        else {
            $query->update($table)->set("$forename = :forename")->set("$surname = :surname")->where("$id = :id");
        }

        $query->bind(':forename', $names['forename'])
            ->bind(':id', $participantID, ParameterType::INTEGER)
            ->bind(':surname', $names['surname']);

        DB::setQuery($query);
        DB::execute();
    }

    /**
     * Resolves a username attribute into forename and surname attributes.
     *
     * @param   int  $userID  the id of the user whose full name should be resolved
     *
     * @return string[] the first and last names of the user
     */
    private static function parseNames(int $userID = 0): array
    {
        $user = User::instance($userID);

        $sanitized  = self::trim(self::cleanAlpha($user->name));
        $fragments  = array_filter(explode(' ', $sanitized));
        $surname    = array_pop($fragments);
        $supplement = '';

        // The next element is a supplementary preposition.
        while (preg_match('/^[a-zß-ÿ]+$/', end($fragments))) {
            $supplement = array_pop($fragments);
            $surname    = "$supplement $surname";
        }

        // These supplements indicate the existence of a further surname fragment.
        if (in_array($supplement, ['zu', 'zum'])) {
            $add     = array_pop($fragments);
            $surname = "$add $surname";

            while (preg_match('/^[a-zß-ÿ]+$/', end($fragments))) {
                $supplement = array_pop($fragments);
                $surname    = "$supplement $surname";
            }
        }

        // Everything left is likely a forename
        return ['forename' => implode(" ", $fragments), 'surname' => $surname];
    }
}
