<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use THM\Organizer\Adapters\{Application, HTML};
use THM\Organizer\Layouts\HTML\{Batch, EmptyList, ListHeaders, ListItem, ListTools};
use THM\Organizer\Views\HTML\ListView;

/** @var ListView $this */

$action    = Route::_('index.php?option=com_organizer&view=' . $this->_name);
$direction = $this->escape($this->state->get('list.direction'));
$orderBy   = $this->escape($this->state->get('list.ordering'));

/** @var ListView $this */
if ($this->toDo) {
    echo '<ul>';
    foreach ($this->toDo as $toDo) {
        echo "<li>$toDo</li>";
    }
    echo '</ul>';
}

if (count($this->headers) > 4) {
    $wa = Application::getDocument()->getWebAssetManager();
    $wa->useScript('table.columns');
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
                            <caption class="visually-hidden">
                                <?php echo Text::_('COM_USERS_USERS_TABLE_CAPTION'); ?>,
                                <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                                <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                            </caption>
                            <?php ListHeaders::render($this); ?>
                            <tbody>
                            <?php foreach ($this->items as $rowNo => $item) : ?>
                                <?php ListItem::render($this, $rowNo, $item); ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php echo $this->pagination->getListFooter(); ?>
                        <?php if ($this->allowBatch and $batch = $this->filterForm->getGroup('batch')): ?>
                            <?php echo HTMLHelper::_(
                                'bootstrap.renderModal',
                                'collapseModal',
                                [
                                    'title'  => Text::_('ORGANIZER_BATCH_PROCESSING'),
                                    'footer' => Batch::renderFooter($this),
                                ],
                                Batch::renderBody($this)
                            ); ?>
                            <?php Batch::renderBody($this); ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    <input type="hidden" name="task" value="">
                    <input type="hidden" name="boxchecked" value="0">
                    <?php echo HTML::token(); ?>
                </div>
            </div>
        </div>
</form>
