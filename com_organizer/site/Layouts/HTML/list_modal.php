<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Joomla\CMS\Layout\LayoutHelper;
use Organizer\Adapters\Toolbar;
use Organizer\Helpers;

$toolbar     = Toolbar::getInstance();
$columnCount = count($this->headers);
$data        = ['view' => $this, 'options' => []];
$showSearch  = !empty($filters['filter_search']);

$filters     = $this->filterForm->getGroup('filter');
$noFilters   = count($filters) === 0;
$onlySearch  = (count($filters) === 1 and !empty($filters['filter_search']));
$showFilters = !($noFilters or $onlySearch);

$viewName = $this->getName();
$type     = $viewName === 'SubjectSelection' ? 's' : 'p';
?>
<form action="index.php?" id="adminForm" method="post" name="adminForm">
    <div class="toolbar clearfix">
        <?php foreach ($toolbar->getItems() as $button) : ?>
            <?php echo $toolbar->renderButton($button); ?>
        <?php endforeach; ?>
    </div>
    <div class="js-stools-container-bar">
        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
    </div>
    <table class="table table-striped" id="<?php echo $viewName; ?>-list">
        <thead>
        <tr>
            <?php foreach ($this->headers as $header) : ?>
                <th><?php echo $header; ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->items as $row) : ?>
            <tr>
                <?php foreach ($row as $column) : ?>
                    <td><?php echo $column; ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr>
            <td colspan="<?php echo $columnCount; ?>">
                <?php echo $this->pagination->getListFooter(); ?>
            </td>
        </tr>
        </tfoot>
    </table>
    <input type="hidden" name="boxchecked" value="0"/>
    <input type="hidden" name="option" value="com_organizer"/>
    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="tmpl" value="component"/>
    <input type="hidden" name="view" value="<?php echo $viewName; ?>"/>
    <?php echo Helpers\HTML::_('form.token'); ?>
</form>
<script>
    jQuery(document).ready(function () {
        jQuery('div#toolbar-new button').click(function () {
            window.parent.closeModal(<?php echo "'#$viewName-list', '$type'"; ?>);
        });
    });
</script>
