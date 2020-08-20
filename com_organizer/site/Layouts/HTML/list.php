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

$columnCount = count($this->headers);
$isSite      = Helpers\OrganizerHelper::getApplication()->isClient('site');
$items       = $this->items;
$iteration   = 0;
$action      = Helpers\OrganizerHelper::dynamic() ? Uri::current() . '?' . Uri::getInstance()->getQuery() : Uri::current();

if ($isSite)
{
	echo Helpers\OrganizerHelper::getApplication()->JComponentTitle;
	echo $this->subtitle;
	echo $this->supplement;
}
if (!empty($this->submenu))
{
	echo '<div id="j-sidebar-container" class="span2">' . $this->submenu . '</div>';
} ?>
<div id="j-main-container" class="span10">
	<?php if ($isSite) : ?>
		<?php echo Toolbar::getInstance()->render(); ?>
	<?php endif; ?>
    <form action="<?php echo $action; ?>" id="adminForm" method="post" name="adminForm">
		<?php require_once 'filters.php'; ?>
        <table class="table table-striped" id="<?php echo $this->get('name'); ?>-list">
            <thead>
            <tr>
				<?php
				foreach ($this->headers as $header)
				{
					$colAttributes = $this->getAttributesOutput($header);
					$colValue      = is_array($header) ? $header['value'] : $header;
					echo "<th $colAttributes>$colValue</th>";
				}
				?>
            </tr>
            </thead>
            <tbody <?php echo $this->getAttributesOutput($items); ?>>
			<?php if (count($items)) : ?>
				<?php foreach ($items as $row) : ?>
                    <tr <?php echo $this->getAttributesOutput($row); ?>>
						<?php
						foreach ($row as $column)
						{
							$colAttributes = $this->getAttributesOutput($column);
							$colValue      = is_array($column) ? $column['value'] : $column;
							echo "<td $colAttributes>$colValue</td>";
						}
						?>
                    </tr>
				<?php endforeach; ?>
			<?php else: ?>
                <tr>
                    <td class="empty-result-set" colspan="<?php echo count($this->headers); ?>">
						<?php echo Helpers\Languages::_('ORGANIZER_EMPTY_RESULT_SET') ?>
                    </td>
                </tr>
			<?php endif; ?>
            <tfoot>
            <tr>
                <td colspan="<?php echo $columnCount; ?>">
					<?php echo $this->pagination->getListFooter(); ?>
            </tr>
            </tfoot>
			<?php
			if (isset($this->batch) && !empty($this->batch))
			{
				foreach ($this->batch as $filename)
				{
					foreach ($this->_path['template'] as $path)
					{
						$exists = file_exists("$path$filename.php");
						if ($exists)
						{
							require_once "$path$filename.php";
							break;
						}
					}
				}
			}
			?>
        </table>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="option" value="com_organizer"/>
        <input type="hidden" name="view" value="<?php echo $this->get('name'); ?>"/>
		<?php echo Helpers\HTML::_('form.token'); ?>
    </form>
	<?php echo $this->disclaimer; ?>
</div>


