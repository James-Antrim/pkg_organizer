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
use THM\Organizer\Adapters\{Application, HTML};
use THM\Organizer\Views\HTML\ListView;

class ListItem
{
    private const ADMIN = true;

    public const DIRECT = 1, NONE = 0, TAB = 2;
    public const LINK_TYPES = [self::DIRECT, self::NONE, self::TAB];

    /**
     * Renders a check all box style list header.
     *
     * @param int    $rowNo the row iteration count
     * @param object $item  the item being rendered
     */
    private static function check(int $rowNo, object $item): void
    {
        ?>
        <td class="text-center">
            <?php echo HTML::_('grid.id', $rowNo, $item->id, false, 'cid', 'cb', $item->name); ?>
        </td>
        <?php
    }

    /**
     * Renders a sorting tool.
     *
     * @param object $item    the item being rendered
     * @param bool   $enabled whether sorting has been enabled
     */
    private static function ordering(object $item, bool $enabled): void
    {
        $attributes = ['class' => 'sortable-handler'];

        if (!$item->access) {
            $attributes['class'] .= ' inactive';
        } elseif (!$enabled) {
            $attributes['class'] .= ' inactive';
            $attributes['title'] = Text::_('JORDERINGDISABLED');
        }

        $properties = HTML::toProperties($attributes);
        ?>
        <td class="text-center d-none d-md-table-cell">
            <span <?php echo $properties ?>>
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
     * @param ListView $view        the view being rendered
     * @param int      $rowNo       the row number being rendered
     * @param object   $item        the item being rendered
     * @param bool     $dragEnabled whether the table has drag enabled
     */
    public static function render(ListView $view, int $rowNo, object $item, bool $dragEnabled = false): void
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

            foreach ($view->headers as $column => $header) {
                $linkType = (!empty($header['link']) and in_array($header['link'], self::LINK_TYPES)) ?
                    $header['link'] : self::NONE;

                $header['properties'] = $header['properties'] ?? [];
                switch ($header['type']) {
                    case 'check':
                        self::check($rowNo, $item);
                        break;
                    case 'ordering':
                        self::ordering($item, $dragEnabled);
                        break;
                    case 'sort':
                    case 'text':
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
     * @param object $item     the current row item
     * @param string $column   the current column
     * @param bool   $context  the display context (false: public, true: admin)
     * @param int    $linkType the link type to use for the displayed column value
     */
    private static function text(object $item, string $column, bool $context, int $linkType): void
    {
        $value = $item->$column ?? '';

        if (is_array($value)) {
            $properties = HTML::toProperties($value['properties']);
            $value      = $value['value'];
        } else {
            $properties = '';
        }

        if ($main = $column === 'name') {
            $opener = "<th $properties scope=\"row\">";
            $closer = "</th>";
        } else {
            $opener = "<td $properties>";
            $closer = "</td>";
        }

        $linkOpen  = '';
        $linkClose = '';

        if ($linkType) {
            $editLink = $item->editLink ?? '';
            $viewLink = $item->viewLink ?? '';
            if ($url = $context === self::ADMIN ? $editLink : $viewLink) {
                $lProperties = ['href' => $url];

                if ($linkType === self::TAB) {
                    $lProperties['target'] = '_blank';
                }

                $linkOpen  = '<a ' . HTML::toProperties($lProperties) . '>';
                $linkClose = '</a>';
            }
        }

        echo $opener;

        if ($main and !empty($item->prefix)) {
            echo $item->prefix;
        }

        echo $linkOpen . $value . $linkClose;

        if ($main and !empty($item->icon)) {
            echo $item->icon;
        }

        if ($main and !empty($item->supplement)) {
            echo "<br><span class=\"small\">$item->supplement</span>";
        }

        if ($main and isset($item->requireReset) and $item->requireReset === 1) {
            echo '<span class="badge bg-warning text-dark">' . Text::_('GROUPS_RESET_REQUIRED') . '</span>';
        }

        echo $closer;
    }
}