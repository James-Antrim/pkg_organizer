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

$link = Uri::base() . '?option=com_organizer&task=checkin.confirmInstance&id=';
?>
<form action="#" id="adminForm" method="post" name="adminForm" class="form-vertical confirm">
    <div class="control-group message"><?php echo Text::_('ORGANIZER_CONFIRM_EVENT_TEXT'); ?></div>
    <?php foreach ($this->instances as $instance): ?>
        <div class="control-group">
            <a class="btn" href="<?php echo $link . $instance['instanceID']; ?>">
                <?php echo $instance['name']; ?>
            </a>
        </div>
    <?php endforeach; ?>
</form>
