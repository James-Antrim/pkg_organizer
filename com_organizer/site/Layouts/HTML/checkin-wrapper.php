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
<div class='head'>
    <div class='banner'>
        <div class='logo'>
            <img aria-hidden="true" src="components/com_organizer/images/logo.svg" alt="THM-Logo"/>
        </div>
    </div>
</div>
<?php echo Helpers\OrganizerHelper::getApplication()->JComponentTitle; ?>
<div id="j-main-container" class="span10">
	<?php if ($this->privacy) : ?>
		<?php require_once 'Checkin/privacy.php'; ?>
	<?php elseif (!$count) : ?>
		<?php require_once 'Checkin/checkin.php'; ?>
	<?php elseif ($count > 1) : ?>
		<?php require_once 'Checkin/instance.php'; ?>
	<?php elseif (!$this->roomID or $this->seat === null) : ?>
		<?php require_once 'Checkin/seating.php'; ?>
	<?php elseif ($this->edit or !$this->complete) : ?>
		<?php require_once 'Checkin/contact.php'; ?>
	<?php else : ?>
		<?php require_once 'Checkin/checkedin.php'; ?>
	<?php endif; ?>
</div>
