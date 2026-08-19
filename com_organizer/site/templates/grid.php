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
use THM\Organizer\Adapters\{Application, HTML};
use THM\Organizer\Layouts\HTML\{EmptySet, Headers, HiddenInputs, Row, Tools};
use THM\Organizer\Views\HTML\Instances;

/** @var Instances $this */
$columns    = array_keys($this->headers);
$lastColumn = end($columns);
$rows       = array_keys($this->items);
$lastRow    = end($rows);

$action = Application::dynamic() ? Uri::current() . '?' . Uri::getInstance()->getQuery() : Uri::current();
$class  = 'instances-grid columns-' . count($columns);
$class  .= array_key_exists('times', $this->headers) ? ' with-times' : '';
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
                    <div class="<?php echo $class; ?>" id="<?php echo $this->_name; ?>Grid">
                        <?php $this->renderGridHeaders(); ?>
                        <?php foreach ($this->items as $key => $row) : ?>
                            <?php foreach ($columns as $column) : ?>
                                <?php echo $this->renderGridCell($row, $column, $key === $lastRow, $column === $lastColumn); ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php HiddenInputs::render($this); ?>
                <input type="hidden" name="task" value="<?php echo strtolower($this->_name); ?>.display">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTML::token(); ?>
            </div>
        </div>
    </div>
    <?php echo $this->disclaimer; ?>
</form>
