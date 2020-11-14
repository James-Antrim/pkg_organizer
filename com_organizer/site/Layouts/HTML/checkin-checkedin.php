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

$instance = $this->instances[0];
?>
<form action="#" id="adminForm" method="post" name="adminForm" class="form-horizontal">
    <div class="control-group message"><?php echo Helpers\Languages::_('ORGANIZER_CHECKED_INTO'); ?></div>
    <div class="control-group message"><?php echo $instance['name']; ?></div>
	<?php if ($instance['method']): ?>
        <div class="control-group message"><?php echo $instance['method']; ?></div>
	<?php endif; ?>
    <div class="control-group message"><?php echo $instance['startTime'] . ' - ' . $instance['endTime']; ?></div>
</form>
