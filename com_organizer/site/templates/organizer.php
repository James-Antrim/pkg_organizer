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
use THM\Organizer\Adapters\HTML;

$query = Uri::getInstance()->getQuery();
?>
<form action="<?php echo Uri::base() . "?$query"; ?>" id="adminForm" method="post"
      name="adminForm">
    <div class="row">
        <div id="j-sidebar-container" class="col-md-2">
            <?php echo $this->sidebar; ?>
        </div>
        <div class="col-md-10 right">
            <?php $this->renderTasks(); ?>
        </div>
        <input type="hidden" name="option" value="com_organizer"/>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="view" value="organizer"/>
        <?php echo HTML::token(); ?>
    </div>
</form>
