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

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Categories extends Associated implements Selectable
{
    use Filtered, Numbered, Suppressed;

    protected static $resource = 'category';

    /**
     * Retrieves the groups associated with a category.
     *
     * @param int  $categoryID the category id
     * @param bool $active     whether to retrieve only active categories
     *
     * @return array[]
     */
    public static function getGroups(int $categoryID, bool $active = true): array
    {
        $tag   = Application::getTag();
        $query = Database::getQuery();
        $query->select("id, code, name_$tag AS name")
            ->from('#__organizer_groups AS g')
            ->where("categoryID = $categoryID");

        if ($active) {
            $query->where('active = 1');
        }

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
        $name    = Application::getTag() === 'en' ? 'name_en' : 'name_de';
        $options = [];
        foreach (self::getResources($access) as $category) {
            if ($category['active']) {
                $options[] = HTML::_('select.option', $category['id'], $category[$name]);
            }
        }

        uasort($options, function ($optionOne, $optionTwo)
        {
            return strcmp($optionOne->text, $optionTwo->text);
        });

        // Any out of sequence indexes cause JSON to treat this as an object
        return array_values($options);
    }

    /**
     * Retrieves the name of the program associated with the category.
     *
     * @param int $categoryID the table id for the program
     *
     * @return string the name of the (plan) program, otherwise empty
     */
    public static function getProgramName(int $categoryID): string
    {
        $noName = Text::_('ORGANIZER_NO_PROGRAM');
        if (!$categoryID) {
            return $noName;
        }

        $query = Database::getQuery();
        $query->select('DISTINCT id')->from('#__organizer_programs')->where("categoryID = $categoryID");
        Database::setQuery($query);

        if ($programIDs = Database::loadIntColumn()) {
            return count($programIDs) > 1 ?
                Text::_('ORGANIZER_MULTIPLE_PROGRAMS') : Programs::getName($programIDs[0]);
        }

        return $noName;
    }

    /**
     * @inheritDoc
     *
     * @param string $access any access restriction which should be performed
     */
    public static function getResources(string $access = ''): array
    {
        $order = Application::getTag() === 'en' ? 'name_en' : 'name_de';
        $query = Database::getQuery();
        $query->select('DISTINCT c.*')->from('#__organizer_categories AS c')->order($order);

        if (!empty($access)) {
            self::addAccessFilter($query, $access, 'category', 'c');
        }

        self::addOrganizationFilter($query, 'category', 'c');
        Database::setQuery($query);

        return Database::loadAssocList('id');
    }
}
