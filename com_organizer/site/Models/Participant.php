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

use Exception;
use THM\Organizer\Adapters\{Application, Database, Input, Text};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;
use THM\Organizer\Tables\Participants as Table;

/**
 * Class which manages stored participant data.
 */
class Participant extends EditModel
{
    protected string $tableClass = 'Participants';
}
