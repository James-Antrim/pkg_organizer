<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use THM\Organizer\Adapters\{HTML, Text};
use THM\Organizer\Helpers\Instances;

$headers    = array_shift($this->grid);
$columns    = array_keys($headers);
$count      = count($columns);
$lastColumn = end($columns);
$sums       = array_shift($this->grid);

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

$sumIcon = HTML::tip('<span>&Sigma;</span>', 'sum', 'SUM');

foreach ($sums as $key => $sum) {
    $class = 'sum-row';

    if ($key === 'week') {
        $class .= ' header-column';
    }
    else {
        $class .= $key === 'sum' ? ' sum-column' : '';
        $class .= $key === $lastColumn ? ' row-end' : '';

        $hybrid   = empty($sum[Instances::HYBRID]) ? 0 : $sum[Instances::HYBRID];
        $online   = empty($sum[Instances::ONLINE]) ? 0 : $sum[Instances::ONLINE];
        $presence = empty($sum[Instances::PRESENCE]) ? 0 : $sum[Instances::PRESENCE];
        $total    = empty($sum['total']) ? 0 : $sum['total'];

        if ($total) {
            $sum = "<div>$sumIcon $total</div>";

            $context = "sum-row-$key";
            $icon    = HTML::tip(HTML::icon('fa fa-user'), "$context-presence", 'PRESENCE' . ": $presence / $total");
            $percent = (int) (($presence / $total) * 100);
            $sum     .= "<div>$icon $percent%</div>";

            $icon    = HTML::icon('fa fa-external-link-square-alt');
            $icon    = HTML::tip($icon, "$context-hybrid", 'HYBRID' . ": $hybrid / $total");
            $percent = (int) (($hybrid / $total) * 100);
            $sum     .= "<div>$icon $percent%</div>";

            $icon    = HTML::tip(HTML::icon('fa fa-laptop'), "$context-online", 'ONLINE' . ": $online / $total");
            $percent = (int) (($online / $total) * 100);
            $sum     .= "<div>$icon $percent%</div>";
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

            $hybrid   = empty($sum[Instances::HYBRID]) ? 0 : $sum[Instances::HYBRID];
            $online   = empty($sum[Instances::ONLINE]) ? 0 : $sum[Instances::ONLINE];
            $presence = empty($sum[Instances::PRESENCE]) ? 0 : $sum[Instances::PRESENCE];
            $total    = empty($sum['total']) ? 0 : $sum['total'];

            if ($total) {
                $sum = "<div>$sumIcon $total</div>";

                $context = "sum-column-$key";
                $icon    = HTML::tip(HTML::icon('fa fa-user'), "$context-presence", 'PRESENCE' . ": $presence / $total");
                $percent = (int) (($presence / $total) * 100);
                $sum     .= "<div>$icon $percent%</div>";

                $icon    = HTML::icon('fa fa-external-link-square-alt');
                $icon    = HTML::tip($icon, "$context-hybrid", 'HYBRID' . ": $hybrid / $total");
                $percent = (int) (($hybrid / $total) * 100);
                $sum     .= "<div>$icon $percent%</div>";

                $icon    = HTML::tip(HTML::icon('fa fa-laptop'), "$context-online", 'ONLINE' . ": $online / $total");
                $percent = (int) (($online / $total) * 100);
                $sum     .= "<div>$icon $percent%</div>";
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
