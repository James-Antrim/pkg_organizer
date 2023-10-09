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

if (!$componentTemplate = Helpers\Input::getCMD('tmpl') === 'component') {
    echo $this->title;
}
?>
<div class="resource-item">
    <div class="curriculum">
        <?php foreach ($this->item['curriculum'] as $pool) : ?>
            <?php $this->renderPanel($pool); ?>
        <?php endforeach; ?>
        <?php if ($componentTemplate): ?>
            <?php $this->renderLegend(); ?>
        <?php endif; ?>
        <?php echo $this->disclaimer; ?>
    </div>
    <?php if (count($this->fields) and !$componentTemplate) : ?>
        <?php $this->renderLegend(); ?>
    <?php endif; ?>
</div>