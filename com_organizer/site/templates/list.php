<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Joomla\CMS\Router\Route;
use THM\Organizer\Adapters\{Application, HTML, Toolbar};
use THM\Organizer\Layouts\HTML\{Batch, EmptySet, Headers, HiddenInputs, Row, Tools};
use THM\Organizer\Views\HTML\ListView;

/** @var ListView $this */

$action = Route::_('index.php?option=com_organizer&view=' . $this->_name);

if (count($this->headers) > 4) {
    $wa = Application::document()->getWebAssetManager();
    $wa->useScript('table.columns');
}

$this->renderTasks();

if (!Application::backend()) {
    echo "<h1>$this->title</h1>";
    echo $this->subtitle ? "<h4>$this->subtitle</h4>" : '';
    echo $this->supplement;
    echo Toolbar::render();
}
?>
<form action="<?php echo $action; ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <?php if (!empty($this->sidebar)) : ?>
        <div id="j-sidebar-container" class="col-md-2">
            <?php echo $this->sidebar; ?>
        </div>
        <div class="col-md-10">
            <?php else: ?>
            <div class="col-md-12">
                <?php endif; ?>
                <div id="j-main-container" class="j-main-container groups">
                    <?php Tools::render($this); ?>
                    <?php if (empty($this->items)) : ?>
                        <?php EmptySet::render($this); ?>
                    <?php else : ?>
                        <table class="table" id="<?php echo $this->_name ?>List">
                            <?php Headers::render($this); ?>
                            <tbody>
                            <?php foreach ($this->items as $rowNo => $item) : ?>
                                <?php Row::render($this, $rowNo, $item); ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php echo $this->pagination->getListFooter(); ?>
                        <?php if ($this->allowBatch and $batch = $this->filterForm->getGroup('batch')): ?>
                            <template id="organizer-batch"><?php Batch::render($this); ?></template>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php HiddenInputs::render($this); ?>
                    <input type="hidden" name="task" value="<?php echo strtolower($this->_name); ?>.display">
                    <input type="hidden" name="boxchecked" value="0">
                    <?php echo HTML::token(); ?>
                </div>
            </div>
        </div>
</form>
