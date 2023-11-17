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

use Joomla\Utilities\ArrayHelper;
use THM\Organizer\Adapters\{Application, Input, Text};
use THM\Organizer\Helpers\{Can, Courses, Mailer, Units, Users};
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
    protected function authorize()
    {
        if (!Can::coordinate('course', Input::getID())) {
            Application::error(403);
        }
    }

    /**
     * Deregisters the user from the course.
     * @return bool
     */
    public function deregister(): bool
    {
        if (!$courseID = Input::getID() or !$participantID = Users::getID()) {
            return false;
        }

        if (!Can::manage('participant', $participantID) and !Can::coordinate('course', $courseID)) {
            Application::error(403);
        }

        $dates = Courses::getDates($courseID);

        if (empty($dates['endDate']) or $dates['endDate'] < date('Y-m-d')) {
            return false;
        }

        $courseParticipant = new Tables\CourseParticipants();
        $cpData            = ['courseID' => $courseID, 'participantID' => $participantID];

        if (!$courseParticipant->load($cpData) or !$courseParticipant->delete()) {
            return false;
        }

        if ($instanceIDs = Courses::getInstanceIDs($courseID)) {
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
     * Imports a list of associated units within an organization/term context
     * @return bool
     */
    public function import(): bool
    {
        $organizationID = Input::getInt('organizationID');
        $termID         = Input::getInt('termID');

        if (!$organizationID or !$termID) {
            Application::error(400);
        }

        if (!Can::schedule('organization', $organizationID)) {
            Application::error(403);
        }

        $input = Input::getInput();

        $file = $input->files->get('jform', [], 'array')['file'];
        $file = fopen($file['tmp_name'], 'r');

        $courses = [];

        while (($row = fgets($file)) !== false) {
            $row = str_replace(chr(13) . chr(10), '', $row);

            if (!$row = trim($row)) {
                continue;
            }

            if (!preg_match('/^[\d, ]+$/', $row)) {
                Application::message("Malformed row: $row.", Application::ERROR);
                continue;
            }

            $courses[] = array_filter(ArrayHelper::toInteger(explode(',', $row)));
        }

        fclose($file);

        foreach ($courses as $unitIDs) {
            $this->addCourseByUnitIDs($organizationID, $termID, $unitIDs);
        }

        Application::message(Text::_('ORGANIZER_IMPORT_SUCCESS'));

        return true;
    }

    /**
     * Creates a course based on the information associated with the given units.
     * @return void
     */
    private function addCourseByUnitIDs(int $organizationID, int $termID, array $unitIDs)
    {
        $organization = new Tables\Organizations();
        $term         = new Tables\Terms();

        if (!$organization->load($organizationID) or !$term->load($termID)) {
            Application::error(500);
        }

        sort($unitIDs);
        $course    = new Tables\Courses();
        $localized = 'name_' . Application::getTag();
        $units     = [];

        foreach ($unitIDs as $unitID) {
            $unit = new Tables\Units();

            if (!$unit->load(['code' => $unitID, 'organizationID' => $organizationID, 'termID' => $termID])) {
                Application::message(Text::sprintf('ORGANIZER_UNIT_ID_INVALID', $unitID));

                return;
            }

            if ($unit->courseID) {
                if ($course->id and $course->id !== $unit->courseID) {
                    Application::message(Text::sprintf('ORGANIZER_UNIT_COURSE_CONFLICT', $unitID, $course->$localized));

                    return;
                }
                elseif (!$course->id) {
                    $course->load($unit->courseID);
                }
            }

            $units[] = $unit;
        }

        $copyOfUnitIDs   = $unitIDs;
        $lastID          = array_pop($copyOfUnitIDs);
        $nameIDs         = count($copyOfUnitIDs) ? implode(', ', $copyOfUnitIDs) . " & $lastID" : $lastID;
        $course->name_de = "$organization->abbreviation_de - $term->name_de - $nameIDs";
        $course->name_en = "$organization->abbreviation_en - $term->name_en - $nameIDs";
        $course->termID  = $termID;

        $event = new Tables\Events();

        foreach ($unitIDs as $unitID) {
            foreach (Units::getEventIDs($unitID) as $eventID) {
                $event->load($eventID);

                if ($course->deadline === null or $event->deadline < $course->deadline) {
                    $course->deadline = $event->deadline;
                }

                if ($course->fee === null or $event->fee < $course->fee) {
                    $course->fee = $event->fee;
                }

                if ($course->maxParticipants === null or $event->maxParticipants < $course->maxParticipants) {
                    $course->maxParticipants = $event->maxParticipants;
                }

                if ($course->registrationType === null or $event->registrationType < $course->registrationType) {
                    $course->registrationType = $event->registrationType;
                }
            }
        }

        $course->campusID = Units::getCampusID($units[0]->id, $event->campusID);

        $course->store();

        foreach ($units as $unit) {
            $unit->courseID = $course->id;
            $unit->store();
        }
    }

    /**
     * Registers the user for the course.
     * @return bool
     */
    public function register(): bool
    {
        $courseID      = Input::getID();
        $participantID = Users::getID();
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
            if ($instanceIDs = Courses::getInstanceIDs($courseID)) {
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
     * @return int|bool int id of the resource on success, otherwise bool false
     */
    public function save(array $data = [])
    {
        $this->authorize();

        $data  = empty($data) ? Input::getFormItems()->toArray() : $data;
        $table = $this->getTable();

        if (empty($data['id'])) {
            return $table->save($data) ? $table->id : false;
        }

        if (!$table->load($data['id'])) {
            return false;
        }

        foreach ($data as $column => $value) {
            $table->$column = $value;
        }

        return $table->store() ? $table->id : false;
    }
}
