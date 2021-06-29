<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Calendar;

/**
 * Provide a grouping of component properties that define an alarm.
 *
 * Description:
 *
 * A "VALARM" calendar component is a grouping of component properties that is a reminder or alarm for an event or a
 * to-do. For example, it may be used to define a reminder for a pending event or an overdue to-do.
 *
 * The "VALARM" calendar component MUST include the "ACTION" and "TRIGGER" properties. The "ACTION" property further
 * constrains the "VALARM" calendar component in the following ways:
 *
 * - When the action is "AUDIO", the alarm can also include one and only one "ATTACH" property, which MUST point to a
 *   sound resource, which is rendered when the alarm is triggered.
 * - When the action is "DISPLAY", the alarm MUST also include a "DESCRIPTION" property, which contains the text to be
 *   displayed when the alarm is triggered.
 * - When the action is "EMAIL", the alarm MUST include a "DESCRIPTION" property, which contains the text to be used as
 *   the message body, a "SUMMARY" property, which contains the text to be used as the message subject, and one or more
 *   "ATTENDEE" properties, which contain the email address of attendees to receive the message. It can also include one
 *   or more "ATTACH" properties, which are intended to be sent as message attachments. When the alarm is triggered, the
 *   email message is sent.
 *
 * The "VALARM" calendar component MUST only appear within either a "VEVENT" or "VTODO" calendar component. "VALARM"
 * calendar components cannot be nested. Multiple mutually independent "VALARM" calendar components can be specified for
 * a single "VEVENT" or "VTODO" calendar component.
 *
 * The "TRIGGER" property specifies when the alarm will be triggered. The "TRIGGER" property specifies a duration prior
 * to the start of an event or a to-do. The "TRIGGER" edge may be explicitly set to be relative to the "START" or "END"
 * of the event or to-do with the "RELATED" parameter of the "TRIGGER" property. The "TRIGGER" property value type can
 * alternatively be set to an absolute calendar date with UTC time.
 *
 * In an alarm set to trigger on the "START" of an event or to-do, the "DTSTART" property MUST be present in the
 * associated event or to-do. In an alarm in a "VEVENT" calendar component set to trigger on the "END" of the event,
 * either the "DTEND" property MUST be present, or the "DTSTART" and "DURATION" properties MUST both be present. In an
 * alarm in a "VTODO" calendar component set to trigger on the "END" of the to-do, either the "DUE" property MUST be
 * present, or the "DTSTART" and "DURATION" properties MUST both be present.
 *
 * The alarm can be defined such that it triggers repeatedly. A definition of an alarm with a repeating trigger MUST
 * include both the "DURATION" and "REPEAT" properties. The "DURATION" property specifies the delay period, after which
 * the alarm will repeat. The "REPEAT" property specifies the number of additional repetitions that the alarm will be
 * triggered. This repetition count is in addition to the initial triggering of the alarm. Both of these properties MUST
 * be present in order to specify a repeating alarm. If one of these two properties is absent, then the alarm will not
 * repeat beyond the initial trigger.
 *
 * The "ACTION" property is used within the "VALARM" calendar component to specify the type of action invoked when the
 * alarm is triggered. The "VALARM" properties provide enough information for a specific action to be invoked. It is
 * typically the responsibility of a "Calendar User Agent" (CUA) to deliver the alarm in the specified fashion. An
 * "ACTION" property value of AUDIO specifies an alarm that causes a sound to be played to alert the user; DISPLAY
 * specifies an alarm that causes a text message to be displayed to the user; and EMAIL specifies an alarm that causes
 * an electronic email message to be delivered to one or more email addresses.
 *
 * In an AUDIO alarm, if the optional "ATTACH" property is included, it MUST specify an audio sound resource. The
 * intention is that the sound will be played as the alarm effect. If an "ATTACH" property is specified that does not
 * refer to a sound resource, or if the specified sound resource cannot be rendered (because its format is unsupported,
 * or because it cannot be retrieved), then the CUA or other entity responsible for playing the sound may choose a
 * fallback action, such as playing a built-in default sound, or playing no sound at all.
 *
 * In a DISPLAY alarm, the intended alarm effect is for the text value of the "DESCRIPTION" property to be displayed to
 * the user.
 *
 * In an EMAIL alarm, the intended alarm effect is for an email message to be composed and delivered to all the
 * addresses specified by the "ATTENDEE" properties in the "VALARM" calendar component. The "DESCRIPTION" property of
 * the "VALARM" calendar component MUST be used as the body text of the message, and the "SUMMARY" property MUST be used
 * as the subject text. Any "ATTACH" properties in the "VALARM" calendar component SHOULD be sent as attachments to the
 * message.
 *
 * Format Definition:
 *
 * alarmc = "BEGIN" ":" "VALARM" CRLF
 *          (audioprop / dispprop / emailprop)
 *          "END" ":" "VALARM" CRLF
 *
 * audioprop = *(
 *   action / trigger - required, can only once
 *   duration / repeat - optional, can only once, mutually dependent
 * )
 *
 * dispprop = *(
 *   action / description / trigger - required, can only once
 *   duration / repeat - optional, can only once, mutually dependent
 * )
 *
 * emailprop = *(
 *   action / description / trigger / summary - required, can only once
 *   attendee - required, may more than once
 *   duration / repeat - optional, can only once, mutually dependent
 * )
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.6.6
 */
class VAlarm extends VComponent
{
	private $type;

	public function __construct(string $type = 'EMAIL')
	{
		$type = strtoupper($type);

		if (!in_array($type, ['AUDIO', 'DISPLAY', 'EMAIL']))
		{
			$type = 'EMAIL';
		}

		$this->type = $type;
	}

	/**
	 * @inheritDoc
	 */
	public function getProps(&$output)
	{
		if (in_array($this->type, ['AUDIO', 'EMAIL']))
		{
			$this->getAttachments($output);
		}

		$this->getIANAProps($output);
		$this->getXProps($output);
	}

	/**
	 * @inheritDoc
	 */
	protected function setAttachment(string $attachment, string $type = 'URI', array $params = [])
	{
		// Display gets none.
		if ($this->type === 'DISPLAY')
		{
			return;
		}

		// Audio can only have one
		if ($this->type === 'AUDIO')
		{
			$this->attachments = [];
		}

		parent::setAttachment($attachment, $type, $params);
	}
}