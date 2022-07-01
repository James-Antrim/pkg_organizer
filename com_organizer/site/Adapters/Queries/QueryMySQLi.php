<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Adapters\Queries;

use JDatabaseQueryMysqli;

class QueryMySQLi extends JDatabaseQueryMysqli
{
	use Extended;
}