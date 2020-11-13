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

$isComponent = Helpers\Input::getCMD('tmpl') === 'component';
?>
<form action="<?php echo Uri::base(); ?>" id="adminForm" method="post" name="adminForm"
      class="form-horizontal form-validate" enctype="multipart/form-data" xmlns="http://www.w3.org/1999/html">
	<?php if (Helpers\Users::getID()): ?>
		<?php if ($isComponent): ?>
            <div class="control-group message"><?php echo Helpers\Languages::_('ORGANIZER_LOGGED_IN'); ?></div>
		<?php endif; ?>
	<?php else: ?>
		<?php echo $this->form->renderField('username'); ?>
		<?php echo $this->form->renderField('password'); ?>
	<?php endif; ?>
	<?php echo $this->form->renderField('code'); ?>
	<?php if ($isComponent): ?>
        <div class="control-group">
            <input class="btn" type="submit" value="<?php echo Helpers\Languages::_('ORGANIZER_CHECKIN'); ?>"/>
        </div>
	<?php endif; ?>
    <input type="hidden" name="option" value="com_organizer"/>
    <input type="hidden" name="task" value="checkin.checkin"/>
    <input type="hidden" name="view" value="checkin"/>
	<?php echo Helpers\HTML::_('form.token'); ?>
</form>
