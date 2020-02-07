<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Tables;

use JDatabaseDriver;

/**
 * Models the organizer_events table.
 */
class Events extends BaseTable
{
	use Addressable;

	/**
	 * The id of the campus entry referenced.
	 * INT(11) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $campusID;

	/**
	 * An abbreviated nomenclature for the resource. Currently corresponding to the identifier in Untis scheduling
	 * software.
	 * VARCHAR(60) DEFAULT NULL
	 *
	 * @var string
	 */
	public $code;

	/**
	 * The organization's German contact information for a group of courses.
	 * TEXT
	 *
	 * @var string
	 */
	public $contact_de;

	/**
	 * The organization's English contact information for a group of courses.
	 * TEXT
	 *
	 * @var string
	 */
	public $contact_en;

	/**
	 * The German description of the event's contents.
	 * TEXT
	 *
	 * @var string
	 */
	public $content_de;

	/**
	 * The English description of the event's contents.
	 * TEXT
	 *
	 * @var string
	 */
	public $content_en;

	/**
	 * The organization's German contact information for courses of this event type.
	 * TEXT
	 *
	 * @var string
	 */
	public $courseContact_de;

	/**
	 * The organization's English contact information for courses of this event type.
	 * TEXT
	 *
	 * @var string
	 */
	public $courseContact_en;

	/**
	 * The number of days before course begin when registration is closed.
	 * INT(2) UNSIGNED DEFAULT 0
	 *
	 * @var int
	 */
	public $deadline;

	/**
	 * The resource's German description.
	 * TEXT
	 *
	 * @var string
	 */
	public $description_de;

	/**
	 * The resource's English description.
	 * TEXT
	 *
	 * @var string
	 */
	public $description_en;

	/**
	 * The fee for participation in the course.
	 * INT(3) UNSIGNED DEFAULT 0
	 *
	 * @var int
	 */
	public $fee;

	/**
	 * The maximum number of participants the course allows.
	 * INT(4) UNSIGNED DEFAULT 1000
	 *
	 * @var int
	 */
	public $maxParticipants;

	/**
	 * The resource's German name.
	 * VARCHAR(150) NOT NULL
	 *
	 * @var string
	 */
	public $name_de;

	/**
	 * The resource's English name.
	 * VARCHAR(150) NOT NULL
	 *
	 * @var string
	 */
	public $name_en;

	/**
	 * A German description of how courses implementing this event are organized.
	 * TEXT
	 *
	 * @var string
	 */
	public $organization_de;

	/**
	 * A English description of how courses implementing this event are organized.
	 * TEXT
	 *
	 * @var string
	 */
	public $organization_en;

	/**
	 * The id of the organization entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $organizationID;

	/**
	 * A German description how to test one's self to see if one should attend or is qualified to attend courses
	 * implementing this event.
	 * TEXT
	 *
	 * @var string
	 */
	public $pretests_de;

	/**
	 * A English description how to test one's self to see if one should attend or is qualified to attend courses
	 * implementing this event.
	 * TEXT
	 *
	 * @var string
	 */
	public $pretests_en;

	/**
	 * Whether or not the event is a preparatory event.
	 * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
	 *
	 * @var bool
	 */
	public $preparatory;

	/**
	 * The method of processing used to accept course registrations. Values: NULL - None, 0 - FIFO, 1 - Manual.
	 * INT(1) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $registrationType;

	/**
	 * The resource's alphanumeric identifier in degree program documentation.
	 * VARCHAR(45) NOT NULL DEFAULT ''
	 *
	 * @var string
	 */
	public $subjectNo;

	/**
	 * Declares the associated table
	 *
	 * @param   JDatabaseDriver &$dbo  A database connector object
	 */
	public function __construct(&$dbo = null)
	{
		parent::__construct('#__organizer_events', 'id', $dbo);
	}
}
