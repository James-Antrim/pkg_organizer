<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers\Validators;

use SimpleXMLElement;
use stdClass;
use THM\Organizer\Adapters\Text;
use THM\Organizer\Controllers\ImportSchedule as Schedule;
use THM\Organizer\Tables\{Associations, Groups as Table};

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Groups implements UntisXMLValidator
{
    /**
     * @inheritDoc
     */
    public static function setID(Schedule $controller, string $code): void
    {
        $group = $controller->groups->$code;

        $table  = new Table();
        $exists = $table->load(['code' => $group->code]);

        if ($exists) {
            $altered = false;
            foreach ($group as $key => $value) {
                if (property_exists($table, $key) and empty($table->$key) and !empty($value)) {
                    $table->set($key, $value);
                    $altered = true;
                }
            }

            if ($altered) {
                $table->store();
            }
        }
        else {
            $table->save($group);
        }

        $association = new Associations();
        if (!$association->load(['groupID' => $table->id])) {
            $association->save(['groupID' => $table->id, 'organizationID' => $controller->organizationID]);
        }

        $controller->groups->$code->id = $table->id;
    }

    /**
     * @inheritDoc
     */
    public static function validate(Schedule $controller, SimpleXMLElement $node): void
    {
        $code     = str_replace('CL_', '', trim((string) $node[0]['id']));
        $fullName = trim((string) $node->longname);
        if (empty($fullName)) {
            $controller->errors[] = Text::sprintf('GROUP_FULLNAME_MISSING', $code);

            return;
        }

        $name = trim((string) $node->classlevel);
        if (empty($name)) {
            $controller->errors[] = Text::sprintf('GROUP_NAME_MISSING', $fullName, $code);

            return;
        }

        if (!$categoryID = str_replace('DP_', '', trim((string) $node->class_department[0]['id']))) {
            $controller->errors[] = Text::sprintf('GROUP_CATEGORY_MISSING', $fullName, $code);

            return;
        }
        elseif (!$category = $controller->categories->$categoryID) {
            $controller->errors[] = Text::sprintf('GROUP_CATEGORY_INCOMPLETE', $fullName, $code, $categoryID);

            return;
        }

        if (!$gridName = (string) $node->timegrid) {
            $controller->errors[] = Text::sprintf('GROUP_GRID_MISSING', $fullName, $code);

            return;
        }
        elseif (!$grid = $controller->grids->$gridName) {
            $controller->errors[] = Text::sprintf('GROUP_GRID_INCOMPLETE', $fullName, $code, $gridName);

            return;
        }

        $group              = new stdClass();
        $group->categoryID  = $category->id;
        $group->code        = $code;
        $group->fullName_de = $fullName;
        $group->fullName_en = $fullName;
        $group->name_de     = $name;
        $group->name_en     = $name;
        $group->gridID      = $grid->id;

        $controller->groups->$code = $group;
        self::setID($controller, $code);
    }
}
