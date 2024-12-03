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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use THM\Organizer\Adapters\{Application, HTML};
use THM\Organizer\Views\HTML\ListView;

/**
 * Class provides standardized rendering functions for table cells in list views.
 */
class Row
{
    private const ADMIN = true;

    // General
    public const NO = 0, YES = 1;

    public const DIRECT = 1, TAB = 2;
    public const LINK_TYPES = [self::DIRECT, self::NO, self::TAB];

    /**
     * Renders a check all box style list header.
     *
     * @param   int     $rowNo  the row iteration count
     * @param   object  $item   the item being rendered
     */
    private static function check(int $rowNo, object $item): void
    {
        ?>
        <td class="text-center">
            <?php echo empty($item->access) ? '' : HTML::checkBox($rowNo, $item->id); ?>
        </td>
        <?php
    }

    /**
     * Renders a sorting tool.
     *
     * @param   object  $item     the item being rendered
     * @param   bool    $enabled  whether sorting has been enabled
     */
    private static function ordering(object $item, bool $enabled): void
    {
        $properties = ['class' => 'sortable-handler'];

        if (!$item->access) {
            $properties['class'] .= ' inactive';
        }
        elseif (!$enabled) {
            $properties['class'] .= ' inactive';
            $properties['title'] = Text::_('JORDERINGDISABLED');
        }

        $item->properties = $properties;

        ?>
        <td class="text-center d-none d-md-table-cell">
            <span <?php echo HTML::properties($item); ?>>
                <span class="icon-ellipsis-v"></span>
            </span>
            <?php if ($item->access and $enabled) : ?>
                <!--suppress HtmlFormInputWithoutLabel -->
                <input type="text" class="hidden" name="order[]" size="5"
                       value="<?php echo $item->ordering; ?>">
            <?php endif; ?>
        </td>
        <?php
    }

    /**
     * Renders a list item.
     *
     * @param   ListView    $view         the view being rendered
     * @param   int|string  $index        the row index being rendered
     * @param   object      $item         the item being rendered
     * @param   bool        $dragEnabled  whether the table has drag enabled
     */
    public static function render(ListView $view, int|string $index, object $item, bool $dragEnabled = false): void
    {
        $dragAttributes = '';

        // The row attributes seem to tell the row in which context it can be dragged.
        if ($dragEnabled) {
            $dragAttributes .= " data-draggable-group=\"1\"";
            $dragAttributes .= " data-level=\"1\"";
            $dragAttributes .= " data-item-id=\"$item->id\"";
            $dragAttributes .= " data-parents=\"\"";
        }
        ?>
        <tr <?php echo $dragAttributes ?>>
            <?php

            $headers = (!isset($view->colScope) or $view->colScope === false) ? $view->headers : $view->headers[$view->colScope];
            foreach ($headers as $column => $header) {
                $linkType = (!empty($header['link']) and in_array($header['link'], self::LINK_TYPES)) ?
                    $header['link'] : self::NO;

                $header['properties'] = $header['properties'] ?? [];
                switch ($header['type']) {
                    case 'check':
                        self::check($index, $item);
                        break;
                    case 'header':
                        self::text($item, $column, Application::backend(), $linkType, false, true);
                        break;
                    case 'ordering':
                        self::ordering($item, $dragEnabled);
                        break;
                    case 'text':
                        $localize = (!empty($header['localize']) and $header['localize'] == self::YES) ? self::YES : self::NO;
                        self::text($item, $column, Application::backend(), $linkType, $localize);
                        break;
                    case 'sort':
                    case 'value':
                    default:
                        self::text($item, $column, Application::backend(), $linkType);
                        break;
                }
            }
            ?>
        </tr>
        <?php
    }

    /**
     * Renders a check all box style list header.
     *
     * @param   object  $item            the current row item
     * @param   string  $column          the current column
     * @param   bool    $administration  the display context (false: public, true: admin)
     * @param   int     $linkType        the link type to use for the displayed column value
     */
    private static function text(object $item,
        string $column,
        bool $administration,
        int $linkType,
        bool $localize = false,
        bool $header = false): void
    {
        $tip   = '';
        $value = $item->$column ?? '';

        if (is_array($value)) {
            $properties = HTML::properties($value);

            if (!empty($value['tip'])) {
                $tip = $value['tip'];
            }

            $value = $value['value'];
        }
        else {
            $properties = '';
        }

        if ($main = $column === 'name' or $header) {
            $opener = "<th $properties scope=\"row\">";
            $closer = "</th>";
        }
        else {
            $opener = "<td $properties>";
            $closer = "</td>";
        }

        echo $opener;

        if ($tip) {
            $context = "$item->id-$column";
            $tip     = '<div role="tooltip" id="' . $context . '">' . $tip . '</div>';
            echo '<div aria-describedby="tip-' . $context . '">';
        }

        if ($main and !empty($item->prefix)) {
            echo $item->prefix;
        }

        if ($linkType and !empty($item->url)) {

            $attributes = $linkType === self::TAB ? ['target' => '_blank'] : [];
            $editURL    = Route::_("$item->url&layout=edit");
            $url        = Route::_($item->url);
            $value      = $localize ? Text::_($value) : $value;

            if (empty($item->access)) {
                echo HTML::link($url, $value, $attributes);
            }
            elseif ($administration === self::ADMIN) {
                echo HTML::link($editURL, $value, $attributes);
            }
            else {
                echo HTML::link($url, $value, $attributes) . ' ' . HTML::link($editURL, HTML::icon('fa fa-edit'), $attributes);
            }
        }
        else {
            echo $value;
        }

        if ($main and !empty($item->icon)) {
            echo $item->icon;
        }

        if ($main and !empty($item->supplement)) {
            echo "<br><span class=\"small\">$item->supplement</span>";
        }

        if ($tip) {
            echo '</div>' . $tip;
        }

        echo $closer;
    }
}