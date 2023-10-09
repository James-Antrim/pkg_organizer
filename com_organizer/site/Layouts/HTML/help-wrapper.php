<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use THM\Organizer\Helpers;

$topic = Helpers\Input::getCMD('topic', 'toc');

echo $this->title;
?>
<div id="j-main-container" class="span10">
    <?php
    /** @noinspection PhpIncludeInspection */
    require_once "Help/$topic.php";
    ?>
</div>
