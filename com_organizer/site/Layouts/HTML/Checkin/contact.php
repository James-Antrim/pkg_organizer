<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Joomla\CMS\Uri\Uri;
use Organizer\Helpers;

$privacyText = Helpers\Languages::_('ORGANIZER_PRIVACY_POLICY');
$privacyURL  = str_replace('profile', 'privacy', Uri::getInstance());
$privacyLink = Helpers\HTML::link($privacyURL, $privacyText);

?>
<form action="<?php echo Uri::base(); ?>" id="adminForm" method="post" name="adminForm"
      class="form-vertical form-validate contact" enctype="multipart/form-data" xmlns="http://www.w3.org/1999/html">
    <?php echo $this->form->renderFieldset('participant'); ?>
    <div class="control-group control-group-horizontal">
        <div class="control-label">
            <label class="required" for="acceptance" id="acceptance-label">
                Die <?php echo $privacyLink; ?> zur Kontakterfassung<br>habe ich zur Kenntnis genommen.
                <span class="star">*</span>
            </label>
        </div>
        <div class="controls">
            <input aria-labelledby="acceptance-label" aria-required="true" class="required" id="acceptance"
                   type="checkbox"/>
        </div>
    </div>
    <div class="control-group">
        <input class="btn" type="submit" value="<?php echo Helpers\Languages::_('ORGANIZER_SAVE'); ?>"/>
    </div>
    <input type="hidden" name="option" value="com_organizer"/>
    <input type="hidden" name="task" value="checkin.contact"/>
    <input type="hidden" name="view" value="checkin"/>
    <?php echo Helpers\HTML::_('form.token'); ?>
</form>
