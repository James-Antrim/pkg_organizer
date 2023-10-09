<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\JSON;

use Organizer\Helpers;
use Organizer\Tables;

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
        $nameProperty = 'name_' . Helpers\Languages::getTag();

        $active     = Helpers\Input::getBool('active', true);
        $categoryID = Helpers\Input::getInt('categoryID');

        foreach (Helpers\Categories::getGroups($categoryID, $active) as $group) {
            $group['events'] = [];

            $groupID = $group['id'];
            unset($group['id']);

            foreach (Helpers\Groups::getUnits($groupID, $date, $interval) as $unit) {
                $eventID = $unit['eventID'];
                unset($unit['eventID']);

                if (empty($group['events'][$eventID])) {
                    $table = new Tables\Events();
                    if (!$table->load($eventID)) {
                        continue;
                    }

                    $group['events'][$eventID] = [
                        'code' => $table->code,
                        'name' => $table->$nameProperty,
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
