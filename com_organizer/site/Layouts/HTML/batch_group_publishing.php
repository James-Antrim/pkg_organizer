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

?>

<div class="modal hide fade" id="modal-publishing">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&#215;</button>
        <h3><?php echo Helpers\Languages::_('ORGANIZER_BATCH_GROUPS'); ?></h3>
    </div>
    <div class="modal-body modal-batch form-horizontal">
		<?php foreach ($this->filterForm->getGroup('batch') as $batchField) : ?>
            <div class='control-group'>
                <div class='control-label'>
					<?php echo $batchField->label; ?>
                </div>
                <div class='controls'>
					<?php echo $batchField->input; ?>
                </div>
            </div>
		<?php endforeach; ?>
    </div>
    <div class="modal-footer">
        <button class="btn" type="button" data-dismiss="modal">
			<?php echo Helpers\Languages::_('ORGANIZER_CANCEL'); ?>
        </button>
        <button class="btn btn-primary" type="submit" onclick="Joomla.submitbutton('groups.batch');">
			<?php echo Helpers\Languages::_('ORGANIZER_SAVE'); ?>
        </button>
    </div>
</div>

