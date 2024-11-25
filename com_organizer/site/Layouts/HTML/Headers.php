<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\HTML;

use THM\Organizer\Adapters\HTML;
use THM\Organizer\Views\HTML\ListView;

/**
 * Class provides standardized rendering functions for table headers in list views.
 */
class Headers
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
     * @param   ListView  $view  the view being displayed
     */
    public static function render(ListView $view): void
    {
        $state     = $view->get('state');
        $direction = $view->escape($state->get('list.direction'));
        $column    = $view->escape($state->get('list.ordering'));

        echo '<thead>';
        if (!isset($view->colScope) or $view->colScope === false) {
            self::renderRow($view->headers, $column, $direction);
        }
        else {
            foreach ($view->headers as $index => $row) {
                self::renderRow($row, $column, $direction, $index);
            }
        }
        echo '</thead>';
    }

    /**
     * Renders an individual list header row.
     *
     * @param   array       $row        the row headers
     * @param   string      $column     the column that the results are being sorted by
     * @param   string      $direction  the current
     * @param   int|string  $rIndex     the index of the header row among header rows
     *
     * @return void
     */
    private static function renderRow(array $row, string $column, string $direction = 'ASC', int|string $rIndex = ''): void
    {
        echo '<tr>';
        foreach ($row as $cIndex => $header) {
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
                    if (!empty($header['tip'])) {
                        $context = $rIndex ? "context-$rIndex-$cIndex" : "context-$cIndex";
                        self::tip($header['properties'], $header['title'], $header['tip'], $context);
                    }
                    else {
                        self::text($header['properties'], $header['title']);
                    }
                    break;
            }
        }
        echo '</tr>';
    }

    /**
     * Renders a check all box style list header.
     *
     * @param   array   $properties  the properties for the containing tag
     * @param   string  $title       the title text to display
     * @param   string  $column      the table column represented by the data displayed in this column
     * @param   string  $orderBy     the column the results are currently ordered by
     * @param   string  $direction   the current sort direction
     */
    private static function sort(array $properties, string $title, string $column, string $orderBy, string $direction): void
    {
        ?>
        <th <?php echo HTML::toString($properties); ?>>
            <?php echo HTML::sort($title, $column, $direction, $orderBy); ?>
        </th>
        <?php
    }

    /**
     * Renders a check all box style list header.
     *
     * @param   array   $properties  the properties for the containing tag
     * @param   string  $title       the title text to display. optional for default processing
     */
    private static function text(array $properties, string $title = ''): void
    {
        ?>
        <th <?php echo HTML::toString($properties); ?>>
            <?php echo $title; ?>
        </th>
        <?php
    }

    /**
     * Renders the cell information with a tooltip.
     *
     * @param   array   $properties  the properties for the containing tag
     * @param   string  $title       the information to display in the table
     * @param   string  $tip         the information to display as the tip to the information in the table
     * @param   string  $context     the unique context used for the tip to reference the information
     *
     * @return void
     */
    private static function tip(array $properties, string $title, string $tip, string $context): void
    {
        ?>
        <th <?php echo HTML::toString($properties); ?>>
            <?php echo HTML::tip($title, $context, $tip); ?>
        </th>
        <?php
    }
}