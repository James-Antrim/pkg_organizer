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

$current          = Uri::getInstance()->toString();
$privacyText      = Helpers\Languages::_('ORGANIZER_PRIVACY_POLICY');
$privacyURL       = Uri::getInstance() . '&layout=privacy';
$privacyLink      = Helpers\HTML::link($privacyURL, $privacyText);
$return           = urlencode(base64_encode($current));
$registerURL      = Uri::base() . "?option=com_users&view=registration&return=$return";
$registrationLink = Helpers\HTML::link($registerURL, Helpers\Languages::_('ORGANIZER_REGISTER_TEXT_LINK'));
$userID           = Helpers\Users::getID();

?>
<form action="<?php echo Uri::base(); ?>" id="adminForm" method="post" name="adminForm"
      class="form-vertical form-validate checkin" enctype="multipart/form-data" xmlns="http://www.w3.org/1999/html">
	<?php if (!$userID): ?>
		<?php echo $this->form->renderField('username'); ?>
		<?php echo $this->form->renderField('password'); ?>
	<?php endif; ?>
	<?php echo $this->form->renderField('code'); ?>
    <div class="control-group">
        <input class="btn" type="submit" value="<?php echo Helpers\Languages::_('ORGANIZER_CHECKIN'); ?>"/>
    </div>
	<?php if ($userID): ?>
        <div class="control-group">
            <a class="btn" href="<?php echo Uri::getInstance() . '&layout=profile'; ?>">
				<?php echo Helpers\Languages::_('ORGANIZER_PROFILE_EDIT'); ?>
            </a>
        </div>
	<?php else: ?>
        <div class="control-group message register">
			<?php echo sprintf(Helpers\Languages::_('ORGANIZER_REGISTER_TEXT_FRAME'), '<br>' . $registrationLink); ?>
        </div>
	<?php endif; ?>
    <div class="control-group message">
		<?php echo $privacyLink; ?>
    </div>
    <input type="hidden" name="option" value="com_organizer"/>
    <input type="hidden" name="task" value="checkin.checkin"/>
    <input type="hidden" name="view" value="checkin"/>
	<?php echo Helpers\HTML::_('form.token'); ?>
</form>
