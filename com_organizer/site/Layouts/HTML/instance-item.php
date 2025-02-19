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
use THM\Organizer\Adapters\{Application, HTML, Input, Text, Toolbar};
use THM\Organizer\Helpers\Instances as Helper;

$columnCount = count($this->headers);
$instance    = $this->instance;
$items       = $this->items;
$iteration   = 0;
$action      = Application::dynamic() ? Uri::current() . '?' . Uri::getInstance()->getQuery() : Uri::current();
$resourceID  = Input::getID();

require_once 'titles.php';
?>
<?php echo $this->minibar; ?>
<div id="j-main-container" class="span10">
    <?php if (!$instance->expired): ?>
        <div class="attribute-item">
            <div class="attribute-label"><?php echo Text::_('ORGANIZER_ORGANIZATIONAL'); ?></div>
            <div class="attribute-content"><?php echo $this->renderOrganizational(); ?></div>
        </div>
    <?php endif; ?>
    <?php if ($instance->description): ?>
        <div class="attribute-item">
            <div class="attribute-label"><?php echo Text::_('ORGANIZER_DESC'); ?></div>
            <div class="attribute-content"><?php echo $instance->description; ?></div>
        </div>
    <?php endif; ?>
    <?php if ($instance->persons): ?>
        <?php $this->renderPersons() ?>
    <?php endif; ?>
    <?php if (!$instance->hideGroups): ?>
        <?php $this->renderResources(Text::_('ORGANIZER_GROUPS'), $instance->groups) ?>
    <?php endif; ?>
    <?php if (!$instance->hideRooms and $instance->presence !== Helper::ONLINE): ?>
        <?php $this->renderResources(Text::_('ORGANIZER_ROOMS'), $instance->rooms) ?>
    <?php endif; ?>
    <?php if ($this->items): ?>
        <h2 class="section-head"><?php echo Text::_('ORGANIZER_UPCOMING_INSTANCES'); ?></h2>
        <?php echo Toolbar::render(); ?>
        <form action="<?php echo $action; ?>" id="adminForm" method="post" name="adminForm">
            <?php if (count($items)) : ?>
                <table class="table table-striped" id="<?php echo $this->get('name'); ?>-list">
                    <thead>
                    <tr>
                        <?php
                        foreach ($this->headers as $header) {
                            $colAttributes = HTML::properties($header);
                            $colValue      = is_array($header) ? $header['value'] : $header;
                            echo "<th $colAttributes>$colValue</th>";
                        }
                        ?>
                    </tr>
                    </thead>
                    <tbody <?php echo HTML::properties($items); ?>>
                    <?php foreach ($items as $row) : ?>
                        <tr <?php echo HTML::properties($row); ?>>
                            <?php
                            foreach ($row as $key => $column) {
                                if ($key === 'attributes') {
                                    continue;
                                }

                                $colAttributes = HTML::properties($column);
                                $colValue      = is_array($column) ? $column['value'] : $column;
                                echo "<td $colAttributes>$colValue</td>";
                            }
                            ?>
                        </tr>
                    <?php endforeach; ?>
                    <tfoot>
                    <tr>
                        <td colspan="<?php echo $columnCount; ?>">
                            <?php echo $this->pagination->getListFooter(); ?>
                    </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
            <input type="hidden" name="boxchecked" value="0"/>
            <input type="hidden" name="id" value="<?php echo Input::getID(); ?>"/>
            <input type="hidden" name="Itemid" value="<?php echo Input::getInt('Itemid'); ?>"/>
            <input type="hidden" name="option" value="com_organizer"/>
            <input type="hidden" name="task" value=""/>
            <input type="hidden" name="view" value="InstanceItem"/>
            <?php echo HTML::token(); ?>
        </form>
    <?php endif; ?>
    <?php echo $this->disclaimer; ?>
</div>


