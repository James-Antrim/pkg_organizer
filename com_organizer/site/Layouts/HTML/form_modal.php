<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @author      Florian Fenzl, <florian.fenzl@mni.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use THM\Organizer\Adapters\{Input, Text};

$task = Input::cmd('view') . '.supplement';

?>
<div class="modal hide fade form-modal" id="form-modal">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&#215;</button>
        <h3><?php echo Text::_('ORGANIZER_SUPPLEMENT'); ?>
    </div>
    <div class="modal-body modal-batch form-vertical">
        <?php foreach ($this->filterForm->getGroup('supplement') as $formField) : ?>
            <div class='control-group'>
                <div class='control-label'>
                    <?php echo $formField->label; ?>
                </div>
                <div class='controls'>
                    <?php echo $formField->input; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="modal-footer">
        <button class="btn" type="button" data-dismiss="modal">
            <?php echo Text::_('ORGANIZER_CANCEL'); ?>
        </button>
        <button class="btn btn-primary" type="submit" onclick="Joomla.submitbutton('<?php echo $task; ?>');">
            <?php echo Text::_('ORGANIZER_SAVE'); ?>
        </button>
    </div>
</div>
