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

use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input};
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Categories extends Scheduled implements Filterable, Selectable
{
    use Active;
    use Numbered;
    use Suppressed;

    protected static string $resource = 'category';

    /**
     * @inheritDoc
     */
    public static function filterBy(DatabaseQuery $query, string $alias, int $resourceID): void
    {
        if ($resourceID === self::UNSELECTED) {
            return;
        }

        $tableID   = DB::qn('categoryAlias.id');
        $condition = DB::qc('categoryAlias.id', "$alias.categoryID");
        $table     = DB::qn("#__organizer_categories", 'categoryAlias');

        if ($resourceID === self::NONE) {
            $query->leftJoin($table, $condition)->where("$tableID.id IS NULL");
            return;
        }

        $query->innerJoin($table, $condition)
            ->where("$tableID = :categoryID")
            ->bind(':categoryID', $resourceID, ParameterType::INTEGER);
    }

    /**
     * Retrieves the groups associated with a category.
     *
     * @param   int   $categoryID  the category id
     * @param   bool  $active      whether to retrieve only active categories
     *
     * @return array[]
     */
    public static function groups(int $categoryID, bool $active = true): array
    {
        $tag   = Application::tag();
        $query = DB::query();
        $query->select(array_merge(DB::qn(['id', 'code']), [DB::qn("name_$tag", 'name')]))
            ->from(DB::qn('#__organizer_groups', 'g'))
            ->where(DB::qn('categoryID') . ' = :categoryID')
            ->bind(':categoryID', $categoryID, ParameterType::INTEGER);

        if ($active) {
            $query->where(DB::qn('active') . ' = 1');
        }

        DB::set($query);

        return DB::arrays();
    }

    /**
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function options(string $access = ''): array
    {
        $name    = Application::tag() === 'en' ? 'name_en' : 'name_de';
        $options = [];
        foreach (self::resources($access) as $category) {
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
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function resources(string $access = ''): array
    {
        $order = Application::tag() === 'en' ? 'name_en' : 'name_de';
        $query = DB::query();
        $query->select('DISTINCT ' . DB::qn('c') . '.*')
            ->from(DB::qn('#__organizer_categories', 'c'))
            ->order($order);

        self::filterByAccess($query, 'c', $access);
        self::filterByOrganization($query, 'c', Input::getInt('organizationID'));
        DB::set($query);

        return DB::arrays('id');
    }
}
