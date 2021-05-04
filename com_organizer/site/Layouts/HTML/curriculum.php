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

$componentTemplate = Helpers\Input::getCMD('tmpl') === 'component';
$resourceID        = Helpers\Input::getID();
$view              = Helpers\Input::getView();
if (!$componentTemplate) {
    require_once 'language_selection.php';
    echo Helpers\OrganizerHelper::getApplication()->JComponentTitle;
}
?>
<div class="resource-item">
    <div class="curriculum">
        <?php foreach ($this->item['curriculum'] as $pool) : ?>
            <?php $this->renderPanel($pool); ?>
        <?php endforeach; ?>
        <?php echo $this->disclaimer; ?>
    </div>
    <?php if (count($this->fields) and !$componentTemplate) : ?>
        <div class="legend">
            <div class="panel-head">
                <div class="panel-title"><?php echo Helpers\Languages::_('ORGANIZER_LEGEND'); ?></div>
            </div>
            <?php foreach ($this->fields as $hex => $field) : ?>
                <div class="legend-item">
                    <div class="item-color" style="background-color: <?php echo $hex; ?>;"></div>
                    <div class="item-title"><?php echo $field; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>