<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use THM\Organizer\Adapters\Text;

$headers    = array_shift($this->grid);
$columns    = array_keys($headers);
$count      = count($columns);
$lastColumn = end($columns);
$sums       = array_shift($this->grid);
$template   = '<span aria-hidden="true" class="hasTooltip" title="XTOOLTIPX">VALUE %</span>';

echo "<div class=\"statistics-grid presence-type columns-$count\">";

foreach ($headers as $key => $header) {
    $class = 'header-row';
    $class .= $key === $lastColumn ? ' row-end' : '';
    $class .= $key === 'sum' ? ' sum-column' : '';

    if ($key !== 'week') {
        $class .= ' header-column';
    }

    echo "<div class=\"$class\">$header</div>";
}

foreach ($sums as $key => $sum) {
    $class = 'sum-row';

    if ($key === 'week') {
        $class .= ' header-column';
    }
    else {
        $class .= $key === 'sum' ? ' sum-column' : '';
        $class .= $key === $lastColumn ? ' row-end' : '';

        $attended = empty($sum['attended']) ? 0 : $sum['attended'];
        $capacity = empty($sum['capacity']) ? 0 : $sum['capacity'];
        $total    = empty($sum['total']) ? 0 : $sum['total'];

        if ($capacity) {
            $percent = (int) (($attended / $capacity) * 100);

            if ($total === $attended) {
                $tip = Text::sprintf('ORGANIZER_PRESENCE_USE_TIP', $total, $capacity, $percent);
            }
            else {
                $tip = Text::sprintf('ORGANIZER_PRESENCE_USE_TIP_LONG', $attended, $total, $capacity, $percent);
            }

            $sum = str_replace('VALUE', $percent, $template);
            $sum = str_replace('XTOOLTIPX', $tip, $sum);
        }
        elseif ($total) {
            $tip = Text::sprintf('ORGANIZER_PRESENCE_USE_TIP_SHORT', $total);
            $sum = str_replace('VALUE', $total, $template);
            $sum = str_replace('XTOOLTIPX', $tip, $sum);
        }
        else {
            $sum = '-';
        }
    }

    echo "<div class=\"$class\">$sum</div>";
}

foreach ($this->grid as $row) {
    foreach ($row as $key => $sum) {
        $class = 'data-row';

        if ($key === 'week') {
            $class .= ' header-column';
            Text::unpack($sum);
        }
        else {
            $class .= $key === 'sum' ? ' sum-column' : ' data-column';

            $attended = empty($sum['attended']) ? 0 : $sum['attended'];
            $capacity = empty($sum['capacity']) ? 0 : $sum['capacity'];
            $total    = empty($sum['total']) ? 0 : $sum['total'];

            if ($capacity) {
                $percent = (int) (($attended / $capacity) * 100);

                if ($total === $attended) {
                    $tip = Text::sprintf('ORGANIZER_PRESENCE_USE_TIP', $total, $capacity, $percent);
                }
                else {
                    $tip = Text::sprintf('ORGANIZER_PRESENCE_USE_TIP_LONG', $attended, $total, $capacity, $percent);
                }

                $sum = str_replace('VALUE', $percent, $template);
                $sum = str_replace('XTOOLTIPX', $tip, $sum);
            }
            elseif ($total) {
                $tip = Text::sprintf('ORGANIZER_PRESENCE_USE_TIP_SHORT', $total);
                $sum = str_replace('VALUE', $total, $template);
                $sum = str_replace('XTOOLTIPX', $tip, $sum);
            }
            else {
                $sum = '-';
            }
        }

        $class .= $key === $lastColumn ? ' row-end' : '';

        echo "<div class=\"$class\">$sum</div>";
    }
}

echo "</div>";
