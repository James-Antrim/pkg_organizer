<?php
/**
 * @package     Groups
 * @extension   com_groups
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use THM\Organizer\Adapters\{Input, Text};
use THM\Organizer\Views\HTML\FormView;

/** @var FormView $this */

// Core behaviour scripts
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');

$formName  = strtoupper($this->getName());
$ariaLabel = Text::_("GROUPS_{$formName}_FORM");

$input          = Input::getInput();
$forcedLanguage = $input->get('forcedLanguage', '');
$return         = $input->getBase64('return');

$tabs   = $this->form->getFieldsets();
$tabbed = count($this->form->getFieldsets()) > 1;

if ($this->toDo) {
    echo '<ul>';
    foreach ($this->todo as $todo) {
        echo "<li>$todo</li>";
    }
    echo '</ul>';
}
?>
<form action="<?php echo Route::_('index.php?option=com_groups'); ?>"
      aria-label="<?php echo $ariaLabel; ?>"
      class="form-validate"
      enctype="multipart/form-data"
      id="adminForm"
      method="post"
      name="adminForm">
    <div class="main-card">
        <?php if ($tabbed): ?>
            <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>
            <?php foreach ($tabs as $name => $tab): ?>
                <?php echo HTMLHelper::_('uitab.addTab', 'myTab', $tab->name, Text::_($tab->label)); ?>
                <fieldset class="options-form">
                    <div class="form-grid">
                        <?php echo $this->form->renderFieldset($name); ?>
                    </div>
                </fieldset>
                <?php echo HTMLHelper::_('uitab.endTab'); ?>
            <?php endforeach; ?>
            <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
        <?php else: ?>
            <fieldset class="options-form">
                <div class="form-grid">
                    <?php echo $this->form->renderFieldset('details'); ?>
                </div>
            </fieldset>
        <?php endif; ?>
        <input type="hidden" name="task" value="">
        <input type="hidden" name="return" value="<?php echo $return; ?>">
        <input type="hidden" name="forcedLanguage" value="<?php echo $forcedLanguage; ?>">
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>