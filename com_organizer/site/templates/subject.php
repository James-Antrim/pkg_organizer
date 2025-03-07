<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

defined('_JEXEC') or die;

use THM\Organizer\Views\HTML\Subject;

/** @var Subject $this */
$this->renderTasks();
require_once 'header.php';
?>
    <div class="item subject">
        <?php foreach ($this->item as $label => $value) : ?>
            <?php if (in_array($label, ['code', 'id', 'name'])) : continue; ?>
            <?php elseif ($label === 'PREREQUISITE_MODULES' or $label === 'POSTREQUISITE_MODULES') : ?>
                <?php $this->renderSubjects($label, $value); ?>
            <?php else: $this->renderAttribute($label, $value); endif; ?>
        <?php endforeach; ?>
    </div>
<?php echo $this->disclaimer; ?>