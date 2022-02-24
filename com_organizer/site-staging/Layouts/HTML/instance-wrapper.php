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

$action = Uri::base() . '?' . Uri::getInstance()->getQuery();
$oClass = "form-$this->orientation";
$layout = Helpers\Input::getCMD('type', 'appointment');

if (!$this->adminContext)
{
	echo $this->title;
	echo $this->subtitle;
	echo $this->supplement;
}
?>
<div id="j-main-container" class="span10">
	<?php if (!$this->adminContext): ?>
		<?php echo Toolbar::getInstance()->render(); ?>
	<?php endif; ?>
    <form action="<?php echo $action; ?>" id="adminForm" method="post" name="adminForm" enctype="multipart/form-data"
          class="<?php echo $oClass; ?> form-validate">
		<?php if ($layout === 'advanced') : ?>
            // Advanced instance edit layout
			<?php //require_once 'Instance/advanced.php'; ?>
		<?php elseif ($layout === 'appointment') : ?>
			<?php require_once 'Instance/appointment.php'; ?>
		<?php else : ?>
            // Simple instance edit layout
			<?php //require_once 'Instance/simple.php'; ?>
		<?php endif; ?>
        <input type="hidden" name="Itemid" value="<?php echo Helpers\Input::getInt('Itemid'); ?>"/>
        <input type="hidden" name="option" value="com_organizer"/>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="view" value="InstanceEdit"/>
		<?php echo Helpers\HTML::_('form.token'); ?>
    </form>
</div>
