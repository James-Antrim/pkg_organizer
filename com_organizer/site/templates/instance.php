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
use THM\Organizer\Adapters\{HTML, Input, Text, Toolbar};
use THM\Organizer\Layouts\HTML\{Headers, Row};
use THM\Organizer\Helpers\Instances as Helper;
use THM\Organizer\Views\HTML\Instance;

/** @var Instance $this */

$instance = $this->instance;
$action   = Uri::getInstance()->getQuery();

$this->renderTasks();
require_once 'header.php';
?>
<form action="<?php echo $action; ?>" method="post" name="adminForm" id="adminForm">
    <?php echo Toolbar::render('minibar'); ?>
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container organizer">
                <?php if (!$instance->expired): ?>
                    <div class="attribute-item">
                        <div class="attribute-label"><?php echo Text::_('ORGANIZER_ORGANIZATIONAL'); ?></div>
                        <div class="attribute-content"><?php $this->renderOrganizational(); ?></div>
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
                    <table class="table" id="InstanceList">
                        <?php Headers::render($this); ?>
                        <tbody>
                        <?php foreach ($this->items as $rowNo => $item) : ?>
                            <?php Row::render($this, $rowNo, $item); ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- add to form hidden fields <input type="hidden" name="id" value="<?php echo Input::id(); ?>"/> -->
    <input type="hidden" name="boxchecked" value="0"/>
    <input type="hidden" name="Itemid" value="<?php echo Input::integer('Itemid'); ?>"/>
    <input type="hidden" name="task" value="<?php echo strtolower($this->_name); ?>.display">
    <?php echo HTML::token(); ?>
</form>