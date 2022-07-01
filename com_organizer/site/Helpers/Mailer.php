<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace Organizer\Helpers;


use Joomla\CMS\Factory;
use Organizer\Tables;

class Mailer
{
	const NONE = null, WAITLIST = 0, REGISTERED = 1;

	/**
	 * Sends a notification mail to the participant.
	 *
	 * @param   int     $participantID  the id of the participant being iterated
	 * @param   string  $subject        the subject of the notification
	 * @param   string  $body           the notification message
	 *
	 * @return void
	 */
	public static function notifyParticipant(int $participantID, string $subject, string $body)
	{
		$user = Users::getUser($participantID);
		if (!$user->id)
		{
			return;
		}

		$participant = new Tables\Participants();
		if (!$participant->load($participantID))
		{
			return;
		}

		$sender = Users::getUser();
		if (!$sender->id)
		{
			return;
		}

		$mailer = Factory::getMailer();
		$mailer->setSender([$sender->email, $sender->name]);
		$mailer->addRecipient($user->email);
		$mailer->setBody($body);
		$mailer->setSubject($subject);
		$mailer->Send();
	}

	/**
	 * Sends a mail confirming the registration
	 *
	 * @param $courseID
	 * @param $participantID
	 *
	 * @return void
	 */
	public static function registrationUpdate($courseID, $participantID, $status)
	{
		$course = new Tables\Courses();
		if (!$course->load($courseID))
		{
			return;
		}

		if (!$dates = Courses::getDateDisplay($courseID))
		{
			return;
		}

		$user = Users::getUser($participantID);
		if (!$user->id)
		{
			return;
		}

		$participant = new Tables\Participants();
		if (!$participant->load($participantID))
		{
			return;
		}

		$params = Input::getParams();
		$sender = Users::getUser($params->get('mailSender'));
		if (empty($sender->id))
		{
			return;
		}

		$userParams = json_decode($user->params);
		if (empty($userParams->language))
		{
			$tag = Languages::getTag();
		}
		else
		{
			// TODO see what variable Joomla needs set here and set it.
			$tag = explode('-', $userParams['language'])[0];
			Input::set('languageTag', $tag);
		}

		$courseName = $course->{"name_$tag"};
		if ($campus = Campuses::getName($course->campusID))
		{
			$courseName .= " - $campus";
		}

		$address    = str_replace(' – ', "\n", $params->get('address'));
		$contact    = str_replace(' – ', "\n", $params->get('contact'));
		$courseName .= " ($dates)";

		if ($status === self::NONE)
		{
			$body = sprintf(
				Languages::_('ORGANIZER_DEREGISTER_BODY'),
				$courseName,
				$sender->name,
				$sender->email,
				$address,
				$contact
			);
		}
		else
		{
			$statusText = $status ? 'ORGANIZER_REGISTERED' : 'ORGANIZER_WAITLIST';
			$statusText = Languages::_($statusText);
			$body       = sprintf(
				Languages::_('ORGANIZER_STATUS_CHANGE_BODY'),
				$courseName,
				$statusText,
				$sender->name,
				$sender->email,
				$address,
				$contact
			);
		}

		$mailer = Factory::getMailer();
		$mailer->setSender([$sender->email, $sender->name]);
		$mailer->addRecipient($user->email);
		$mailer->setBody($body);
		$mailer->setSubject($courseName);
		$mailer->Send();
	}
}