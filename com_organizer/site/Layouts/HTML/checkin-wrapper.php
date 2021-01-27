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
	<?php if ($this->edit or ($count and !$this->complete)) : ?>
		<?php require_once 'Checkin/contact.php'; ?>
	<?php elseif ($count and $count > 1) : ?>
		<?php require_once 'Checkin/confirm.php'; ?>
	<?php elseif ($count and $count === 1) : ?>
		<?php require_once 'Checkin/checkedin.php'; ?>
	<?php else : ?>
		<?php require_once 'Checkin/checkin.php'; ?>
	<?php endif; ?>
</div>
