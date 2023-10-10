<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\Text;

$instance = $this->instances[0];
$room     = '';

if (!empty($instance['room'])) {
    $room .= $instance['room'];

    if (!empty($instance['seat'])) {
        $room .= ", {$instance['seat']}";
    }
}

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
<form action="#" id="adminForm" method="post" name="adminForm" class="form-vertical checkedin">
    <div class="control-group message"><?php echo Text::_('ORGANIZER_CHECKED_INTO'); ?></div>
    <div class="control-group message"><b><?php echo $instance['name']; ?></b></div>
    <?php if ($instance['method']): ?>
        <div class="control-group message"><?php echo $instance['method']; ?></div>
    <?php endif; ?>
    <?php if ($room): ?>
        <div class="control-group message">
            <?php echo $room; ?>
        </div>
    <?php endif; ?>
    <div class="control-group message"><?php echo $instance['startTime'] . ' - ' . $instance['endTime']; ?></div>
    <div class="control-group message"><?php echo Text::_('ORGANIZER_CHECKOUT_REMINDER'); ?></div>
    <div class="control-group">
        <a class="btn" href="<?php echo Uri::getInstance() . '&layout=profile' ?>">
            <?php echo Text::_('ORGANIZER_PROFILE_EDIT'); ?>
        </a>
    </div>
</form>
