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

use Organizer\Helpers;

$task = Helpers\Input::getCMD('view') . '.notes';

?>
<div class="modal hide fade" id="modal-notes">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&#215;</button>
        <h3><?php echo Helpers\Languages::_('ORGANIZER_NOTES'); ?>
    </div>
    <div class="modal-body modal-batch form-vertical">
		<?php foreach ($this->filterForm->getGroup('notes') as $batchField) : ?>
            <div class='control-group'>
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
        <button class="btn btn-primary" type="submit" onclick="Joomla.submitbutton(<?php echo $task; ?>);">
			<?php echo Helpers\Languages::_('ORGANIZER_SAVE'); ?>
        </button>
    </div>
</div>
