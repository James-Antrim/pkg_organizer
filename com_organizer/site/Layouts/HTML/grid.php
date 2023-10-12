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
use THM\Organizer\Adapters\{Application, Input, Toolbar};
use THM\Organizer\Helpers;

require_once 'refresh.php';
require_once 'titles.php';

$action     = Application::dynamic() ? Uri::current() . '?' . Uri::getInstance()->getQuery() : Uri::current();
$columns    = array_keys($this->headers);
$class      = 'instances-grid columns-' . count($columns);
$class      .= array_key_exists('times', $this->headers) ? ' with-times' : '';
$items      = $this->items;
$lastColumn = end($columns);
$rows       = array_keys($this->items);
$lastRow    = end($rows);

?>
<div id="j-main-container" class="span10">
    <?php echo Toolbar::getInstance()->render(); ?>
    <form action="<?php echo $action; ?>" id="adminForm" method="post" name="adminForm">
        <?php require_once 'filters.php'; ?>
        <div class="<?php echo $class; ?>" id="instances-grid">
            <?php
            foreach ($this->headers as $key => $header) {
                $class = 'grid-header';
                $class .= $key === $lastColumn ? ' row-end' : '';
                echo "<div class=\"$class\">$header</div>";
            }
            ?>
            <?php if (count($items)) : ?>
                <?php foreach ($items as $key => $row) : ?>
                    <?php
                    foreach ($columns as $column) {
                        $empty = false;
                        if (is_array($row[$column])) {
                            $busy  = $row[$column]['busy'];
                            $value = $row[$column]['instances'];

                            if (!$value and !empty($row[$column]['label'])) {
                                $empty = true;
                                $value = $row[$column]['label'];
                            }
                        } else {
                            $busy  = false;
                            $value = $row[$column];
                        }

                        $class = '';

                        if ($column === 'times') {
                            $class .= 'grid-header times';
                        } elseif ($column === $lastColumn) {
                            $class .= 'row-end';
                        }

                        if ($key === $lastRow) {
                            $class .= ' column-end';
                        }

                        if ($busy) {
                            $class .= ' block-busy';
                        }

                        if (!empty($row[$column]['type'])) {
                            $class .= " {$row[$column]['type']}";

                            if ($empty) {
                                $class .= " empty";
                            }
                        } elseif (!empty($row['type']) and $row['type'] === 'break') {
                            $class .= ' break';
                        }

                        $class = trim($class);

                        if ($class) {
                            $class = "class=\"$class\"";
                        }

                        echo "<div $class>$value</div>";
                    }
                    ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-result-set"><?php echo $this->empty; ?></div>
            <?php endif; ?>
        </div>
        <input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="id" value="<?php echo Input::getID(); ?>"/>
        <input type="hidden" name="Itemid" value="<?php echo Input::getInt('Itemid'); ?>"/>
        <input type="hidden" name="option" value="com_organizer"/>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="view" value="<?php echo $this->get('name'); ?>"/>
        <?php echo Helpers\HTML::_('form.token'); ?>
    </form>
    <?php echo $this->disclaimer; ?>
</div>


