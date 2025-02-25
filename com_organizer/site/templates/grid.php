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
use THM\Organizer\Adapters\HTML;
use THM\Organizer\Layouts\HTML\{EmptySet, Headers, HiddenInputs, Row, Tools};
use THM\Organizer\Views\HTML\GridView;

/** @var GridView $this */
$action = Route::_('index.php?option=com_organizer&view=' . $this->_name);
$this->renderTasks();
require_once 'header.php';
?>
<form action="<?php echo $action; ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
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
                <?php endif; ?>
                <?php HiddenInputs::render($this); ?>
                <input type="hidden" name="task" value="<?php echo strtolower($this->_name); ?>.display">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTML::token(); ?>
            </div>
        </div>
    </div>
</form>
