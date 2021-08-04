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

Helpers\HTML::_('searchtools.form', '#languageForm', []);
$languageAction = Helpers\OrganizerHelper::dynamic() ?
	Uri::current() . '?' . Uri::getInstance()->getQuery() : Uri::current();
$selectedTag    = Helpers\Languages::getTag();
$languages      = [Helpers\Languages::_('ORGANIZER_ENGLISH') => 'en', Helpers\Languages::_('ORGANIZER_GERMAN') => 'de'];
ksort($languages);
$options = [];
foreach ($languages as $language => $tag)
{
	$selected  = $selectedTag === $tag ? ' selected="selected"' : '';
	$options[] = "<option value=\"$tag\"$selected>$language</option>";
}
$options = implode('', $options);
?>
<form id="languageForm" name="languageForm" method="post" action="<?php echo $languageAction; ?>"
      class="form-horizontal">
    <div class="js-stools clearfix">
        <div class="clearfix">
            <div class="js-stools-container-list">
                <div class="ordering-select">
                    <div class="js-stools-field-list">
                        <select id="languageTag" name="languageTag" onchange="this.form.submit();">
							<?php echo $options ?>
                        </select>
                        <input name="option" type="hidden" value="com_organizer">
                        <input name="view" type="hidden" value="<?php echo Helpers\Input::getView(); ?>">
                        <input name="id" type="hidden" value="<?php echo Helpers\Input::getID() ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
