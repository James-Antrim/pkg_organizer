<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Organizer\Helpers\Languages;

$headers    = array_shift($this->grid);
$columns    = array_keys($headers);
$count      = count($columns);
$lastColumn = end($columns);
$sums       = array_shift($this->grid);
$template   = '<span aria-hidden="true" class="hasTooltip" title="XTOOLTIPX">VALUE</span>';

echo "<div class=\"statistics-grid presence-type columns-$count\">";

foreach ($headers as $key => $header)
{
	$class = 'header-row';
	$class .= $key === $lastColumn ? ' row-end' : '';
	$class .= $key === 'sum' ? ' sum-column' : '';

	if ($key !== 'week')
	{
		$class .= ' header-column';
	}

	echo "<div class=\"$class\">$header</div>";
}

foreach ($sums as $key => $sum)
{
	$class = 'sum-row';

	if ($key === 'week')
	{
		$class .= ' header-column';
	}
	else
	{
		$class .= $key === 'sum' ? ' sum-column' : '';
		$class .= $key === $lastColumn ? ' row-end' : '';

		$attended     = empty($sum['attended']) ? 0 : $sum['attended'];
		$noShows      = empty($sum['no-shows']) ? 0 : $sum['no-shows'];
		$registered   = empty($sum['registered']) ? 0 : $sum['registered'];
		$unregistered = empty($sum['unregistered']) ? 0 : $sum['unregistered'];

		if ($attended or $registered)
		{
			$tip = '';

			if ($attended !== $registered)
			{
				$registrations = Languages::_('ORGANIZER_REGISTRATIONS');
				$tip           .= "$registered $registrations <br>";
			}

			$tip .= "$attended " . Languages::_('ORGANIZER_PARTICIPANTS');

			if ($noShows)
			{
				$tip .= "<br>$noShows " . Languages::_('ORGANIZER_NO_SHOWS');
			}

			if ($unregistered)
			{
				$tip .= "<br>$unregistered " . Languages::_('ORGANIZER_UNREGISTERED_PARTICIPANTS');
			}

			$sum = str_replace('VALUE', $registered, $template);
			$sum = str_replace('XTOOLTIPX', $tip, $sum);
		}
		else
		{
			$sum = '-';
		}
	}

	echo "<div class=\"$class\">$sum</div>";
}

foreach ($this->grid as $row)
{
	foreach ($row as $key => $sum)
	{
		$class = 'data-row';

		if ($key === 'week')
		{
			$class .= ' header-column';
			Languages::unpack($sum);
		}
		else
		{
			$class .= $key === 'sum' ? ' sum-column' : ' data-column';

			$attended     = empty($sum['attended']) ? 0 : $sum['attended'];
			$noShows      = empty($sum['no-shows']) ? 0 : $sum['no-shows'];
			$registered   = empty($sum['registered']) ? 0 : $sum['registered'];
			$unregistered = empty($sum['unregistered']) ? 0 : $sum['unregistered'];

			if ($attended or $registered)
			{
				$tip = '';

				if ($attended !== $registered)
				{
					$registrations = Languages::_('ORGANIZER_REGISTRATIONS');
					$tip           .= "$registered $registrations <br>";
				}

				$tip .= "$attended " . Languages::_('ORGANIZER_PARTICIPANTS');

				if ($noShows)
				{
					$tip .= "<br>$noShows " . Languages::_('ORGANIZER_NO_SHOWS');
				}

				if ($unregistered)
				{
					$tip .= "<br>$unregistered " . Languages::_('ORGANIZER_UNREGISTERED_PARTICIPANTS');
				}

				$sum = str_replace('VALUE', $registered, $template);
				$sum = str_replace('XTOOLTIPX', $tip, $sum);
			}
			else
			{
				$sum = '-';
			}
		}

		$class .= $key === $lastColumn ? ' row-end' : '';

		echo "<div class=\"$class\">$sum</div>";
	}
}

echo "</div>";
