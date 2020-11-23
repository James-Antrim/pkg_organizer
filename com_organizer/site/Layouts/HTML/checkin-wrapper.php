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
use Organizer\Helpers;

$count = count($this->instances);
?>
<div class='head'>
    <div class='banner'>
        <div class='logo'>
            <a href="<?php echo Uri::base(); ?>" aria-label="Organizer Home">
                <img aria-hidden="true" src="components/com_organizer/images/logo.svg" alt="THM-Logo"/>
            </a>
        </div>
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
