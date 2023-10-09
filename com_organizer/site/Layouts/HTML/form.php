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
use Organizer\Adapters\Toolbar;
use Organizer\Helpers;

$query = Uri::getInstance()->getQuery();

if (!$this->adminContext) {
    require_once 'titles.php';
}
?>
<div id="j-main-container" class="span10">
    <?php if (!$this->adminContext) : ?>
        <?php echo Toolbar::getInstance()->render(); ?>
    <?php endif; ?>
    <form action="<?php echo Uri::base() . "?$query"; ?>" id="adminForm" method="post" name="adminForm"
          class="form-<?php echo $this->orientation; ?> form-validate" enctype="multipart/form-data">
        <?php echo $this->form->renderFieldset('details'); ?>
        <input type="hidden" name="Itemid" value="<?php echo Helpers\Input::getInt('Itemid'); ?>"/>
        <input type="hidden" name="option" value="com_organizer"/>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="view" value="<?php echo $this->get('name'); ?>"/>
        <?php echo Helpers\HTML::_('form.token'); ?>
    </form>
</div>
