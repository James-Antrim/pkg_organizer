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

use THM\Organizer\Adapters\{Application, Input, User};
use THM\Organizer\Helpers\{Can, Courses, Mailer};
use THM\Organizer\Tables;

/**
 * Class which manages stored course data.
 */
class Course extends BaseModel
{
    private const REGISTERED = 1;

    /**
     * Authorizes the user.
     * @return void
     */
    protected function authorize(): void
    {
        if (!Courses::coordinatable(Input::getID())) {
            Application::error(403);
        }
    }

    /**
     * Deregisters the user from the course.
     * @return bool
     */
    public function deregister(): bool
    {
        if (!$courseID = Input::getID() or !$participantID = User::id()) {
            return false;
        }

        if (!Can::manage('participant', $participantID) and !Courses::coordinatable($courseID)) {
            Application::error(403);
        }

        $dates = Courses::dates($courseID);

        if (empty($dates['endDate']) or $dates['endDate'] < date('Y-m-d')) {
            return false;
        }

        $courseParticipant = new Tables\CourseParticipants();
        $cpData            = ['courseID' => $courseID, 'participantID' => $participantID];

        if (!$courseParticipant->load($cpData) or !$courseParticipant->delete()) {
            return false;
        }

        if ($instanceIDs = Courses::instanceIDs($courseID)) {
            foreach ($instanceIDs as $instanceID) {
                $ipData              = ['instanceID' => $instanceID, 'participantID' => $participantID];
                $instanceParticipant = new Tables\InstanceParticipants();
                if ($instanceParticipant->load($ipData)) {
                    $instanceParticipant->delete();
                }
            }
        }

        Mailer::registrationUpdate($courseID, $participantID, null);

        return true;
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return Tables\Courses A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = []): Tables\Courses
    {
        return new Tables\Courses();
    }

    /**
     * Registers the user for the course.
     * @return bool
     */
    public function register(): bool
    {
        $courseID      = Input::getID();
        $participantID = User::id();
        $cpData        = ['courseID' => $courseID, 'participantID' => $participantID];

        $courseParticipant = new Tables\CourseParticipants();
        if (!$courseParticipant->load($cpData)) {
            $cpData['participantDate'] = date('Y-m-d H:i:s');
            $cpData['status']          = self::REGISTERED;
            $cpData['statusDate']      = date('Y-m-d H:i:s');
            $cpData['attended']        = 0;
            $cpData['paid']            = 0;

            if (!$courseParticipant->save($cpData)) {
                return false;
            }
        }

        if ($courseParticipant->status === self::REGISTERED) {
            if ($instanceIDs = Courses::instanceIDs($courseID)) {
                foreach ($instanceIDs as $instanceID) {
                    $ipData              = ['instanceID' => $instanceID, 'participantID' => $participantID];
                    $instanceParticipant = new Tables\InstanceParticipants();
                    if (!$instanceParticipant->load($ipData)) {
                        $instanceParticipant->save($ipData);
                    }
                }
            }
        }

        Mailer::registrationUpdate($courseID, $participantID, $courseParticipant->status);

        return true;
    }

    /**
     * Attempts to save the resource.
     *
     * @param   array  $data  the data from the form
     *
     * @return int
     */
    public function save(array $data = []): int
    {
        $this->authorize();

        $data  = empty($data) ? Input::getFormItems() : $data;
        $table = $this->getTable();

        if (empty($data['id'])) {
            return $table->save($data) ? $table->id : 0;
        }

        if (!$table->load($data['id'])) {
            return 0;
        }

        foreach ($data as $column => $value) {
            $table->$column = $value;
        }

        return $table->store() ? $table->id : 0;
    }
}
