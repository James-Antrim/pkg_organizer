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

$query = Uri::getInstance()->getQuery();

if (!$this->adminContext)
{
	echo Helpers\OrganizerHelper::getApplication()->JComponentTitle;
	echo $this->subtitle;
	echo $this->supplement;
}
?>
<div id="j-main-container" class="span10">
	<?php if ($count = count($this->instances) and $count > 1) : ?>
		<?php require_once 'checkin-confirm.php'; ?>
	<?php elseif ($count and $count === 1) : ?>
		<?php require_once 'checkin-checkedin.php'; ?>
	<?php else : ?>
		<?php echo Toolbar::getInstance()->render(); ?>
		<?php require_once 'checkin-checkin.php'; ?>
	<?php endif; ?>
</div>
