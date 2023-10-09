<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Organizer\Helpers\HTML;
use Organizer\Helpers\Languages;
use Organizer\Helpers\Instances;

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

$sumIcon = '<span class="icon-sum hasTooltip" ' . Languages::_('ORGANIZER_SUM') . '>&sum;</span>';

foreach ($sums as $key => $sum) {
    $class = 'sum-row';

    if ($key === 'week') {
        $class .= ' header-column';
    } else {
        $class .= $key === 'sum' ? ' sum-column' : '';
        $class .= $key === $lastColumn ? ' row-end' : '';

        $hybrid   = empty($sum[Instances::HYBRID]) ? 0 : $sum[Instances::HYBRID];
        $online   = empty($sum[Instances::ONLINE]) ? 0 : $sum[Instances::ONLINE];
        $presence = empty($sum[Instances::PRESENCE]) ? 0 : $sum[Instances::PRESENCE];
        $total    = empty($sum['total']) ? 0 : $sum['total'];

        if ($total) {
            $sum = "<div>$sumIcon $total</div>";

            $presenceIcon = HTML::icon('user', Languages::_('ORGANIZER_PRESENCE') . ": $presence / $total");
            $percent      = (int) (($presence / $total) * 100);
            $sum          .= "<div>$presenceIcon $percent%</div>";

            $hybridIcon = HTML::icon('out-3', Languages::_('ORGANIZER_HYBRID') . ": $hybrid / $total");
            $percent    = (int) (($hybrid / $total) * 100);
            $sum        .= "<div>$hybridIcon $percent%</div>";

            $onlineIcon = HTML::icon('laptop', Languages::_('ORGANIZER_ONLINE') . ": $online / $total");
            $percent    = (int) (($online / $total) * 100);
            $sum        .= "<div>$onlineIcon $percent%</div>";
        } else {
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
            Languages::unpack($sum);
        } else {
            $class .= $key === 'sum' ? ' sum-column' : ' data-column';

            $hybrid   = empty($sum[Instances::HYBRID]) ? 0 : $sum[Instances::HYBRID];
            $online   = empty($sum[Instances::ONLINE]) ? 0 : $sum[Instances::ONLINE];
            $presence = empty($sum[Instances::PRESENCE]) ? 0 : $sum[Instances::PRESENCE];
            $total    = empty($sum['total']) ? 0 : $sum['total'];

            if ($total) {
                $sum = "<div>$sumIcon $total</div>";

                $presenceIcon = HTML::icon('user', Languages::_('ORGANIZER_PRESENCE') . ": $presence / $total");
                $percent      = (int) (($presence / $total) * 100);
                $sum          .= "<div>$presenceIcon $percent%</div>";

                $hybridIcon = HTML::icon('out-3', Languages::_('ORGANIZER_HYBRID') . ": $hybrid / $total");
                $percent    = (int) (($hybrid / $total) * 100);
                $sum        .= "<div>$hybridIcon $percent%</div>";

                $onlineIcon = HTML::icon('laptop', Languages::_('ORGANIZER_ONLINE') . ": $online / $total");
                $percent    = (int) (($online / $total) * 100);
                $sum        .= "<div>$onlineIcon $percent%</div>";
            } else {
                $sum = '-';
            }
        }

        $class .= $key === $lastColumn ? ' row-end' : '';

        echo "<div class=\"$class\">$sum</div>";
    }
}

echo "</div>";
