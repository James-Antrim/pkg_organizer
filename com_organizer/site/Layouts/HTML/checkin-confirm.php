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

$link = Uri::base() . '?option=com_organizer&task=checkin.confirm&id=';
?>
<form action="#" id="adminForm" method="post" name="adminForm" class="form-horizontal">
    <div class="control-group message"><?php echo Helpers\Languages::_('ORGANIZER_CONFIRM_EVENT'); ?></div>
	<?php foreach ($this->instances as $instance): ?>
        <div class="control-group">
            <div class="control-label inverse"><?php echo $instance['name']; ?></div>
            <div class="controls inverse">
                <a class="btn" href="<?php echo $link . $instance['instanceID']; ?>">
					<?php echo Helpers\Languages::_('ORGANIZER_CONFIRM'); ?>
                </a>
            </div>
        </div>
	<?php endforeach; ?>
</form>
