<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class RoomTypes extends ListController
{
    use FluMoxed;
    use Suppressed;

    protected string $item = 'RoomType';
}
