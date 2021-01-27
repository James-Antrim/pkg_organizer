<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Organizer\Helpers;

$layout = Helpers\Input::getCMD('layout', 'toc');

require_once 'language_selection.php';
echo Helpers\OrganizerHelper::getApplication()->JComponentTitle;
require_once "Help/$layout.php";
?>
<div id="j-main-container" class="span10">
	<?php require_once "Help/$layout.php"; ?>
</div>
