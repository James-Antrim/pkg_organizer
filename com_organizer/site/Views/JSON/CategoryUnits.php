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

use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

/**
 * Class answers dynamic (degree) program related queries
 */
class CategoryUnits extends BaseView
{
    use Planned;

    /**
     * loads model data into view context
     * @return void
     */
    public function display()
    {
        $date         = $this->getDate();
        $groups       = [];
        $interval     = $this->getInterval();
        $nameProperty = 'name_' . Application::tag();

        $active     = Input::bool('active', true);
        $categoryID = Input::integer('categoryID');

        foreach (Helpers\Categories::groups($categoryID, $active) as $group) {
            $group['events'] = [];

            $groupID = $group['id'];
            unset($group['id']);

            foreach (Helpers\Groups::units($groupID, $date, $interval) as $unit) {
                $eventID = $unit['eventID'];
                unset($unit['eventID']);

                if (empty($group['events'][$eventID])) {
                    $table = new Tables\Events();
                    if (!$table->load($eventID)) {
                        continue;
                    }

                    $group['events'][$eventID] = [
                        'code'  => $table->code,
                        'name'  => $table->$nameProperty,
                        'units' => []
                    ];
                }

                $unitID = $unit['id'];
                unset($unit['id']);

                if (empty($group['events'][$eventID]['units'][$unitID])) {
                    $group['events'][$eventID]['units'][$unitID] = $unit;
                }
            }

            if (count($group['events'])) {
                $groups[$groupID] = $group;
            }
        }

        echo json_encode($groups, JSON_UNESCAPED_UNICODE);
    }
}
