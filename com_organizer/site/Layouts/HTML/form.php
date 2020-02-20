<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers;

$isSite = Helpers\OrganizerHelper::getApplication()->isClient('site');
$query  = Uri::getInstance()->getQuery();

if ($isSite)
{
	echo Helpers\OrganizerHelper::getApplication()->JComponentTitle;
	echo $this->subtitle;
	echo $this->supplement;
}
?>
<div id="j-main-container" class="span10">
	<?php if ($isSite) : ?>
		<?php echo Toolbar::getInstance()->render(); ?>
	<?php endif; ?>
    <form action="<?php echo Uri::base() . "?$query"; ?>" id="adminForm" method="post" name="adminForm"
          class="form-horizontal form-validate" enctype="multipart/form-data">
		<?php echo $this->form->renderFieldset('details'); ?>
		<?php echo Helpers\HTML::_('form.token'); ?>
        <input type="hidden" name="task" value=""/>
    </form>
</div>
