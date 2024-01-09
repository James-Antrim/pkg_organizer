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

use THM\Organizer\Adapters\{Application, Database as DB, HTML, Text};
use Joomla\Database\ParameterType;

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Categories extends Associated implements Selectable
{
    use Active;
    use Filtered;
    use Numbered;
    use Suppressed;

    protected static string $resource = 'category';

    /**
     * Retrieves the groups associated with a category.
     *
     * @param   int   $categoryID  the category id
     * @param   bool  $active      whether to retrieve only active categories
     *
     * @return array[]
     */
    public static function getGroups(int $categoryID, bool $active = true): array
    {
        $tag   = Application::getTag();
        $query = DB::getQuery();
        $query->select(array_merge(DB::qn(['id', 'code']), [DB::qn("name_$tag", 'name')]))
            ->from(DB::qn('#__organizer_groups', 'g'))
            ->where(DB::qn('categoryID') . ' = :categoryID')
            ->bind(':categoryID', $categoryID, ParameterType::INTEGER);

        if ($active) {
            $query->where(DB::qn('active') . ' = 1');
        }

        DB::setQuery($query);

        return DB::loadAssocList();
    }

    /**
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function getOptions(string $access = ''): array
    {
        $name    = Application::getTag() === 'en' ? 'name_en' : 'name_de';
        $options = [];
        foreach (self::getResources($access) as $category) {
            if ($category['active']) {
                $options[] = HTML::option($category['id'], $category[$name]);
            }
        }

        uasort($options, function ($optionOne, $optionTwo) {
            return strcmp($optionOne->text, $optionTwo->text);
        });

        // Any out of sequence indexes cause JSON to treat this as an object
        return array_values($options);
    }

    /**
     * Retrieves the name of the program associated with the category.
     *
     * @param   int  $categoryID  the id of the program
     *
     * @return string
     */
    public static function getProgramName(int $categoryID): string
    {
        $noName = Text::_('ORGANIZER_NO_PROGRAM');

        if (!$categoryID) {
            return $noName;
        }

        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('id'))
            ->from(DB::qn('#__organizer_programs'))
            ->where(DB::qn('categoryID') . ' = :categoryID')
            ->bind(':categoryID', $categoryID, ParameterType::INTEGER);
        DB::setQuery($query);

        if ($programIDs = DB::loadIntColumn()) {
            return count($programIDs) > 1 ?
                Text::_('ORGANIZER_MULTIPLE_PROGRAMS') : Programs::getName($programIDs[0]);
        }

        return $noName;
    }

    /**
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function getResources(string $access = ''): array
    {
        $order = Application::getTag() === 'en' ? 'name_en' : 'name_de';
        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('c') . '*')
            ->from(DB::qn('#__organizer_categories', 'c'))
            ->order($order);

        if (!empty($access)) {
            self::filterAccess($query, $access, 'category', 'c');
        }

        self::filterOrganizations($query, 'category', 'c');
        DB::setQuery($query);

        return DB::loadAssocList('id');
    }
}
