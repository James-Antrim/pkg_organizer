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
use THM\Organizer\Layouts\HTML\{Batch, EmptyList, ListHeaders, ListItem, ListTools};
use THM\Organizer\Views\HTML\ListView;

/** @var ListView $this */

$action = Route::_('index.php?option=com_organizer&view=' . $this->_name);

if (count($this->headers) > 4) {
    $wa = Application::getDocument()->getWebAssetManager();
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
                    <?php ListTools::render($this); ?>
                    <?php if (empty($this->items)) : ?>
                        <?php EmptyList::render($this); ?>
                    <?php else : ?>
                        <table class="table" id="<?php echo $this->_name ?>List">
                            <?php ListHeaders::render($this); ?>
                            <tbody>
                            <?php foreach ($this->items as $rowNo => $item) : ?>
                                <?php ListItem::render($this, $rowNo, $item); ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php echo $this->pagination->getListFooter(); ?>
                        <?php if ($this->allowBatch and $batch = $this->filterForm->getGroup('batch')): ?>
                            <template id="organizer-batch"><?php Batch::render($this); ?></template>
                        <?php endif; ?>
                    <?php endif; ?>
                    <input type="hidden" name="task" value="">
                    <input type="hidden" name="boxchecked" value="0">
                    <?php echo HTML::token(); ?>
                </div>
            </div>
        </div>
</form>
