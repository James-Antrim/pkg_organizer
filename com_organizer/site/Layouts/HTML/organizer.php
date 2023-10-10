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
use THM\Organizer\Helpers;

$logoURL = 'components/com_organizer/images/organizer.png';
$logo    = Helpers\HTML::_('image', $logoURL, Text::_('ORGANIZER'), ['class' => 'organizer_main_image']);
$query   = Uri::getInstance()->getQuery();
?>
<div id="j-sidebar-container" class="span2">
    <?php echo $this->submenu; ?>
</div>
<div id="j-main-container" class="span10">
    <form action="<?php echo Uri::base() . "?$query"; ?>" id="adminForm" method="post"
          name="adminForm">
        <h2 class="organizer-header">Organizer</h2>
        <input type="hidden" name="option" value="com_organizer"/>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="view" value="organizer"/>
        <?php echo Helpers\HTML::_('form.token'); ?>
    </form>
</div>
