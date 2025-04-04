<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Joomla\CMS\{Router\Route, Uri\Uri};
use THM\Organizer\Adapters\{Application, HTML, Input, Text};

$itemID = Input::getInt('Itemid');
$url    = $itemID ? "index.php?Itemid=$itemID" : "index.php?option=com_organizer&view=$this->_name";

// Core behaviour scripts
$wa = Application::document()->getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');

$formName  = strtoupper($this->getName());
$ariaLabel = Text::_("ORGANIZER_{$formName}_FORM");

$query  = Uri::getInstance()->getQuery();
$script = "document.getElementById('ics-url').select();";
$script .= "document.getElementById('ics-url').setSelectionRange(0,99999);";
$script .= "document.execCommand('copy');";

$sets = $this->form->getFieldSets();

$this->renderTasks();
require_once 'header.php';
?>
<?php if ($this->url): ?>
    <div class="control-group">
        <div class="control-label">
            <label id="ics-url-lbl" for="ics-url"><?php echo Text::_('ORGANIZER_ICS_URL_DESC'); ?></label>
        </div>
        <div class="controls">
            <input type="text" id="ics-url" class="ics-url" value="<?php echo $this->url; ?>"/>
            <button class="button-copy btn" onclick="<?php echo $script; ?>">
                <span class="icon-copy"></span><?php echo Text::_('ORGANIZER_COPY'); ?>
            </button>
        </div>
    </div>
<?php endif; ?>
<form action="<?php echo Route::_($url); ?>"
      aria-label="<?php echo $ariaLabel; ?>"
      class="form-validate"
      enctype="multipart/form-data"
      id="adminForm"
      method="post"
      name="adminForm">
    <div class="main-card row">
        <div class="col-lg-6">
            <h4><?php echo Text::_($sets['selection']->label); ?></h4>
            <?php echo $this->form->renderFieldset('selection'); ?>
        </div>
        <div class="col-lg-6">
            <h4><?php echo Text::_($sets['settings']->label); ?></h4>
            <?php echo $this->form->renderFieldset('settings'); ?>
        </div>
        <div class="col-lg-6"></div>
        <input type="hidden" name="Itemid" value="<?php echo $itemID; ?>"/>
        <input type="hidden" name="task" value="<?php echo $this->defaultTask; ?>">
        <?php echo HTML::token(); ?>
    </div>
</form>