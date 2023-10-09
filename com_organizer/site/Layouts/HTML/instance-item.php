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
use THM\Organizer\Adapters\Toolbar;
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Instances as Helper;
use THM\Organizer\Helpers\Languages;

$columnCount = count($this->headers);
$instance    = $this->instance;
$items       = $this->items;
$iteration   = 0;
$action      = Helpers\OrganizerHelper::dynamic() ? Uri::current() . '?' . Uri::getInstance()->getQuery() : Uri::current();
$resourceID  = Helpers\Input::getID();

require_once 'titles.php';
?>
<?php echo $this->minibar; ?>
<div id="j-main-container" class="span10">
    <?php if (!$instance->expired): ?>
        <div class="attribute-item">
            <div class="attribute-label"><?php echo Languages::_('ORGANIZER_ORGANIZATIONAL'); ?></div>
            <div class="attribute-content"><?php echo $this->renderOrganizational(); ?></div>
        </div>
    <?php endif; ?>
    <?php if ($instance->description): ?>
        <div class="attribute-item">
            <div class="attribute-label"><?php echo Languages::_('ORGANIZER_DESC'); ?></div>
            <div class="attribute-content"><?php echo $instance->description; ?></div>
        </div>
    <?php endif; ?>
    <?php if ($instance->persons): ?>
        <?php $this->renderPersons() ?>
    <?php endif; ?>
    <?php if (!$instance->hideGroups): ?>
        <?php $this->renderResources(Languages::_('ORGANIZER_GROUPS'), $instance->groups) ?>
    <?php endif; ?>
    <?php if (!$instance->hideRooms and $instance->presence !== Helper::ONLINE): ?>
        <?php $this->renderResources(Languages::_('ORGANIZER_ROOMS'), $instance->rooms) ?>
    <?php endif; ?>
    <?php if ($this->items): ?>
        <h2 class="section-head"><?php echo Languages::_('ORGANIZER_UPCOMING_INSTANCES'); ?></h2>
        <?php echo Toolbar::getInstance()->render(); ?>
        <form action="<?php echo $action; ?>" id="adminForm" method="post" name="adminForm">
            <?php if (count($items)) : ?>
                <table class="table table-striped" id="<?php echo $this->get('name'); ?>-list">
                    <thead>
                    <tr>
                        <?php
                        foreach ($this->headers as $header) {
                            $colAttributes = $this->getAttributesOutput($header);
                            $colValue      = is_array($header) ? $header['value'] : $header;
                            echo "<th $colAttributes>$colValue</th>";
                        }
                        ?>
                    </tr>
                    </thead>
                    <tbody <?php echo $this->getAttributesOutput($items); ?>>
                    <?php foreach ($items as $row) : ?>
                        <tr <?php echo $this->getAttributesOutput($row); ?>>
                            <?php
                            foreach ($row as $key => $column) {
                                if ($key === 'attributes') {
                                    continue;
                                }

                                $colAttributes = $this->getAttributesOutput($column);
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
            <input type="hidden" name="id" value="<?php echo Helpers\Input::getID(); ?>"/>
            <input type="hidden" name="Itemid" value="<?php echo Helpers\Input::getInt('Itemid'); ?>"/>
            <input type="hidden" name="option" value="com_organizer"/>
            <input type="hidden" name="task" value=""/>
            <input type="hidden" name="view" value="InstanceItem"/>
            <?php echo Helpers\HTML::_('form.token'); ?>
        </form>
    <?php endif; ?>
    <?php echo $this->disclaimer; ?>
</div>


