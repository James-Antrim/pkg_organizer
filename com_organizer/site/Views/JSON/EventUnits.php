<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\JSON;

use THM\Organizer\Adapters\Input;
use THM\Organizer\Helpers;

/**
 * Class answers dynamic (degree) program related queries
 */
class EventUnits extends BaseView
{
    use Planned;

    /**
     * loads model data into view context
     * @return void
     */
    public function display()
    {
        $date     = $this->getDate();
        $eventID  = Input::integer('eventID');
        $interval = $this->getInterval();
        $units    = [];

        foreach (Helpers\Events::units($eventID, $date, $interval) as $unit) {
            $unitID = $unit['id'];
            unset($unit['id']);

            $unit['contexts'] = [];

            foreach (Helpers\Units::getContexts($unitID, $eventID) as $groupID => $context) {
                unset($context['groupID']);
                $unit['contexts'][$groupID] = $context;
            }

            $units[$unitID] = $unit;
        }

        echo json_encode($units, JSON_UNESCAPED_UNICODE);
    }
}
