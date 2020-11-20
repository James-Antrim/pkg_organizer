<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Organizer\Helpers;

$count = count($this->instances);
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
<div class='head'>
    <div class='banner'>
        <div class='logo'><img src="components/com_organizer/images/logo.svg" alt="THM-Logo"/></div>
    </div>
</div>
<?php echo Helpers\OrganizerHelper::getApplication()->JComponentTitle; ?>
<div id="j-main-container" class="span10">
	<?php if ($count and !$this->complete) : ?>
		<?php require_once 'checkin-contact.php'; ?>
	<?php elseif ($count and $count > 1) : ?>
		<?php require_once 'checkin-confirm.php'; ?>
	<?php elseif ($count and $count === 1) : ?>
		<?php require_once 'checkin-checkedin.php'; ?>
	<?php else : ?>
		<?php require_once 'checkin-checkin.php'; ?>
	<?php endif; ?>
</div>
<div class="tail"></div>
