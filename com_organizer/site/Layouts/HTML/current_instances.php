<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Layouts\HTML;

use Organizer\Helpers;

$time      = date('H:i');
$rowNumber = 0;
?>
<script type="text/javascript">
    let timer = null;

    function auto_reload()
    {
        window.location = document.URL;
    }

    window.onload = function () {
        timer = setTimeout('auto_reload()', 60000);
    }
</script>
<div class='screen'>
    <div class='head'>
        <div class='banner'>
            <div class='logo'><img src="components/com_organizer/images/logo.svg" alt="THM-Logo"/></div>
            <div class="title"><?php echo $this->model->room['name']; ?></div>
        </div>
        <div class='date-info'>
            <div class='time'><?php echo $time; ?></div>
            <div class='date'><?php echo date('d.m.Y'); ?></div>
        </div>
    </div>
    <div class="instances current-layout">
		<?php
		foreach ($this->model->grid as $periodNo => $period)
		{
			$endTime   = Helpers\Dates::formatTime($period['endTime']);
			$rowClass  = 'row' . ($rowNumber % 2);
			$startTime = Helpers\Dates::formatTime($period['startTime']);

			$activeClass  = ($time >= $startTime and $time <= $endTime) ? 'active' : 'inactive';
			$paddingClass = empty($period['comment']) ? 'fluffy' : '';

			$event = implode(' / ', $period['events']);
			$event .= $period['method'] ? " - {$period['method']}" : '';

			?>
            <div class="<?php echo $rowClass . ' ' . $activeClass; ?> ym-clearfix instance">
                <div class="event-times">
					<?php echo "$startTime<br>-<br>$endTime"; ?>
                </div>
                <div class="event-main">
                    <div class="event-names <?php echo $paddingClass; ?>">
						<?php echo $event; ?>
                    </div>
                    <div class="instance-persons"><?php echo implode(' / ', $period['persons']); ?></div>
					<?php if (!empty($period['comment'])): ?>
                        <div class="event-comment">
                            (<?php echo $period['comment']; ?>)
                        </div>
					<?php endif; ?>
                </div>
            </div>
			<?php
			$rowNumber++;
		}
		?>
    </div>
</div>
