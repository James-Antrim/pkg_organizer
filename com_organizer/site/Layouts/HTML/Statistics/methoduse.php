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

echo "<div class=\"statistics-grid methods-use columns-$count\">";

foreach ($headers as $key => $header) {
    $class = 'header-row';
    $class .= $key === $lastColumn ? ' row-end' : '';
    $class .= $key === 'sum' ? ' sum-column' : '';

    if ($key === 'method') {
        Text::unpack($header);
    }
    else {
        $class .= ' header-column';
    }

    echo "<div class=\"$class\">$header</div>";
}

foreach ($sums as $key => $sum) {
    $class = 'sum-row';
    $class .= $key === 'method' ? ' header-column' : '';
    $class .= $key === 'sum' ? ' sum-column' : '';
    $class .= $key === $lastColumn ? ' row-end' : '';
    echo "<div class=\"$class\">$sum</div>";
}

foreach ($this->grid as $row) {
    foreach ($row as $key => $sum) {
        $class = 'data-row';

        if ($key === 'method') {
            $class .= ' header-column';
            Text::unpack($sum);
        }
        elseif ($key === 'sum') {
            $class .= ' sum-column';
        }
        else {
            $class .= ' data-column';
        }

        $class .= $key === $lastColumn ? ' row-end' : '';
        echo "<div class=\"$class\">$sum</div>";
    }
}

echo "</div>";
