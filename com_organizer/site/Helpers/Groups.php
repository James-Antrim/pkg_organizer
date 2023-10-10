<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use THM\Organizer\Adapters\{Application, Database, Text};
use THM\Organizer\Tables\Categories as Category;
use THM\Organizer\Tables\Groups as Group;

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Groups extends Associated implements Selectable
{
    use Filtered, Planned, Suppressed;

    protected static $resource = 'group';

    /**
     * Returns the category (table entry) associated with a group.
     *
     * @param int $groupID
     *
     * @return Category
     */
    public static function getCategory(int $groupID): Category
    {
        $category = new Category();
        $group    = new Group();

        if ($group->load($groupID)) {
            $category->load($group->categoryID);
        }

        return $category;
    }

    /**
     * Gets the name of the category with which the group is associated.
     *
     * @param int $groupID
     *
     * @return string
     */
    public static function getCategoryID(int $groupID): string
    {
        $category = self::getCategory($groupID);

        if (!$category->id) {
            return 0;
        }

        return $category->id;
    }

    /**
     * Gets the name of the category with which the group is associated.
     *
     * @param int $groupID
     *
     * @return string
     */
    public static function getCategoryName(int $groupID): string
    {
        $category = self::getCategory($groupID);

        if (!$category->id) {
            return Text::_('ORGANIZER_NO_CATEGORIES');
        }

        $column = 'name_' . Application::getTag();

        return $category->$column;
    }

    /**
     * Retrieves the events associated with a group.
     *
     * @param int $groupID the id of the group
     *
     * @return array[]
     */
    public static function getEvents(int $groupID): array
    {
        $query = Database::getQuery();
        $tag   = Application::getTag();
        $query->select("DISTINCT e.id, e.code, e.name_$tag AS name, e.description_$tag AS description")
            ->from('#__organizer_events AS e')
            ->innerJoin('#__organizer_instances AS i ON i.eventID = e.id')
            ->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
            ->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ip.id')
            ->where("groupID = $groupID");
        Database::setQuery($query);

        return Database::loadAssocList();
    }

    /**
     * @inheritDoc
     *
     * @param string $access any access restriction which should be performed
     */
    public static function getOptions(string $access = ''): array
    {
        $categoryID  = Input::getInt('categoryID');
        $categoryIDs = $categoryID ? [$categoryID] : Input::getFilterIDs('category');
        $tag         = Application::getTag();
        $name        = count($categoryIDs) === 1 ? "name_$tag" : "fullName_$tag";
        $options     = [];

        foreach (self::getResources() as $group) {
            if ($group['active']) {
                $options[] = HTML::_('select.option', $group['id'], $group[$name]);
            }
        }

        uasort($options, function ($optionOne, $optionTwo) {
            return strcmp($optionOne->text, $optionTwo->text);
        });

        // Any out of sequence indexes cause JSON to treat this as an object
        return array_values($options);
    }

    /**
     * @inheritDoc
     *
     * @param string $access any access restriction which should be performed
     */
    public static function getResources(string $access = ''): array
    {
        if ($categoryID = Input::getInt('categoryID')) {
            $categoryIDs = [$categoryID];
        }

        $categoryIDs = empty($categoryIDs) ? Input::getIntCollection('categoryIDs') : $categoryIDs;
        $categoryIDs = empty($categoryIDs) ? Input::getFilterIDs('category') : $categoryIDs;

        if ($organizationID = Input::getInt('organizationID')) {
            $organizationIDs = [$organizationID];
        }

        $organizationIDs = empty($organizationIDs) ? Input::getIntCollection('organizationIDs') : $organizationIDs;
        $organizationIDs = empty($organizationIDs) ? Input::getFilterIDs('organization') : $organizationIDs;

        if (empty($categoryIDs) and empty($organizationIDs)) {
            return [];
        }

        $query = Database::getQuery();
        $query->select('g.*')->from('#__organizer_groups AS g');

        if (!empty($access)) {
            self::addAccessFilter($query, $access, 'group', 'g');
        }

        if (!empty($organizationIDs)) {
            self::addOrganizationFilter($query, 'group', 'g');
        }

        if (!empty($categoryIDs)) {
            self::addResourceFilter($query, 'category', 'cat', 'g');
        }

        Database::setQuery($query);

        return Database::loadAssocList('id');
    }

    /**
     * Retrieves the units associated with an event.
     *
     * @param int    $groupID  the id of the referenced event
     * @param string $date     the date context for the unit search
     * @param string $interval the interval to use as context for units
     *
     * @return array[]
     */
    public static function getUnits(int $groupID, string $date, string $interval = 'term'): array
    {
        $query = Database::getQuery();
        $tag   = Application::getTag();
        $query->select("DISTINCT u.id, u.comment, m.abbreviation_$tag AS method, eventID")
            ->from('#__organizer_units AS u')
            ->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
            ->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
            ->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ip.id')
            ->leftJoin('#__organizer_methods AS m ON m.id = i.methodID')
            ->where("groupID = $groupID")
            ->where("u.delta != 'removed'");
        self::addUnitDateRestriction($query, $date, $interval);
        Database::setQuery($query);

        return Database::loadAssocList();
    }
}
