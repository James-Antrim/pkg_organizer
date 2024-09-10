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
use THM\Organizer\Helpers\{Can, Courses, Mailer};

/**
 * Class which manages stored course data.
 */
class CourseParticipant extends BaseModel
{
    /**
     * Sends a circular mail to all course participants.
     * @return bool true on success, false on error
     */
    public function notify(): bool
    {
        if (!$courseID = Input::getID()) {
            return false;
        }

        if (!Courses::coordinatable($courseID)) {
            Application::error(403);
        }

        $courseParticipants   = Courses::participantIDs($courseID);
        $selectedParticipants = Input::getIntArray('cid');

        if (empty($courseParticipants) and empty($selectedParticipants)) {
            return false;
        }

        $participantIDs = $selectedParticipants ?: $courseParticipants;

        $form = Input::getBatchItems();
        if (!$subject = trim($form->get('subject', '')) or !$body = trim($form->get('body', ''))) {
            return false;
        }

        foreach ($participantIDs as $participantID) {
            Mailer::notifyParticipant($participantID, $subject, $body);
        }

        return true;
    }

    /** @inheritDoc */
    public function toggle(): bool
    {
        $attribute     = Input::getCMD('attribute');
        $courseID      = Input::getInt('courseID');
        $participantID = Input::getInt('participantID');

        if (!$attribute or !$courseID or !$participantID) {
            return false;
        }

        if (!Courses::coordinatable($courseID) or !Can::manage('participant', $participantID)) {
            Application::error(403);
        }

        $table = $this->getTable();
        if (!property_exists($table, $attribute)) {
            return false;
        }

        if (!$table->load(['courseID' => $courseID, 'participantID' => $participantID])) {
            return false;
        }

        $table->$attribute = !$table->$attribute;

        if (!$table->store()) {
            return false;
        }

        if ($attribute === 'status') {
            Mailer::registrationUpdate($courseID, $participantID, $table->$attribute);
        }

        return true;
    }
}