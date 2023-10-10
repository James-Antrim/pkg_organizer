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
use THM\Organizer\Adapters\{Input, Text, Toolbar};
use THM\Organizer\Helpers;

$query = Uri::getInstance()->getQuery();

if (!$this->adminContext) {
    require_once 'titles.php';
}
?>
<?php if (!$this->adminContext) : ?>
    <?php echo Toolbar::getInstance()->render(); ?>
<?php endif; ?>
<form action="<?php echo Uri::base() . "?$query"; ?>" id="adminForm" method="post" name="adminForm"
      class="form-horizontal form-validate" enctype="multipart/form-data">

    <?php
    echo Helpers\HTML::_('bootstrap.startTabSet', 'myTab', ['active' => 'details']);

    foreach ($this->form->getFieldSets() as $set) {
        $isInitialized  = (bool) $this->form->getValue('id');
        $displayInitial = !isset($set->displayinitial) || $set->displayinitial;

        if ($displayInitial or $isInitialized) {
            echo Helpers\HTML::_(
                'bootstrap.addTab',
                'myTab',
                $set->name,
                Text::_('ORGANIZER_' . $set->label, true)
            );
            echo $this->form->renderFieldset($set->name);
            echo Helpers\HTML::_('bootstrap.endTab');
        }
    }
    echo Helpers\HTML::_('bootstrap.endTabSet');
    ?>
    <input type="hidden" name="Itemid" value="<?php echo Input::getInt('Itemid'); ?>"/>
    <input type="hidden" name="option" value="com_organizer"/>
    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="view" value="<?php echo $this->get('name'); ?>"/>
    <?php echo Helpers\HTML::_('form.token'); ?>
</form>