<?php
/**
 * @package     Groups
 * @extension   com_groups
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\HTML;

use Joomla\CMS\Language\Text;
use THM\Organizer\Adapters\HTML;
use THM\Organizer\Views\HTML\ListView;

class ListHeaders
{
    /**
     * Renders a check all box style list header.
     */
    private static function check(): void
    {
        ?>
        <th class="w-1 text-center">
            <?php echo HTML::_('grid.checkall'); ?>
        </th>
        <?php
    }

    /**
     * Renders an icon for the ordering column header.
     */
    private static function ordering(): void
    {
        ?>
        <th class="w-1 text-center d-none d-md-table-cell" scope="col">
            <?php echo HTML::icon('fa fa-arrows-alt-v'); ?>
        </th>
        <?php
    }

    /**
     * Renders list headers.
     *
     * @param ListView $view the view being displayed
     */
    public static function render(ListView $view): void
    {
        $state     = $view->get('state');
        $direction = $view->escape($state->get('list.direction', 'ASC'));
        $column    = $view->escape($state->get('list.ordering'));

        ?>
        <thead>
        <tr>
            <?php
            foreach ($view->headers as $header) {
                $header['properties'] = $header['properties'] ?? [];
                switch ($header['type']) {
                    case 'check':
                        self::check();
                        break;
                    case 'ordering':
                        self::ordering();
                        break;
                    case 'sort':
                        self::sort($header['properties'], $header['title'], $header['column'], $column, $direction);
                        break;
                    case 'text':
                    default:
                        self::text($header['properties'], $header['title']);
                        break;
                }
            }
            ?>
        </tr>
        </thead>
        <?php
    }

    /**
     * Renders a check all box style list header.
     *
     * @param array  $properties the properties for the containing tag
     * @param string $title      the title text to display
     * @param string $column     the table column represented by the data displayed in this column
     * @param string $orderBy    the column the results are currently ordered by
     * @param string $direction  the current sort direction
     */
    private static function sort(array $properties, string $title, string $column, string $orderBy, string $direction): void
    {
        $properties = HTML::toProperties($properties);
        ?>
        <th <?php echo $properties; ?>>
            <?php echo HTML::_('searchtools.sort', $title, $column, $direction, $orderBy); ?>
        </th>
        <?php
    }

    /**
     * Renders a check all box style list header.
     *
     * @param array  $properties the properties for the containing tag
     * @param string $title      the title text to display. optional for default processing
     */
    private static function text(array $properties, string $title = ''): void
    {
        $properties = HTML::toProperties($properties);
        ?>
        <th <?php echo $properties; ?>>
            <?php echo Text::_($title); ?>
        </th>
        <?php
    }
}