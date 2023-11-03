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
use THM\Organizer\Adapters\{Application, HTML, Toolbar};

$rows      = $this->rows;
$iteration = 0;

require_once 'titles.php';

?>
<div id="j-main-container" class="span10">
    <?php if (!Application::backend()) : ?>
        <?php echo Toolbar::getInstance()->render(); ?>
    <?php endif; ?>
    <form action="<?php echo Uri::current(); ?>" id="adminForm" method="post" name="adminForm">
        <?php require_once 'filters.php'; ?>
        <table class="table table-striped organizer-table">
            <thead><?php echo $this->renderHeaders(); ?></thead>
            <tbody><?php echo $this->renderRows(); ?></tbody>
            <tfoot>
            <tr>
                <td colspan="<?php echo $this->labelCount + $this->columnCount; ?>">
                    <?php echo $this->pagination->getListFooter(); ?>
            </tr>
            </tfoot>
        </table>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="option" value="com_organizer"/>
        <input type="hidden" name="view" value="<?php echo $this->get('name'); ?>"/>
        <?php echo HTML::token(); ?>
    </form>
    <?php echo $this->disclaimer; ?>
</div>


