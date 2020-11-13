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

?>
<div class='head'>
    <div class='banner'>
        <div class='logo'><img src="components/com_organizer/images/logo.svg" alt="THM-Logo"/></div>
    </div>
</div>
<?php echo Helpers\OrganizerHelper::getApplication()->JComponentTitle; ?>
<div id="j-main-container" class="span10">
	<?php require_once 'checkin-checkin.php'; ?>
</div>
<div class="tail"></div>
