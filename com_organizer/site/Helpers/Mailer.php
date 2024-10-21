<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Helpers;

use Exception;
use Joomla\CMS\Factory;
use THM\Organizer\Adapters\{Application, Input, Text, User};
use THM\Organizer\Tables\{Courses as CTable, Participants};

class Mailer
{
    /**
     * Sends a notification mail to the participant.
     *
     * @param   int     $participantID  the id of the participant being iterated
     * @param   string  $subject        the subject of the notification
     * @param   string  $body           the notification message
     *
     * @return bool
     */
    public static function notifyParticipant(int $participantID, string $subject, string $body): bool
    {
        $user = User::instance($participantID);

        if (!$user->id) {
            return false;
        }

        $participant = new Participants();
        if (!$participant->load($participantID)) {
            return false;
        }

        $sender = User::instance();
        if (!$sender->id) {
            return false;
        }

        $mailer = Factory::getMailer();

        try {
            $mailer->setSender([$sender->email, $sender->name]);
            $mailer->addRecipient($user->email);
            $mailer->setBody($body);
            $mailer->setSubject($subject);

            return $mailer->Send();
        }
        catch (Exception $exception) {
            Application::handleException($exception);
            return false;
        }
    }

    /**
     * Sends a mail confirming the registration
     *
     * @param   int       $courseID       the course id
     * @param   int       $participantID  the participant id
     * @param   int|null  $status         the participant's status
     *
     * @return void
     */
    public static function registrationUpdate(int $courseID, int $participantID, ?int $status): void
    {
        $course = new CTable();
        if (!$course->load($courseID)) {
            return;
        }

        if (!$dates = Courses::displayDate($courseID)) {
            return;
        }

        $user = User::instance($participantID);
        if (!$user->id) {
            return;
        }

        $participant = new Participants();
        if (!$participant->load($participantID)) {
            return;
        }

        $params = Input::getParams();
        $sender = User::instance($params->get('mailSender'));
        if (empty($sender->id)) {
            return;
        }

        $userParams = json_decode($user->params);
        if (empty($userParams->language)) {
            $tag = Application::tag();
        }
        else {
            // TODO see what variable Joomla needs set here and set it.
            $tag = explode('-', $userParams['language'])[0];
            Input::set('languageTag', $tag);
        }

        $courseName = $course->{"name_$tag"};
        if ($campus = Campuses::name($course->campusID)) {
            $courseName .= " - $campus";
        }

        $address    = str_replace(' – ', "\n", $params->get('address'));
        $contact    = str_replace(' – ', "\n", $params->get('contact'));
        $courseName .= " ($dates)";

        if ($status === CourseParticipants::UNREGISTERED) {
            $body = Text::sprintf('ORGANIZER_DEREGISTER_BODY',
                $courseName,
                $sender->name,
                $sender->email,
                $address,
                $contact
            );
        }
        else {
            $statusText = $status ? 'ORGANIZER_REGISTERED' : 'ORGANIZER_WAITLIST';
            $statusText = Text::_($statusText);
            $body       = Text::sprintf(
                'ORGANIZER_STATUS_CHANGE_BODY',
                $courseName,
                $statusText,
                $sender->name,
                $sender->email,
                $address,
                $contact
            );
        }

        $mailer = Factory::getMailer();

        try {
            $mailer->setSender([$sender->email, $sender->name]);
            $mailer->addRecipient($user->email);
            $mailer->setBody($body);
            $mailer->setSubject($courseName);
            $mailer->Send();
        }
        catch (Exception $exception) {
            Application::handleException($exception);
        }
    }
}