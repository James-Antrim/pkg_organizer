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

/**
 * Models the organizer_group_publishing table.
 */
class GroupPublishing extends BaseTable
{
	/**
	 * The id of the group entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $groupID;

	/**
	 * The publishing status of the group for the term.
	 * TINYINT(1) UNSIGNED NOT NULL DEFAULT 1
	 *
	 * @var bool
	 */
	public $published;

	/**
	 * The id of the term entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $termID;

	/**
	 * Declares the associated table
	 *
	 * @param   \JDatabaseDriver &$dbo  A database connector object
	 */
	public function __construct(&$dbo = null)
	{
		parent::__construct('#__organizer_group_publishing', 'id', $dbo);
	}
}
