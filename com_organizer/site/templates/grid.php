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
use THM\Organizer\Layouts\HTML\{EmptyList, ListHeaders, ListHidden, ListItem, ListTools};
use THM\Organizer\Views\HTML\GridView;

/** @var GridView $this */

$action = Route::_('index.php?option=com_organizer&view=' . $this->_name);
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
        <div class="col-md-12">
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
                <?php endif; ?>
                <?php ListHidden::render($this); ?>
                <input type="hidden" name="task" value="<?php echo strtolower($this->_name); ?>.display">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTML::token(); ?>
            </div>
        </div>
    </div>
</form>
