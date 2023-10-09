<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\HTML;

use THM\Organizer\Helpers;

$count     = 0;
$dates     = [];
$rowNumber = 0;
?>
<script type="text/javascript">
    let timer = null;

    function auto_reload() {
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
            <div class='time'><?php echo date('H:i'); ?></div>
            <div class='date'><?php echo date('d.m.Y'); ?></div>
        </div>
    </div>
    <div class="instances upcoming-layout">
        <div class="explanation"><?php echo Helpers\Languages::_('ORGANIZER_NEXT_4'); ?></div>
        <?php foreach ($this->model->instances as $instance) {
            if ($count >= 4) {
                break;
            }

            $newHead       = !in_array($instance['date'], $dates);
            $closePrevious = ($newHead and count($dates));

            if ($closePrevious) {
                echo '</div>';
            }
            if ($newHead) {
                $date      = Helpers\Dates::formatDate($instance['date']);
                $dates[]   = $instance['date'];
                $rowNumber = 0;

                echo '<div class="date-container">';
                echo "<div class=\"date-header\"><span>$date</span></div>";
            }

            $rowClass = 'row' . ($rowNumber % 2);
            $rowNumber++;

            if (empty($instance['comment'])) {
                $paddingClass = 'fluffy';
            } else {
                $paddingClass        = '';
                $instance['comment'] = $this->processComment($instance['comment']);
            }

            $event = empty($instance['method']) ? $instance['event'] : "{$instance['event']} - {$instance['method']}"
            ?>
            <div class="<?php echo $rowClass; ?> ym-clearfix instance">
                <div class="block-times">
                    <?php echo Helpers\Dates::formatTime($instance['startTime']); ?><br>
                    -<br>
                    <?php echo Helpers\Dates::formatEndTime($instance['endTime']); ?>
                </div>
                <div class="instance-display">
                    <div class="event-names <?php echo $paddingClass; ?>">
                        <?php echo $event; ?>
                    </div>
                    <div class="instance-persons"><?php echo implode(' / ', $instance['persons']); ?></div>
                    <?php
                    if (!empty($instance['comment'])) {
                        ?>
                        <div class="unit-comment">
                            (<?php echo $instance['comment']; ?>)
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
            $count++;
        }
        ?>
    </div>
</div>
